<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'
import { markKioskSession, clearKioskSession } from '@/composables/useKioskSession'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const slug = route.params.slug as string

const rfidInput = ref('')
const inputRef = ref<HTMLInputElement | null>(null)
const isLoading = ref(false)
const welcomeMessage = ref('')
const showWelcome = ref(false)

function focusInput() {
  nextTick(() => inputRef.value?.focus())
}

onMounted(() => {
  authStore.user = null
  authStore.token = null
  localStorage.removeItem('token')
  clearKioskSession() // NEW
  focusInput()
})

async function handleScan() {
  if (!rfidInput.value) return
  isLoading.value = true
  try {
    const res = await api.post(`/labs/${slug}/rfid-login`, { rfid_uid: rfidInput.value })
    const { token, user_name } = res.data.data

    authStore.token = token
    localStorage.setItem('token', token)
    await authStore.fetchUser()

    markKioskSession(slug) // NEW — tandai sesi ini sebagai sesi kios

    welcomeMessage.value = res.data.message
    showWelcome.value = true
    toast.success(`Selamat datang, ${user_name}!`)

    setTimeout(() => {
      router.push('/my-bookings')
    }, 3000)
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Kartu tidak terdaftar.')
  } finally {
    rfidInput.value = ''
    isLoading.value = false
    if (!showWelcome.value) focusInput()
  }
}

function scanAgain() {
  showWelcome.value = false
  authStore.user = null
  authStore.token = null
  localStorage.removeItem('token')
  clearKioskSession()
  focusInput()
}
</script>

<template>
  <div
    class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center p-6"
  >
    <div class="max-w-lg w-full">
      <div v-if="!showWelcome" class="text-center space-y-6">
        <div class="text-6xl">💳</div>
        <div>
          <h1 class="text-2xl font-bold text-white">Tap Kartu Mahasiswa</h1>
          <p class="text-gray-400 mt-2">
            Lab akan otomatis dipinjam gratis kalau sedang jam kuliah
          </p>
        </div>

        <input
          ref="inputRef"
          v-model="rfidInput"
          @keyup.enter="handleScan"
          @blur="focusInput"
          type="text"
          class="w-full text-center text-2xl py-6 border-2 border-dashed border-gray-600 rounded-xl bg-gray-800 text-white focus:border-blue-500 focus:outline-none"
          placeholder="Menunggu kartu..."
          autofocus
        />

        <p v-if="isLoading" class="text-gray-400">Memproses...</p>
      </div>

      <div v-else class="text-center space-y-4 bg-white rounded-2xl p-8">
        <div class="text-5xl">✅</div>
        <h2 class="text-xl font-bold text-gray-900">{{ welcomeMessage }}</h2>
        <p class="text-gray-500 text-sm">Mengarahkan ke halaman booking kamu...</p>
        <button @click="scanAgain" class="text-sm text-blue-600 hover:underline">
          Bukan kamu? Scan ulang
        </button>
      </div>
    </div>
  </div>
</template>
