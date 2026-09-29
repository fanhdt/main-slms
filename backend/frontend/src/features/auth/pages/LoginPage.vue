<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { consumePendingBookingRedirect } from '@/composables/usePendingBookingRedirect'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AuthFooter from '@/features/auth/components/AuthFooter.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Eye, EyeOff, Mail, Lock } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const showPassword = ref(false)
const rememberMe = ref(true)

async function handleLogin() {
  error.value = ''

  if (!email.value || !password.value) {
    error.value = 'Email dan password wajib diisi.'
    return
  }

  const result = await authStore.login(email.value, password.value)

  if (result.success) {
    toast.success('Login berhasil!')

    // Kalau user datang dari landing page dengan jadwal booking yang sudah
    // dipilih (misal lewat kalender ketersediaan), lanjutkan ke sana dulu
    // sebelum alur redirect berbasis role yang biasa.
    const pendingBooking = consumePendingBookingRedirect()
    if (pendingBooking) {
      router.push(pendingBooking)
      return
    }

    if (authStore.hasRole('super_admin')) {
      router.push({ name: 'admin-dashboard' })
    } else if (authStore.hasRole('customer')) {
      router.push({ name: 'booking' })
    } else {
      const labStore = useLabStore()
      await labStore.fetchManagedLabs()

      if (labStore.managedLabs.length === 1) {
        router.push(`/dashboard/lab/${labStore.managedLabs[0]?.slug ?? ''}`)
      } else {
        router.push({ name: 'dashboard' })
      }
    }
  } else {
    error.value = result.message ?? 'Login gagal.'
  }
}
</script>

<template>
  <AuthLayout title="Selamat Datang!" subtitle="Masukkan email dan kata sandi untuk masuk">
    <form @submit.prevent="handleLogin" class="space-y-5">
      <div
        v-if="error"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >
        {{ error }}
      </div>

      <!-- Email -->
      <div class="space-y-1.5">
        <Label for="login-email">Email<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Mail :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            id="login-email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="nama@email.com"
            class="h-12 pl-10"
          />
        </div>
      </div>

      <!-- Password -->
      <div class="space-y-1.5">
        <Label for="login-password">Kata sandi<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Lock :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            id="login-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="••••••••••••"
            class="h-12 pl-10 pr-11"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-3 flex items-center text-gray-400 transition-colors hover:text-gray-600"
            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
          >
            <EyeOff v-if="showPassword" :size="18" />
            <Eye v-else :size="18" />
          </button>
        </div>
      </div>

      <!-- Remember + forgot -->
      <div class="flex items-center justify-between text-sm">
        <label class="flex cursor-pointer select-none items-center gap-2 text-gray-700">
          <input
            v-model="rememberMe"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 accent-gray-900"
          />
          Ingat saya
        </label>
        <RouterLink to="/forgot-password" class="font-medium text-gray-900 hover:underline">
          Lupa kata sandi?
        </RouterLink>
      </div>

      <!-- Submit -->
      <Button type="submit" :disabled="authStore.loading" class="h-12 w-full">
        {{ authStore.loading ? 'Memproses...' : 'Masuk' }}
      </Button>

      <!-- Divider -->
      <div class="flex items-center gap-3 py-1">
        <Separator class="flex-1" />
        <span class="text-xs text-gray-400">atau</span>
        <Separator class="flex-1" />
      </div>

      <!-- Google (visual only) -->
      <Button type="button" variant="outline" class="h-12 w-full gap-2.5">
        <svg viewBox="0 0 48 48" class="h-4 w-4 shrink-0">
          <path
            fill="#FFC107"
            d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"
          />
          <path
            fill="#FF3D00"
            d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"
          />
          <path
            fill="#4CAF50"
            d="M24 44c5.5 0 10.4-2.1 14.1-5.6l-6.5-5.5C29.6 34.7 26.9 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.6 39.6 16.3 44 24 44z"
          />
          <path
            fill="#1976D2"
            d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.5 5.5C40.4 36.4 44 30.9 44 24c0-1.3-.1-2.7-.4-3.5z"
          />
        </svg>
        Masuk dengan Google
      </Button>

      <AuthFooter text="Tidak punya akun?" link-text="Daftar" to="/register" />
    </form>
  </AuthLayout>
</template>