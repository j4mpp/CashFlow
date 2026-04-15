<script setup>
import { ref, onMounted, watch, nextTick } from "vue"
import Chart from "chart.js/auto"
import BankAccountCard from "@/components/BankAccountCard.vue"

/* =========================
   STATE
========================= */

const banks = ref([])
const transactions = ref([])

const totalIncome = ref(0)
const totalExpense = ref(0)
const savingsRate = ref(0)

const loadingBanks = ref(true)
const editingBankId = ref(null)

const pieCanvas = ref(null)
let pieChart = null

function getValidUserId() {
    const userid = localStorage.getItem("userid")
    if (!userid || !/^\d+$/.test(userid)) {
        throw new Error("Ungültige Session. Bitte neu einloggen.")
    }
    return userid
}

async function fetchJson(url, options = {}) {
    const res = await fetch(url, {
        credentials: "include",
        ...options
    })

    const data = await res.json()
    if (!res.ok || data?.error) {
        throw new Error(data?.error || "Request fehlgeschlagen")
    }
    return data
}

/* =========================
    ENTRY MODAL
========================= */
const selectedBankName = ref("")
const showEntryModal = ref(false)
const creatingEntry = ref(false)
const activeSubcategoryId = ref("")

const entryName = ref("")
const entryDescription = ref("")
const entryAmount = ref("")
const entryBankId = ref("")
const entryDate = ref(new Date().toISOString().slice(0, 10)) // YYYY-MM-DD

function openEntryModal(bankId, bankName) {
    entryBankId.value = bankId
    selectedBankName.value = bankName

    entryName.value = ""
    entryDescription.value = ""
    entryAmount.value = ""
    entryDate.value = new Date().toISOString().slice(0, 10)

    showEntryModal.value = true
}

function closeEntryModal() {
    showEntryModal.value = false
    activeSubcategoryId.value = ""
}

async function saveEntry() {
    const userid = getValidUserId()

    const name = entryName.value.trim()
    const description = entryDescription.value.trim()
    const amount = Number(entryAmount.value)
    const bankid = entryBankId.value
    const subcategoryid = activeSubcategoryId.value
    const date = entryDate.value

    if (!subcategoryid) return alert("Unterkategorie fehlt.")
    if (!name) return alert("Bitte Name eingeben.")
    if (!bankid) return alert("Bitte Konto wählen.")
    if (!Number.isFinite(amount)) return alert("Bitte gültigen Betrag eingeben.")
    if (!date) return alert("Bitte Datum wählen.")

    creatingEntry.value = true
    try {
        await fetchJson("/cashflow_api/transactions/create.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                userid,
                subcategoryid: Number(subcategoryid),
                name,
                description,
                amount,
                bankid: Number(bankid),
                date
            })
        })

        // Bank-Balance aktualisieren: neuer Betrag = alter Betrag + Transaktionsbetrag
        const bank = banks.value.find(b => String(b.id) === String(bankid))
        if (bank) {
            const current = Number(bank.amount)
            const nextAmount = (Number.isFinite(current) ? current : 0) + amount

            await fetchJson("/cashflow_api/banks/update.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    userid,
                    id: Number(bank.id),
                    name: bank.name,
                    iban: bank.iban,
                    amount: nextAmount,
                    bankfirma: bank.bankfirma
                })
            })
        }

        closeEntryModal()
        loading.value = true
        await fetchCategories()
    } catch (err) {
        console.log("Fehler beim Speichern:", err)
        alert("Fehler beim Speichern.")
    } finally {
        creatingEntry.value = false
    }
}

/* =========================
   FETCH BANKS
========================= */

async function fetchBanks() {
    const userid = getValidUserId()
    const safeUserId = encodeURIComponent(userid)
    banks.value = await fetchJson(`/cashflow_api/banks/get.php?userid=${safeUserId}`)
    loadingBanks.value = false

    renderChart()
}

/* =========================
   FETCH TRANSACTIONS
========================= */

async function fetchTransactions() {
    const userid = getValidUserId()
    const safeUserId = encodeURIComponent(userid)
    transactions.value = await fetchJson(`/cashflow_api/transactions/get.php?userid=${safeUserId}`)

    calculateStats()
}

/* =========================
   STATS
========================= */

function calculateStats() {
    let income = 0
    let expense = 0

    transactions.value.forEach(t => {
        const amount = Number(t.amount)

        if (amount > 0) income += amount
        else expense += amount
    })

    totalIncome.value = income
    totalExpense.value = Math.abs(expense)

    // Sparquote berechnen
    if (income > 0) {
        savingsRate.value = ((income - Math.abs(expense)) / income) * 100
    } else {
        savingsRate.value = 0
    }
}

/* =========================
   CHART
========================= */

function renderChart() {
    if (!pieCanvas.value) return

    const labels = banks.value.map(b => b.name)
    const data = banks.value.map(b => Number(b.amount))

    const colors = [
        "#4CAF50",
        "#2196F3",
        "#FF9800",
        "#E91E63",
        "#9C27B0",
        "#00BCD4"
    ]

    if (pieChart) {
        pieChart.destroy()
    }

    pieChart = new Chart(pieCanvas.value, {
        type: "pie",
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    })
}

