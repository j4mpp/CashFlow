<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue"

const activities = ref([])
const loading = ref(true)
const banks = ref([])
const subcategories = ref([])

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

const mockActivities = [
  { id: crypto.randomUUID(), name: "Billa Einkauf", amount: -45.8, account: "Girokonto #9237", category: "Lebensmittel", date: "2025-01-12" },
  { id: crypto.randomUUID(), name: "Netflix Abo", amount: -15.99, account: "PayPal #2895", category: "Abos", date: "2025-01-10" },
  { id: crypto.randomUUID(), name: "Gehalt", amount: 2400, account: "Girokonto #9237", category: "Einnahme", date: "2025-01-01" }
]

// Filter: jetzt nach Bank-ID statt Name
const selectedBankId = ref("all")

async function fetchActivities() {
  try {
    const userid = getValidUserId()
    const safeUserId = encodeURIComponent(userid)

    const [txs, fetchedBanks, fetchedSubs] = await Promise.all([
      fetchJson(`/cashflow_api/transactions/get.php?userid=${safeUserId}`),
      fetchJson(`/cashflow_api/banks/get.php?userid=${safeUserId}`),
      fetchJson(`/cashflow_api/subcategories/get.php?userid=${safeUserId}`)
    ])

    banks.value = Array.isArray(fetchedBanks) ? fetchedBanks : []
    subcategories.value = Array.isArray(fetchedSubs) ? fetchedSubs : []

    const bankById = Object.fromEntries((fetchedBanks || []).map(b => [b.id, b]))
    const subById = Object.fromEntries((fetchedSubs || []).map(s => [s.id, s]))

    activities.value = (txs || []).map(t => {
      const bank = bankById[t.bankid]
      const sub = subById[t.subcategoryid]
      return {
        id: t.id,
        name: t.name,
        amount: Number(t.amount),
        bankid: t.bankid,
        subcategoryid: t.subcategoryid,
        account: bank ? bank.name : "Unbekanntes Konto",
        category: sub ? sub.name : "Ohne Kategorie",
        description: t.description ?? "",
        date: t.created_at || t.date || t.timestamp || null
      }
    })
  } catch (err) {
    console.log("Backend Fehler → Mock geladen", err)
    activities.value = mockActivities
  } finally {
    loading.value = false
  }
}

const showModal = ref(false)
const mode = ref("create") // create | edit
const editingEntry = ref(null)
const creating = ref(false)
const txName = ref("")
const txDescription = ref("")
const txAmount = ref("")
const txBankId = ref("")
const txSubcategoryId = ref("")
const txDate = ref(new Date().toISOString().slice(0, 10))

function normalizeDateToISO(dateValue) {
  // Backend erwartet YYYY-MM-DD (date_iso).
  if (!dateValue) return new Date().toISOString().slice(0, 10)
  if (typeof dateValue === "string") return dateValue.slice(0, 10)
  return new Date().toISOString().slice(0, 10)
}

function openModal() {
  mode.value = "create"
  editingEntry.value = null
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  mode.value = "create"
  editingEntry.value = null
  txName.value = ""
  txDescription.value = ""
  txAmount.value = ""
  txBankId.value = ""
  txSubcategoryId.value = ""
  txDate.value = new Date().toISOString().slice(0, 10)
}

function openEditModal(activity) {
  mode.value = "edit"
  editingEntry.value = activity

  txName.value = activity.name ?? ""
  txDescription.value = activity.description ?? ""
  txAmount.value = Number(activity.amount ?? 0)
  txBankId.value = String(activity.bankid ?? "")
  txSubcategoryId.value = String(activity.subcategoryid ?? "")
  txDate.value = normalizeDateToISO(activity.date)

  showModal.value = true
}

async function updateBankBalance(userid, bank, nextAmount) {
  // banks/update.php fordert: name + iban + bankfirma + amount.
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

async function saveTransaction() {
  const userid = getValidUserId()
  const name = txName.value.trim()
  const description = txDescription.value.trim()
  const amount = Number(txAmount.value)
  const bankid = txBankId.value
  const subcategoryid = txSubcategoryId.value
  const date = txDate.value

  if (!name) return alert("Bitte Name eingeben.")
  if (!bankid) return alert("Bitte Konto wählen.")
  if (!subcategoryid) return alert("Bitte Kategorie wählen.")
  if (!Number.isFinite(amount)) return alert("Bitte gültigen Betrag eingeben.")
  if (!date) return alert("Bitte Datum wählen.")

  creating.value = true
  try {
    if (mode.value === "edit") {
      const e = editingEntry.value
      if (!e?.id) throw new Error("Kein Eintrag ausgewählt.")

      const oldAmount = Number(e.amount ?? 0)
      const oldBankId = Number(e.bankid ?? 0)
      const newBankId = Number(bankid)

      await fetchJson("/cashflow_api/transactions/update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          userid,
          id: Number(e.id),
          subcategoryid: Number(subcategoryid),
          name,
          description,
          amount,
          bankid: newBankId,
          date
        })
      })

      const oldBank = banks.value.find(b => Number(b.id) === oldBankId)
      const newBank = banks.value.find(b => Number(b.id) === newBankId)

      if (oldBankId === newBankId) {
        if (oldBank) {
          const current = Number(oldBank.amount)
          const nextAmount = (Number.isFinite(current) ? current : 0) + (amount - oldAmount)
          await updateBankBalance(userid, oldBank, nextAmount)
        }
      } else {
        if (oldBank) {
          const oldCurrent = Number(oldBank.amount)
          const nextOldAmount = (Number.isFinite(oldCurrent) ? oldCurrent : 0) - oldAmount
          await updateBankBalance(userid, oldBank, nextOldAmount)
        }
        if (newBank) {
          const newCurrent = Number(newBank.amount)
          const nextNewAmount = (Number.isFinite(newCurrent) ? newCurrent : 0) + amount
          await updateBankBalance(userid, newBank, nextNewAmount)
        }
      }
    } else {
      await fetchJson("/cashflow_api/transactions/create.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userid, subcategoryid: Number(subcategoryid), name, description, amount, bankid: Number(bankid), date })
      })

      const bank = banks.value.find(b => String(b.id) === String(bankid))
      if (bank) {
        const nextAmount = (Number.isFinite(Number(bank.amount)) ? Number(bank.amount) : 0) + amount
        await updateBankBalance(userid, bank, nextAmount)
      }
    }

    closeModal()
    loading.value = true
    await fetchActivities()
  } catch (err) {
    alert("Fehler beim Speichern.")
  } finally {
    creating.value = false
  }
}

