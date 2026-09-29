<script setup lang="ts">
import AuthCarousel from './AuthCarousel.vue'
</script>

<template>
  <aside class="relative hidden lg:flex lg:w-[55%] xl:w-[60%] shrink-0 overflow-hidden">
    <!-- Background -->
    <img
      src="/images/auth-bg.svg"
      alt=""
      class="absolute inset-0 h-full w-full object-cover"
      aria-hidden="true"
    />
    <div
      class="absolute inset-0 bg-linear-to-b from-black/45 via-black/20 to-black/55"
      aria-hidden="true"
    />

    <div class="relative z-10 flex w-full flex-col p-10 xl:p-14">
      <!-- Header: brand logos -->
      <div class="flex items-center gap-4">
        <img
          src="/images/logo-lab.svg"
          alt="Logo Aplikasi"
          class="h-10 w-10 object-contain shrink-0"
        />
        <span class="h-8 w-px bg-white/25" aria-hidden="true" />
        <img src="/images/logo-upi.svg" alt="Logo UPI" class="w-32 object-contain shrink-0" />
      </div>

      <!-- Hero — pinned near the top, right under the logos -->
      <div class="mt-10 max-w-4xl xl:mt-14">
        <h1 class="text-4xl font-bold leading-[1.1] text-white md:text-5xl xl:text-6xl">
          Smart Laboratory Management System
        </h1>
        <p class="mt-5 text-base leading-relaxed text-white/70">
          One platform to manage laboratories, assets, bookings, and academic services efficiently.
        </p>
      </div>

      <!-- Spacer pushes carousel to the bottom -->
      <div class="flex-1" />

      <!-- Carousel -->
      <AuthCarousel />
    </div>
  </aside>
</template>