/* =========================
   WATCH (WICHTIG)
========================= */

watch(banks, async () => {
    await nextTick()
    renderChart()
})

/* =========================
   HELPERS
========================= */

function formatBalance(amount) {
    return "€" + Number(amount || 0).toLocaleString("de-DE")
}

function getBankIcon(type) {
    const userid = localStorage.getItem("userid") || ""
    const safeUserId = /^\d+$/.test(userid) ? userid : "0"
    return `/cashflow_api/banks/logo.php?userid=${encodeURIComponent(safeUserId)}&bankid=${encodeURIComponent(type)}`
}

/* =========================
   INIT
========================= */

onMounted(async () => {
    try {
        await fetchBanks()
        await fetchTransactions()
    } catch (err) {
        console.error("Dashboard konnte nicht geladen werden:", err)
        banks.value = []
        transactions.value = []
        loadingBanks.value = false
    }

    // ResizeObserver Error global unterdrücken (vor dem Observer registrieren)
    const origError = window.onerror
    window.onerror = (msg, ...args) => {
        if (typeof msg === "string" && msg.includes("ResizeObserver")) return true
        return origError?.(...args)
    }

    let resizeTimer = null
    const resizeObserver = new ResizeObserver(() => {
        clearTimeout(resizeTimer)
        resizeTimer = setTimeout(() => {
            if (pieChart) pieChart.resize()
        }, 100)
    })

    if (pieCanvas.value) {
        resizeObserver.observe(pieCanvas.value.parentElement)
    }
})
</script>

<template>
    <div class="flex text-gray-900">
        <main class="flex-1 min-w-0 p-6">

            <h1 class="text-3xl font-semibold mb-6">Dashboard</h1>

            <!-- CHART + STATS -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">

                <!-- LEFT: PIE -->
                <div class="bg-white rounded-2xl shadow p-6 flex flex-col items-center justify-center">
                    <h2 class="text-lg font-semibold mb-4 text-center">Kontostände</h2>
                    <div class="w-full">
                        <canvas ref="pieCanvas"></canvas>
                    </div>
                </div>

                <!-- MIDDLE: Einnahmen + Ausgaben -->
                <div class="flex flex-col gap-4 h-full">

                    <div class="bg-green-100 rounded-2xl p-6 shadow flex-1 flex flex-col justify-center">
                        <h3 class="text-sm text-gray-600">Einnahmen</h3>
                        <p class="text-4xl font-bold text-green-600">€{{ totalIncome.toFixed(2) }}</p>
                    </div>

                    <div class="bg-red-100 rounded-2xl p-6 shadow flex-1 flex flex-col justify-center">
                        <h3 class="text-sm text-gray-600">Ausgaben</h3>
                        <p class="text-4xl font-bold text-red-600">€{{ totalExpense.toFixed(2) }}</p>
                    </div>

                </div>

                <!-- RIGHT: Sparquote – bei md überbeide Spalten zentriert, bei xl normal -->
                <div
                    class="bg-blue-100 rounded-2xl p-6 shadow flex flex-col justify-center items-center md:col-span-2 xl:col-span-1">
                    <h3 class="text-sm text-gray-600 mb-2">Sparquote</h3>
                    <p class="text-4xl font-bold text-blue-600">{{ savingsRate.toFixed(1) }}%</p>
                    <p class="text-xs text-gray-500 mt-2 text-center">Anteil vom Einkommen gespart</p>
                </div>

            </div>

            <!-- BANKS -->
            <div v-if="loadingBanks">Konten werden geladen...</div>

            <div v-for="bank in banks" :key="bank.id">
                <BankAccountCard :accountName="bank.name" :balanceId="bank.id" :iban="bank.iban" :balance="bank.amount"
                    :icon="getBankIcon(bank.bankfirma)" @add-entry="openEntryModal" />
            </div>

        </main>
    </div>

    <!-- MODAL -->
    <div v-if="showEntryModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">

        <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">

            <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="add-circle" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">
                    Neuer Eintrag
                </h2>
            </span>

            <label class="block text-sm mb-1">Name</label>
            <input v-model="entryName" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Beschreibung</label>
            <input v-model="entryDescription" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Betrag (€)</label>
            <input v-model="entryAmount" type="number" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Konto</label>
            <select v-model="entryBankId" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
                <option value="">Bitte wählen</option>
                <option v-for="b in banks" :key="b.id" :value="b.id">
                    {{ b.name }}
                </option>
            </select>

            <label class="block text-sm mb-1">Datum</label>
            <input v-model="entryDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

            <div class="flex justify-end gap-3 pt-2">
                <button @click="closeEntryModal" class="px-4 py-2 border rounded-xl" :disabled="creatingEntry">
                    Abbrechen
                </button>

                <button @click="saveEntry" class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl"
                    :disabled="creatingEntry">
                    Speichern
                </button>
            </div>

        </div>
    </div>
</template>