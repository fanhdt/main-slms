<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center">
      <h1 class="text-8xl font-bold text-gray-200">404</h1>
      <h2 class="text-2xl font-bold text-gray-900 mt-4">Halaman tidak ditemukan</h2>
      <p class="text-gray-500 mt-2">Halaman yang kamu cari tidak ada atau sudah dipindahkan.</p>
      <button
        @click="router.push('/')"
        class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg text-sm transition-colors"
      >
        Kembali ke Dashboard
      </button>
    </div>
  </div>
</template>
