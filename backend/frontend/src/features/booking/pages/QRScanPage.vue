<script setup lang="ts">
import { ref } from 'vue'
import { useMutation } from '@tanstack/vue-query'
import { bookingApi } from '@/features/booking/api/bookingApi'
import QRScanner from '@/features/booking/components/QRScanner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  Camera,
  ClipboardList,
  ScanSearch,
  Smartphone,
  CheckCircle2,
  Circle,
  TriangleAlert,
  BadgeCheck,
} from 'lucide-vue-next'

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
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-gray-900">Scan QR Check-in</h1>
      <p class="text-sm text-gray-500 mt-0.5">
        Scan QR Code customer untuk melakukan check-in otomatis.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Scanner -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold flex items-center gap-2">
            <Camera class="size-4 text-gray-500" />
            Kamera Scanner
          </CardTitle>
        </CardHeader>
        <CardContent class="p-5">
          <div v-if="!scanComplete">
            <QRScanner @scanned="handleScanned" />
            <p class="text-xs text-gray-400 text-center mt-3">
              Arahkan kamera ke QR Code booking customer
            </p>
          </div>

          <div v-else class="text-center py-10">
            <div
              class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center mx-auto mb-3"
            >
              <CheckCircle2 class="size-7 text-green-600" />
            </div>
            <p class="font-medium text-gray-900 font-mono text-lg">{{ scannedCode }}</p>
            <p class="text-sm text-gray-500 mt-1 mb-4">QR Code berhasil discan</p>
            <Button variant="link" size="sm" @click="resetScan">Scan ulang</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Booking Result -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold flex items-center gap-2">
            <ClipboardList class="size-4 text-gray-500" />
            Detail Booking
          </CardTitle>
        </CardHeader>
        <CardContent class="p-5">
          <!-- Loading -->
          <div v-if="isLookingUp" class="text-center py-16">
            <ScanSearch class="size-9 mx-auto text-gray-300 mb-2 animate-pulse" />
            <p class="text-sm text-gray-400">Mencari booking...</p>
          </div>

          <!-- Empty state -->
          <div v-else-if="!bookingData" class="text-center py-16">
            <Smartphone class="size-10 mx-auto text-gray-200 mb-3" />
            <p class="text-sm text-gray-400">Scan QR Code untuk melihat detail booking</p>
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

            <Separator />

            <!-- Total -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Total</span>
              <span class="font-bold text-gray-900">
                {{ formatPrice(bookingData.total_price) }}
              </span>
            </div>

            <!-- Action -->
            <div>
              <Button
                v-if="bookingData.status.value === 'approved'"
                class="w-full bg-green-600 hover:bg-green-700"
                :disabled="isPending"
                @click="checkIn()"
              >
                <CheckCircle2 class="size-4" />
                {{ isPending ? 'Memproses...' : 'Check-in Sekarang' }}
              </Button>

              <div
                v-else-if="bookingData.status.value === 'ongoing'"
                class="w-full py-3 rounded-xl font-medium text-center bg-purple-50 text-purple-700 flex items-center justify-center gap-2"
              >
                <BadgeCheck class="size-4" />
                Sudah Check-in
              </div>

              <div
                v-else-if="bookingData.status.value === 'pending'"
                class="w-full py-3 rounded-xl font-medium text-center bg-yellow-50 text-yellow-700 flex items-center justify-center gap-2"
              >
                <TriangleAlert class="size-4" />
                Booking belum diapprove
              </div>

              <div
                v-else-if="bookingData.status.value === 'completed'"
                class="w-full py-3 rounded-xl font-medium text-center bg-green-50 text-green-700 flex items-center justify-center gap-2"
              >
                <CheckCircle2 class="size-4" />
                Booking selesai
              </div>

              <div
                v-else
                class="w-full py-3 rounded-xl font-medium text-center bg-gray-100 text-gray-600 flex items-center justify-center gap-2"
              >
                <Circle class="size-4" />
                Booking tidak dapat diproses
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
