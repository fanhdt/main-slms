<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AuthFooter from '@/features/auth/components/AuthFooter.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Eye, EyeOff, MailCheck, User, Mail, Phone, Lock } from 'lucide-vue-next'

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
})
const error = ref('')
const loading = ref(false)
const registered = ref(false)
const resending = ref(false)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

async function handleRegister() {
  error.value = ''
  loading.value = true

  try {
    await api.post('/auth/register', form.value)
    registered.value = true
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

async function handleResend() {
  resending.value = true
  try {
    const res = await api.post('/auth/email/resend', { email: form.value.email })
    toast.success(res.data.message ?? 'Email verifikasi sudah dikirim ulang.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal mengirim ulang email.')
  } finally {
    resending.value = false
  }
}
</script>

<template>
  <AuthLayout
    :hide-header="registered"
    :title="registered ? undefined : 'Buat Akun Baru'"
    :subtitle="registered ? undefined : 'Daftar untuk mulai booking layanan lab'"
  >
    <!-- Success state -->
    <div v-if="registered" class="space-y-4 text-center">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
        <MailCheck :size="26" class="text-red-500" />
      </div>
      <h1 class="text-xl font-bold text-gray-900">Cek Email Kamu</h1>
      <p class="text-sm text-gray-500">
        Kami sudah kirim link verifikasi ke <b class="text-gray-700">{{ form.email }}</b
        >. Klik link itu untuk mengaktifkan akun sebelum bisa login.
      </p>

      <Button
        type="button"
        variant="outline"
        :disabled="resending"
        class="h-12 w-full"
        @click="handleResend"
      >
        {{ resending ? 'Mengirim...' : 'Kirim Ulang Email' }}
      </Button>

      <AuthFooter link-text="← Kembali ke Login" to="/login" />
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="handleRegister" class="space-y-4">
      <div
        v-if="error"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >
        {{ error }}
      </div>

      <div class="space-y-1.5">
        <Label for="register-name">Nama Lengkap<span class="text-red-500">*</span></Label>
        <div class="relative">
          <User
            :size="17"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
          />
          <Input
            id="register-name"
            v-model="form.name"
            type="text"
            placeholder="John Doe"
            class="h-12 pl-10"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="register-email">Email<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Mail
            :size="17"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
          />
          <Input
            id="register-email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            placeholder="nama@email.com"
            class="h-12 pl-10"
          />
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="register-phone">No. Telepon</Label>
        <div class="relative">
          <Phone
            :size="17"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
          />
          <Input
            id="register-phone"
            v-model="form.phone"
            type="tel"
            placeholder="08123456789"
            class="h-12 pl-10"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="register-password">Kata Sandi<span class="text-red-500">*</span></Label>
          <div class="relative">
            <Lock
              :size="16"
              class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
            />
            <Input
              id="register-password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="••••••••"
              class="h-12 pl-9 pr-11"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
            >
              <EyeOff v-if="showPassword" :size="17" />
              <Eye v-else :size="17" />
            </button>
          </div>
        </div>

        <div class="space-y-1.5">
          <Label for="register-password-confirm"
            >Konfirmasi<span class="text-red-500">*</span></Label
          >
          <div class="relative">
            <Lock
              :size="16"
              class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
            />
            <Input
              id="register-password-confirm"
              v-model="form.password_confirmation"
              :type="showPasswordConfirm ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="••••••••"
              class="h-12 pl-9 pr-11"
            />
            <button
              type="button"
              @click="showPasswordConfirm = !showPasswordConfirm"
              class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
            >
              <EyeOff v-if="showPasswordConfirm" :size="17" />
              <Eye v-else :size="17" />
            </button>
          </div>
        </div>
      </div>

      <Button type="submit" :disabled="loading" class="h-12 w-full">
        {{ loading ? 'Memproses...' : 'Daftar' }}
      </Button>

      <AuthFooter text="Sudah punya akun?" link-text="Masuk" to="/login" />
    </form>
  </AuthLayout>
</template>
