<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'

const rfidInput = ref('')
const inputRef = ref<HTMLInputElement | null>(null)
const bookings = ref<any[]>([])
const userName = ref('')
const isLoading = ref(false)

function focusInput() {
  nextTick(() => inputRef.value?.focus())
}

onMounted(focusInput)

async function handleScan() {
  if (!rfidInput.value) return
  isLoading.value = true
  try {
    const res = await api.get(`/users/rfid/${rfidInput.value}/bookings`)
    userName.value = res.data.data.user_name
    bookings.value = res.data.data.bookings
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Kartu tidak terdaftar.')
    bookings.value = []
    userName.value = ''
  } finally {
    rfidInput.value = ''
    isLoading.value = false
    focusInput() // auto-refocus biar siap scan kartu berikutnya
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-6 space-y-6">
    <div class="text-center">
      <h2 class="text-xl font-bold text-gray-900">Cek Riwayat Booking via Kartu RFID</h2>
      <p class="text-gray-500 text-sm mt-1">Tempelkan kartu mahasiswa ke reader.</p>
    </div>

    <!-- Input tersembunyi tapi tetap fungsional, biar tidak terlihat seperti "ngetik manual" -->
    <input
      ref="inputRef"
      v-model="rfidInput"
      @keyup.enter="handleScan"
      @blur="focusInput"
      type="text"
      class="w-full text-center text-2xl py-4 border-2 border-dashed border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none"
      placeholder="Menunggu kartu..."
      autofocus
    />

    <div v-if="isLoading" class="text-center text-gray-400">Mencari...</div>

    <div v-else-if="userName" class="bg-white rounded-xl border border-gray-200 p-4">
      <p class="font-semibold text-gray-900 mb-3">{{ userName }}</p>
      <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-100">
          <tr v-for="b in bookings" :key="b.uuid">
            <td class="py-2 font-mono text-xs">{{ b.booking_code }}</td>
            <td class="py-2">{{ b.status.label }}</td>
            <td class="py-2 text-gray-500">{{ new Date(b.start_time).toLocaleDateString('id-ID') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>