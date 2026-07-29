<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { watchDebounced } from '@vueuse/core'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import AvailabilityCalendar from '@/components/AvailabilityCalendar.vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useCartStore } from '@/features/booking/stores/useCartStore'
import { useBookingDraftStore } from '@/features/booking/stores/useBookingDraftStore'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Plus, ArrowLeft, RotateCcw } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const draftStore = useBookingDraftStore()
const { isStaffMode, goBack } = useBookingFlowMode()
const selectedOptionUuids = ref<string[]>([])
const photoCount = ref<number>(1)
const customPrices = ref<Record<string, number>>({})
// NEW — catatan permintaan customer untuk layanan mode custom/nego
const customNote = ref('')
type AssetAvailability = { total_quantity: number; reserved_qty: number; available_qty: number }
const availability = ref<Record<string, AssetAvailability>>({})
const isCheckingAvailability = ref(false)

const slug = computed(() => route.params.slug as string)

const bookingType = computed(() => (route.query.bookingType as string) || 'service')
const itemType = computed(() => route.query.type as string)
const itemId = computed(() => route.query.id as string)
const queryClient = useQueryClient()

const draftItemKey = computed(() => (bookingType.value === 'service' ? itemId.value : undefined))

const notes = ref('')
const schedule = ref<{ date: string; start: string; end: string; durationHours: number } | null>(
  null,
)

const purpose = ref<'academic' | 'organization' | 'public'>('public')
const nim = ref('')

const cartItems = computed(() => cartStore.getItems(slug.value))
const rentalStartDate = ref('')
const rentalEndDate = ref('')

const draftLoaded = ref(false)

onMounted(() => {
  const draft = draftStore.getDraft(slug.value, bookingType.value, draftItemKey.value)

  if (draft.purpose) purpose.value = draft.purpose
  if (draft.nim) nim.value = draft.nim
  if (draft.notes) notes.value = draft.notes
  if (draft.schedule) schedule.value = draft.schedule
  if (draft.rentalStartDate) rentalStartDate.value = draft.rentalStartDate
  if (draft.rentalEndDate) rentalEndDate.value = draft.rentalEndDate

  draftLoaded.value = true
})

async function checkAssetAvailability() {
  if (bookingType.value !== 'asset_rental') return
  if (!rentalStartDate.value || !rentalEndDate.value || !cartItems.value.length) {
    availability.value = {}
    return
  }
  if (rentalEndDate.value < rentalStartDate.value) return

  isCheckingAvailability.value = true
  try {
    const res = await api.get('/assets/availability', {
      params: {
        'asset_uuids[]': cartItems.value.map((i) => i.uuid),
        start_date: rentalStartDate.value,
        end_date: rentalEndDate.value,
      },
      paramsSerializer: { indexes: null },
    })
    availability.value = Object.fromEntries(res.data.data.map((a: any) => [a.asset_uuid, a]))
  } catch {
    availability.value = {}
  } finally {
    isCheckingAvailability.value = false
  }
}
watchDebounced([rentalStartDate, rentalEndDate, cartItems], checkAssetAvailability, {
  debounce: 400,
  deep: true,
})

const insufficientStockItems = computed(() => {
  return cartItems.value.filter((item) => {
    const avail = availability.value[item.uuid]
    return avail && item.quantity > avail.available_qty
  })
})

watch(
  [purpose, nim, notes, schedule, rentalStartDate, rentalEndDate],
  () => {
    if (!draftLoaded.value) return
    draftStore.saveDraft(slug.value, bookingType.value, draftItemKey.value, {
      purpose: purpose.value,
      nim: nim.value,
      notes: notes.value,
      schedule: schedule.value,
      rentalStartDate: rentalStartDate.value,
      rentalEndDate: rentalEndDate.value,
    })
  },
  { deep: true },
)

function resetDraft() {
  if (!confirm('Hapus semua isian dan mulai dari awal?')) return
  purpose.value = 'public'
  nim.value = ''
  notes.value = ''
  schedule.value = null
  rentalStartDate.value = ''
  rentalEndDate.value = ''
  draftStore.clearDraft(slug.value, bookingType.value, draftItemKey.value)
  toast.info('Isian sebelumnya sudah dihapus.')
}

