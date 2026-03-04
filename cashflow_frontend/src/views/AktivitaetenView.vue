<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue"

/* =========================
   STATE
========================= */

const activities = ref([])
const loading = ref(true)
const banks = ref([])
const subcategories = ref([])

/* =========================
   MOCK FALLBACK
========================= */

const mockActivities = [
  {
    id: crypto.randomUUID(),
    name: "Billa Einkauf",
    amount: -45.8,
    account: "Girokonto #9237",
    category: "Lebensmittel",
    date: "2025-01-12"
  },
  {
    id: crypto.randomUUID(),
    name: "Netflix Abo",
    amount: -15.99,
    account: "PayPal #2895",
    category: "Abos",
    date: "2025-01-10"
  },
  {
    id: crypto.randomUUID(),
    name: "Gehalt",
    amount: 2400,
    account: "Girokonto #9237",
    category: "Einnahme",
    date: "2025-01-01"
  }
]

/* =========================
   DROPDOWN STATE
========================= */

const selectedAccount = ref("Alle Konten")
const dropdownOpen = ref(false)

/* Alle vorhandenen Konten dynamisch erzeugen */

const accounts = computed(() => {
    const unique = [...new Set(activities.value.map(a => a.account))]
    return ["Alle Konten", ...unique]
})

/* =========================
   FETCH FROM BACKEND
========================= */

async function fetchActivities() {
  try {
    const userid = localStorage.getItem("userid")

    if (!userid) {
      activities.value = mockActivities
      return
    }

    const [txRes, banksRes, subsRes] = await Promise.all([
      fetch(`http://localhost:8000/transactions/get.php?userid=${userid}`),
      fetch(`http://localhost:8000/banks/get.php?userid=${userid}`),
      fetch(`http://localhost:8000/subcategories/get.php?userid=${userid}`)
    ])

    const [txs, fetchedBanks, fetchedSubs] = await Promise.all([
      txRes.json(),
      banksRes.json(),
      subsRes.json()
    ])

    banks.value = Array.isArray(fetchedBanks) ? fetchedBanks : []
    subcategories.value = Array.isArray(fetchedSubs) ? fetchedSubs : []

    const bankById = Object.fromEntries(
      (fetchedBanks || []).map(b => [b.id, b])
    )

    const subById = Object.fromEntries(
      (fetchedSubs || []).map(s => [s.id, s])
    )

    activities.value = (txs || []).map(t => {
      const bank = bankById[t.bankid]
      const sub = subById[t.subcategoryid]

      const rawDate = t.created_at || t.date || t.timestamp || null

      return {
        id: t.id,
        name: t.name,
        amount: Number(t.amount),
        account: bank ? bank.name : "Unbekanntes Konto",
        category: sub ? sub.name : "Ohne Kategorie",
        description: t.description,
        date: rawDate
      }
    })
  } catch (err) {
    console.log("Backend Fehler → Mock Aktivitäten geladen", err)
    activities.value = mockActivities
  } finally {
    loading.value = false
  }
}

/* =========================
   MODAL STATE (CREATE)
========================= */

const showModal = ref(false)
const creating = ref(false)

const txName = ref("")
const txDescription = ref("")
const txAmount = ref("")
const txBankId = ref("")
const txSubcategoryId = ref("")
const txDate = ref(new Date().toISOString().slice(0, 10)) // YYYY-MM-DD

function openModal() {
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  txName.value = ""
  txDescription.value = ""
  txAmount.value = ""
  txBankId.value = ""
  txSubcategoryId.value = ""
  txDate.value = new Date().toISOString().slice(0, 10)
}

async function saveTransaction() {
  const userid = localStorage.getItem("userid")
  if (!userid) return alert("Nicht eingeloggt.")

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
    await fetch("http://localhost:8000/transactions/create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        userid,
        subcategoryid,
        name,
        description,
        amount,
        bankid,
        date
      })
    })

    // Kontostand aktualisieren: neuer Betrag = alter Betrag + Transaktionsbetrag
    const bank = banks.value.find(b => String(b.id) === String(bankid))
    if (bank) {
      const current = Number(bank.amount)
      const nextAmount = (Number.isFinite(current) ? current : 0) + amount

      await fetch("http://localhost:8000/banks/update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          userid,
          id: bank.id,
          name: bank.name,
          iban: bank.iban,
          amount: nextAmount,
          bankfirma: bank.bankfirma
        })
      })
    }

    closeModal()
    loading.value = true
    await fetchActivities()
  } catch (err) {
    console.log("Fehler beim Speichern:", err)
    alert("Fehler beim Speichern.")
  } finally {
    creating.value = false
  }
}

