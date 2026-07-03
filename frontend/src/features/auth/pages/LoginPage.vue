<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')

async function handleLogin() {
  error.value = ''

  if (!email.value || !password.value) {
    error.value = 'Email dan password wajib diisi.'
    return
  }

  const result = await authStore.login(email.value, password.value)

  if (result.success) {
    toast.success('Login berhasil!')

    // Redirect berdasarkan role
    if (authStore.hasRole('super_admin')) {
      router.push({ name: 'admin-dashboard' })
    } else if (authStore.hasRole('customer')) {
      router.push({ name: 'booking' })
    } else {
      // Lab staff — cek jumlah lab
      const labStore = useLabStore()
      await labStore.fetchLabs()

      if (labStore.labs.length === 1) {
        // Langsung masuk ke satu-satunya lab
        router.push(`/dashboard/lab/${labStore.labs[0].slug}`)
      } else {
        // Punya lebih dari 1 lab — ke selector
        router.push({ name: 'dashboard' })
      }
    }
  } else {
    error.value = result.message ?? 'Login gagal.'
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">SLMS</h1>
          <p class="text-gray-500 mt-1 text-sm">Smart Lab Management System</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Error -->
          <div
            v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"
          >
            {{ error }}
          </div>

          <!-- Email -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="email"
              type="email"
              placeholder="admin@slms.local"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Password -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="password"
              type="password"
              placeholder="••••••••"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="authStore.loading"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors"
          >
            {{ authStore.loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
