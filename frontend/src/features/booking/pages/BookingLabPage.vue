<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import api from '@/lib/axios'

const route = useRoute()
const router = useRouter()
const slug = computed(() => route.params.slug as string)

const activeTab = ref<'services' | 'packages'>('packages')

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
  enabled: computed(() => !!lab.value),
})

const { data: packages } = useQuery({
  queryKey: ['booking-packages', slug],
  queryFn: async () => {
    const res = await api.get('/packages', {
      params: { lab_id: lab.value?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => !!lab.value),
})

function selectPackage(pkg: any) {
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: { type: 'package', id: pkg.uuid },
  })
}

function selectService(service: any) {
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: { type: 'service', id: service.uuid },
  })
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header
      class="sticky top-0 z-10 border-b"
      :style="{ backgroundColor: lab?.branding.primary_color ?? '#1a1a2e' }"
    >
      <div class="max-w-4xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="router.push('/booking')"
          class="text-white/70 hover:text-white transition-colors"
        >
          ← Kembali
        </button>
        <span class="text-white font-bold">{{ lab?.name }}</span>
      </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Pilih Layanan</h1>
      <p class="text-gray-500 mb-6">Pilih paket atau layanan satuan yang kamu inginkan.</p>

      <!-- Tabs -->
      <div class="flex gap-2 mb-6 border-b border-gray-200">
        <button
          @click="activeTab = 'packages'"
          class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
          :class="
            activeTab === 'packages'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          "
        >
          Paket
        </button>
        <button
          @click="activeTab = 'services'"
          class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
          :class="
            activeTab === 'services'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700'
          "
        >
          Layanan Satuan
        </button>
      </div>

      <!-- Packages -->
      <div v-if="activeTab === 'packages'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-if="!packages?.length" class="col-span-2 text-center py-12 text-gray-400">
          Belum ada paket tersedia.
        </div>
        <button
          v-for="pkg in packages"
          :key="pkg.uuid"
          @click="selectPackage(pkg)"
          class="text-left bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all"
        >
          <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
          <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ pkg.description }}</p>
          <div class="mt-3 flex items-center justify-between">
            <span class="font-bold text-lg text-gray-900">
              {{ formatPrice(pkg.final_price ?? pkg.price) }}
            </span>
            <span class="text-xs text-blue-600 font-medium">Pilih →</span>
          </div>
        </button>
      </div>

      <!-- Services -->
      <div v-if="activeTab === 'services'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-if="!services?.length" class="col-span-2 text-center py-12 text-gray-400">
          Belum ada layanan tersedia.
        </div>
        <button
          v-for="service in services"
          :key="service.uuid"
          @click="selectService(service)"
          class="text-left bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all"
        >
          <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ service.description }}</p>
          <div class="mt-3 flex items-center justify-between">
            <div>
              <span class="font-bold text-lg text-gray-900">
                {{ formatPrice(service.price) }}
              </span>
              <span class="text-xs text-gray-400 block">{{ service.pricing_type?.label }}</span>
            </div>
            <span class="text-xs text-blue-600 font-medium">Pilih →</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
