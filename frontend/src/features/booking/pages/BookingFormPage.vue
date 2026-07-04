<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useCartStore } from '@/features/booking/stores/useCartStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const slug = computed(() => route.params.slug as string)

// 'lab_rental' | 'asset_rental' | 'service' — default 'service' untuk kompatibilitas link lama
const bookingType = computed(() => (route.query.bookingType as string) || 'service')

const itemType = computed(() => route.query.type as string) // dipakai kalau bookingType === 'service'
const itemId = computed(() => route.query.id as string)

const notes = ref('')
const schedule = ref<{ date: string; start: string; end: string; durationHours: number } | null>(
  null,
)

// --- Khusus Pinjam Lab ---
const purpose = ref<'academic' | 'organization' | 'public'>('public')
const nim = ref('')

// --- Khusus Sewa Alat: item sudah dipilih lewat halaman Keranjang ---
const cartItems = computed(() => cartStore.getItems(slug.value))
const rentalStartDate = ref('')
const rentalEndDate = ref('')

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
  enabled: computed(() => bookingType.value === 'service' && !!itemId.value),
})

const needsStudentVerification = computed(() => purpose.value !== 'public')
const currentUserNim = computed(() => authStore.user?.nim ?? null)

const itemPrice = computed(() => {
  if (!item.value) return 0
  return Number(item.value.final_price ?? item.value.price)
})

const labRentalRatePerHour = computed(() => {
  if (!lab.value?.lab_rental_rates) return 0
  if (purpose.value === 'academic') return 0
  return purpose.value === 'organization'
    ? Number(lab.value.lab_rental_rates.student_price_per_hour)
    : Number(lab.value.lab_rental_rates.public_price_per_hour)
})

const rentalDays = computed(() => {
  if (!rentalStartDate.value || !rentalEndDate.value) return 0
  const start = new Date(rentalStartDate.value)
  const end = new Date(rentalEndDate.value)
  const diff = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
  return Math.max(1, diff + 1)
})

const assetRentalTotal = computed(
  () => cartItems.value.reduce((sum, a) => sum + Number(a.rental_price), 0) * rentalDays.value,
)

const totalPrice = computed(() => {
  if (bookingType.value === 'lab_rental') {
    return labRentalRatePerHour.value * (schedule.value?.durationHours ?? 0)
  }
  if (bookingType.value === 'asset_rental') {
    return assetRentalTotal.value
  }
  // service
  if (itemType.value === 'service') {
    return itemPrice.value * (schedule.value?.durationHours ?? 1)
  }
  return itemPrice.value
})

const minRentalEndDate = computed(() => rentalStartDate.value || minDateValueFallback())
function minDateValueFallback() {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
}

function handleScheduleConfirm(payload: {
  date: string
  start: string
  end: string
  durationHours: number
}) {
  schedule.value = payload
}

const startDateTime = computed(() => {
  if (bookingType.value === 'asset_rental') {
    return rentalStartDate.value ? `${rentalStartDate.value}T08:00:00` : null
  }
  return schedule.value ? `${schedule.value.date}T${schedule.value.start}:00` : null
})
const endDateTime = computed(() => {
  if (bookingType.value === 'asset_rental') {
    return rentalEndDate.value ? `${rentalEndDate.value}T17:00:00` : null
  }
  return schedule.value ? `${schedule.value.date}T${schedule.value.end}:00` : null
})

