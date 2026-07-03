<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()

const slug = computed(() => route.params.slug as string)
const itemType = computed(() => route.query.type as string)
const itemId = computed(() => route.query.id as string)

const form = ref({
  date: '',
  startTime: '09:00',
  duration: 1, // jam
  notes: '',
})

const { data: lab } = useQuery({
  queryKey: ['booking-form-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

const { data: item } = useQuery({
  queryKey: ['booking-item', itemType, itemId],
  queryFn: async () => {
    const endpoint = itemType.value === 'package' ? '/packages' : '/services'
    const res = await api.get(`${endpoint}/${itemId.value}`)
    return res.data.data
  },
  enabled: computed(() => !!itemId.value),
})

const itemPrice = computed(() => {
  if (!item.value) return 0
  return Number(item.value.final_price ?? item.value.price)
})

const totalPrice = computed(() => {
  if (itemType.value === 'service') {
    return itemPrice.value * form.value.duration
  }
  return itemPrice.value
})

const startDateTime = computed(() => {
  if (!form.value.date) return null
  return `${form.value.date}T${form.value.startTime}:00`
})

const endDateTime = computed(() => {
  if (!startDateTime.value) return null
  const start = new Date(startDateTime.value)
  start.setHours(start.getHours() + form.value.duration)

  // Format manual jadi YYYY-MM-DDTHH:mm:ss agar konsisten dengan startDateTime
  const pad = (n: number) => String(n).padStart(2, '0')
  const year = start.getFullYear()
  const month = pad(start.getMonth() + 1)
  const day = pad(start.getDate())
  const hours = pad(start.getHours())
  const minutes = pad(start.getMinutes())

  return `${year}-${month}-${day}T${hours}:${minutes}:00`
})

const { mutate: submitBooking, isPending } = useMutation({
  mutationFn: async () => {
    const payload = {
      lab_uuid: lab.value?.uuid,
      start_time: startDateTime.value,
      end_time: endDateTime.value,
      notes: form.value.notes,
      items: [
        itemType.value === 'package'
          ? { package_uuid: item.value?.uuid, quantity: 1 }
          : { service_uuid: item.value?.uuid, quantity: form.value.duration },
      ],
    }
    return api.post('/bookings', payload)
  },
  onSuccess: (response) => {
    const code = response.data.data.booking_code
    toast.success('Booking berhasil dibuat!')
    router.push({
      name: 'booking-success',
      params: { slug: slug.value, code },
    })
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal membuat booking.')
  },
})

function formatPrice(price: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price)
}

function handleSubmit() {
  if (!form.value.date) {
    toast.error('Pilih tanggal booking terlebih dahulu.')
    return
  }
  submitBooking()
}

// Tanggal minimal besok
const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header
      class="sticky top-0 z-10"
      :style="{ backgroundColor: lab?.branding.primary_color ?? '#1a1a2e' }"
    >
      <div class="max-w-2xl mx-auto px-6 h-16 flex items-center gap-4">
        <button @click="router.back()" class="text-white/70 hover:text-white transition-colors">
          ← Kembali
        </button>
        <span class="text-white font-bold">Konfirmasi Booking</span>
      </div>
    </header>

    <div class="max-w-2xl mx-auto px-6 py-8">
      <!-- Item Summary -->
      <div v-if="item" class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">
          {{ itemType === 'package' ? 'Paket Dipilih' : 'Layanan Dipilih' }}
        </p>
        <h3 class="font-semibold text-gray-900 text-lg">{{ item.name }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ item.description }}</p>
        <p class="font-bold text-xl text-gray-900 mt-3">
          {{ formatPrice(itemPrice) }}
          <span v-if="itemType === 'service'" class="text-sm font-normal text-gray-400">
            / {{ item.pricing_type?.label }}
          </span>
        </p>
      </div>

      <!-- Form -->
      <form
        @submit.prevent="handleSubmit"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-5"
      >
        <h3 class="font-semibold text-gray-900">Pilih Jadwal</h3>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Tanggal</label>
          <input
            v-model="form.date"
            type="date"
            :min="minDate"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Jam Mulai</label>
            <input
              v-model="form.startTime"
              type="time"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Durasi (jam)</label>
            <select
              v-model="form.duration"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="1">1 jam</option>
              <option :value="2">2 jam</option>
              <option :value="3">3 jam</option>
              <option :value="4">4 jam</option>
            </select>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Catatan (opsional)</label>
          <textarea
            v-model="form.notes"
            rows="3"
            placeholder="Tulis permintaan khusus..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Total -->
        <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
          <span class="text-gray-600">Total Pembayaran</span>
          <span class="text-2xl font-bold text-gray-900">
            {{ formatPrice(totalPrice) }}
          </span>
        </div>

        <button
          type="submit"
          :disabled="isPending"
          class="w-full py-3 rounded-xl font-semibold text-white transition-colors disabled:opacity-50"
          :style="{ backgroundColor: lab?.branding.secondary_color ?? '#e94560' }"
        >
          {{ isPending ? 'Memproses...' : 'Konfirmasi Booking' }}
        </button>
      </form>
    </div>
  </div>
</template>