const hasDraftBeforeSubmit = computed(() => {
  return !!(
    purpose.value !== 'public' ||
    nim.value ||
    notes.value ||
    schedule.value ||
    rentalStartDate.value ||
    rentalEndDate.value
  )
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
  enabled: computed(() => bookingType.value === 'service' && !!itemId.value),
})

const needsStudentVerification = computed(() => purpose.value !== 'public')
const currentUserNim = computed(() => authStore.user?.nim ?? null)

const needsScheduleForService = computed(() => {
  return bookingType.value === 'service' && item.value?.requires_schedule === true
})

// NEW — item ini pakai mode custom/nego (harga ditentukan petugas saat booking)
const isCustomPricingItem = computed(
  () =>
    bookingType.value === 'service' &&
    itemType.value === 'service' &&
    item.value?.is_custom_pricing === true,
)

const itemPrice = computed(() => {
  if (!item.value) return 0
  return Number(item.value.final_price ?? item.value.price ?? 0)
})
const isOptionOnlyItem = computed(
  () => bookingType.value === 'service' && !isCustomPricingItem.value && itemPrice.value === 0,
)
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
  // NEW — item custom pricing: total selalu 0, harga ditentukan petugas
  if (isCustomPricingItem.value) {
    return 0
  }
  const base =
    needsScheduleForService.value && item.value?.pricing_type?.value === 'per_hour'
      ? itemPrice.value * (schedule.value?.durationHours ?? 1)
      : itemPrice.value
  return base + selectedOptionsTotal.value
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
          : {
              service_uuid: item.value?.uuid,
              quantity: 1,
              option_uuids: selectedOptionUuids.value,
              photo_count: hasPerPhotoOption.value ? photoCount.value : undefined,
              custom_prices: customPrices.value,
              // NEW
              custom_note: isCustomPricingItem.value ? customNote.value : undefined,
            },
      ],
    })
  },
  onSuccess: (response) => {
    const code = response.data.data.booking_code

    // ---> BAGIAN YANG DITAMBAHKAN <---
    // Bersihkan cache riwayat booking user agar halaman "My Bookings" & "Dashboard" otomatis update
    queryClient.invalidateQueries({ queryKey: ['my-bookings'] })
    queryClient.invalidateQueries({ queryKey: ['booking-summary'] })
    queryClient.invalidateQueries({ queryKey: ['dashboard-bookings'] })
    // ---------------------------------

    queryClient.invalidateQueries({ queryKey: ['rentable-assets'] })
    queryClient.invalidateQueries({ queryKey: ['assets'] })

    draftStore.clearDraft(slug.value, bookingType.value, draftItemKey.value)
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

function toggleOption(uuid: string) {
  if (selectedOptionUuids.value.includes(uuid)) {
    selectedOptionUuids.value = selectedOptionUuids.value.filter((u) => u !== uuid)
  } else {
    selectedOptionUuids.value.push(uuid)
  }
}

const hasPerPhotoOption = computed(() => {
  return (item.value?.options ?? []).some(
    (opt: any) =>
      selectedOptionUuids.value.includes(opt.uuid) && opt.price_type.value === 'per_photo',
  )
})

const selectedOptionsTotal = computed(() => {
  return (item.value?.options ?? [])
    .filter((opt: any) => selectedOptionUuids.value.includes(opt.uuid))
    .reduce((sum: number, opt: any) => {
      if (opt.price_type.value === 'per_photo') {
        return sum + Number(opt.price) * Math.max(1, photoCount.value)
      }
      if (opt.price_type.value === 'custom') {
        return sum + Number(customPrices.value[opt.uuid] ?? 0)
      }
      return sum + Number(opt.price)
    }, 0)
})

function formatPrice(price: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(price)
}

function handleSubmit() {
  if ((bookingType.value === 'lab_rental' || needsScheduleForService.value) && !schedule.value) {
    toast.error('Konfirmasi jadwal terlebih dahulu lewat kalender di bawah.')
    return
  }

  // NEW — validasi wajib isi catatan permintaan custom
  if (isCustomPricingItem.value && !customNote.value.trim()) {
    toast.error('Jelaskan permintaan custom kamu terlebih dahulu.')
    return
  }

  if (
    bookingType.value === 'service' &&
    itemType.value === 'service' &&
    !isCustomPricingItem.value &&
    itemPrice.value === 0 &&
    (item.value?.options?.length ?? 0) > 0 &&
    selectedOptionUuids.value.length === 0
  ) {
    toast.error('Pilih minimal satu pilihan editing sebelum melanjutkan.')
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
    if (insufficientStockItems.value.length > 0) {
      toast.error(
        'Ada alat yang stoknya tidak cukup untuk tanggal ini. Kurangi jumlah atau ganti tanggal.',
      )
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

function getAvailability(uuid: string) {
  return availability.value[uuid] ?? null
}

function isStockInsufficient(assetUuid: string, requestedQty: number): boolean {
  const avail = getAvailability(assetUuid)
  return avail !== null && requestedQty > avail.available_qty
}
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
      <div
        v-if="hasDraftBeforeSubmit"
        class="mb-5 flex items-center justify-between gap-3 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3"
      >
        <p class="text-sm text-blue-800">
          Isian sebelumnya otomatis dimuat kembali. Tinggal ubah bagian yang perlu diganti.
        </p>
        <Button
          variant="ghost"
          size="sm"
          class="text-blue-700 hover:bg-blue-100 shrink-0"
          @click="resetDraft"
        >
          <RotateCcw class="size-3.5" />
          Mulai Ulang
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 items-start">
        <div class="space-y-6 min-w-0">
          <!-- Detail layanan/paket terpilih — gambar & deskripsi -->
          <Card v-if="bookingType === 'service' && item" class="p-0 overflow-hidden">
            <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
              <img
                v-if="item.image"
                :src="item.image"
                :alt="item.name"
                class="w-full h-full object-cover"
              />
            </div>
            <CardContent class="p-5">
              <h2 class="font-semibold text-gray-900 text-lg">{{ item.name }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ item.description }}</p>
            </CardContent>
          </Card>

          <!-- NEW — Card khusus permintaan custom, taruh setelah detail layanan -->
          <Card v-if="isCustomPricingItem" class="p-0 border-purple-200">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold text-purple-900">
                Detail Permintaan Custom
              </CardTitle>
              <p class="text-xs text-purple-600">
                Layanan ini harganya nego — jelaskan kebutuhanmu selengkap mungkin, petugas lab akan
                menghubungimu untuk konfirmasi harga sebelum diproses.
              </p>
            </CardHeader>
            <CardContent class="p-5 space-y-1.5">
              <textarea
                v-model="customNote"
                rows="4"
                placeholder="Contoh: Edit foto rapor kelas 6, ukuran 4x6, background merah, total 30 lembar..."
                class="w-full px-3 py-2 border border-purple-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"
              />
            </CardContent>
          </Card>

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
                  <p
                    v-if="isStockInsufficient(asset.uuid, asset.quantity)"
                    class="text-xs text-red-600 font-medium mt-0.5"
                  >
                    Stok tidak cukup — tersisa {{ getAvailability(asset.uuid)?.available_qty }} unit
                    untuk tanggal ini
                  </p>
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
              <CardTitle class="text-sm font-semibold"
                >Lama Peminjaman
                <p v-if="isCheckingAvailability" class="text-xs text-gray-400">
                  Mengecek ketersediaan stok...
                </p>
              </CardTitle>
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

          <!-- Kalender jadwal -->
          <Card v-if="bookingType === 'lab_rental' || needsScheduleForService" class="p-0">
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

          <!-- Mode Jasa yang TIDAK butuh jadwal: info proses full online -->
          <Card
            v-if="
              bookingType === 'service' && item && !needsScheduleForService && !isCustomPricingItem
            "
            class="p-0"
          >
            <CardContent class="p-5">
              <p class="text-sm text-gray-600">
                Booking ini akan diproses langsung oleh petugas lab setelah dikonfirmasi. Kamu akan
                mendapat notifikasi begitu progresnya berubah — tidak perlu datang ke lab untuk
                booking jenis ini.
              </p>
            </CardContent>
          </Card>

          <Card
            v-if="
              bookingType === 'service' &&
              itemType === 'service' &&
              !isCustomPricingItem &&
              item?.options?.length
            "
            class="p-0"
          >
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">
                {{ isOptionOnlyItem ? 'Pilih Layanan Editing' : 'Opsi Tambahan' }}
              </CardTitle>
              <p
                class="text-xs"
                :class="isOptionOnlyItem ? 'text-amber-600 font-medium' : 'text-gray-400'"
              >
                {{
                  isOptionOnlyItem
                    ? 'Wajib pilih minimal satu layanan editing di bawah ini'
                    : 'Pilih layanan tambahan yang kamu butuhkan (opsional)'
                }}
              </p>
            </CardHeader>
            <CardContent class="p-5 space-y-3">
              <label
                v-for="option in item.options"
                :key="option.uuid"
                class="flex flex-col gap-2 p-3 rounded-lg border cursor-pointer transition-colors"
                :class="
                  selectedOptionUuids.includes(option.uuid)
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200'
                "
              >
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <input
                      type="checkbox"
                      :checked="selectedOptionUuids.includes(option.uuid)"
                      @change="toggleOption(option.uuid)"
                      class="w-4 h-4 rounded border-gray-300"
                    />
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ option.name }}</p>
                      <p v-if="option.description" class="text-xs text-gray-500">
                        {{ option.description }}
                      </p>
                    </div>
                  </div>

                  <span
                    v-if="option.price_type.value !== 'custom'"
                    class="text-sm font-semibold text-gray-900 shrink-0"
                  >
                    {{ isOptionOnlyItem ? '' : '+ ' }}{{ formatPrice(Number(option.price)) }}
                    <span
                      v-if="option.price_type.value === 'per_photo'"
                      class="text-xs font-normal text-gray-400"
                    >
                      /foto
                    </span>
                  </span>
                  <span v-else class="text-xs font-medium text-blue-600 shrink-0">Custom</span>
                </div>

                <div
                  v-if="
                    option.price_type.value === 'custom' &&
                    selectedOptionUuids.includes(option.uuid)
                  "
                  class="pl-7"
                  @click.stop
                >
                  <input
                    v-model.number="customPrices[option.uuid]"
                    type="number"
                    min="0"
                    placeholder="Masukkan perkiraan biaya (Rp)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <p class="text-xs text-gray-400 mt-1">
                    Harga final akan dikonfirmasi ulang oleh petugas lab sebelum diproses.
                  </p>
                </div>
              </label>

              <div v-if="hasPerPhotoOption" class="space-y-1.5 pt-1">
                <label class="text-sm font-medium text-gray-700">Jumlah Foto</label>
                <input
                  v-model.number="photoCount"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
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

        <!-- KOLOM KANAN — Ringkasan -->
        <div class="lg:sticky lg:top-24 space-y-4">
          <Card class="p-0">
            <CardHeader class="px-5 pt-5 pb-0">
              <CardTitle class="text-sm font-semibold">Ringkasan Booking</CardTitle>
            </CardHeader>
            <CardContent class="p-5 space-y-4">
              <div v-if="bookingType === 'service' && item" class="space-y-2">
                <div
                  class="aspect-video rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center"
                >
                  <img
                    v-if="item.image"
                    :src="item.image"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="space-y-1">
                  <p class="text-xs text-gray-400 uppercase tracking-wide">
                    {{ itemType === 'package' ? 'Paket Dipilih' : 'Layanan Dipilih' }}
                  </p>
                  <h3 class="font-semibold text-gray-900">{{ item.name }}</h3>
                  <p class="text-sm text-gray-500 line-clamp-2">{{ item.description }}</p>
                </div>
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

              <div
                v-if="selectedOptionUuids.length"
                class="space-y-1 text-sm text-gray-600 border-t border-gray-100 pt-3"
              >
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Opsi Tambahan</p>
                <div
                  v-for="option in item?.options?.filter((o: any) =>
                    selectedOptionUuids.includes(o.uuid),
                  )"
                  :key="option.uuid"
                  class="flex items-center justify-between"
                >
                  <span>{{ option.name }}</span>
                  <span class="font-medium text-gray-900">
                    +
                    {{
                      formatPrice(
                        Number(option.price) *
                          (option.price_type.value === 'per_photo' ? photoCount : 1),
                      )
                    }}
                  </span>
                </div>
              </div>

              <Separator />

              <div class="flex items-center justify-between">
                <span class="text-gray-600 text-sm">Total Pembayaran</span>
                <span v-if="isCustomPricingItem" class="text-sm font-semibold text-purple-600">
                  Menunggu Konfirmasi Petugas
                </span>
                <span v-else class="text-xl font-bold text-gray-900">{{
                  formatPrice(totalPrice)
                }}</span>
              </div>
              <p
                v-if="isOptionOnlyItem && selectedOptionUuids.length === 0"
                class="text-xs text-amber-600 -mt-1"
              >
                Pilih minimal satu layanan editing untuk menghitung total.
              </p>

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
