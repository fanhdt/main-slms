<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useBookingFlowMode } from '@/composables/useBookingFlowMode'
import QRCode from 'qrcode'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'

declare const snap: any

const route = useRoute()
const router = useRouter()
const { isStaffMode } = useBookingFlowMode()

const slug = computed(() => route.params.slug as string)
const code = computed(() => route.params.code as string)
const qrDataUrl = ref('')
const isPaying = ref(false)

const { data: lab } = useQuery({
  queryKey: ['success-lab', slug],
  queryFn: async () => {
    const res = await api.get(`/labs/${slug.value}`)
    return res.data.data
  },
})

// NEW — ambil data booking by code, buat cek status pembayaran
const { data: booking, refetch: refetchBooking } = useQuery({
  queryKey: ['success-booking', code],
  queryFn: async () => {
    const res = await api.get('/bookings/my', { params: { search: code.value, per_page: 1 } })
    return res.data.data.data?.[0] ?? null
  },
})

const isPaid = computed(() => booking.value?.payment_status?.value === 'paid')

onMounted(async () => {
  // QR cuma di-generate kalau memang sudah lunas (jaga-jaga kalau halaman ini
  // di-refresh setelah bayar, QR tetap bisa muncul)
  if (isPaid.value) {
    qrDataUrl.value = await QRCode.toDataURL(code.value, {
      width: 200,
      margin: 2,
      color: { dark: '#1a1a2e', light: '#ffffff' },
    })
  }
})

// NEW — trigger pembayaran langsung dari halaman ini
async function payNow() {
  if (!booking.value) return
  isPaying.value = true
  try {
    const res = await api.post(`/bookings/${booking.value.uuid}/pay`)
    const { snap_token } = res.data.data

    snap.pay(snap_token, {
      onSuccess: async () => {
        toast.success('Pembayaran berhasil!')
        await refetchBooking()
        qrDataUrl.value = await QRCode.toDataURL(code.value, {
          width: 200,
          margin: 2,
          color: { dark: '#1a1a2e', light: '#ffffff' },
        })
      },
      onPending: () => {
        toast.info('Menunggu pembayaran kamu diselesaikan.')
      },
      onError: () => {
        toast.error('Pembayaran gagal.')
      },
    })
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal memulai pembayaran.')
  } finally {
    isPaying.value = false
  }
}

function goToBookings() {
  if (isStaffMode.value) {
    router.push({ name: 'lab-bookings', params: { labSlug: slug.value } })
  } else {
    router.push('/my-bookings')
  }
}

function goHome() {
  if (isStaffMode.value) {
    router.push({ name: 'lab-dashboard', params: { labSlug: slug.value } })
  } else {
    router.push('/')
  }
}

function createAnother() {
  router.push({ name: 'booking-lab', params: { slug: slug.value }, query: { mode: 'staff' } })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Success Header -->
        <div
          class="p-8 text-center text-white"
          :style="{ backgroundColor: lab?.branding.primary_color ?? '#1a1a2e' }"
        >
          <div class="text-5xl mb-4">✓</div>
          <h1 class="text-2xl font-bold">Booking Berhasil!</h1>
          <p class="text-white/70 mt-1">Pesanan kamu di {{ lab?.name }} telah dikonfirmasi</p>
        </div>

        <div class="p-8 text-center">
          <p class="text-sm text-gray-500 mb-2">Kode Booking</p>
          <div class="bg-gray-50 rounded-xl p-3 mb-6 inline-block">
            <p class="text-xl font-mono font-bold text-gray-900 tracking-wider">
              {{ code }}
            </p>
          </div>

          <!-- CHANGED: kalau belum bayar, tampilkan CTA bayar, bukan QR -->
          <template v-if="!isPaid">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
              <p class="text-sm text-yellow-800 font-medium">⏳ Menunggu Pembayaran</p>
              <p class="text-xs text-yellow-700 mt-1">
                Selesaikan pembayaran untuk mendapatkan QR Code check-in.
              </p>
            </div>

            <button
              @click="payNow"
              :disabled="isPaying"
              class="w-full py-3 rounded-xl font-semibold text-white transition-colors disabled:opacity-50 mb-3"
              :style="{ backgroundColor: lab?.branding.secondary_color ?? '#e94560' }"
            >
              {{ isPaying ? 'Memproses...' : '💳 Bayar Sekarang' }}
            </button>
          </template>

          <!-- Sudah lunas — tampilkan QR seperti biasa -->
          <template v-else>
            <div class="flex justify-center mb-4">
              <div class="bg-white p-3 rounded-xl border border-gray-200 inline-block">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Code Booking" class="w-48 h-48" />
                <div v-else class="w-48 h-48 bg-gray-100 animate-pulse rounded" />
              </div>
            </div>

            <p class="text-sm text-gray-500 mb-6">
              Tunjukkan QR Code ini ke admin saat datang ke lokasi untuk check-in otomatis.
            </p>
          </template>

          <div class="flex flex-col gap-3">
            <button
              @click="goToBookings"
              class="w-full py-3 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200"
            >
              {{ isStaffMode ? 'Kembali ke Daftar Booking' : 'Lihat Semua Booking' }}
            </button>
            <button
              @click="router.push(`/booking/${slug}`)"
              class="w-full py-3 rounded-xl font-medium text-gray-600 hover:bg-gray-50 transition-colors border border-gray-200"
            >
              {{ isStaffMode ? 'Buat Booking Lain' : 'Booking Lagi' }}
            </button>
            <button
              v-if="!isStaffMode"
              @click="goHome"
              class="w-full py-3 rounded-xl font-medium text-gray-500 hover:bg-gray-50 transition-colors"
            >
              Kembali ke Beranda
            </button>
            <button
              v-else
              @click="goHome"
              class="w-full py-3 rounded-xl font-medium text-gray-500 hover:bg-gray-50 transition-colors"
            >
              + Kembali ke Dashboard Lab +
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
