<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { CreditCard } from 'lucide-vue-next'

const route = useRoute()
const labSlug = route.params.labSlug as string

const rfidInput = ref('')
const inputRef = ref<HTMLInputElement | null>(null)
const isLoading = ref(false)
const result = ref<{ user_name: string; bookings: any[]; message: string } | null>(null)

function focusInput() {
  nextTick(() => inputRef.value?.focus())
}

onMounted(focusInput)

async function handleScan() {
  if (!rfidInput.value) return
  isLoading.value = true
  try {
    const res = await api.post(`/labs/${labSlug}/rfid-login`, { rfid_uid: rfidInput.value })
    result.value = {
      user_name: res.data.data.user_name,
      bookings: res.data.data.bookings,
      message: res.data.message,
    }
    toast.success(res.data.message)
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Kartu tidak terdaftar.')
    result.value = null
  } finally {
    rfidInput.value = ''
    isLoading.value = false
    focusInput()
  }
}

const STATUS_STYLES: Record<string, string> = {
  pending: 'bg-yellow-50 text-yellow-700',
  approved: 'bg-blue-50 text-blue-700',
  ongoing: 'bg-purple-50 text-purple-700',
  completed: 'bg-green-50 text-green-700',
}
function statusColor(status: string) {
  return STATUS_STYLES[status] ?? 'bg-gray-100 text-gray-600'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="max-w-lg w-full space-y-6">
      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-xl bg-white border border-gray-200 flex items-center justify-center mx-auto">
          <CreditCard class="size-5 text-gray-500" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Tap Kartu Mahasiswa</h1>
        <p class="text-gray-500 text-sm">
          Lab ini akan otomatis dipinjam gratis kalau sedang jam kuliah.
        </p>
      </div>

      <input
        ref="inputRef"
        v-model="rfidInput"
        @keyup.enter="handleScan"
        @blur="focusInput"
        type="text"
        class="w-full text-center text-2xl py-6 border-2 border-dashed border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none bg-white"
        placeholder="Menunggu kartu..."
        autofocus
      />

      <div v-if="isLoading" class="text-center text-gray-400 text-sm">Memproses...</div>

      <Card v-else-if="result" class="p-0">
        <CardContent class="p-5 space-y-3">
          <p class="font-semibold text-gray-900">{{ result.user_name }}</p>
          <p class="text-sm text-gray-500">{{ result.message }}</p>

          <div v-if="result.bookings.length" class="space-y-2 pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 uppercase">Riwayat Booking Lab Ini</p>
            <div
              v-for="b in result.bookings"
              :key="b.uuid"
              class="flex items-center justify-between text-sm py-1.5"
            >
              <span class="font-mono text-xs">{{ b.booking_code }}</span>
              <Badge variant="outline" class="border-0" :class="statusColor(b.status.value)">
                {{ b.status.label }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>