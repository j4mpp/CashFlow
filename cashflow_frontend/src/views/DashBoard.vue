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
    return `/banks/logo.php?userid=${encodeURIComponent(safeUserId)}&bankid=${encodeURIComponent(type)}`
}

/* =========================
   INIT
========================= */

onMounted(async () => {
<<<<<<< HEAD
    try {
        await fetchBanks()
        await fetchTransactions()
    } catch (err) {
        console.error("Dashboard konnte nicht geladen werden:", err)
        banks.value = []
        transactions.value = []
        loadingBanks.value = false
    }
=======
    await fetchBanks()
    await fetchTransactions()
>>>>>>> bfc3b71 (Pie Chart resizing gefixed)

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

<<<<<<< HEAD
                <!-- RIGHT: Sparquote – bei md überbeide Spalten zentriert, bei xl normal -->
=======
                <!-- RIGHT: Sparquote – bei md über beide Spalten zentriert, bei xl normal -->
>>>>>>> bfc3b71 (Pie Chart resizing gefixed)
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

                <BankAccountCard :accountName="bank.name" :accountOwner="'CashFlow User'" :icon="getBankIcon(bank.id)"
                    :balance="formatBalance(bank.amount)" :iban="bank.iban" />

            </div>

        </main>
    </div>
</template>