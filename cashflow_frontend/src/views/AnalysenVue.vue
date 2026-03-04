<script setup>
import { ref, onMounted, watch, nextTick } from "vue"
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
   CHART RENDER
========================= */

async function renderChart(labels, values) {
    await nextTick()

    const canvas = chartCanvas.value
    if (!canvas) return

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
                    label: "Ausgaben (€)",
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
    loading.value = true
    noData.value = false

    const months = selectedMonths.value

    const labels = []
    const values = []

    for (let i = 1; i <= months; i++) {
        labels.push(`Monat ${i}`)
        values.push(0)
    }

    if (values.every(v => v === 0)) {
        noData.value = true
    }

    await renderChart(labels, values)

    loading.value = false
}

watch(selectedMonths, updateChart)

onMounted(updateChart)
</script>


<template>
<div class="min-h-screen flex text-gray-900">

    <main class="flex-1 min-w-0 p-4 md:p-6">

        <h1 class="text-3xl font-semibold mb-4">Analysen</h1>

        <h2 class="text-2xl font-semibold mb-2">Gesamtausgaben</h2>

        <hr>

        <!-- Filter Buttons -->
        <div class="flex gap-4 mb-6 mt-5">
            <button
                v-for="m in [3,6,12]"
                :key="m"
                @click="selectedMonths = m"
                :class="[
                    'px-4 py-2 rounded-lg border',
                    selectedMonths === m
                        ? 'bg-teal-400 text-white'
                        : 'bg-white hover:bg-gray-100'
                ]"
            >
                {{ m }} Monate
            </button>
        </div>

        <!-- Chart -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow border border-gray-200 w-full min-w-0 overflow-hidden">

            <div v-if="loading" class="text-gray-400">
                Lade Daten...
            </div>

            <div class="relative w-full min-w-0 h-[320px] sm:h-[350px] md:h-[380px] lg:h-[420px] xl:h-[450px]">
                <canvas ref="chartCanvas" class="block w-full h-full"></canvas>
            </div>

            <div v-if="noData" class="text-center text-gray-400 mt-4">
                Keine Daten verfügbar.
            </div>

        </div>

    </main>

</div>
</template>