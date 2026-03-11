<script setup>
import Navbar from "@/components/Navbar.vue"
import { ref, computed, onMounted, watch } from "vue"


/* =========================
   STATE
========================= */

const categories = ref([])
const loading = ref(true)
const banks = ref([])

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

    const [catRes, subRes, txRes, banksRes] = await Promise.all([
      fetch(`http://localhost:8000/categories/get.php?userid=${userid}`),
      fetch(`http://localhost:8000/subcategories/get.php?userid=${userid}`),
      fetch(`http://localhost:8000/transactions/get.php?userid=${userid}`),
      fetch(`http://localhost:8000/banks/get.php?userid=${userid}`)
    ])

    const [cats, subs, txs, fetchedBanks] = await Promise.all([
      catRes.json(),
      subRes.json(),
      txRes.json(),
      banksRes.json()
    ])

    banks.value = Array.isArray(fetchedBanks) ? fetchedBanks : []

    const txBySubId = (Array.isArray(txs) ? txs : []).reduce((acc, t) => {
      const key = String(t.subcategoryid)
      if (!acc[key]) acc[key] = []
      acc[key].push(t)
      return acc
    }, {})

    // Kategorien Struktur aufbauen
    const structured = cats.map(cat => ({
      ...cat,
      open: false,
      subcategories: subs
        .filter(sub => sub.categoryid === cat.id)
        .map(sub => ({
          ...sub,
          open: false,
          entries: (txBySubId[String(sub.id)] || []).slice().sort((a, b) => {
            const ad = a.created_at || a.date || a.timestamp || 0
            const bd = b.created_at || b.date || b.timestamp || 0
            return new Date(bd) - new Date(ad)
          })
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
   ENTRY MODAL (NEW TRANSACTION)
========================= */

const showEntryModal = ref(false)
const creatingEntry = ref(false)
const activeSubcategoryId = ref("")

const entryName = ref("")
const entryDescription = ref("")
const entryAmount = ref("")
const entryBankId = ref("")
const entryDate = ref(new Date().toISOString().slice(0, 10)) // YYYY-MM-DD

function openEntryModal(sub) {
  activeSubcategoryId.value = String(sub.id)
  entryName.value = ""
  entryDescription.value = ""
  entryAmount.value = ""
  entryBankId.value = ""
  entryDate.value = new Date().toISOString().slice(0, 10)
  showEntryModal.value = true
}

function closeEntryModal() {
  showEntryModal.value = false
  activeSubcategoryId.value = ""
}

async function saveEntry() {
  const userid = localStorage.getItem("userid")
  if (!userid) return alert("Nicht eingeloggt.")

  const name = entryName.value.trim()
  const description = entryDescription.value.trim()
  const amount = Number(entryAmount.value)
  const bankid = entryBankId.value
  const subcategoryid = activeSubcategoryId.value
  const date = entryDate.value

  if (!subcategoryid) return alert("Unterkategorie fehlt.")
  if (!name) return alert("Bitte Name eingeben.")
  if (!bankid) return alert("Bitte Konto wählen.")
  if (!Number.isFinite(amount)) return alert("Bitte gültigen Betrag eingeben.")
  if (!date) return alert("Bitte Datum wählen.")

  creatingEntry.value = true
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

    // Bank-Balance aktualisieren: neuer Betrag = alter Betrag + Transaktionsbetrag
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

    closeEntryModal()
    loading.value = true
    await fetchCategories()
  } catch (err) {
    console.log("Fehler beim Speichern:", err)
    alert("Fehler beim Speichern.")
  } finally {
    creatingEntry.value = false
  }
}

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

            <!-- NACH dem v-for der Subcategories, aber noch innerhalb von div v-if="cat.open" -->

            <button @click.stop="openModal(); catType = 'sub'; parentCategory = cat.id"
              class="mt-3 w-full rounded-xl border border-gray-200 bg-white/70 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 transition">
              <span class="inline-flex items-center gap-2">
                <ion-icon name="add-outline"></ion-icon>
                Unterkategorie hinzufügen
              </span>
            </button>

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

              <div v-if="sub.open" class="px-4 pb-4">
                <div class="pt-3">
                  <button @click.stop="openEntryModal(sub)"
                    class="w-full rounded-xl border border-gray-200 bg-white/70 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 transition">
                    <span class="inline-flex items-center gap-2">
                      <ion-icon name="add-outline"></ion-icon>
                      Eintrag hinzufügen
                    </span>
                  </button>
                </div>


                <div class="mt-2 space-y-2">
                  <div v-for="e in sub.entries" :key="e.id"
                    class="flex items-start justify-between rounded-xl bg-white/70 border border-gray-200 px-4 py-3">
                    <div class="min-w-0">
                      <div class="font-medium text-gray-900 truncate">
                        {{ e.name }}
                      </div>
                      <div v-if="e.description" class="text-xs text-gray-500 mt-1">
                        {{ e.description }}
                      </div>
                      <div v-if="e.date" class="text-xs text-gray-400 mt-1">
                        {{ new Date(e.date).toLocaleDateString("de-DE") }}
                      </div>
                    </div>

                    <div :class="[
                      'ml-4 shrink-0 font-semibold',
                      Number(e.amount) < 0 ? 'text-red-500' : 'text-green-600'
                    ]">
                      {{ Number(e.amount) < 0 ? "-" : "+" }} {{ Math.abs(Number(e.amount)).toLocaleString("de-DE") }} €
                        </div>
                    </div>
                  </div>
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

            <input v-model="catName" placeholder="Name"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

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

        <!-- ENTRY MODAL -->
        <div v-if="showEntryModal"
          class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
          <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">
            <span class="h-20 pb-3 flex items-center gap-3">
              <ion-icon name="add-circle" class="w-8 h-8 text-teal-400"></ion-icon>
              <h2 class="text-2xl font-semibold">
                Neuer Eintrag
              </h2>
            </span>

            <label class="block text-sm mb-1">Name</label>
            <input v-model="entryName" placeholder="z.B. Billa Einkauf"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Beschreibung (optional)</label>
            <input v-model="entryDescription" placeholder="z.B. Wochenendeinkauf"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Betrag (€)</label>
            <input v-model="entryAmount" type="number" step="0.01" placeholder="-45.80"
              class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

            <label class="block text-sm mb-1">Konto</label>
            <select v-model="entryBankId" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
              <option value="">Bitte wählen</option>
              <option v-for="b in banks" :key="b.id" :value="b.id">
                {{ b.name }}
              </option>
            </select>

            <label class="block text-sm mb-1">Datum</label>
            <input v-model="entryDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

            <div class="flex justify-end gap-3 pt-2">
              <button @click="closeEntryModal" class="px-4 py-2 border rounded-xl" :disabled="creatingEntry">
                Abbrechen
              </button>

              <button @click="saveEntry"
                class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl disabled:opacity-50"
                :disabled="creatingEntry">
                Speichern
              </button>
            </div>
          </div>
        </div>

    </main>
  </div>
</template>
