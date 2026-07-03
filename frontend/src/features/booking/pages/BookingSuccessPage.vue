<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import QRCode from 'qrcode'
import api from '@/lib/axios'

const route = useRoute()
const router = useRouter()

const slug = computed(() => route.params.slug as string)
const code = computed(() => route.params.code as string)
const qrDataUrl = ref('')

const { data: lab } = useQuery({
  queryKey: ['success-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

onMounted(async () => {
  // Generate QR Code dari kode booking
  qrDataUrl.value = await QRCode.toDataURL(code.value, {
    width: 200,
    margin: 2,
    color: {
      dark: '#1a1a2e',
      light: '#ffffff',
    },
  })
})

function goToBookings() {
  router.push('/my-bookings')
}

function goHome() {
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Success Header -->
        <div
          class="p-8 text-center text-white"
          :style="{ backgroundColor: lab?.branding.primary_color ?? '#1a1a2e' }"
        >
          <div class="text-5xl mb-4">✓</div>
          <h1 class="text-2xl font-bold">Booking Berhasil!</h1>
          <p class="text-white/70 mt-1">Pesanan kamu di {{ lab?.name }} telah dikonfirmasi</p>
        </div>

        <!-- QR Code + Booking Code -->
        <div class="p-8 text-center">
          <!-- QR Code -->
          <div class="flex justify-center mb-4">
            <div class="bg-white p-3 rounded-xl border border-gray-200 inline-block">
              <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code Booking" class="w-48 h-48" />
              <div v-else class="w-48 h-48 bg-gray-100 animate-pulse rounded" />
            </div>
          </div>

          <p class="text-sm text-gray-500 mb-2">Kode Booking</p>
          <div class="bg-gray-50 rounded-xl p-3 mb-4 inline-block">
            <p class="text-xl font-mono font-bold text-gray-900 tracking-wider">
              {{ code }}
            </p>
          </div>

          <p class="text-sm text-gray-500 mb-6">
            Tunjukkan QR Code ini ke admin saat datang ke lokasi untuk check-in otomatis.
          </p>

          <div class="flex flex-col gap-3">
            <button
              @click="goToBookings"
              class="w-full py-3 rounded-xl font-semibold text-white transition-colors"
              :style="{ backgroundColor: lab?.branding.secondary_color ?? '#e94560' }"
            >
              Lihat Semua Booking
            </button>
            <button
              @click="router.push(`/booking/${slug}`)"
              class="w-full py-3 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200"
            >
              Booking Lagi
            </button>
            <button
              @click="goHome"
              class="w-full py-3 rounded-xl font-medium text-gray-500 hover:bg-gray-50 transition-colors"
            >
              Kembali ke Beranda
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
