<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import api from '@/lib/axios'
import { useCartStore } from '@/features/booking/stores/useCartStore'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, ShoppingCart, Trash2, ArrowRight, Minus, Plus } from 'lucide-vue-next'

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

const items = computed(() => cartStore.getItems(slug.value))

const totalPerDay = computed(() =>
  items.value.reduce((sum, item) => sum + Number(item.rental_price) * item.quantity, 0),
)

function removeItem(uuid: string, name: string) {
  cartStore.removeItem(slug.value, uuid)
  toast.info(`${name} dihapus dari keranjang.`)
}

function increment(uuid: string) {
  cartStore.incrementQuantity(slug.value, uuid)
}

function decrement(uuid: string, name: string) {
  const item = items.value.find((i) => i.uuid === uuid)
  if (item && item.quantity <= 1) {
    removeItem(uuid, name)
    return
  }
  cartStore.decrementQuantity(slug.value, uuid)
}

function checkout() {
  if (items.value.length === 0) {
    toast.error('Keranjang masih kosong.')
    return
  }
  router.push({
    name: 'booking-form',
    params: { slug: slug.value },
    query: withMode({ bookingType: 'asset_rental' }),
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
      :style="{
        backgroundColor: isStaffMode ? '#111827' : (lab?.branding?.primary_color ?? '#1a1a2e'),
      }"
    >
      <div class="max-w-2xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="goBack(slug)"
          class="text-white/70 hover:text-white transition-colors flex items-center gap-1.5"
        >
          <ArrowLeft class="size-4" />
          Kembali
        </button>
        <span class="text-white font-bold">Keranjang Sewa</span>
      </div>
    </header>

    <div class="max-w-2xl mx-auto px-6 py-8">
      <!-- Empty -->
      <div v-if="items.length === 0" class="text-center py-20">
        <ShoppingCart class="size-10 mx-auto text-gray-300 mb-3" />
        <p class="text-gray-400 mb-4">Keranjang kamu masih kosong.</p>
        <Button
          @click="router.push({ name: 'asset-catalog', params: { slug }, query: withMode() })"
        >
          Pilih Alat untuk Disewa
        </Button>
      </div>

      <div v-else class="space-y-4">
        <Card class="p-0">
          <CardContent class="p-0 divide-y divide-gray-100">
            <div
              v-for="item in items"
              :key="item.uuid"
              class="p-4 flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <p class="font-medium text-gray-900 truncate">{{ item.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ item.brand }}</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">
                  {{ formatPrice(Number(item.rental_price)) }}
                  <span class="text-xs font-normal text-gray-400">/ hari / unit</span>
                </p>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <div class="flex items-center border border-gray-200 rounded-lg">
                  <button
                    class="p-1.5 text-gray-500 hover:text-red-600 transition-colors"
                    @click="decrement(item.uuid, item.name)"
                  >
                    <Minus class="size-3.5" />
                  </button>
                  <span class="w-8 text-center text-sm font-medium text-gray-900">
                    {{ item.quantity }}
                  </span>
                  <button
                    class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                    :disabled="item.max_quantity <= 1 || item.quantity >= item.max_quantity"
                    @click="increment(item.uuid)"
                  >
                    <Plus class="size-3.5" />
                  </button>
                </div>
                <button
                  title="Hapus dari keranjang"
                  @click="removeItem(item.uuid, item.name)"
                  class="p-2 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                >
                  <Trash2 class="size-4" />
                </button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="p-0">
          <CardContent class="p-5">
            <div class="flex items-center justify-between mb-1">
              <span class="text-gray-600 text-sm">Total per hari</span>
              <span class="font-bold text-gray-900">{{ formatPrice(totalPerDay) }}</span>
            </div>
            <p class="text-xs text-gray-400">
              {{ cartStore.totalQuantity(slug) }} unit alat &middot; total akhir dihitung sesuai
              lama peminjaman di langkah berikutnya.
            </p>
          </CardContent>
        </Card>

        <Button
          class="w-full"
          size="lg"
          :style="{ backgroundColor: lab?.branding?.secondary_color ?? '#e94560' }"
          @click="checkout"
        >
          Checkout — Tentukan Lama Sewa
          <ArrowRight class="size-4" />
        </Button>
      </div>
    </div>
  </div>
</template>
