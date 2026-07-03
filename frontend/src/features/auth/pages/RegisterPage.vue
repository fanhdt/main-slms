<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
})
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  loading.value = true

  try {
    const response = await api.post('/auth/register', form.value)
    const { user, token } = response.data.data

    authStore.user = user
    authStore.token = token
    localStorage.setItem('token', token)

    toast.success('Registrasi berhasil!')
    router.push({ name: 'dashboard' })
  } catch (err: any) {
    const errors = err.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(', ')
    } else {
      error.value = err.response?.data?.message ?? 'Registrasi gagal.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Daftar Akun</h1>
          <p class="text-gray-500 mt-1 text-sm">Buat akun baru untuk mengakses SLMS</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleRegister" class="space-y-4">
          <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"
          >
            {{ error }}
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="John Doe"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="form.email"
              type="email"
              placeholder="john@example.com"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">No. Telepon</label>
            <input
              v-model="form.phone"
              type="tel"
              placeholder="08123456789"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Konfirmasi Password</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors"
          >
            {{ loading ? 'Memproses...' : 'Daftar' }}
          </button>

          <p class="text-center text-sm text-gray-500">
            Sudah punya akun?
            <RouterLink to="/login" class="text-blue-600 hover:underline">Masuk</RouterLink>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
