<script setup lang="ts">
import { computed } from 'vue'
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
      category: { label: string }
    }[]
  },
  enabled: computed(() => !!lab.value?.id),
})

const cartCount = computed(() => cartStore.itemCount(slug.value))

function toggleCart(asset: NonNullable<typeof assets.value>[number]) {
  if (cartStore.isInCart(slug.value, asset.uuid)) {
    cartStore.removeItem(slug.value, asset.uuid)
    toast.info(`${asset.name} dihapus dari keranjang.`)
  } else {
    cartStore.addItem(slug.value, {
      uuid: asset.uuid,
      name: asset.name,
      brand: asset.brand,
      rental_price: asset.rental_price,
    })
    toast.success(`${asset.name} ditambahkan ke keranjang.`)
  }
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

      <!-- Empty -->
      <div v-else-if="!assets?.length" class="text-center py-16">
        <PackageX class="size-9 mx-auto text-gray-300 mb-2" />
        <p class="text-gray-400 text-sm">Belum ada alat yang tersedia untuk disewa.</p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Card v-for="asset in assets" :key="asset.uuid" class="p-0 flex flex-col">
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
            <p class="text-xs text-gray-400">{{ asset.category?.label }}</p>
            <h3 class="font-semibold text-gray-900">{{ asset.name }}</h3>
            <p class="text-sm text-gray-500 mb-2">{{ asset.brand }}</p>
            <p class="font-bold text-gray-900 mb-3">
              {{ formatPrice(asset.rental_price) }}
              <span class="text-xs font-normal text-gray-400">/ hari</span>
            </p>

            <Button
              class="mt-auto w-full"
              :variant="cartStore.isInCart(slug, asset.uuid) ? 'outline' : 'default'"
              :class="
                cartStore.isInCart(slug, asset.uuid)
                  ? 'border-red-200 text-red-600 hover:bg-red-50'
                  : ''
              "
              @click="toggleCart(asset)"
            >
              <Minus v-if="cartStore.isInCart(slug, asset.uuid)" class="size-4" />
              <Plus v-else class="size-4" />
              {{
                cartStore.isInCart(slug, asset.uuid)
                  ? 'Hapus dari Keranjang'
                  : 'Tambah ke Keranjang'
              }}
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Floating checkout bar -->
    <div
      v-if="cartCount > 0"
      class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg"
    >
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="border-0 bg-blue-50 text-blue-700">
            {{ cartCount }} alat dipilih
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
