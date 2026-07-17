<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useCartStore } from '@/features/booking/stores/useCartStore'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Plus, ArrowLeft } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const { isStaffMode, goBack } = useBookingFlowMode()

const slug = computed(() => route.params.slug as string)

const bookingType = computed(() => (route.query.bookingType as string) || 'service')
const itemType = computed(() => route.query.type as string)
const itemId = computed(() => route.query.id as string)

const notes = ref('')
const schedule = ref<{ date: string; start: string; end: string; durationHours: number } | null>(
  null,
)

const purpose = ref<'academic' | 'organization' | 'public'>('public')
const nim = ref('')

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
  () =>
    cartItems.value.reduce((sum, a) => sum + Number(a.rental_price) * a.quantity, 0) *
    rentalDays.value,
)

const totalPrice = computed(() => {
  if (bookingType.value === 'lab_rental') {
    return labRentalRatePerHour.value * (schedule.value?.durationHours ?? 0)
  }
  if (bookingType.value === 'asset_rental') {
    return assetRentalTotal.value
  }
  // Service & Package — harga sudah fix, tidak bergantung jam
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
  if (bookingType.value === 'service') {
    // Biar backend yang otomatis hitung berdasarkan durasi jasa/paket
    return null
  }
  return schedule.value ? `${schedule.value.date}T${schedule.value.start}:00` : null
})
const endDateTime = computed(() => {
  if (bookingType.value === 'asset_rental') {
    return rentalEndDate.value ? `${rentalEndDate.value}T17:00:00` : null
  }
  if (bookingType.value === 'service') {
    return null
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
        assets: cartItems.value.map((i) => ({
          asset_uuid: i.uuid,
          quantity: i.quantity,
        })),
      })
    }

    return api.post('/bookings', {
      ...base,
      items: [
        itemType.value === 'package'
          ? { package_uuid: item.value?.uuid, quantity: 1 }
          : { service_uuid: item.value?.uuid, quantity: 1 },
      ],
    })
  },
  onSuccess: (response) => {
    const code = response.data.data.booking_code
    if (bookingType.value === 'asset_rental') {
      cartStore.clearCart(slug.value)
    }
    toast.success('Booking berhasil dibuat!')
    if (isStaffMode.value) {
      toast.success('Booking berhasil dibuat.')
    } else {
      router.push({
        name: 'booking-success',
        params: { slug: slug.value, code },
        query: isStaffMode.value ? { mode: 'staff' } : undefined,
      })
    }
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
  if (bookingType.value === 'lab_rental' && !schedule.value) {
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
      <div class="max-w-5xl mx-auto px-6 h-16 flex items-center gap-4">
        <button
          @click="goBack(slug)"
          class="text-white/70 hover:text-white transition-colors flex items-center gap-1.5"
        >
          <ArrowLeft class="size-4" />
          Kembali
        </button>
        <span class="text-white font-bold">{{ pageTitle }}</span>
      </div>
    </header>

    <div class="max-w-5xl mx-auto px-6 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 items-start">
        <!-- ============================================================
             KOLOM KIRI — Jadwal & detail spesifik tipe booking
        ============================================================= -->
        <div class="space-y-6 min-w-0">
          <!-- Mode Pinjam Lab: pilih keperluan -->
          <Card v-if="bookingType === 'lab_rental'" class="p-0">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">Keperluan Peminjaman</CardTitle>
            </CardHeader>
            <CardContent class="p-5 space-y-3">
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
                :class="
                  purpose === 'organization' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                "
              >
                <input v-model="purpose" type="radio" value="organization" class="w-4 h-4" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Organisasi / Mandiri (Mahasiswa)</p>
                  <p class="text-xs text-gray-500">
                    Tarif diskon —
                    {{ formatPrice(Number(lab?.lab_rental_rates?.student_price_per_hour ?? 0)) }} /
                    jam
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
                    {{ formatPrice(Number(lab?.lab_rental_rates?.public_price_per_hour ?? 0)) }} /
                    jam
                  </p>
                </div>
              </label>

              <div v-if="needsStudentVerification && !currentUserNim" class="space-y-1.5 pt-1">
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
            </CardContent>
          </Card>

          <!-- Mode Sewa Alat: ringkasan keranjang -->
          <Card v-if="bookingType === 'asset_rental'" class="p-0">
            <CardHeader class="px-5 pt-5 pb-0 flex flex-row items-center justify-between space-y-0">
              <CardTitle class="text-sm font-semibold">Alat yang Disewa</CardTitle>
              <button
                @click="router.push({ name: 'asset-catalog', params: { slug } })"
                class="text-xs text-blue-600 hover:underline"
              >
                + Tambah alat lain
              </button>
            </CardHeader>
            <CardContent class="p-5">
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
                  <p class="text-sm font-medium text-gray-900">
                    {{ asset.name }} × {{ asset.quantity }}
                  </p>
                  <p class="text-xs text-gray-500">{{ asset.brand }}</p>
                </div>
                <span class="text-sm font-semibold text-gray-900">
                  {{ formatPrice(Number(asset.rental_price) * asset.quantity) }} / hari
                </span>
              </div>
            </CardContent>
          </Card>

          <!-- Mode Sewa Alat: rentang tanggal -->
          <Card v-if="bookingType === 'asset_rental'" class="p-0">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">Lama Peminjaman</CardTitle>
            </CardHeader>
            <CardContent class="p-5 space-y-3">
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
            </CardContent>
          </Card>

          <!-- Kalender jadwal — HANYA untuk Pinjam Lab -->
          <Card v-if="bookingType === 'lab_rental'" class="p-0">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">Pilih Jadwal</CardTitle>
            </CardHeader>
            <CardContent class="p-5">
              <AvailabilityCalendar
                :slug="slug"
                interactive
                :min-date="minDate"
                @confirm-slot="handleScheduleConfirm"
              />
            </CardContent>
          </Card>

          <!-- Mode Jasa: info bahwa proses dikerjakan lab, tanpa perlu pilih jam -->
          <Card v-if="bookingType === 'service'" class="p-0">
            <CardContent class="p-5">
              <p class="text-sm text-gray-600">
                Booking ini akan diproses langsung oleh petugas lab setelah dikonfirmasi. Kamu akan
                mendapat notifikasi begitu progresnya berubah — tidak perlu datang ke lab untuk
                booking jenis ini.
              </p>
            </CardContent>
          </Card>

          <!-- Catatan -->
          <Card class="p-0">
            <CardContent class="p-5 space-y-1.5">
              <label class="text-sm font-medium text-gray-700">Catatan (opsional)</label>
              <textarea
                v-model="notes"
                rows="3"
                placeholder="Tulis permintaan khusus..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </CardContent>
          </Card>
        </div>

        <!-- ============================================================
             KOLOM KANAN — Ringkasan (sticky)
        ============================================================= -->
        <div class="lg:sticky lg:top-24 space-y-4">
          <Card class="p-0">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">Ringkasan Booking</CardTitle>
            </CardHeader>
            <CardContent class="p-5 space-y-4">
              <!-- Item terpilih (mode Jasa & Paket) -->
              <div v-if="bookingType === 'service' && item" class="space-y-1">
                <p class="text-xs text-gray-400 uppercase tracking-wide">
                  {{ itemType === 'package' ? 'Paket Dipilih' : 'Layanan Dipilih' }}
                </p>
                <h3 class="font-semibold text-gray-900">{{ item.name }}</h3>
                <p class="text-sm text-gray-500 line-clamp-2">{{ item.description }}</p>
              </div>

              <div v-if="bookingType === 'lab_rental'" class="text-sm text-gray-600">
                Keperluan: <span class="font-medium text-gray-900 capitalize">{{ purpose }}</span>
              </div>

              <div v-if="schedule" class="text-sm text-gray-600">
                Jadwal:
                <span class="font-medium text-gray-900">
                  {{ schedule.date }} · {{ schedule.start }}–{{ schedule.end }}
                </span>
              </div>

              <div
                v-if="bookingType === 'asset_rental' && rentalDays > 0"
                class="text-sm text-gray-600"
              >
                Durasi: <span class="font-medium text-gray-900">{{ rentalDays }} hari</span>
              </div>

              <Separator />

              <div class="flex items-center justify-between">
                <span class="text-gray-600 text-sm">Total Pembayaran</span>
                <span class="text-xl font-bold text-gray-900">{{ formatPrice(totalPrice) }}</span>
              </div>

              <Button class="w-full" :disabled="isPending" @click="handleSubmit">
                {{ isPending ? 'Memproses...' : 'Konfirmasi Booking' }}
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>
