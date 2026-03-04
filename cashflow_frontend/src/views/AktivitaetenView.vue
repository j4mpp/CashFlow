<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue"

/* =========================
   STATE
========================= */

const activities = ref([])
const loading = ref(true)

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

    const [txs, banks, subs] = await Promise.all([
      txRes.json(),
      banksRes.json(),
      subsRes.json()
    ])

    const bankById = Object.fromEntries(
      (banks || []).map(b => [b.id, b])
    )

    const subById = Object.fromEntries(
      (subs || []).map(s => [s.id, s])
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

            <div v-for="activity in filteredActivities" :key="activity.id" v-else
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

                        <p class="text-xs text-gray-500 mt-1">
                            {{ formatDate(activity.date) }}
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

            </div>

        </div>
</template>
