<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue"
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from "chart.js"

Chart.register(DoughnutController, ArcElement, Tooltip, Legend)

const props = defineProps({
    accountName: String,
    accountOwner: String,
    icon: String,
    balance: [String, Number],
    balanceId: [String, Number],
    iban: String,
})

const emit = defineEmits(["add-entry", "edit-bank"])

function handleAddClick(e) {
    e.stopPropagation()
    emit("add-entry", props.balanceId, props.accountName)
}

function handleEditClick(e) {
    e.stopPropagation()
    emit("edit-bank")
}

/* =========================
   MODAL STATE
========================= */

const showModal = ref(false)
const transactions = ref([])
const loadingTx = ref(false)

const donutCanvas = ref(null)
let donutChart = null

async function fetchJson(url) {
    const res = await fetch(url, { credentials: "include" })
    const data = await res.json()
    if (!res.ok || data?.error) throw new Error(data?.error || "Fehler")
    return data
}

async function openModal() {
    showModal.value = true
    loadingTx.value = true
    try {
        const userid = localStorage.getItem("userid") || ""
        const safeUserId = encodeURIComponent(userid)
        const safeId = encodeURIComponent(props.balanceId)
        const all = await fetchJson(`/cashflow_api/transactions/get.php?userid=${safeUserId}`)
        transactions.value = (Array.isArray(all) ? all : [])
            .filter(t => String(t.bankid) === String(props.balanceId))
            .sort((a, b) => {
                const ad = new Date(a.date || a.created_at || a.timestamp || 0)
                const bd = new Date(b.date || b.created_at || b.timestamp || 0)
                return bd - ad
            })
    } catch (err) {
        transactions.value = []
    } finally {
        loadingTx.value = false
        await nextTick()
        renderDonut()
    }
}

function closeModal() {
    showModal.value = false
    if (donutChart) {
        donutChart.destroy()
        donutChart = null
    }
}

/* =========================
   STATS
========================= */

const totalIncome = computed(() => {
    return transactions.value.reduce((sum, t) => {
        const a = Number(t.amount)
        return a > 0 ? sum + a : sum
    }, 0)
})

const totalExpense = computed(() => {
    return transactions.value.reduce((sum, t) => {
        const a = Number(t.amount)
        return a < 0 ? sum + Math.abs(a) : sum
    }, 0)
})

const savingsRate = computed(() => {
    if (totalIncome.value <= 0) return 0
    return ((totalIncome.value - totalExpense.value) / totalIncome.value) * 100
})

const balance = computed(() => Number(props.balance || 0))

/* =========================
   DONUT CHART
========================= */

function renderDonut() {
    if (!donutCanvas.value) return
    if (donutChart) { donutChart.destroy(); donutChart = null }

    const income = totalIncome.value
    const expense = totalExpense.value

    if (income === 0 && expense === 0) return

    donutChart = new Chart(donutCanvas.value, {
        type: "doughnut",
        data: {
            labels: ["Einnahmen", "Ausgaben"],
            datasets: [{
                data: [income, expense],
                backgroundColor: ["#4ade80", "#f87171"],
                borderWidth: 2,
                borderColor: "#fff"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: "65%",
            plugins: {
                legend: { position: "bottom" },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const val = ctx.parsed
                            return ` €${val.toLocaleString("de-DE", { minimumFractionDigits: 2 })}`
                        }
                    }
                }
            }
        }
    })
}

watch(showModal, async (val) => {
    if (val) {
        await nextTick()
        renderDonut()
    }
})
</script>

