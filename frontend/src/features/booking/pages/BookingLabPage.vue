<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import api from '@/lib/axios'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  ArrowLeft,
  ChevronRight,
  Building2,
  Camera,
  Palette,
  Package,
  Wrench,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const slug = computed(() => route.params.slug as string)
const { isStaffMode, withMode, goBack } = useBookingFlowMode()

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

const flowOptions = [
  {
    key: 'lab_rental' as const,
    title: 'Pinjam Lab',
    description: 'Pinjam ruangan/studio sesuai jadwal. Gratis untuk keperluan akademik.',
    icon: Building2,
    tone: 'text-blue-600 bg-blue-50',
  },
  {
    key: 'asset_rental' as const,
    title: 'Sewa Alat',
    description: 'Sewa kamera, properti, dan peralatan lainnya.',
    icon: Camera,
    tone: 'text-purple-600 bg-purple-50',
  },
  {
    key: 'service' as const,
    title: 'Jasa & Paket',
    description: 'Fotografi, editing, cetak foto, dan paket bundling (prewedding, dll).',
    icon: Palette,
    tone: 'text-green-600 bg-green-50',
  },
]

function selectFlow(key: 'lab_rental' | 'asset_rental' | 'service') {
  if (key === 'lab_rental') return goToLabRental()
  if (key === 'asset_rental') return goToAssetRental()
  activeFlow.value = 'service'
}

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
      <div class="max-w-4xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="activeFlow ? (activeFlow = null) : goBack(slug)"
          class="text-white/70 hover:text-white transition-colors flex items-center gap-1.5"
        >
          <ArrowLeft class="size-4" />
          Kembali
        </button>
        <span class="text-white font-bold">{{ lab?.name ?? 'Booking' }}</span>
        <Badge
          v-if="isStaffMode"
          variant="outline"
          class="ml-auto border-white/20 text-white/70 bg-transparent"
        >
          Mode Admin
        </Badge>
      </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-10">
      <!-- Layar pilih flow -->
      <div v-if="!activeFlow">
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Mau booking apa?</h1>
          <p class="text-gray-500 mt-1 text-sm">Pilih salah satu jenis layanan di bawah ini.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <button
            v-for="option in flowOptions"
            :key="option.key"
            @click="selectFlow(option.key)"
            class="group text-left"
          >
            <Card
              class="p-0 h-full hover:shadow-lg hover:-translate-y-1 transition-all duration-200"
            >
              <CardContent class="p-6 flex flex-col items-start gap-4">
                <div
                  class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                  :class="option.tone"
                >
                  <component :is="option.icon" class="size-7" />
                </div>
                <div>
                  <h3
                    class="font-semibold text-gray-900 text-lg group-hover:text-blue-600 transition-colors"
                  >
                    {{ option.title }}
                  </h3>
                  <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                    {{ option.description }}
                  </p>
                </div>
                <span
                  class="mt-auto text-sm font-medium text-blue-600 flex items-center gap-1 group-hover:underline"
                >
                  Pilih
                  <ChevronRight class="size-3.5" />
                </span>
              </CardContent>
            </Card>
          </button>
        </div>
      </div>

      <!-- Layar Jasa & Paket -->
      <div v-else-if="activeFlow === 'service'">
        <div class="flex gap-2 mb-6">
          <button
            @click="serviceTab = 'packages'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="
              serviceTab === 'packages'
                ? 'bg-blue-600 text-white'
                : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
            "
          >
            <Package class="size-3.5 inline mr-1.5 -mt-0.5" />
            Paket
          </button>
          <button
            @click="serviceTab = 'services'"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="
              serviceTab === 'services'
                ? 'bg-blue-600 text-white'
                : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
            "
          >
            <Wrench class="size-3.5 inline mr-1.5 -mt-0.5" />
            Layanan Satuan
          </button>
        </div>

        <!-- Paket -->
        <div v-if="serviceTab === 'packages'" class="grid gap-4">
          <button
            v-for="pkg in packages"
            :key="pkg.uuid"
            @click="selectPackage(pkg)"
            class="text-left"
          >
            <Card class="p-0 overflow-hidden hover:border-blue-400 hover:shadow-md transition-all">
              <div
                class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden"
              >
                <img
                  v-if="pkg.image"
                  :src="pkg.image"
                  :alt="pkg.name"
                  class="w-full h-full object-cover"
                />
                <Package v-else class="size-8 text-gray-300" />
              </div>
              <CardContent class="p-5">
                <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ pkg.description }}</p>
                <p class="font-bold text-gray-900 mt-2">
                  {{ formatPrice(pkg.price - pkg.discount) }}
                </p>
              </CardContent>
            </Card>
          </button>
          <p v-if="!packages?.length" class="text-sm text-gray-400 text-center py-8">
            Belum ada paket tersedia.
          </p>
        </div>

        <!-- Layanan satuan -->
        <div v-else class="grid gap-4">
          <button
            v-for="service in services"
            :key="service.uuid"
            @click="selectService(service)"
            class="text-left"
          >
            <Card class="p-0 overflow-hidden hover:border-blue-400 hover:shadow-md transition-all">
              <div
                class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden"
              >
                <img
                  v-if="service.image"
                  :src="service.image"
                  :alt="service.name"
                  class="w-full h-full object-cover"
                />
                <Wrench v-else class="size-8 text-gray-300" />
              </div>
              <CardContent class="p-5">
                <h3 class="font-semibold text-gray-900">{{ service.name }}</h3>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ service.description }}</p>

                <p v-if="service.price !== null" class="font-bold text-gray-900 mt-2">
                  {{ formatPrice(service.price) }}
                  <span class="text-sm font-normal text-gray-400"
                    >/ {{ service.pricing_type?.label }}</span
                  >
                </p>
                <p v-else class="text-sm font-medium text-blue-600 mt-2">
                  Harga sesuai pilihan editing yang dipilih
                </p>
              </CardContent>
            </Card>
          </button>
          <p v-if="!services?.length" class="text-sm text-gray-400 text-center py-8">
            Belum ada layanan tersedia.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
