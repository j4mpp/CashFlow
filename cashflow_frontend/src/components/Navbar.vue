<script setup>
import { ref, onMounted } from "vue"
import { useRouter } from "vue-router"

const mobileOpen = ref(false)
const username = ref(null)
const router = useRouter()
const showUserMenu = ref(false)
const fileInput = ref(null)
const showNameModal = ref(false)
const nameInput = ref("")
const savingName = ref(false)

function triggerFileInput() {
    fileInput.value.click()
}

function handleExcelImport(event) {
    const file = event.target.files[0]
    if (!file) return
    console.log("Importierte Datei:", file.name)
    // später die Verarbeitung einbauen
}

function closeMenu() {
    mobileOpen.value = false
}

async function fetchJson(url, options = {}) {
    const res = await fetch(url, {
        credentials: "include",
        ...options
    })

    let data = null
    try {
        data = await res.json()
    } catch {
        data = null
    }

    if (!res.ok || data?.error) {
        throw new Error(data?.error || "Request fehlgeschlagen")
    }

    return data
}

function openNameModal() {
    showUserMenu.value = false
    showNameModal.value = true
    nameInput.value = username.value ?? ""
}

function closeNameModal() {
    showNameModal.value = false
    savingName.value = false
}

async function saveNameChange() {
    const newName = String(nameInput.value ?? "").trim()
    if (!newName) return alert("Bitte einen gültigen Namen eingeben.")

    savingName.value = true
    try {
        const data = await fetchJson("/cashflow_api/user/update_name.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ name: newName })
        })

        const updatedName = data?.name ?? newName
        localStorage.setItem("username", updatedName)
        username.value = updatedName

        closeNameModal()
    } catch (err) {
        console.error("Fehler beim Namen ändern:", err)
        alert("Fehler beim Namen ändern.")
    } finally {
        savingName.value = false
    }
}

function logout() {
    showUserMenu.value = false
    localStorage.removeItem("userid")
    localStorage.removeItem("username")
    username.value = null
    router.push("/login")
}

onMounted(() => {
    username.value = localStorage.getItem("username")
})
</script>

<template>
    <!-- ================= MOBILE TOP BAR ================= -->
    <div class="md:hidden flex items-center justify-between p-4 bg-white border-b border-gray-200">

        <button @click="mobileOpen = true">
            <ion-icon name="menu-outline" class="w-7 h-7"></ion-icon>
        </button>
    </div>

    <!-- ================= MOBILE OVERLAY ================= -->
    <div v-if="mobileOpen" class="fixed inset-0 bg-black/40 z-40 md:hidden" @click="closeMenu"></div>

    <!-- ================= SIDEBAR ================= -->
    <aside :class="[
        'fixed md:fixed top-0 left-0 h-screen w-72 bg-white/60 backdrop-blur-xl border-r border-gray-200 flex flex-col z-50 transition-transform duration-300',
        mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
    ]">
        <!-- Brand -->
        <div class="h-20 pt-6 px-5 flex items-center gap-3">
            <ion-icon name="cash-outline" class="w-8 h-8 text-teal-400"></ion-icon>
            <span class="text-2xl font-semibold">CashFlow</span>
        </div>

        <!-- Nav -->
        <nav class="px-4 pt-10 flex-1">
            <ul class="space-y-3">
                <li>
                    <router-link to="/" @click="closeMenu"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-gray-200/60">
                        <ion-icon name="easel" class="w-6 h-6"></ion-icon>
                        <span class="font-medium">Dashboard</span>
                    </router-link>
                </li>

                <li>
                    <router-link to="/kategorien" @click="closeMenu"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-gray-200/60">
                        <ion-icon name="file-tray-stacked" class="w-6 h-6"></ion-icon>
                        <span class="font-medium">Kategorien</span>
                    </router-link>
                </li>

                <li>
                    <router-link to="/analysen" @click="closeMenu"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-gray-200/60">
                        <ion-icon name="podium" class="w-6 h-6"></ion-icon>
                        <span class="font-medium">Analysen</span>
                    </router-link>
                </li>

                <li>
                    <router-link to="/activity" @click="closeMenu"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-gray-200/60">
                        <ion-icon name="eye" class="w-6 h-6"></ion-icon>
                        <span class="font-medium">Aktivitäten</span>
                    </router-link>
                </li>
            </ul>
        </nav>

        <!-- User -->
        <div class="mt-auto p-5 relative">
            <!-- Trigger -->
            <div v-if="username" class="flex gap-3 items-center cursor-pointer" @click="showUserMenu = !showUserMenu">
                <ion-icon name="person" class="w-6 h-6"></ion-icon>
                <span class="font-semibold">{{ username }}</span>
            </div>
            <router-link v-else to="/login" class="flex gap-3 items-center">
                <ion-icon name="person" class="w-6 h-6"></ion-icon>
                <span class="font-semibold">User</span>
            </router-link>

            <!-- Popup -->
            <div v-if="showUserMenu"
                class="absolute bottom-16 left-4 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">
                <button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-100 text-left"
                    @click="openNameModal">
                    <ion-icon name="person-outline" class="w-5 h-5"></ion-icon>
                    <span>Name ändern</span>
                </button>

                <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden"
                    @change="handleExcelImport" />

                <button @click="triggerFileInput"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-100 text-left">
                    <ion-icon name="cloud-upload-outline" class="w-5 h-5"></ion-icon>
                    <span>Excel importieren</span>
                </button>
                <button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-100 text-left">
                    <ion-icon name="cloud-download-outline" class="w-5 h-5"></ion-icon>
                    <span>Excel exportieren</span>
                </button>
                <button @click="logout"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-100 text-left text-red-500">
                    <ion-icon name="log-out-outline" class="w-5 h-5"></ion-icon>
                    <span>Abmelden</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- NAME CHANGE MODAL -->
    <div v-if="showNameModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center"
        @click.self="closeNameModal">
        <div class="w-11/12 max-w-md p-6 rounded-2xl bg-white backdrop-blur-xl border border-white/40 shadow-xl">
            <span class="h-20 pb-3 flex items-center gap-3">
                <ion-icon name="person" class="w-8 h-8 text-teal-400"></ion-icon>
                <h2 class="text-2xl font-semibold">Name ändern</h2>
            </span>

            <label class="block text-sm mb-1">Neuer Name</label>
            <input v-model="nameInput" class="w-full px-3 py-2 border border-gray-300 rounded-xl mb-6"
                :disabled="savingName" />

            <div class="flex justify-end gap-3 pt-2">
                <button @click="closeNameModal" class="px-4 py-2 border rounded-xl" :disabled="savingName">
                    Abbrechen
                </button>
                <button @click="saveNameChange"
                    class="px-4 py-2 bg-teal-400 hover:bg-teal-500 text-white rounded-xl disabled:opacity-50"
                    :disabled="savingName">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</template>