const filteredActivities = computed(() => {
  let list = activities.value

  if (selectedBankId.value !== "all") {
    list = list.filter(a => String(a.bankid) === String(selectedBankId.value))
  }

  return [...list].sort((a, b) => {
    if (!a.date && !b.date) return 0
    if (!a.date) return 1
    if (!b.date) return -1
    return new Date(b.date) - new Date(a.date)
  })
})

onMounted(() => {
  fetchActivities()
})
</script>

<template>
  <div class="min-h-screen p-6 text-gray-900">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">

      <h1 class="text-3xl font-semibold">Aktivitäten</h1>

      <!-- FILTER (neues Design) -->
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Konto:</span>
        <select v-model="selectedBankId" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
          <option value="all">Alle Konten</option>
          <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

    </div>

    <!-- ACTIVITY LIST -->
    <div class="space-y-4">
      <div v-if="loading" class="text-center text-gray-500 py-10">Aktivitäten werden geladen...</div>

      <template v-else>
        <div v-for="activity in filteredActivities" :key="activity.id"
          class="rounded-2xl p-5 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl hover:scale-[1.01] transition duration-200">
          <div class="flex items-center justify-between">
            <div>
              <div class="flex items-center gap-2">
                <p class="font-semibold text-lg">{{ activity.name }}</p>
                <button @click.stop="openEditModal(activity)" type="button"
                  class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-xl hover:bg-gray-100 transition"
                  title="Aktivität bearbeiten">
                  <ion-icon name="pencil" class="w-4 h-4 text-teal-400"></ion-icon>
                </button>
              </div>
              <p class="text-sm text-gray-600">{{ activity.account }} • {{ activity.category }}</p>
              <p v-if="activity.description" class="text-xs text-gray-500 mt-1">{{ activity.description }}</p>
              <p v-if="activity.date" class="text-xs text-gray-400 mt-1">
                {{ new Date(activity.date).toLocaleDateString("de-DE") }}
              </p>
            </div>
            <div :class="['text-xl font-semibold', activity.amount < 0 ? 'text-red-500' : 'text-green-600']">
              {{ activity.amount < 0 ? "-" : "+" }} {{ Math.abs(activity.amount).toLocaleString("de-DE") }} € </div>
            </div>
          </div>

          <div v-if="filteredActivities.length === 0" class="text-center text-gray-500 py-10">
            Keine Aktivitäten gefunden.
          </div>
      </template>
    </div>

    <!-- FLOAT BUTTON -->
    <button @click="openModal"
      class="fixed bottom-6 right-6 w-14 h-14 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition hover:scale-110">
      <ion-icon name="add-outline"></ion-icon>
    </button>

    <!-- MODAL -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
      <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">

        <span class="h-20 pb-3 flex items-center gap-3">
          <ion-icon name="add-circle" class="w-8 h-8 text-teal-400"></ion-icon>
          <h2 class="text-2xl font-semibold">{{ mode === "edit" ? "Aktivität bearbeiten" : "Neue Aktivität" }}</h2>
        </span>

        <label class="block text-sm mb-1">Name</label>
        <input v-model="txName" placeholder="z.B. Billa Einkauf"
          class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

        <label class="block text-sm mb-1">Beschreibung (optional)</label>
        <input v-model="txDescription" placeholder="z.B. Wochenendeinkauf"
          class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

        <label class="block text-sm mb-1">Betrag (€)</label>
        <input v-model="txAmount" type="number" step="0.01" placeholder="-45.80"
          class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

        <label class="block text-sm mb-1">Konto</label>
        <select v-model="txBankId" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
          <option value="">Bitte wählen</option>
          <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>

        <label class="block text-sm mb-1">Kategorie</label>
        <select v-model="txSubcategoryId" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
          <option value="">Bitte wählen</option>
          <option v-for="s in subcategories" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>

        <label class="block text-sm mb-1">Datum</label>
        <input v-model="txDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

        <div class="flex justify-end gap-3 pt-2">
          <button @click="closeModal" class="px-4 py-2 border rounded-xl" :disabled="creating">Abbrechen</button>
          <button @click="saveTransaction"
            class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl disabled:opacity-50"
            :disabled="creating">Speichern</button>
        </div>
      </div>
    </div>

  </div>
</template>