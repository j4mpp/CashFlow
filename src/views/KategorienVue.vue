<script setup>
import Navbar from "@/components/Navbar.vue"
import { ref, computed, onMounted, watch } from "vue"


/* =========================
   STATE
========================= */

const categories = ref([])
const loading = ref(true)

/* =========================
   MOCK FALLBACK
========================= */

const mockCategories = [
  {
    id: crypto.randomUUID(),
    name: "Haushalt",
    open: false,
    subcategories: [
      {
        id: crypto.randomUUID(),
        name: "Lebensmittel",
        open: false,
        entries: []
      },
      {
        id: crypto.randomUUID(),
        name: "Strom",
        open: false,
        entries: []
      },
      {
        id: crypto.randomUUID(),
        name: "Wasser",
        open: false,
        entries: []
      }
    ]
  }
]

/* =========================
   FETCH FROM BACKEND
========================= */

async function fetchCategories() {
  try {
    const response = await fetch("http://localhost:8081/api/categories")

    if (!response.ok) throw new Error("No backend data")

    const data = await response.json()

    if (!data || data.length === 0) {
      categories.value = mockCategories
    } else {
      categories.value = data
    }
  } catch (err) {
    console.log("Backend nicht erreichbar → Mock geladen")
    categories.value = mockCategories
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCategories()
})

/* =========================
   MODAL STATE
========================= */

const showModal = ref(false)
const mode = ref("category")

const catName = ref("")
const catType = ref("main")
const parentCategory = ref("")

function openModal() {
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  catName.value = ""
  catType.value = "main"
  parentCategory.value = ""
}

/* =========================
   TOGGLE LOGIC
========================= */

function toggleCategory(cat) {
  cat.open = !cat.open
}

function toggleSub(sub) {
  sub.open = !sub.open
}

/* =========================
   SAVE CATEGORY
========================= */

function saveCategory() {
  if (!catName.value) return alert("Bitte Name eingeben.")

  if (catType.value === "main") {
    categories.value.push({
      id: crypto.randomUUID(),
      name: catName.value,
      open: false,
      subcategories: []
    })
  }

  if (catType.value === "sub") {
    const parent = categories.value.find(c => c.id === parentCategory.value)
    if (!parent) return alert("Übergeordnete Kategorie wählen")

    parent.subcategories.push({
      id: crypto.randomUUID(),
      name: catName.value,
      open: false,
      entries: []
    })
  }

  closeModal()
}

const mainCategories = computed(() => categories.value)
</script>

<template>
  <div class="min-h-screen flex text-gray-900">
    <main class="flex-1 p-6 relative">

      <!-- Hintergrund Glow -->
      <div class="absolute inset-0 -z-10">
        <div class="absolute left-0 top-0 w-[500px] h-[500px] bg-teal-300/40 rounded-full blur-3xl"></div>
        <div class="absolute right-0 bottom-0 w-[400px] h-[400px] bg-emerald-400/40 rounded-full blur-3xl"></div>
      </div>

      <h1 class="text-3xl font-semibold mb-6 ">
        Kategorien
      </h1>

      <div v-if="loading" class="text-center py-10 text-gray-500">
        Kategorien werden geladen...
      </div>

      <div v-else class="space-y-4">

        <!-- MAIN CATEGORIES -->
        <div v-for="cat in categories" :key="cat.id"
          class="rounded-2xl bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

          <button @click="toggleCategory(cat)"
            class="w-full flex items-center justify-between px-5 py-4 text-left font-medium text-lg">
            {{ cat.name }}
            <ion-icon :name="cat.open ? 'chevron-up-outline' : 'chevron-down-outline'" />
          </button>

          <div v-if="cat.open" class="px-6 pb-6">

            <div v-if="cat.subcategories.length === 0" class="text-sm text-gray-400">
              Noch keine Unterkategorien.
            </div>

            <!-- SUBCATEGORIES -->
            <div v-for="sub in cat.subcategories" :key="sub.id"
              class="mt-3 rounded-xl bg-white/80 backdrop-blur-md border border-gray-200">

              <button @click="toggleSub(sub)" class="w-full flex items-center justify-between px-4 py-3 text-left">
                {{ sub.name }}
                <ion-icon :name="sub.open ? 'chevron-up-outline' : 'chevron-down-outline'" />
              </button>

              <div v-if="sub.open" class="px-4 pb-4 text-sm text-gray-500">
                Noch keine Einträge.
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- FLOAT BUTTON -->
      <button @click="openModal"
        class="fixed bottom-6 right-6 w-14 h-14 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition hover:scale-110">
        <ion-icon name="add-outline"></ion-icon>
      </button>

      <!-- MODAL -->
      <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">

        <div class="w-11/12 max-w-md p-6 rounded-2xl
                    bg-white backdrop-blur-xl
                    border border-white/40 shadow-xl">


          <span class="h-20 pb-3 flex items-center gap-3">
            <ion-icon name="duplicate" class="w-8 h-8 text-teal-400"></ion-icon>
            <h1 class="text-2xl font-semibold">
              Neue Kategorie hinzufügen
            </h1>
          </span>


          <select v-model="catType" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
            <option value="main">Hauptkategorie</option>
            <option value="sub">Unterkategorie</option>
          </select>

          <input v-model="catName" placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

          <select v-if="catType === 'sub'" v-model="parentCategory"
            class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
            <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>

          <div class="flex justify-end gap-3 pt-4">
            <button @click="closeModal" class="px-4 py-2 border rounded-xl">
              Abbrechen
            </button>

            <button @click="saveCategory" class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl">
              Speichern
            </button>
          </div>
        </div>
      </div>

    </main>
  </div>
</template>
