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
            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 424.53 430.47">
                <g>
                    <path fill="#80c17f"
                        d="M424.48,214.57l-22.04-1.14c-.86-140.05-148.02-233.76-275.47-169.08C5.98,105.76-12.64,274.19,90.49,361.99c121.11,103.1,304.39,18.42,311.94-137.16l22.04-.38c-6.37,148.65-154.47,247.06-294.46,189.22C-16.7,353.06-45.62,152.35,76.06,50.44c140.54-117.72,349.44-15.58,348.42,164.14h0Z" />
                    <g>
                        <path fill="#fffeff"
                            d="M424.48,214.57c-.02,3.27.14,6.61,0,9.88l-22.04.38c-7.55,155.59-190.83,240.26-311.94,137.16C-12.64,274.19,5.99,105.76,126.98,44.35c127.44-64.68,274.61,29.03,275.47,169.08l22.04,1.14h-.01Z" />
                        <path fill="#008d36"
                            d="M208.29,65.63c42.47-3.08,91.33,12.03,118.93,45.21l-22.42,26.22c-53.18-57.02-148.88-45.14-183.52,25.46l174.4,1.14-13.68,31.92-243.55-.38c2.81-4.58,11.03-28.2,13.68-30.4,3.43-2.84,21.8-.22,26.98-1.14,21.57-53.55,70.48-93.77,129.18-98.03h0Z" />
                        <path fill="#e50d36"
                            d="M115.96,251.8c23.54,84.1,133.26,104.01,189.6,39.52l22.42,27.74c-80.26,85.01-226.59,46.49-252.29-68.01,13.39.09,26.94.73,40.27.76h0Z" />
                        <path fill="#e50d36"
                            d="M115.96,251.8c-13.33-.03-26.89-.67-40.27-.76s-26.37.38-39.52-.38l14.82-32.3h221.13l-13.68,32.68c-47.47-.91-95.05.88-142.48.76h0Z" />
                    </g>
                </g>
            </svg>
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
    <div v-if="showNameModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center"
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
