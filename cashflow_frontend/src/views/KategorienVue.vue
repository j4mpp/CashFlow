<script setup>
import Navbar from "@/components/Navbar.vue"
import { ref, computed, onMounted, watch } from "vue"

const categories = ref([])
const loading = ref(true)
const banks = ref([])

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

const mockCategories = [
  {
    id: crypto.randomUUID(),
    name: "Haushalt",
    open: false,
    subcategories: [
      { id: crypto.randomUUID(), name: "Lebensmittel", open: false, entries: [] },
      { id: crypto.randomUUID(), name: "Strom", open: false, entries: [] },
      { id: crypto.randomUUID(), name: "Wasser", open: false, entries: [] }
    ]
  }
]

/* =========================
   EDIT MODAL (Kategorie / Unterkategorie)
========================= */

const showEditCatModal = ref(false)
const editCatModal = ref({ type: "", id: null, name: "" })

function openEditCatModal(type, item) {
  editCatModal.value = { type, id: item.id, name: item.name }
  showEditCatModal.value = true
}

function closeEditCatModal() {
  showEditCatModal.value = false
}

async function saveEditCatModal() {
  const userid = getValidUserId()
  const { type, id, name } = editCatModal.value
  const trimmed = name.trim()
  if (!trimmed) return alert("Bitte Namen eingeben.")

  if (type === "cat") {
    await fetchJson("/cashflow_api/categories/update.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userid, id: Number(id), name: trimmed })
    })
  } else {
    await fetchJson("/cashflow_api/subcategories/update.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userid, id: Number(id), name: trimmed })
    })
  }

  closeEditCatModal()
  await fetchCategories()
}

async function deleteFromEditCatModal() {
  const userid = getValidUserId()
  const { type, id } = editCatModal.value

  const label = type === "cat" ? "Kategorie wirklich löschen? (inkl. Unterkategorien)" : "Unterkategorie wirklich löschen?"
  if (!confirm(label)) return

  if (type === "cat") {
    await fetchJson("/cashflow_api/categories/delete.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userid, id: Number(id) })
    })
  } else {
    await fetchJson("/cashflow_api/subcategories/delete.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userid, id: Number(id) })
    })
  }

  closeEditCatModal()
  await fetchCategories()
}

/* =========================
   FETCH FROM BACKEND
========================= */

async function fetchCategories() {
  try {
    const userid = getValidUserId()
    const safeUserId = encodeURIComponent(userid)

    const [cats, subs, txs, fetchedBanks] = await Promise.all([
      fetchJson(`/cashflow_api/categories/get.php?userid=${safeUserId}`),
      fetchJson(`/cashflow_api/subcategories/get.php?userid=${safeUserId}`),
      fetchJson(`/cashflow_api/transactions/get.php?userid=${safeUserId}`),
      fetchJson(`/cashflow_api/banks/get.php?userid=${safeUserId}`)
    ])

    banks.value = Array.isArray(fetchedBanks) ? fetchedBanks : []

    const txBySubId = (Array.isArray(txs) ? txs : []).reduce((acc, t) => {
      const key = String(t.subcategoryid)
      if (!acc[key]) acc[key] = []
      acc[key].push(t)
      return acc
    }, {})

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
const entryDate = ref(new Date().toISOString().slice(0, 10))

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
  const userid = getValidUserId()
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
    await fetchJson("/cashflow_api/transactions/create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ userid, subcategoryid: Number(subcategoryid), name, description, amount, bankid: Number(bankid), date })
    })

    const bank = banks.value.find(b => String(b.id) === String(bankid))
    if (bank) {
      const current = Number(bank.amount)
      const nextAmount = (Number.isFinite(current) ? current : 0) + amount
      await fetchJson("/cashflow_api/banks/update.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userid, id: Number(bank.id), name: bank.name, iban: bank.iban, amount: nextAmount, bankfirma: bank.bankfirma })
      })
    }

    closeEntryModal()
    loading.value = true
    await fetchCategories()
  } catch (err) {
    alert("Fehler beim Speichern.")
  } finally {
    creatingEntry.value = false
  }
}

/* =========================
   MODAL STATE (neue Kategorie)
========================= */

const showModal = ref(false)
const catName = ref("")
const catType = ref("main")
const parentCategory = ref("")

function openModalMain() {
  catType.value = "main"
  catName.value = ""
  showModal.value = true
}

