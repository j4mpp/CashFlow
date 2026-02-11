<script setup>
import { ref, onMounted, watch, nextTick } from "vue"
import axios from "axios"
import Navbar from "@/components/Navbar.vue"
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
const loading = ref(false)
const noData = ref(false)

/* =========================
   MOCK DATA (fallback)
========================= */

function generateMockData(months) {
    const labels = []
    const values = []

    for (let i = 1; i <= months; i++) {
        labels.push(`Monat ${i}`)
        values.push(Math.floor(Math.random() * 1000))
    }

    return { labels, values }
}

/* =========================
   BACKEND CALL
========================= */

async function fetchAnalytics(months) {
    try {
        loading.value = true
        noData.value = false

        const response = await axios.get(
            `/api/analytics?months=${months}`
        )

        if (!response.data || response.data.values.length === 0) {
            noData.value = true
            return generateMockData(months)
        }

        return response.data

    } catch (error) {
        console.log("Backend nicht erreichbar – Mock Daten werden verwendet.")
        noData.value = true
        return generateMockData(months)
    } finally {
        loading.value = false
    }
}

/* =========================
   CHART RENDER
========================= */

async function renderChart(labels, values) {
    await nextTick()

    const canvas = chartCanvas.value
    if (!canvas) return

    // 🔥 WICHTIGER FIX
    const existingChart = Chart.getChart(canvas)
    if (existingChart) {
        existingChart.destroy()
    }

    const ctx = canvas.getContext("2d")

    chartInstance = new Chart(ctx, {
        type: "bar",
        data: {
            labels: labels,
            datasets: [
                {
                    label: noData.value ? "No Data (Mock)" : "Ausgaben (€)",
                    data: values,
                    backgroundColor: "#2dd4bf"
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    })
}


/* =========================
   UPDATE FLOW
========================= */

async function updateChart() {
    const data = await fetchAnalytics(selectedMonths.value)
    await renderChart(data.labels, data.values)
}

watch(selectedMonths, updateChart)

onMounted(updateChart)
</script>


<template>
    <div class="min-h-screen flex bg-gray-50 text-gray-900">
        <Navbar />

        <main class="flex-1 min-w-0 p-4 md:p-6">

            <h1 class="text-3xl font-semibold mb-8">Analysen</h1>

            <!-- Filter Buttons -->
            <div class="flex gap-4 mb-6">
                <button v-for="m in [3, 6, 12]" :key="m" @click="selectedMonths = m" :class="[
                    'px-4 py-2 rounded-lg border',
                    selectedMonths === m
                        ? 'bg-teal-400 text-white'
                        : 'bg-white hover:bg-gray-100'
                ]">
                    {{ m }} Monate
                </button>
            </div>

            <!-- Chart Card -->
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow border border-gray-200 w-full min-w-0 overflow-hidden">
                <div v-if="loading" class="text-gray-400">
                    Lade Daten...
                </div>

                <div class="relative w-full min-w-0 h-[320px] sm:h-[350px] md:h-[380px] lg:h-[420px] xl:h-[450px]">
                    <canvas ref="chartCanvas" class="block w-full h-full"></canvas>
                </div>

                <div v-if="noData" class="text-center text-gray-400 mt-4">
                    Keine echten Daten verfügbar – Mock Daten werden angezeigt.
                </div>

            </div>

        </main>
    </div>
</template>
