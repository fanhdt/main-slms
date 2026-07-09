<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation } from '@tanstack/vue-query'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { bookingApi } from '@/features/booking/api/bookingApi'
import QRScanner from '@/features/booking/components/QRScanner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'

const router = useRouter()
const labStore = useLabStore()

const scannedCode = ref('')
const bookingData = ref<any>(null)
const isLookingUp = ref(false)
const scanComplete = ref(false)

async function handleScanned(code: string) {
  if (scanComplete.value) return
  scanComplete.value = true
  scannedCode.value = code
  isLookingUp.value = true

  try {
    // Cari booking berdasarkan kode
    const res = await api.get('/bookings', {
      params: { code: code },
    })
    const bookings = res.data.data.data
    if (bookings.length > 0) {
      bookingData.value = bookings[0]
    } else {
      toast.error('Booking tidak ditemukan.')
      resetScan()
    }
  } catch {
    toast.error('Gagal mencari booking.')
    resetScan()
  } finally {
    isLookingUp.value = false
  }
}

const { mutate: checkIn, isPending } = useMutation({
  mutationFn: () => bookingApi.updateStatus(bookingData.value.uuid, 'ongoing'),
  onSuccess: () => {
    toast.success('Check-in berhasil! Status booking diupdate ke Ongoing.')
    bookingData.value.status = { value: 'ongoing', label: 'Sedang Berlangsung' }
  },
  onError: () => {
    toast.error('Gagal melakukan check-in.')
  },
})

function resetScan() {
  scannedCode.value = ''
  bookingData.value = null
  scanComplete.value = false
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Scan QR Check-in</h2>
        <p class="text-gray-500 mt-1 text-sm">
          Scan QR Code customer untuk melakukan check-in otomatis.
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Scanner -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-4">📷 Kamera Scanner</h3>

        <div v-if="!scanComplete">
          <QRScanner @scanned="handleScanned" />
          <p class="text-xs text-gray-400 text-center mt-3">
            Arahkan kamera ke QR Code booking customer
          </p>
        </div>

        <div v-else class="text-center py-8">
          <div class="text-4xl mb-3">✓</div>
          <p class="font-medium text-gray-900 font-mono text-lg">{{ scannedCode }}</p>
          <p class="text-sm text-gray-500 mt-1 mb-4">QR Code berhasil discan</p>
          <button @click="resetScan" class="text-sm text-blue-600 hover:underline">
            Scan ulang
          </button>
        </div>
      </div>

      <!-- Booking Result -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-4">📋 Detail Booking</h3>

        <!-- Loading -->
        <div v-if="isLookingUp" class="text-center py-12 text-gray-400">
          <div class="text-3xl mb-2">🔍</div>
          <p>Mencari booking...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="!bookingData" class="text-center py-12 text-gray-300">
          <div class="text-5xl mb-3">📱</div>
          <p class="text-sm">Scan QR Code untuk melihat detail booking</p>
        </div>

        <!-- Booking Detail -->
        <div v-else class="space-y-4">
          <!-- Code & Status -->
          <div class="flex items-center justify-between">
            <p class="font-mono font-bold text-lg text-gray-900">
              {{ bookingData.booking_code ?? bookingData.code }}
            </p>
            <StatusBadge :status="bookingData.status" type="booking" />
          </div>

          <!-- Customer -->
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-400">Customer</p>
            <p class="font-medium text-gray-900">{{ bookingData.user?.name }}</p>
            <p class="text-sm text-gray-500">{{ bookingData.user?.email }}</p>
          </div>

          <!-- Jadwal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <p class="text-xs text-gray-400">Mulai</p>
              <p class="text-sm font-medium text-gray-700">
                {{ formatDate(bookingData.start_time) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Selesai</p>
              <p class="text-sm font-medium text-gray-700">
                {{ formatDate(bookingData.end_time) }}
              </p>
            </div>
          </div>

          <!-- Total -->
          <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <span class="text-sm text-gray-500">Total</span>
            <span class="font-bold text-gray-900">
              {{ formatPrice(bookingData.total_price) }}
            </span>
          </div>

          <!-- Action -->
          <div class="pt-2">
            <button
              v-if="bookingData.status.value === 'approved'"
              @click="checkIn()"
              :disabled="isPending"
              class="w-full py-3 rounded-xl font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors"
            >
              {{ isPending ? 'Memproses...' : '✓ Check-in Sekarang' }}
            </button>

            <div
              v-else-if="bookingData.status.value === 'ongoing'"
              class="w-full py-3 rounded-xl font-medium text-center bg-purple-100 text-purple-700"
            >
              ✓ Sudah Check-in
            </div>

            <div
              v-else-if="bookingData.status.value === 'pending'"
              class="w-full py-3 rounded-xl font-medium text-center bg-yellow-100 text-yellow-700"
            >
              ⚠ Booking belum diapprove
            </div>

            <div
              v-else-if="bookingData.status.value === 'completed'"
              class="w-full py-3 rounded-xl font-medium text-center bg-green-100 text-green-700"
            >
              ✓ Booking selesai
            </div>

            <div
              v-else
              class="w-full py-3 rounded-xl font-medium text-center bg-gray-100 text-gray-600"
            >
              Booking tidak dapat diproses
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
