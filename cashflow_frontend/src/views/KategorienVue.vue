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

const editing = ref(null)
// editing = { type: "cat"|"sub", id: number|string, originalName: string }

function startEditCategory(cat) {
  editing.value = { type: "cat", id: cat.id, originalName: cat.name }
  cat._editName = cat.name
}

function startEditSub(sub) {
  editing.value = { type: "sub", id: sub.id, originalName: sub.name }
  sub._editName = sub.name
}

function cancelEdit(catOrSub) {
  if (catOrSub?._editName !== undefined) {
    catOrSub._editName = undefined
  }
  editing.value = null
}

async function saveEditCategory(cat) {
  const userid = localStorage.getItem("userid")
  const newName = (cat._editName || "").trim()
  if (!newName) return alert("Bitte Namen eingeben.")

  await fetch("http://localhost:8000/categories/update.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: cat.id, name: newName })
  })

  editing.value = null
  await fetchCategories()
}

async function deleteCategory(cat) {
  const userid = localStorage.getItem("userid")
  if (!confirm("Kategorie wirklich löschen? (inkl. Unterkategorien)")) return

  await fetch("http://localhost:8000/categories/delete.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: cat.id })
  })

  editing.value = null
  await fetchCategories()
}

async function saveEditSub(sub) {
  const userid = localStorage.getItem("userid")
  const newName = (sub._editName || "").trim()
  if (!newName) return alert("Bitte Namen eingeben.")

  await fetch("http://localhost:8000/subcategories/update.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: sub.id, name: newName })
  })

  editing.value = null
  await fetchCategories()
}

async function deleteSub(sub) {
  const userid = localStorage.getItem("userid")
  if (!confirm("Unterkategorie wirklich löschen?")) return

  await fetch("http://localhost:8000/subcategories/delete.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: sub.id })
  })

  editing.value = null
  await fetchCategories()
}


/* =========================
   FETCH FROM BACKEND
========================= */

async function fetchCategories() {
  try {
    const userid = localStorage.getItem("userid")

    if (!userid) {
      categories.value = mockCategories
      return
    }

    const catRes = await fetch(
      `http://localhost:8000/categories/get.php?userid=${userid}`
    )

    const subRes = await fetch(
      `http://localhost:8000/subcategories/get.php?userid=${userid}`
    )

    const cats = await catRes.json()
    const subs = await subRes.json()

    // Kategorien Struktur aufbauen
    const structured = cats.map(cat => ({
      ...cat,
      open: false,
      subcategories: subs
        .filter(sub => sub.categoryid === cat.id)
        .map(sub => ({
          ...sub,
          open: false,
          entries: []
        }))
    }))

    categories.value = structured

  } catch (err) {
    console.log("Backend Fehler → Mock geladen")
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

async function saveCategory() {
  if (!catName.value) return alert("Bitte Name eingeben.")

  const userid = localStorage.getItem("userid")
  if (!userid) return alert("Nicht eingeloggt.")

  try {
    // 🔹 Hauptkategorie speichern
    if (catType.value === "main") {
      await fetch("http://localhost:8000/categories/create.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          userid,
          name: catName.value
        })
      })
    }

    // 🔹 Unterkategorie speichern
    if (catType.value === "sub") {
      if (!parentCategory.value)
        return alert("Übergeordnete Kategorie wählen")

      await fetch("http://localhost:8000/subcategories/create.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          userid,
          categoryid: parentCategory.value,
          name: catName.value
        })
      })
    }

    closeModal()

    // 🔥 Danach neu laden aus DB
    await fetchCategories()

  } catch (err) {
    console.log("Fehler beim Speichern:", err)
  }
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
            <!-- 🖊️ EDIT ICON -->
            <ion-icon name="pencil" class="mr-3" @click.stop="startEditCategory(cat)"></ion-icon>

            <!-- Name oder Input -->
            <template v-if="editing?.type === 'cat' && editing?.id === cat.id">
              <input v-model="cat._editName" class="flex-1 bg-transparent outline-none" />
              <div class="flex items-center gap-3 ml-3">
                <button class="text-sm text-red-600" @click.stop="deleteCategory(cat)">
                  Delete
                </button>
                <button class="text-sm text-emerald-600" @click.stop="saveEditCategory(cat)">
                  Save
                </button>
              </div>
            </template>

            <template v-else>
              <span class="flex-1">{{ cat.name }}</span>
            </template>

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
                <!-- 🖊️ STIFT für SUB -->
                <ion-icon name="pencil" class="mr-3" @click.stop="startEditSub(sub)"></ion-icon>

                <!-- Name oder Input -->
                <template v-if="editing?.type === 'sub' && editing?.id === sub.id">
                  <input v-model="sub._editName" class="flex-1 bg-transparent outline-none" />
                  <div class="flex items-center gap-3 ml-3">
                    <button class="text-sm text-red-600" @click.stop="deleteSub(sub)">
                      Delete
                    </button>
                    <button class="text-sm text-emerald-600" @click.stop="saveEditSub(sub)">
                      Save
                    </button>
                  </div>
                </template>

                <template v-else>
                  <span class="flex-1">{{ sub.name }}</span>
                </template>

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