function openModalSub(cat) {
  catType.value = "sub"
  catName.value = ""
  parentCategory.value = cat.id
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  catName.value = ""
  catType.value = "main"
  parentCategory.value = ""
}

/* =========================
   TOGGLE
========================= */

function toggleCategory(cat) { cat.open = !cat.open }
function toggleSub(sub) { sub.open = !sub.open }

/* =========================
   EDIT ENTRY
========================= */

const showEditEntryModal = ref(false)
const editingEntry = ref(null)

function openEditEntryModal(e) {
  editingEntry.value = { ...e }
  showEditEntryModal.value = true
}

function closeEditEntryModal() {
  showEditEntryModal.value = false
  editingEntry.value = null
}

async function saveEditEntry() {
  const userid = getValidUserId()
  const e = editingEntry.value
  await fetchJson("/cashflow_api/transactions/update.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: Number(e.id), subcategoryid: Number(e.subcategoryid), name: e.name, description: e.description, amount: Number(e.amount), bankid: Number(e.bankid), date: e.date })
  })
  closeEditEntryModal()
  loading.value = true
  await fetchCategories()
}

async function deleteEntry(e) {
  const userid = getValidUserId()
  if (!confirm("Eintrag wirklich löschen?")) return
  await fetchJson("/cashflow_api/transactions/delete.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ userid, id: Number(e.id) })
  })
  closeEditEntryModal()
  loading.value = true
  await fetchCategories()
}

/* =========================
   SAVE CATEGORY
========================= */

