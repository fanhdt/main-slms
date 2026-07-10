<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { CreditCard } from 'lucide-vue-next'

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
    focusInput()
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto p-6 space-y-6">
    <div class="text-center space-y-2">
      <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mx-auto">
        <CreditCard class="size-5 text-gray-500" />
      </div>
      <h1 class="text-xl font-semibold tracking-tight text-gray-900">
        Cek Riwayat Booking via Kartu RFID
      </h1>
      <p class="text-gray-500 text-sm">Tempelkan kartu mahasiswa ke reader.</p>
    </div>

    <!-- Input tersembunyi tapi tetap fungsional -->
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

    <div v-if="isLoading" class="text-center text-gray-400 text-sm">Mencari...</div>

    <Card v-else-if="userName" class="p-0">
      <CardContent class="p-4">
        <p class="font-semibold text-gray-900 mb-3">{{ userName }}</p>
        <table class="w-full text-sm">
          <tbody class="divide-y divide-gray-100">
            <tr v-for="b in bookings" :key="b.uuid">
              <td class="py-2 font-mono text-xs">{{ b.booking_code }}</td>
              <td class="py-2">{{ b.status.label }}</td>
              <td class="py-2 text-gray-500">
                {{ new Date(b.start_time).toLocaleDateString('id-ID') }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardContent>
    </Card>
  </div>
</template>