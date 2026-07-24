<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { useCartStore } from '@/features/booking/stores/useCartStore'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { ArrowLeft, ShoppingCart, Camera, Minus, Plus, PackageX } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const slug = computed(() => route.params.slug as string)
const { isStaffMode, withMode, goBack } = useBookingFlowMode()

const { data: lab } = useQuery({
  queryKey: ['booking-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

const { data: assets, isLoading } = useQuery({
  queryKey: ['rentable-assets', slug],
  queryFn: async () => {
    const res = await api.get('/assets', {
      params: { lab_id: lab.value?.id, is_rentable: 1, status: 'available' },
    })
    return res.data.data.data as {
      uuid: string
      name: string
      brand: string | null
      rental_price: string
      image: string | null
      quantity: number
      category: { value: string; label: string }
    }[]
  },
  enabled: computed(() => !!lab.value?.id),
})

// ============================================================
// Kategori & Filter
// ============================================================
const CATEGORY_LABELS: Record<string, string> = {
  all: 'Semua',
  camera: 'Kamera',
  lens: 'Lensa',
  lighting: 'Lighting',
  drone: 'Drone',
  tripod: 'Tripod',
  computer: 'Komputer',
  projector: 'Proyektor',
  audio: 'Audio',
  microphone: 'Mikrofon',
  printer: 'Printer',
  backdrop: 'Backdrop / Properti',
  costume: 'Kostum / Aksesoris',
  other: 'Lainnya',
}

const activeCategory = ref<string>('all')

// Hanya tampilkan tab kategori yang benar-benar ada isinya di lab ini
const availableCategories = computed(() => {
  const set = new Set((assets.value ?? []).map((a) => a.category.value))
  return ['all', ...Array.from(set)]
})

const filteredAssets = computed(() => {
  if (activeCategory.value === 'all') return assets.value ?? []
  return (assets.value ?? []).filter((a) => a.category.value === activeCategory.value)
})

function selectCategory(cat: string) {
  activeCategory.value = cat
}

// ============================================================
// Keranjang
// ============================================================
const cartCount = computed(() => cartStore.itemCount(slug.value))

function addToCart(asset: NonNullable<typeof assets.value>[number]) {
  cartStore.addItem(slug.value, {
    uuid: asset.uuid,
    name: asset.name,
    brand: asset.brand,
    rental_price: asset.rental_price,
    max_quantity: asset.quantity,
  })
  toast.success(`${asset.name} ditambahkan ke keranjang.`)
}

function removeFromCart(asset: { uuid: string; name: string }) {
  cartStore.removeItem(slug.value, asset.uuid)
  toast.info(`${asset.name} dihapus dari keranjang.`)
}

function increment(asset: { uuid: string; name: string }) {
  cartStore.incrementQuantity(slug.value, asset.uuid)
}

function decrement(asset: { uuid: string; name: string }) {
  const qty = cartStore.getQuantity(slug.value, asset.uuid)
  if (qty <= 1) {
    removeFromCart(asset)
    return
  }
  cartStore.decrementQuantity(slug.value, asset.uuid)
}

function goToCart() {
  router.push({ name: 'booking-cart', params: { slug: slug.value }, query: withMode() })
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
  <div class="min-h-screen bg-gray-50 pb-24">
    <header
      class="sticky top-0 z-10"
      :style="{
        backgroundColor: isStaffMode ? '#111827' : (lab?.branding?.primary_color ?? '#1a1a2e'),
      }"
    >
      <div class="max-w-4xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="goBack(slug)"
          class="text-white/70 hover:text-white transition-colors flex items-center gap-1.5"
        >
          <ArrowLeft class="size-4" />
          Kembali
        </button>
        <span class="text-white font-bold flex-1">Sewa Alat</span>
        <button @click="goToCart" class="relative text-white/90 hover:text-white transition-colors">
          <ShoppingCart class="size-5" />
          <span
            v-if="cartCount > 0"
            class="absolute -top-2 -right-2 w-4.5 h-4.5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-8">
      <!-- Loading -->
      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Skeleton v-for="i in 4" :key="i" class="h-72 w-full rounded-xl" />
      </div>

      <!-- Empty total (belum ada aset sama sekali) -->
      <div v-else-if="!assets?.length" class="text-center py-16">
        <PackageX class="size-9 mx-auto text-gray-300 mb-2" />
        <p class="text-gray-400 text-sm">Belum ada alat yang tersedia untuk disewa.</p>
      </div>

      <template v-else>
        <!-- Tab Kategori -->
        <div class="flex gap-2 overflow-x-auto pb-1 mb-5 -mx-1 px-1">
          <button
            v-for="cat in availableCategories"
            :key="cat"
            @click="selectCategory(cat)"
            class="px-3.5 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors shrink-0 border"
            :class="
              activeCategory === cat
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
            "
          >
            {{ CATEGORY_LABELS[cat] ?? cat }}
          </button>
        </div>

        <!-- Empty state per kategori -->
        <div
          v-if="filteredAssets.length === 0"
          class="text-center py-16 text-gray-400 text-sm"
        >
          Tidak ada alat di kategori "{{ CATEGORY_LABELS[activeCategory] ?? activeCategory }}".
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Card v-for="asset in filteredAssets" :key="asset.uuid" class="p-0 flex flex-col">
            <CardContent class="p-4 flex flex-col flex-1">
              <div
                class="aspect-video bg-gray-100 rounded-lg mb-3 flex items-center justify-center overflow-hidden"
              >
                <img
                  v-if="asset.image"
                  :src="asset.image"
                  :alt="asset.name"
                  class="w-full h-full object-cover"
                />
                <Camera v-else class="size-8 text-gray-300" />
              </div>
              <div class="flex items-center justify-between">
                <Badge variant="outline" class="border-0 bg-blue-50 text-blue-700 text-[11px]">
                  {{ asset.category?.label }}
                </Badge>
                <Badge variant="outline" class="border-0 bg-gray-100 text-gray-500 text-[11px]">
                  Stok: {{ asset.quantity }}
                </Badge>
              </div>
              <h3 class="font-semibold text-gray-900 mt-2">{{ asset.name }}</h3>
              <p class="text-sm text-gray-500 mb-2">{{ asset.brand }}</p>
              <p class="font-bold text-gray-900 mb-3">
                {{ formatPrice(asset.rental_price) }}
                <span class="text-xs font-normal text-gray-400">/ hari / unit</span>
              </p>

              <!-- Belum di keranjang -->
              <Button
                v-if="!cartStore.isInCart(slug, asset.uuid)"
                class="mt-auto w-full"
                @click="addToCart(asset)"
              >
                <Plus class="size-4" />
                Tambah ke Keranjang
              </Button>

              <!-- Sudah di keranjang: stepper quantity -->
              <div v-else class="mt-auto flex items-center justify-between gap-2">
                <Button
                  variant="outline"
                  size="icon-sm"
                  class="border-red-200 text-red-600 hover:bg-red-50"
                  @click="decrement(asset)"
                >
                  <Minus class="size-3.5" />
                </Button>
                <span class="flex-1 text-center text-sm font-semibold text-gray-900">
                  {{ cartStore.getQuantity(slug, asset.uuid) }} unit
                </span>
                <Button
                  variant="outline"
                  size="icon-sm"
                  :disabled="
                    asset.quantity <= 1 || cartStore.getQuantity(slug, asset.uuid) >= asset.quantity
                  "
                  @click="increment(asset)"
                >
                  <Plus class="size-3.5" />
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </template>
    </div>

    <!-- Floating checkout bar -->
    <div
      v-if="cartCount > 0"
      class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg"
    >
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="border-0 bg-blue-50 text-blue-700">
            {{ cartCount }} jenis alat &middot; {{ cartStore.totalQuantity(slug) }} unit
          </Badge>
        </div>
        <Button @click="goToCart">
          Lihat Keranjang
          <ShoppingCart class="size-4" />
        </Button>
      </div>
    </div>
  </div>
</template>