/* =========================
   FILTER + SORT
========================= */

const filteredActivities = computed(() => {
  let list = activities.value

  if (selectedAccount.value !== "Alle Konten") {
    list = list.filter(a => a.account === selectedAccount.value)
  }

  return [...list].sort((a, b) => {
    if (!a.date && !b.date) return 0
    if (!a.date) return 1
    if (!b.date) return -1
    return new Date(b.date) - new Date(a.date)
  })
})

function selectAccount(acc) {
  selectedAccount.value = acc
  dropdownOpen.value = false
}

function formatDate(date) {
  if (!date) return "-"
  const d = new Date(date)
  if (isNaN(d.getTime())) return "-"
  return d.toLocaleDateString("de-DE")
}

/* Click outside close */

function handleClickOutside(e) {
  if (!e.target.closest(".filter-dropdown")) {
    dropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside)
  fetchActivities()
})

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside)
})
</script>

<template>
    <div class="min-h-screen p-6 text-gray-900">

        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">

            <h1 class="text-3xl font-semibold">
                Aktivitäten
            </h1>

            <!-- FILTER DROPDOWN -->
            <div class="relative filter-dropdown w-64">

                <!-- BUTTON -->
                <button @click="dropdownOpen = !dropdownOpen"
                    class="w-full flex items-center justify-between px-4 py-2 rounded-xl bg-white/60 backdrop-blur-xl border border-white/40 shadow-sm hover:bg-teal-50 transition">
                    <span class="text-gray-800 font-medium">
                        {{ selectedAccount }}
                    </span>

                    <ion-icon name="filter" class="text-teal-500 text-lg"></ion-icon>
                </button>

                <!-- DROPDOWN MENU -->
                <div v-if="dropdownOpen"
                    class="absolute mt-2 w-full rounded-xl bg-white/80 backdrop-blur-xl border border-white/40 shadow-xl overflow-hidden z-50">
                    <div v-for="acc in accounts" :key="acc" @click="selectAccount(acc)"
                        class="px-4 py-3 cursor-pointer hover:bg-teal-100 transition" :class="acc === selectedAccount
                            ? 'bg-teal-50 font-semibold text-teal-600'
                            : 'text-gray-800'">
                        {{ acc }}
                    </div>
                </div>

            </div>

        </div>

        <!-- ACTIVITY LIST -->
        <div class="space-y-4">

            <div v-if="loading" class="text-center text-gray-500 py-10">
                Aktivitäten werden geladen...
            </div>

            <template v-else>
                <div v-for="activity in filteredActivities" :key="activity.id"
                    class="rounded-2xl p-5 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl hover:scale-[1.01] transition duration-200">
                    <div class="flex items-center justify-between">

                        <!-- LEFT -->
                        <div>
                            <p class="font-semibold text-lg">
                                {{ activity.name }}
                            </p>

                            <p class="text-sm text-gray-600">
                                {{ activity.account }} • {{ activity.category }}
                            </p>

                            <p v-if="activity.description" class="text-xs text-gray-500 mt-1">
                                {{ activity.description }}
                            </p>

                            <p v-if="activity.date" class="text-xs text-gray-400 mt-1">
                                {{new Date(activity.date).toLocaleDateString("de-DE") }}
                            </p>
                        </div>

                        <!-- RIGHT -->
                        <div :class="[
                            'text-xl font-semibold',
                            activity.amount < 0 ? 'text-red-500' : 'text-green-600'
                        ]">
                            {{ activity.amount < 0 ? "-" : "+" }} {{ Math.abs(activity.amount).toLocaleString("de-DE") }} €
                        </div>

                    </div>
                </div>

                <!-- Falls keine Aktivitäten -->
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
                    <h2 class="text-2xl font-semibold">
                        Neue Aktivität
                    </h2>
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
                    <option v-for="b in banks" :key="b.id" :value="b.id">
                        {{ b.name }}
                    </option>
                </select>

                <label class="block text-sm mb-1">Kategorie</label>
                <select v-model="txSubcategoryId" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6">
                    <option value="">Bitte wählen</option>
                    <option v-for="s in subcategories" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </option>
                </select>

                <label class="block text-sm mb-1">Datum</label>
                <input v-model="txDate" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

                <div class="flex justify-end gap-3 pt-2">
                    <button @click="closeModal" class="px-4 py-2 border rounded-xl" :disabled="creating">
                        Abbrechen
                    </button>

                    <button @click="saveTransaction"
                        class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl disabled:opacity-50"
                        :disabled="creating">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
