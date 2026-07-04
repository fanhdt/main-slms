<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { useCartStore } from '@/features/booking/stores/useCartStore'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const slug = computed(() => route.params.slug as string)

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
  router.push({ name: 'booking-cart', params: { slug: slug.value } })
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
      :style="{ backgroundColor: lab?.branding?.primary_color ?? '#1a1a2e' }"
    >
      <div class="max-w-4xl mx-auto px-6 h-16 flex items-center gap-4">
        <button @click="router.back()" class="text-white/70 hover:text-white transition-colors">
          ← Kembali
        </button>
        <span class="text-white font-bold flex-1">Sewa Alat</span>
        <button @click="goToCart" class="relative text-white/90 hover:text-white transition-colors">
          🛒
          <span
            v-if="cartCount > 0"
            class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
          >
            {{ cartCount }}
          </span>
        </button>
      </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-8">
      <div v-if="isLoading" class="text-center py-12 text-gray-400">Memuat daftar alat...</div>
      <div v-else-if="!assets?.length" class="text-center py-12 text-gray-400">
        Belum ada alat yang tersedia untuk disewa.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div
          v-for="asset in assets"
          :key="asset.uuid"
          class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col"
        >
          <div
            class="aspect-video bg-gray-100 rounded-lg mb-3 flex items-center justify-center text-3xl"
          >
            {{ asset.image ? '' : '📷' }}
            <img
              v-if="asset.image"
              :src="asset.image"
              :alt="asset.name"
              class="w-full h-full object-cover rounded-lg"
            />
          </div>
          <p class="text-xs text-gray-400">{{ asset.category?.label }}</p>
          <h3 class="font-semibold text-gray-900">{{ asset.name }}</h3>
          <p class="text-sm text-gray-500 mb-2">{{ asset.brand }}</p>
          <p class="font-bold text-gray-900 mb-3">
            {{ formatPrice(asset.rental_price) }}
            <span class="text-xs font-normal text-gray-400">/ hari</span>
          </p>

          <button
            @click="toggleCart(asset)"
            class="mt-auto py-2 rounded-lg text-sm font-medium transition-colors"
            :class="
              cartStore.isInCart(slug, asset.uuid)
                ? 'bg-red-50 text-red-600 hover:bg-red-100'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            "
          >
            {{
              cartStore.isInCart(slug, asset.uuid)
                ? '− Hapus dari Keranjang'
                : '+ Tambah ke Keranjang'
            }}
          </button>
        </div>
      </div>
    </div>

    <!-- Floating checkout bar -->
    <div
      v-if="cartCount > 0"
      class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4"
    >
      <div class="max-w-4xl mx-auto flex items-center justify-between">
        <p class="text-sm text-gray-600">{{ cartCount }} alat dipilih</p>
        <button
          @click="goToCart"
          class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors"
        >
          Lihat Keranjang →
        </button>
      </div>
    </div>
  </div>
</template>
