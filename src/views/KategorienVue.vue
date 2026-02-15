<script setup>
import Navbar from "@/components/Navbar.vue"
import { ref, computed } from "vue"

/* =========================
   STATE
========================= */

const categories = ref([
  {
    id: crypto.randomUUID(),
    name: "Haushalt",
    open: false,
    subcategories: [
      {
        id: "uk1",
        name: "Lebensmittel",
        open: false,
        entries: []
      },
      {
        id: "uk2",
        name: "Strom",
        open: false,
        entries: []
      },
      {
        id: "uk3",
        name: "Wasser",
        open: false,
        entries: []
      }
    ]
  }
])

/* =========================
   MODAL STATE
========================= */

const showModal = ref(false)
const mode = ref("category")

/* Category Form */
const catName = ref("")
const catType = ref("main")
const parentCategory = ref("")

/* Entry Form */
const entryAmount = ref("")
const entryName = ref("")
const entryDate = ref("")
const entryDesc = ref("")
const entrySubCategory = ref("")

/* =========================
   HELPERS
========================= */

function toggleCategory(cat) {
  cat.open = !cat.open
}

function toggleSub(sub) {
  sub.open = !sub.open
}

function openModal() {
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  resetForms()
}

function resetForms() {
  catName.value = ""
  catType.value = "main"
  parentCategory.value = ""
  entryAmount.value = ""
  entryName.value = ""
  entryDate.value = ""
  entryDesc.value = ""
  entrySubCategory.value = ""
}

/* =========================
   SAVE LOGIC
========================= */

function saveData() {
  if (mode.value === "category") saveCategory()
  if (mode.value === "entry") saveEntry()
}

/* -------- CATEGORY -------- */

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
    if (!parent) return alert("Übergeordnete Kategorie nicht gefunden")

    parent.subcategories.push({
      id: crypto.randomUUID(),
      name: catName.value,
      open: false,
      entries: []
    })
  }

  closeModal()
}

/* -------- ENTRY -------- */

function saveEntry() {
  if (!entryAmount.value || !entryName.value)
    return alert("Bitte mindestens Betrag und Name eingeben.")

  const sub = findSubcategory(entrySubCategory.value)
  if (!sub) return alert("Unterkategorie nicht gefunden")

  sub.entries.push({
    id: crypto.randomUUID(),
    amount: Number(entryAmount.value).toFixed(2),
    name: entryName.value,
    date: entryDate.value,
    desc: entryDesc.value
  })

  closeModal()
}

function findSubcategory(id) {
  for (const cat of categories.value) {
    const found = cat.subcategories.find(s => s.id === id)
    if (found) return found
  }
  return null
}

/* Dropdown options */

const mainCategories = computed(() => categories.value)
const allSubcategories = computed(() =>
  categories.value.flatMap(cat => cat.subcategories)
)
</script>

<template>
  <div class="min-h-screen flex text-gray-900">

    <!-- MAIN -->
    <main class="flex-1 p-6">
      <h1 class="text-3xl font-semibold mb-6">Kategorien</h1>

      <div class="space-y-4">

        <!-- MAIN CATEGORIES -->
        <div v-for="cat in categories" :key="cat.id" class="border border-gray-300 rounded-xl bg-white">
          <button @click="toggleCategory(cat)"
            class="w-full flex items-center justify-between px-4 py-3 text-left font-medium">
            {{ cat.name }}
            <ion-icon :name="cat.open ? 'chevron-up-outline' : 'chevron-down-outline'" />
          </button>

          <div v-if="cat.open" class="px-4 pb-4">

            <div v-if="cat.subcategories.length === 0" class="text-sm text-gray-400">
              Noch keine Unterkategorien.
            </div>

            <!-- SUBCATEGORIES -->
            <div v-for="sub in cat.subcategories" :key="sub.id"
              class="mt-3 border border-gray-200 rounded-lg bg-gray-50">
              <button @click="toggleSub(sub)" class="w-full flex items-center justify-between px-3 py-2 text-left">
                {{ sub.name }}
                <ion-icon :name="sub.open ? 'chevron-up-outline' : 'chevron-down-outline'" />
              </button>

              <div v-if="sub.open" class="px-5 pb-3 space-y-2">
                <div v-if="sub.entries.length === 0" class="text-sm text-gray-400">
                  Noch keine Beträge.
                </div>

                <div v-for="entry in sub.entries" :key="entry.id"
                  class="p-3 bg-white rounded-lg border border-gray-200 shadow-sm text-sm space-y-1">
                  <p><strong>{{ entry.name }}</strong> – {{ entry.amount }} €</p>
                  <p v-if="entry.date" class="text-gray-600">
                    {{ entry.date }}
                  </p>
                  <p v-if="entry.desc" class="text-gray-500">
                    {{ entry.desc }}
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <!-- FLOAT BUTTON -->
    <button @click="openModal"
      class="fixed bottom-6 right-6 w-14 h-14 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl">
      <ion-icon name="add-outline"></ion-icon>
    </button>

    <!-- MODAL -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50">
      <div class="bg-white w-11/12 max-w-md mx-auto mt-40 p-6 rounded-xl shadow-xl">
        <h2 class="text-xl font-semibold mb-4">
          Neuen Eintrag hinzufügen
        </h2>

        <select v-model="mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4">
          <option value="category">Kategorie</option>
          <option value="entry">Eintrag</option>
        </select>

        <!-- CATEGORY FORM -->
        <div v-if="mode === 'category'" class="space-y-3">
          <input v-model="catName" placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />

          <select v-model="catType" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="main">Hauptkategorie</option>
            <option value="sub">Unterkategorie</option>
          </select>

          <select v-if="catType === 'sub'" v-model="parentCategory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- ENTRY FORM -->
        <div v-if="mode === 'entry'" class="space-y-3">
          <input v-model="entryAmount" type="number" placeholder="Betrag" class="w-full px-3 py-2 border rounded-lg" />
          <input v-model="entryName" placeholder="Name" class="w-full px-3 py-2 border rounded-lg" />
          <input v-model="entryDate" type="date" class="w-full px-3 py-2 border rounded-lg" />
          <textarea v-model="entryDesc" placeholder="Beschreibung"
            class="w-full px-3 py-2 border rounded-lg"></textarea>

          <select v-model="entrySubCategory" class="w-full px-3 py-2 border rounded-lg">
            <option v-for="sub in allSubcategories" :key="sub.id" :value="sub.id">
              {{ sub.name }}
            </option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button @click="closeModal" class="px-4 py-2 border rounded-lg">
            Abbrechen
          </button>
          <button @click="saveData" class="px-4 py-2 bg-rose-500 text-white rounded-lg">
            Speichern
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
