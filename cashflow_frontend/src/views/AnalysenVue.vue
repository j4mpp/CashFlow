<script setup>
import { ref, onMounted, watch, nextTick, computed } from "vue"
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
} from "chart.js"

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
)

const chartCanvas = ref(null)
let chartInstance = null

const selectedMonths = ref(6)
const activeTab = ref("ausgaben") // "ausgaben" | "einnahmen"
const loading = ref(false)
const noData = ref(false)

const transactions = ref([])
const banks = ref([])
const selectedBankId = ref("all")

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
   CHART RENDER
========================= */

async function renderChart(labels, values) {
    await nextTick()

    const canvas = chartCanvas.value
    if (!canvas) return

    const existingChart = Chart.getChart(canvas)
    if (existingChart) existingChart.destroy()

    const ctx = canvas.getContext("2d")
    const isAusgaben = activeTab.value === "ausgaben"

    chartInstance = new Chart(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: isAusgaben ? "Ausgaben (€)" : "Einnahmen (€)",
                    data: values,
                    backgroundColor: isAusgaben ? "#f87171" : "#2dd4bf"
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => "€" + v.toLocaleString("de-DE")
                    }
                }
            }
        }
    })
}

/* =========================
   BUILD CHART
========================= */

async function buildChartFromData() {
    const months = selectedMonths.value
    const now = new Date()
    const monthLabels = []
    const monthKeys = []

    for (let i = months - 1; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, "0")
        monthKeys.push(`${year}-${month}`)
        monthLabels.push(d.toLocaleDateString("de-DE", { month: "short", year: "2-digit" }))
    }

    const values = monthKeys.map(() => 0)

    transactions.value.forEach(t => {
        if (selectedBankId.value !== "all" && String(t.bankid) !== String(selectedBankId.value)) return

        const rawDate = t.date || t.created_at || t.timestamp
        if (!rawDate) return
        const d = new Date(rawDate)
        if (isNaN(d.getTime())) return

        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`
        const idx = monthKeys.indexOf(key)
        if (idx === -1) return

        const amount = Number(t.amount)
        if (!Number.isFinite(amount)) return

        if (activeTab.value === "ausgaben" && amount < 0) {
            values[idx] += Math.abs(amount)
        } else if (activeTab.value === "einnahmen" && amount > 0) {
            values[idx] += amount
        }
    })

    noData.value = values.every(v => v === 0)
    await renderChart(monthLabels, values)
}

async function loadData() {
    loading.value = true
    noData.value = false
    try {
        const userid = getValidUserId()
        const safeUserId = encodeURIComponent(userid)
        const [txs, fetchedBanks] = await Promise.all([
            fetchJson(`/cashflow_api/transactions/get.php?userid=${safeUserId}`),
            fetchJson(`/cashflow_api/banks/get.php?userid=${safeUserId}`)
        ])
        transactions.value = Array.isArray(txs) ? txs : []
        banks.value = Array.isArray(fetchedBanks) ? fetchedBanks : []
        await buildChartFromData()
    } catch (err) {
        console.error("Fehler beim Laden:", err)
        transactions.value = []
        banks.value = []
        noData.value = true
        await renderChart([], [])
    } finally {
        loading.value = false
    }
}

/* =========================
   TABELLE
========================= */

const filteredTransactions = computed(() => {
    const months = selectedMonths.value
    const now = new Date()
    const oldestMonthDate = new Date(now.getFullYear(), now.getMonth() - (months - 1), 1)

    return transactions.value
        .filter(t => {
            if (selectedBankId.value !== "all" && String(t.bankid) !== String(selectedBankId.value)) return false

            const rawDate = t.date || t.created_at || t.timestamp
            if (!rawDate) return false
            const d = new Date(rawDate)
            if (isNaN(d.getTime())) return false
            if (d < oldestMonthDate) return false

            const amount = Number(t.amount)
            if (activeTab.value === "ausgaben") return amount < 0
            if (activeTab.value === "einnahmen") return amount > 0
            return true
        })
        .sort((a, b) => {
            const ad = new Date(a.date || a.created_at || a.timestamp || 0)
            const bd = new Date(b.date || b.created_at || b.timestamp || 0)
            return bd - ad
        })
})

watch(selectedMonths, buildChartFromData)
watch(selectedBankId, buildChartFromData)
watch(activeTab, buildChartFromData)

onMounted(() => {
    loadData()
})
</script>

<template>
    <div class="min-h-screen flex text-gray-900">
        <main class="flex-1 min-w-0 p-4 md:p-6">

            <h1 class="text-3xl font-semibold mb-6">Analysen</h1>

            <!-- TAB SWITCH -->
            <div class="flex gap-2 mb-6">
                <button @click="activeTab = 'ausgaben'" :class="[
                    'px-5 py-2 rounded-xl font-medium transition border',
                    activeTab === 'ausgaben'
                        ? 'bg-red-400 text-white border-red-400'
                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                ]">
                    Ausgaben
                </button>
                <button @click="activeTab = 'einnahmen'" :class="[
                    'px-5 py-2 rounded-xl font-medium transition border',
                    activeTab === 'einnahmen'
                        ? 'bg-teal-400 text-white border-teal-400'
                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                ]">
                    Einnahmen
                </button>
            </div>

            <h2 class="text-2xl font-semibold mb-2">
                {{ activeTab === 'ausgaben' ? 'Gesamtausgaben' : 'Gesamteinnahmen' }}
            </h2>
            <hr class="mb-5">

            <!-- FILTER -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <!-- Monate -->
                <div class="flex gap-2 flex-wrap">
                    <button v-for="m in [3, 6, 9, 12]" :key="m" @click="selectedMonths = m" :class="[
                        'px-4 py-2 rounded-lg border text-sm',
                        selectedMonths === m
                            ? (activeTab === 'ausgaben' ? 'bg-red-400 text-white border-red-400' : 'bg-teal-400 text-white border-teal-400')
                            : 'bg-white hover:bg-gray-100 border-gray-200'
                    ]">
                        {{ m }} Monate
                    </button>
                </div>

                <!-- Konto-Filter -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Konto:</span>
                    <select v-model="selectedBankId"
                        class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                        <option value="all">Alle Konten</option>
                        <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
            </div>

            <!-- CHART -->
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow border border-gray-200 w-full min-w-0 overflow-hidden">

                <div v-if="loading" class="text-gray-400 mb-4">Lade Daten...</div>

                <div class="relative w-full h-[320px] sm:h-[350px] md:h-[400px]">
                    <canvas ref="chartCanvas" class="block w-full h-full"></canvas>
                </div>

                <div v-if="noData && !loading" class="text-center text-gray-400 mt-4">
                    Keine Daten verfügbar.
                </div>

                <!-- TABELLE -->
                <div v-if="!noData && filteredTransactions.length" class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-gray-500">
                                <th class="text-left pb-2 pr-4">Datum</th>
                                <th class="text-left pb-2 pr-4">Konto</th>
                                <th class="text-left pb-2 pr-4">Name</th>
                                <th class="text-right pb-2">Betrag (€)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in filteredTransactions" :key="t.id" class="border-b last:border-0">
                                <td class="py-2 pr-4 text-gray-700">
                                    {{ new Date(t.date || t.created_at || t.timestamp).toLocaleDateString("de-DE") }}
                                </td>
                                <td class="py-2 pr-4 text-gray-700">
                                    {{banks.find(b => String(b.id) === String(t.bankid))?.name || "Unbekannt"}}
                                </td>
                                <td class="py-2 pr-4 text-gray-800">{{ t.name }}</td>
                                <td class="py-2 text-right font-medium"
                                    :class="Number(t.amount) < 0 ? 'text-red-500' : 'text-green-600'">
                                    {{ Number(t.amount).toLocaleString("de-DE", {
                                        minimumFractionDigits: 2,
                                    maximumFractionDigits: 2 }) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</template>