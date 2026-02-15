<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import Navbar from "@/components/Navbar.vue"

const route = useRoute()

const showNavbar = computed(() => {
  return route.path !== "/login"
})
</script>

<template>
  <div class="h-screen flex overflow-hidden relative">

    <!-- Background -->
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-emerald-50 via-white to-emerald-100">
      <div
        class="absolute top-[-120px] left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-emerald-300/30 rounded-full blur-3xl">
      </div>
      <div class="absolute bottom-[-120px] right-[-120px] w-[520px] h-[380px] bg-emerald-400/20 rounded-full blur-3xl">
      </div>
    </div>

    <!-- Navbar -->
    <Navbar v-if="showNavbar" />

    <!-- Content -->
    <div :class="[
      'flex-1 h-screen overflow-y-auto',
      showNavbar ? 'md:ml-72' : ''
    ]">
      <router-view />
    </div>

  </div>
</template>