async function saveCategory() {
  if (!catName.value) return alert("Bitte Name eingeben.")
  const userid = localStorage.getItem("userid")
  if (!userid || !/^\d+$/.test(userid)) return alert("Nicht eingeloggt.")

  try {
    if (catType.value === "main") {
      await fetchJson("/cashflow_api/categories/create.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userid, name: catName.value })
      })
    }
    if (catType.value === "sub") {
      if (!parentCategory.value) return alert("Übergeordnete Kategorie wählen")
      await fetchJson("/cashflow_api/subcategories/create.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userid, categoryid: Number(parentCategory.value), name: catName.value })
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

      <div class="absolute inset-0 -z-10">
        <div class="absolute left-0 top-0 w-[500px] h-[500px] bg-teal-300/40 rounded-full blur-3xl"></div>
        <div class="absolute right-0 bottom-0 w-[400px] h-[400px] bg-emerald-400/40 rounded-full blur-3xl"></div>
      </div>

      <h1 class="text-3xl font-semibold mb-6">Kategorien</h1>

      <div v-if="loading" class="text-center py-10 text-gray-500">
        Kategorien werden geladen...
      </div>

      <div v-else class="space-y-4">

        <div v-for="cat in categories" :key="cat.id"
          class="rounded-2xl bg-white/60 backdrop-blur-xl border border-white/40 shadow-xl">

          <button @click="toggleCategory(cat)"
            class="w-full flex items-center justify-between px-5 py-4 text-left font-medium text-lg">

            <!-- Stift öffnet jetzt Modal -->
            <ion-icon name="pencil" class="mr-3 shrink-0 cursor-pointer" @click.stop="openEditCatModal('cat', cat)" />

            <span class="flex-1">{{ cat.name }}</span>

            <ion-icon :name="cat.open ? 'chevron-up-outline' : 'chevron-down-outline'" />
          </button>

          <div v-if="cat.open" class="px-6 pb-6">

            <button @click.stop="openModalSub(cat)"
              class="mt-3 w-full rounded-xl border border-gray-200 bg-white/70 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 transition">
              <span class="inline-flex items-center gap-2">
                <ion-icon name="add-outline"></ion-icon>
                Unterkategorie hinzufügen
              </span>
            </button>

            <div v-for="sub in cat.subcategories" :key="sub.id"
              class="mt-3 rounded-xl bg-white/80 backdrop-blur-md border border-gray-200">

              <button @click="toggleSub(sub)" class="w-full flex items-center justify-between px-4 py-3 text-left">

                <!-- Stift öffnet jetzt Modal -->
                <ion-icon name="pencil" class="mr-3 shrink-0 cursor-pointer"
                  @click.stop="openEditCatModal('sub', sub)" />

                <span class="flex-1">{{ sub.name }}</span>

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

                    <ion-icon name="pencil" class="mr-3 mt-1 cursor-pointer shrink-0" @click="openEditEntryModal(e)" />

                    <div class="min-w-0 flex-1">
                      <div class="font-medium text-gray-900 truncate">{{ e.name }}</div>
                      <div v-if="e.description" class="text-xs text-gray-500 mt-1">{{ e.description }}</div>
                      <div v-if="e.date" class="text-xs text-gray-400 mt-1">
                        {{ new Date(e.date).toLocaleDateString("de-DE") }}
                      </div>
                    </div>

                    <div
                      :class="['ml-4 shrink-0 font-semibold', Number(e.amount) < 0 ? 'text-red-500' : 'text-green-600']">
                      {{ Number(e.amount) < 0 ? "-" : "+" }} {{ Math.abs(Number(e.amount)).toLocaleString("de-DE") }} €
                        </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- FLOAT BUTTON -->
          <button @click="openModalMain"
            class="fixed bottom-6 right-6 w-14 h-14 bg-teal-400 hover:bg-teal-500 text-white rounded-full shadow-lg flex items-center justify-center text-3xl transition hover:scale-110">
            <ion-icon name="add-outline"></ion-icon>
          </button>

          <!-- MODAL: Neue Kategorie -->
          <div v-if="showModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">
              <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="duplicate" class="w-8 h-8 text-teal-400"></ion-icon>
                <h1 class="text-2xl font-semibold">
                  {{ catType === 'main' ? 'Neue Hauptkategorie' : 'Neue Unterkategorie' }}
                </h1>
              </span>

              <input v-model="catName" placeholder="Name"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

              <div class="flex justify-end gap-3 pt-4">
                <button @click="closeModal" class="px-4 py-2 border rounded-xl">Abbrechen</button>
                <button @click="saveCategory"
                  class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl">Speichern</button>
              </div>
            </div>
          </div>

          <!-- MODAL: Kategorie / Unterkategorie bearbeiten -->
          <div v-if="showEditCatModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">

              <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="pencil" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">
                  {{ editCatModal.type === 'cat' ? 'Kategorie bearbeiten' : 'Unterkategorie bearbeiten' }}
                </h2>
              </span>

              <label class="block text-sm mb-1">Name</label>
              <input v-model="editCatModal.name" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

              <div class="flex justify-between pt-2">
                <button @click="deleteFromEditCatModal"
                  class="px-4 py-2 text-red-500 border border-red-200 rounded-xl hover:bg-red-50">
                  Löschen
                </button>
                <div class="flex gap-3">
                  <button @click="closeEditCatModal" class="px-4 py-2 border rounded-xl">Abbrechen</button>
                  <button @click="saveEditCatModal"
                    class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl">Speichern</button>
                </div>
              </div>
            </div>
          </div>

          <!-- ENTRY MODAL -->
          <div v-if="showEntryModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">
              <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="add-circle" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">Neuer Eintrag</h2>
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
                <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>

              <label class="block text-sm mb-1">Datum</label>
              <input v-model="entryDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

              <div class="flex justify-end gap-3 pt-2">
                <button @click="closeEntryModal" class="px-4 py-2 border rounded-xl"
                  :disabled="creatingEntry">Abbrechen</button>
                <button @click="saveEntry"
                  class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl disabled:opacity-50"
                  :disabled="creatingEntry">Speichern</button>
              </div>
            </div>
          </div>

          <!-- EDIT ENTRY MODAL -->
          <div v-if="showEditEntryModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">
              <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="pencil" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">Eintrag bearbeiten</h2>
              </span>

              <label class="block text-sm mb-1">Name</label>
              <input v-model="editingEntry.name" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

              <label class="block text-sm mb-1">Beschreibung (optional)</label>
              <input v-model="editingEntry.description"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

              <label class="block text-sm mb-1">Betrag (€)</label>
              <input v-model="editingEntry.amount" type="number" step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4" />

              <label class="block text-sm mb-1">Konto</label>
              <select v-model="editingEntry.bankid" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-4">
                <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>

              <label class="block text-sm mb-1">Datum</label>
              <input v-model="editingEntry.date" type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6" />

              <div class="flex justify-between pt-2">
                <button @click="deleteEntry(editingEntry)"
                  class="px-4 py-2 text-red-500 border border-red-200 rounded-xl hover:bg-red-50">Löschen</button>
                <div class="flex gap-3">
                  <button @click="closeEditEntryModal" class="px-4 py-2 border rounded-xl">Abbrechen</button>
                  <button @click="saveEditEntry"
                    class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl">Speichern</button>
                </div>
              </div>
            </div>
          </div>

        </div>
    </main>
  </div>
</template>