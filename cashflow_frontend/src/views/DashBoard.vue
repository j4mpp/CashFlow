<script setup>
import { ref, onMounted } from "vue"
import BankAccountCard from "@/components/BankAccountCard.vue"

/* =========================
   STATE
========================= */

const banks = ref([])
const loadingBanks = ref(true)
const editingBankId = ref(null)

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

        banks.value = await res.json()
    } catch (err) {
        console.log("Fehler beim Laden der Banken", err)
    } finally {
        loadingBanks.value = false
    }
}

/* =========================
   EDIT LOGIC
========================= */

function startEdit(bank) {
    editingBankId.value = bank.id
    bank._editName = bank.name
    bank._editIban = bank.iban
    bank._editAmount = bank.amount
    bank._editType = bank.bankfirma
}

async function saveEdit(bank) {
    const userid = localStorage.getItem("userid")

    await fetch("http://localhost:8000/banks/update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            userid,
            id: bank.id,
            name: bank._editName,
            iban: bank._editIban,
            amount: bank._editAmount,
            bankfirma: bank._editType
        })
    })

    editingBankId.value = null
    await fetchBanks()
}

async function deleteBank(bank) {
    const userid = localStorage.getItem("userid")
    if (!confirm("Konto wirklich löschen?")) return

    await fetch("http://localhost:8000/banks/delete.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            userid,
            id: bank.id
        })
    })

    editingBankId.value = null
    await fetchBanks()
}

/* =========================
   HELPERS
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

onMounted(fetchBanks)
</script>

<template>
    <div class="flex text-gray-900">
        <main class="flex-1 min-w-0 p-6">

            <h1 class="text-3xl font-semibold mb-6">Dashboard</h1>

            <div v-if="loadingBanks" class="text-gray-500">
                Konten werden geladen...
            </div>

            <!-- BANK CARDS -->
            <div v-for="bank in banks" :key="bank.id">

                <BankAccountCard :accountName="bank.name" :accountOwner="'CashFlow User'"
                    :icon="getBankIcon(bank.bankfirma)" :balance="formatBalance(bank.amount)"
                    :balanceId="'balance-' + bank.id" :iban="bank.iban" @edit-bank="startEdit(bank)" />

                <!-- EDIT PANEL -->
                <div v-if="editingBankId === bank.id" class="rounded-xl border border-gray-200 p-4 mb-6 bg-white/70">

                    <input v-model="bank._editName" class="w-full mb-2 border px-3 py-2 rounded-xl" />

                    <input v-model="bank._editIban" class="w-full mb-2 border px-3 py-2 rounded-xl" />

                    <input v-model="bank._editAmount" type="number" class="w-full mb-2 border px-3 py-2 rounded-xl" />

                    <select v-model="bank._editType" class="w-full mb-4 border px-3 py-2 rounded-xl">
                        <option value="mastercard">Mastercard</option>
                        <option value="paypal">PayPal</option>
                        <option value="revolut">Revolut</option>
                    </select>

                    <div class="flex gap-4">
                        <button class="text-red-600" @click="deleteBank(bank)">
                            Delete
                        </button>

                        <button class="text-emerald-600" @click="saveEdit(bank)">
                            Save
                        </button>
                    </div>
                </div>

            </div>

        </main>
    </div>
</template>
