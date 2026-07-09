<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import api from '@/lib/axios'

const route = useRoute()
const router = useRouter()
const slug = computed(() => route.params.slug as string)
const { isStaffMode, withMode, goBack } = useBookingFlowMode()

// Layar utama: pilih salah satu dari 3 flow. null = belum pilih (tampilkan 3 kartu).
const activeFlow = ref<'lab_rental' | 'asset_rental' | 'service' | null>(null)
const serviceTab = ref<'services' | 'packages'>('packages')

const { data: lab } = useQuery({
  queryKey: ['booking-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

const { data: services } = useQuery({
  queryKey: ['booking-services', slug],
  queryFn: async () => {
    const res = await api.get('/services', {
      params: { lab_id: lab.value?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => activeFlow.value === 'service' && !!lab.value),
})

const { data: packages } = useQuery({
  queryKey: ['booking-packages', slug],
  queryFn: async () => {
    const res = await api.get('/packages', {
      params: { lab_id: lab.value?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => activeFlow.value === 'service' && !!lab.value),
})

function goToLabRental() {
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: withMode({ bookingType: 'lab_rental' }),
  })
}

function goToAssetRental() {
  router.push({
    name: 'asset-catalog',
    params: { slug: slug.value },
    query: withMode(),
  })
}

function selectPackage(pkg: any) {
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: withMode({ bookingType: 'service', type: 'package', id: pkg.uuid }),
  })
}

function selectService(service: any) {
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: withMode({ bookingType: 'service', type: 'service', id: service.uuid }),
  })
}

function formatPrice(price: string | number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <header
      class="sticky top-0 z-10"
      :style="{
        backgroundColor: isStaffMode ? '#111827' : (lab?.branding?.primary_color ?? '#1a1a2e'),
      }"
    >
      <div class="max-w-3xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="activeFlow ? (activeFlow = null) : goBack(slug)"
          class="text-white/70 hover:text-white transition-colors"
        >
          ← Kembali
        </button>
          <span class="text-white font-bold">{{ lab?.name ?? 'Booking' }}</span>
       <span
         v-if="isStaffMode"
         class="ml-auto text-xs font-medium text-white/70 border border-white/20 rounded-full px-2.5 py-0.5"
      >
         Mode Admin
       </span>
      </div>
    </header>

    <div class="max-w-3xl mx-auto px-6 py-8">
      <!-- Layar pilih flow -->
      <div v-if="!activeFlow" class="space-y-4">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Mau booking apa?</h2>

        <button
          @click="goToLabRental"
          class="w-full text-left bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md p-5 transition-all flex items-center gap-4"
        >
          <div
            class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-2xl shrink-0"
          >
            🏢
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">Pinjam Lab</h3>
            <p class="text-sm text-gray-500">
              Pinjam ruangan/studio sesuai jadwal. Gratis untuk keperluan akademik.
            </p>
          </div>
          <span class="text-gray-300">›</span>
        </button>

        <button
          @click="goToAssetRental"
          class="w-full text-left bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md p-5 transition-all flex items-center gap-4"
        >
          <div
            class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-2xl shrink-0"
          >
            📷
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">Sewa Alat</h3>
            <p class="text-sm text-gray-500">Sewa kamera, properti, dan peralatan lainnya.</p>
          </div>
          <span class="text-gray-300">›</span>
        </button>

        <button
          @click="activeFlow = 'service'"
          class="w-full text-left bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md p-5 transition-all flex items-center gap-4"
        >
          <div
            class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-2xl shrink-0"
          >
            🎨
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-900">Jasa & Paket</h3>
            <p class="text-sm text-gray-500">
              Fotografi, editing, cetak foto, dan paket bundling (prewedding, dll).
            </p>
          </div>
          <span class="text-gray-300">›</span>
        </button>
      </div>

      <!-- Layar Jasa & Paket (tab lama) -->
      <div v-else-if="activeFlow === 'service'">
        <div class="flex gap-2 mb-6">
          <button
            @click="serviceTab = 'packages'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="
              serviceTab === 'packages'
                ? 'bg-blue-600 text-white'
                : 'bg-white border border-gray-200 text-gray-600'
            "
          >
            Paket
          </button>
          <button
            @click="serviceTab = 'services'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="
              serviceTab === 'services'
                ? 'bg-blue-600 text-white'
                : 'bg-white border border-gray-200 text-gray-600'
            "
          >
            Layanan Satuan
          </button>
        </div>

        <div v-if="serviceTab === 'packages'" class="grid gap-4">
          <button
            v-for="pkg in packages"
            :key="pkg.uuid"
            @click="selectPackage(pkg)"
            class="text-left bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md p-5 transition-all"
          >
            <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ pkg.description }}</p>
            <p class="font-bold text-gray-900 mt-2">{{ formatPrice(pkg.price - pkg.discount) }}</p>
          </button>
          <p v-if="!packages?.length" class="text-sm text-gray-400 text-center py-8">
            Belum ada paket tersedia.
          </p>
        </div>

        <div v-else class="grid gap-4">
          <button
            v-for="service in services"
            :key="service.uuid"
            @click="selectService(service)"
            class="text-left bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md p-5 transition-all"
          >
            <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ service.description }}</p>
            <p class="font-bold text-gray-900 mt-2">
              {{ formatPrice(service.price) }}
              <span class="text-sm font-normal text-gray-400"
                >/ {{ service.pricing_type?.label }}</span
              >
            </p>
          </button>
          <p v-if="!services?.length" class="text-sm text-gray-400 text-center py-8">
            Belum ada layanan tersedia.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
