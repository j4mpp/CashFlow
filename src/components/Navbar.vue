<template>
    <!-- Sidebar: collapsed on < md, expanded on >= md -->
        <aside
            class="transition-all duration-200 w-20 md:w-72 bg-gray-100 border-r border-gray-200 flex flex-col h-screen sticky top-0">

            <!-- Brand -->
            <div class="h-20 pt-6 px-4 md:px-5 flex items-center justify-center sm:justify-start">
                <div class="flex items-center gap-3">
                    <ion-icon name="cash-outline" class="w-8 h-8 text-teal-400"></ion-icon>
                    <span class="hidden md:inline text-2xl font-semibold">CashFlow</span>
                </div>
            </div>

            <!-- Nav -->
            <nav class="px-2 md:px-4 pt-10 flex-1">
                <ul class="space-y-3">
                    <li>
                        <a class="flex items-center justify-center md:justify-start gap-3 rounded-xl px-2 md:px-3 py-2 bg-white border border-gray-200 shadow-sm hover:bg-gray-50"
                            href="#">
                            <ion-icon name="easel" class="w-6 h-6"></ion-icon>
                            <span class="hidden md:inline font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center justify-center md:justify-start gap-3 rounded-xl px-2 md:px-3 py-2 hover:bg-gray-200/60"
                            href="/categories.html">
                            <ion-icon name="file-tray-stacked-outline" class="w-6 h-6"></ion-icon>
                            <span class="hidden md:inline font-medium">Kategorien</span>
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center justify-center md:justify-start gap-3 rounded-xl px-2 md:px-3 py-2 hover:bg-gray-200/60"
                            href="#">
                            <ion-icon name="podium-outline" class="w-6 h-6"></ion-icon>
                            <span class="hidden md:inline font-medium">Analysen</span>
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center justify-center md:justify-start gap-3 rounded-xl px-2 md:px-3 py-2 hover:bg-gray-200/60"
                            href="#">
                            <ion-icon name="eye-outline" class="w-6 h-6"></ion-icon>
                            <span class="hidden md:inline font-medium">Aktivitäten</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User -->
            <div class="mt-auto p-3 md:p-5">
                <a href="login.html" class="flex justify-center md:justify-start gap-3 items-center">
                    <ion-icon name="person" class="w-6 h-6"></ion-icon>
                    <span class="hidden md:block font-semibold">User</span>
                </a>
            </div>

        </aside>
</template>