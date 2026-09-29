<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AuthFooter from '@/features/auth/components/AuthFooter.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { MailCheck, Mail } from 'lucide-vue-next'

const authStore = useAuthStore()
const email = ref('')
const sent = ref(false)
const isPending = ref(false)

async function submit() {
  isPending.value = true
  const result = await authStore.forgotPassword(email.value)
  isPending.value = false

  if (result.success) {
    sent.value = true
  } else {
    toast.error(result.message ?? 'Terjadi kesalahan. Coba lagi.')
  }
}
</script>

<template>
  <AuthLayout
    :hide-header="sent"
    :title="sent ? undefined : 'Lupa Kata Sandi'"
    :subtitle="sent ? undefined : 'Masukkan email kamu, kami kirim link reset kata sandi'"
  >
    <div v-if="sent" class="space-y-4 text-center">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50">
        <MailCheck :size="26" class="text-red-500" />
      </div>
      <h1 class="text-xl font-bold text-gray-900">Cek Email Kamu</h1>
      <p class="text-sm text-gray-500">
        Kalau email <b class="text-gray-700">{{ email }}</b> terdaftar, link reset kata sandi
        sudah dikirim. Cek inbox kamu.
      </p>

      <AuthFooter link-text="← Kembali ke Login" to="/login" />
    </div>

    <form v-else @submit.prevent="() => submit()" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="forgot-email">Email<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Mail :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            id="forgot-email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="nama@email.com"
            class="h-12 pl-10"
          />
        </div>
      </div>

      <Button type="submit" :disabled="isPending" class="h-12 w-full">
        {{ isPending ? 'Mengirim...' : 'Kirim Link Reset' }}
      </Button>

      <AuthFooter link-text="← Kembali ke Login" to="/login" />
    </form>
  </AuthLayout>
</template>