<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'

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

function statusColor(status: string) {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    ongoing: 'bg-purple-100 text-purple-700',
    completed: 'bg-green-100 text-green-700',
  }
  return colors[status] ?? 'bg-gray-100 text-gray-600'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="max-w-lg w-full space-y-6">
      <div class="text-center">
        <h2 class="text-xl font-bold text-gray-900">Tap Kartu Mahasiswa</h2>
        <p class="text-gray-500 text-sm mt-1">
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

      <div v-if="isLoading" class="text-center text-gray-400">Memproses...</div>

      <div v-else-if="result" class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
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
            <span
              class="px-2 py-0.5 rounded-full text-xs font-medium"
              :class="statusColor(b.status.value)"
            >
              {{ b.status.label }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
