<script setup>
import { ref } from "vue"

const mobileOpen = ref(false)

function closeMenu() {
    mobileOpen.value = false
}
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
        'fixed md:static top-0 left-0 h-screen w-72 bg-gray-100 border-r border-gray-200 flex flex-col z-50 transition-transform duration-300',
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
                    <a href="#" class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-gray-200/60">
                        <ion-icon name="eye" class="w-6 h-6"></ion-icon>
                        <span class="font-medium">Aktivitäten</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User -->
        <div class="mt-auto p-5">
            <a href="login.html" class="flex gap-3 items-center">
                <ion-icon name="person" class="w-6 h-6"></ion-icon>
                <span class="font-semibold">User</span>
            </a>
        </div>
    </aside>
</template>
