<template>
  <div class="h-screen flex overflow-hidden relative">

    <!-- ================= GLOBAL BACKGROUND ================= -->
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-emerald-50 via-white to-emerald-100">
      <div
        class="absolute top-[-120px] left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-emerald-300/30 rounded-full blur-3xl">
      </div>
      <div class="absolute bottom-[-120px] right-[-120px] w-[520px] h-[380px] bg-emerald-400/20 rounded-full blur-3xl">
      </div>
    </div>

    <!-- ================= SIDEBAR ================= -->
    <Navbar />

    <!-- ================= SCROLL AREA ================= -->
    <div class="flex-1 ml-0 md:ml-72 h-screen overflow-y-auto">
      <router-view />
    </div>

  </div>
</template>
