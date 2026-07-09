<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AuthFooter from '@/features/auth/components/AuthFooter.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
      <div
        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
        style="background: rgba(233, 69, 96, 0.1)"
      >
        <MailCheck :size="26" style="color: #e94560" />
      </div>
      <h1 class="text-xl font-extrabold text-[#14162a]">Cek Email Kamu</h1>
      <p class="text-sm text-gray-500">
        Kalau email <b class="text-gray-700">{{ email }}</b> terdaftar, link reset kata sandi sudah
        dikirim. Cek inbox kamu.
      </p>

      <AuthFooter link-text="← Kembali ke Login" to="/login" />
    </div>

    <form v-else @submit.prevent="() => submit()" class="space-y-5">
      <div class="space-y-1.5">
        <Label class="text-[#1a1a2e] font-semibold text-[0.8125rem]">
          Email<span class="text-[#e94560]">*</span>
        </Label>
        <div class="relative">
          <Mail :size="17" class="field-icon" />
          <Input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="nama@email.com"
            class="h-12 rounded-xl pl-10 transition focus-visible:ring-2 focus-visible:ring-[#e94560]/20 focus-visible:border-[#e94560]"
          />
        </div>
      </div>

      <Button
        type="submit"
        :disabled="isPending"
        class="w-full h-12 rounded-xl text-white font-bold shadow-lg shadow-[#e94560]/30 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#e94560]/40 disabled:opacity-60"
        style="background: linear-gradient(135deg, #ff6b81 0%, #e94560 55%, #b5223f 100%)"
      >
        {{ isPending ? 'Mengirim...' : 'Kirim Link Reset' }}
      </Button>

      <AuthFooter link-text="← Kembali ke Login" to="/login" />
    </form>
  </AuthLayout>
</template>

<style scoped>
.field-icon {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  color: #b7b8c6;
  pointer-events: none;
  z-index: 10;
}
</style>