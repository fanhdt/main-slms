<script setup lang="ts">
import AuthAside from '@/features/auth/components/AuthAside.vue'
import AuthCard from '@/features/auth/components/AuthCard.vue'

withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    /** Hide the logo/title header block entirely (some states render their own) */
    hideHeader?: boolean
  }>(),
  { hideHeader: false },
)
</script>

<template>
  <div class="flex min-h-screen w-full bg-white">
    <!-- Left — branding (60% desktop / 55% laptop), hidden below lg -->
    <AuthAside />

    <!-- Right — authentication -->
    <main class="flex flex-1 flex-col items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
      <!-- Compact branding shown on tablet/mobile only (aside is hidden below lg) -->
      <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
        <div class="flex items-center gap-3">
          <img src="/images/logo-lab.svg" alt="Logo Aplikasi" class="h-9 w-9 object-contain" />
          <span class="h-6 w-px bg-gray-200" aria-hidden="true" />
          <img src="/images/logo-upi.svg" alt="Logo UPI" class="w-24 object-contain" />
        </div>
        <!-- Tagline only on tablet (md+), keeps mobile minimal per spec -->
        <div class="hidden text-center md:block">
          <p class="text-lg font-bold text-gray-900">Smart Laboratory Management System</p>
          <p class="mt-1 max-w-sm text-sm text-gray-500">
            One platform to manage laboratories, assets, bookings, and academic services.
          </p>
        </div>
      </div>

      <AuthCard :title="title" :subtitle="subtitle" :hide-header="hideHeader">
        <slot />
      </AuthCard>
    </main>
  </div>
</template>
