<script setup>
import { ref, onMounted } from "vue"
import Navbar from "@/components/Navbar.vue"
import BankAccountCard from "@/components/BankAccountCard.vue"

/* =========================
   STATE
========================= */

const banks = ref([])
const loadingBanks = ref(true)

const newBankType = ref("")
const newAccountName = ref("")
const newAccountIban = ref("")
const newAccountBalance = ref("")

let activeBalanceId = null
let activeAccountName = ""

/* =========================
   FETCH BANKS
========================= */

async function fetchBanks() {
    try {
        const userid = localStorage.getItem("userid")
        if (!userid) return

        const res = await fetch(
            `http://localhost:8000/banks/get.php?userid=${userid}`
        )

        const data = await res.json()
        banks.value = data

    } catch (err) {
        console.log("Fehler beim Laden der Banken", err)
    } finally {
        loadingBanks.value = false
    }
}

/* =========================
   FORMAT HELPERS
========================= */

function formatBalance(amount) {
    const value = Number(amount) || 0
    return "€" + value.toLocaleString("de-DE")
}

function getBankIcon(type) {
    if (type === "mastercard") return "/img/mc_symbol.svg"
    if (type === "paypal") return "/img/paypal_icon.svg"
    if (type === "revolut") return "/img/revolut_icon.svg"
    return "/img/mc_symbol.svg"
}

/* =========================
   MODALS
========================= */

function openAddModal() {
    newBankType.value = ""
    newAccountName.value = ""
    newAccountIban.value = ""
    newAccountBalance.value = ""

    document.getElementById("addAccountModal").classList.remove("hidden")
}

function closeAddModal() {
    document.getElementById("addAccountModal").classList.add("hidden")
}

function openAccountModal(balanceId, accountName) {
    activeBalanceId = balanceId
    activeAccountName = accountName

    document.getElementById("accountModal").classList.remove("hidden")
}

function closeAccountModal() {
    document.getElementById("accountModal").classList.add("hidden")
    activeBalanceId = null
}

/* =========================
   CREATE BANK
========================= */

async function saveNewAccount() {
    if (!newBankType.value ||
        !newAccountName.value ||
        !newAccountIban.value ||
        !newAccountBalance.value) {
        alert("Bitte alle Felder korrekt ausfüllen.")
        return
    }

    const userid = localStorage.getItem("userid")
    if (!userid) {
        alert("Nicht eingeloggt.")
        return
    }

    try {
        await fetch("http://localhost:8000/banks/create.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                userid,
                name: newAccountName.value,
                iban: newAccountIban.value,
                amount: parseFloat(newAccountBalance.value),
                bankfirma: newBankType.value
            })
        })

        closeAddModal()
        await fetchBanks()

    } catch (err) {
        console.log("Fehler beim Speichern:", err)
    }
}

onMounted(() => {
    fetchBanks()
})
</script>

<template>
    <div class="flex text-gray-900">

        <main class="flex-1 min-w-0 p-6">
            <h1 class="text-3xl font-semibold mb-6 ">Dashboard</h1>

            <div v-if="loadingBanks" class="text-gray-500">
                Konten werden geladen...
            </div>

            <BankAccountCard v-for="bank in banks" :key="bank.id" :accountName="bank.name"
                :accountOwner="'CashFlow User'" :icon="getBankIcon(bank.bankfirma)"
                :balance="formatBalance(bank.amount)" :balanceId="'balance-' + bank.id" :iban="bank.iban"
                @add-entry="openAccountModal" />

        </main>
    </div>

    <!-- Floating Add Button -->
    <button @click="openAddModal"
        class="fixed bottom-6 right-6 w-16 h-16 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition-transform duration-200 ease-out hover:scale-110">
        <ion-icon name="add-outline"></ion-icon>
    </button>

    <!-- ADD ACCOUNT MODAL -->
    <div id="addAccountModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
        <div class="bg-white w-11/12 max-w-lg mx-auto mt-32 p-8 rounded-2xl shadow-xl">

            <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="card" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">Neues Bankkonto hinzufügen</h2>
            </span>

            <label class="block text-sm mb-1">Bank / Konto</label>
            <select v-model="newBankType" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
                <option value="">Bitte wählen</option>
                <option value="mastercard">Mastercard</option>
                <option value="paypal">PayPal</option>
                <option value="revolut">Revolut</option>
            </select>

            <label class="block text-sm mb-1">Kontoname / Nummer</label>
            <input v-model="newAccountName" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4"
                placeholder="z.B. Girokonto #1234" />

            <label class="block text-sm mb-1">IBAN</label>
            <input v-model="newAccountIban" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4"
                placeholder="AT12 1234 5678 1234 5678" />

            <label class="block text-sm mb-1">Aktueller Kontostand (€)</label>
            <input v-model="newAccountBalance" type="number" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

            <div class="flex justify-end gap-4">
                <button @click="closeAddModal" class="px-4 py-2 rounded-lg border border-gray-300">
                    Abbrechen
                </button>
                <button @click="saveNewAccount" class="px-4 py-2 rounded-lg bg-teal-400 text-white hover:bg-teal-500">
                    Hinzufügen
                </button>
            </div>

        </div>
    </div>

</template>