const { mutate: submitBooking, isPending } = useMutation({
  mutationFn: async () => {
    const base = {
      lab_uuid: lab.value?.uuid,
      booking_type: bookingType.value,
      start_time: startDateTime.value,
      end_time: endDateTime.value,
      notes: notes.value,
    }

    if (bookingType.value === 'lab_rental') {
      return api.post('/bookings', {
        ...base,
        purpose: purpose.value,
        nim: needsStudentVerification.value && !currentUserNim.value ? nim.value : undefined,
      })
    }

    if (bookingType.value === 'asset_rental') {
      return api.post('/bookings', {
        ...base,
        asset_uuids: cartItems.value.map((i) => i.uuid),
      })
    }

    // service
    return api.post('/bookings', {
      ...base,
      items: [
        itemType.value === 'package'
          ? { package_uuid: item.value?.uuid, quantity: 1 }
          : { service_uuid: item.value?.uuid, quantity: schedule.value?.durationHours ?? 1 },
      ],
    })
  },
  onSuccess: (response) => {
    const code = response.data.data.booking_code
    if (bookingType.value === 'asset_rental') {
      cartStore.clearCart(slug.value)
    }
    toast.success('Booking berhasil dibuat!')
    router.push({ name: 'booking-success', params: { slug: slug.value, code } })
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
  if (bookingType.value !== 'asset_rental' && !schedule.value) {
    toast.error('Konfirmasi jadwal terlebih dahulu lewat kalender di bawah.')
    return
  }
  if (
    bookingType.value === 'lab_rental' &&
    needsStudentVerification.value &&
    !currentUserNim.value &&
    !nim.value
  ) {
    toast.error('Isi NIM terlebih dahulu untuk keperluan akademik/organisasi.')
    return
  }
  if (bookingType.value === 'asset_rental') {
    if (cartItems.value.length === 0) {
      toast.error('Keranjang kosong. Pilih alat terlebih dahulu.')
      return
    }
    if (!rentalStartDate.value || !rentalEndDate.value) {
      toast.error('Pilih tanggal mulai dan selesai sewa.')
      return
    }
    if (rentalEndDate.value < rentalStartDate.value) {
      toast.error('Tanggal selesai harus setelah tanggal mulai.')
      return
    }
  }
  submitBooking()
}

const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const pageTitle = computed(() => {
  return (
    {
      lab_rental: 'Pinjam Lab',
      asset_rental: 'Sewa Alat',
      service: 'Konfirmasi Booking',
    }[bookingType.value] ?? 'Konfirmasi Booking'
  )
})
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
        <span class="text-white font-bold">{{ pageTitle }}</span>
      </div>
    </header>

    <div class="max-w-2xl mx-auto px-6 py-8 space-y-6">
      <!-- Ringkasan item (mode Jasa & Paket) -->
      <div
        v-if="bookingType === 'service' && item"
        class="bg-white rounded-xl border border-gray-200 p-5"
      >
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">
          {{ itemType === 'package' ? 'Paket Dipilih' : 'Layanan Dipilih' }}
        </p>
        <h3 class="font-semibold text-gray-900 text-lg">{{ item.name }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ item.description }}</p>
        <p class="font-bold text-xl text-gray-900 mt-3">
          {{ formatPrice(itemPrice) }}
          <span v-if="itemType === 'service'" class="text-sm font-normal text-gray-400"
            >/ {{ item.pricing_type?.label }}</span
          >
        </p>
      </div>

      <!-- Mode Pinjam Lab: pilih keperluan -->
      <div
        v-if="bookingType === 'lab_rental'"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-4"
      >
        <h3 class="font-semibold text-gray-900">Keperluan Peminjaman</h3>
        <div class="grid grid-cols-1 gap-2">
          <label
            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
            :class="purpose === 'academic' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
          >
            <input v-model="purpose" type="radio" value="academic" class="w-4 h-4" />
            <div>
              <p class="text-sm font-medium text-gray-900">Akademik / Perkuliahan</p>
              <p class="text-xs text-gray-500">
                Gratis — khusus mahasiswa, untuk keperluan mata kuliah.
              </p>
            </div>
          </label>
          <label
            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
            :class="purpose === 'organization' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
          >
            <input v-model="purpose" type="radio" value="organization" class="w-4 h-4" />
            <div>
              <p class="text-sm font-medium text-gray-900">Organisasi / Mandiri (Mahasiswa)</p>
              <p class="text-xs text-gray-500">
                Tarif diskon —
                {{ formatPrice(Number(lab?.lab_rental_rates?.student_price_per_hour ?? 0)) }} / jam
              </p>
            </div>
          </label>
          <label
            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
            :class="purpose === 'public' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
          >
            <input v-model="purpose" type="radio" value="public" class="w-4 h-4" />
            <div>
              <p class="text-sm font-medium text-gray-900">Umum</p>
              <p class="text-xs text-gray-500">
                Tarif penuh —
                {{ formatPrice(Number(lab?.lab_rental_rates?.public_price_per_hour ?? 0)) }} / jam
              </p>
            </div>
          </label>
        </div>

        <div v-if="needsStudentVerification && !currentUserNim" class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">NIM</label>
          <input
            v-model="nim"
            type="text"
            placeholder="Masukkan NIM kamu"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p class="text-xs text-gray-400">
            Sementara pakai NIM manual — nanti akan diverifikasi otomatis via kartu RFID.
          </p>
        </div>
      </div>

      <!-- Mode Sewa Alat: ringkasan keranjang + rentang tanggal -->
      <div
        v-if="bookingType === 'asset_rental'"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-3"
      >
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Alat yang Disewa</h3>
          <button
            @click="router.push({ name: 'asset-catalog', params: { slug } })"
            class="text-xs text-blue-600 hover:underline"
          >
            + Tambah alat lain
          </button>
        </div>

        <div v-if="!cartItems.length" class="text-sm text-gray-400 py-4 text-center">
          Keranjang kosong.
          <button
            @click="router.push({ name: 'asset-catalog', params: { slug } })"
            class="text-blue-600 hover:underline"
          >
            Pilih alat dulu
          </button>
        </div>

        <div
          v-for="asset in cartItems"
          :key="asset.uuid"
          class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
        >
          <div>
            <p class="text-sm font-medium text-gray-900">{{ asset.name }}</p>
            <p class="text-xs text-gray-500">{{ asset.brand }}</p>
          </div>
          <span class="text-sm font-semibold text-gray-900"
            >{{ formatPrice(Number(asset.rental_price)) }} / hari</span
          >
        </div>
      </div>

      <!-- Mode Sewa Alat: rentang tanggal sewa (harian, bukan per jam) -->
      <div
        v-if="bookingType === 'asset_rental'"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-4"
      >
        <h3 class="font-semibold text-gray-900">Lama Peminjaman</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Tanggal Mulai</label>
            <input
              v-model="rentalStartDate"
              type="date"
              :min="minDate"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Tanggal Selesai</label>
            <input
              v-model="rentalEndDate"
              type="date"
              :min="minRentalEndDate"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        <p v-if="rentalDays > 0" class="text-sm text-gray-600">
          Durasi sewa: <strong>{{ rentalDays }} hari</strong>
        </p>
      </div>

      <!-- Kalender jadwal (mode Pinjam Lab & Jasa) -->
      <div
        v-if="bookingType !== 'asset_rental'"
        class="bg-white rounded-xl border border-gray-200 p-5 space-y-4"
      >
        <h3 class="font-semibold text-gray-900">Pilih Jadwal</h3>
        <AvailabilityCalendar
          :slug="slug"
          interactive
          :min-date="minDate"
          @confirm-slot="handleScheduleConfirm"
        />
      </div>

      <!-- Catatan -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Catatan (opsional)</label>
        <textarea
          v-model="notes"
          rows="3"
          placeholder="Tulis permintaan khusus..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Total & submit -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-gray-600">Total Pembayaran</span>
          <span class="text-2xl font-bold text-gray-900">{{ formatPrice(totalPrice) }}</span>
        </div>

        <button
          @click="handleSubmit"
          :disabled="isPending"
          class="w-full py-3 rounded-xl font-semibold text-white transition-colors disabled:opacity-50"
          :style="{ backgroundColor: lab?.branding?.secondary_color ?? '#e94560' }"
        >
          {{ isPending ? 'Memproses...' : 'Konfirmasi Booking' }}
        </button>
      </div>
    </div>
  </div>
</template>