<template>
    <!-- KARTE -->
    <section class="pb-5">
        <div class="rounded-2xl p-6 md:p-8 bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl cursor-pointer hover:shadow-2xl hover:bg-white/70 transition"
            @click="openModal">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center">
                    <img :src="icon" class="max-w-full max-h-full object-contain" />
                </div>

                <div>
                    <h2 class="text-xl md:text-2xl font-semibold">{{ accountName }}</h2>
                    <p v-if="iban" class="text-sm text-gray-500">{{ iban }}</p>
                </div>

                <div class="ml-auto flex items-center gap-4">
                    <ion-icon name="pencil" class="h-6 w-6 cursor-pointer hover:scale-125 active:scale-95"
                        @click="handleEditClick" />
                    <ion-icon name="add-outline" class="h-6 w-6 cursor-pointer hover:scale-125 active:scale-95"
                        @click="handleAddClick" />
                </div>
            </div>

            <hr class="my-4 border-gray-200" />

            <p class="text-sm text-gray-500 mb-1">Kontostand</p>
            <p class="text-3xl md:text-4xl font-semibold" :class="balance < 0 ? 'text-red-500' : 'text-gray-900'">
                {{ balance.toLocaleString("de-DE", { minimumFractionDigits: 2 }) }} €
            </p>
        </div>
    </section>

    <!-- DETAIL MODAL -->
    <Teleport to="body">
        <div v-if="showModal"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
            @click.self="closeModal">
            <div
                class="w-full sm:w-11/12 sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[92dvh] flex flex-col">

                <!-- MODAL HEADER -->
                <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-gray-100 shrink-0">
                    <img :src="icon" class="w-10 h-10 object-contain" />
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-semibold truncate">{{ accountName }}</h2>
                        <p v-if="iban" class="text-xs text-gray-400">{{ iban }}</p>
                    </div>
                    <button @click="closeModal"
                        class="ml-2 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition shrink-0">
                        <ion-icon name="close-outline" class="text-lg" />
                    </button>
                </div>

                <!-- SCROLLABLE CONTENT -->
                <div class="overflow-y-auto flex-1 px-6 py-5 space-y-6">

                    <!-- KONTOSTAND -->
                    <div class="rounded-2xl p-5 bg-gray-50 border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1">Kontostand</p>
                        <p class="text-4xl font-bold" :class="balance < 0 ? 'text-red-500' : 'text-emerald-600'">
                            {{ balance.toLocaleString("de-DE", { minimumFractionDigits: 2 }) }} €
                        </p>
                    </div>

                    <!-- STATS GRID -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-center">
                            <p class="text-xs text-gray-500 mb-1">Einnahmen</p>
                            <p class="text-lg font-bold text-emerald-600">
                                +{{ totalIncome.toLocaleString("de-DE", { minimumFractionDigits: 2 }) }} €
                            </p>
                        </div>
                        <div class="rounded-xl bg-red-50 border border-red-100 p-4 text-center">
                            <p class="text-xs text-gray-500 mb-1">Ausgaben</p>
                            <p class="text-lg font-bold text-red-500">
                                -{{ totalExpense.toLocaleString("de-DE", { minimumFractionDigits: 2 }) }} €
                            </p>
                        </div>
                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-4 text-center">
                            <p class="text-xs text-gray-500 mb-1">Sparquote</p>
                            <p class="text-lg font-bold" :class="savingsRate >= 0 ? 'text-blue-600' : 'text-red-500'">
                                {{ savingsRate.toFixed(1) }}%
                            </p>
                        </div>
                    </div>

                    <!-- DONUT CHART -->
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Auswertung</h3>
                        <div v-if="loadingTx" class="text-center text-gray-400 py-6">Lade...</div>
                        <div v-else-if="totalIncome === 0 && totalExpense === 0" class="text-center text-gray-400 py-6">
                            Keine Transaktionen
                        </div>
                        <div v-else class="max-w-[260px] mx-auto">
                            <canvas ref="donutCanvas"></canvas>
                        </div>
                    </div>

                    <!-- HISTORIE -->
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Historie</h3>

                        <div v-if="loadingTx" class="text-center text-gray-400 py-4">Lade...</div>

                        <div v-else-if="transactions.length === 0" class="text-center text-gray-400 py-4">
                            Keine Einträge
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="t in transactions" :key="t.id"
                                class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900 truncate">{{ t.name }}</p>
                                    <p v-if="t.description" class="text-xs text-gray-400 truncate">{{ t.description }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ new Date(t.date || t.created_at || t.timestamp).toLocaleDateString("de-DE")
                                        }}
                                    </p>
                                </div>
                                <span class="ml-4 shrink-0 font-semibold text-base"
                                    :class="Number(t.amount) < 0 ? 'text-red-500' : 'text-emerald-600'">
                                    {{ Number(t.amount) < 0 ? "-" : "+" }}{{
                                        Math.abs(Number(t.amount)).toLocaleString("de-DE", { minimumFractionDigits: 2 })
                                        }} € </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </Teleport>
</template>