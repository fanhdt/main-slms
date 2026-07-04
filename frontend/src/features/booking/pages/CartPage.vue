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

const items = computed(() => cartStore.getItems(slug.value))

const totalPerDay = computed(() =>
  items.value.reduce((sum, item) => sum + Number(item.rental_price), 0),
)

function removeItem(uuid: string) {
  cartStore.removeItem(slug.value, uuid)
  toast.info('Alat dihapus dari keranjang.')
}

function checkout() {
  if (items.value.length === 0) {
    toast.error('Keranjang masih kosong.')
    return
  }
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: { bookingType: 'asset_rental' },
  })
}

function formatPrice(price: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <header
      class="sticky top-0 z-10"
      :style="{ backgroundColor: lab?.branding?.primary_color ?? '#1a1a2e' }"
    >
      <div class="max-w-2xl mx-auto px-6 h-16 flex items-center gap-4">
        <button @click="router.back()" class="text-white/70 hover:text-white transition-colors">
          ← Kembali
        </button>
        <span class="text-white font-bold">Keranjang Sewa</span>
      </div>
    </header>

    <div class="max-w-2xl mx-auto px-6 py-8">
      <div v-if="items.length === 0" class="text-center py-16">
        <p class="text-gray-400 mb-4">Keranjang kamu masih kosong.</p>
        <button
          @click="router.push({ name: 'asset-catalog', params: { slug } })"
          class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
        >
          Pilih Alat untuk Disewa
        </button>
      </div>

      <div v-else class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
          <div v-for="item in items" :key="item.uuid" class="p-4 flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-900">{{ item.name }}</p>
              <p class="text-xs text-gray-400">{{ item.brand }}</p>
              <p class="text-sm font-semibold text-gray-900 mt-1">
                {{ formatPrice(Number(item.rental_price)) }}
                <span class="text-xs font-normal text-gray-400">/ hari</span>
              </p>
            </div>
            <button @click="removeItem(item.uuid)" class="text-red-500 hover:underline text-sm">
              Hapus
            </button>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-1">
            <span class="text-gray-600 text-sm">Total per hari</span>
            <span class="font-bold text-gray-900">{{ formatPrice(totalPerDay) }}</span>
          </div>
          <p class="text-xs text-gray-400">
            Total akhir dihitung sesuai lama peminjaman di langkah berikutnya.
          </p>
        </div>

        <button
          @click="checkout"
          class="w-full py-3 rounded-xl font-semibold text-white transition-colors"
          :style="{ backgroundColor: lab?.branding?.secondary_color ?? '#e94560' }"
        >
          Checkout — Tentukan Lama Sewa →
        </button>
      </div>
    </div>
  </div>
</template>
