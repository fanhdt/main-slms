<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import { useAuthLayout } from '@/composables/useAuthLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Eye, EyeOff, Lock } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

useAuthLayout({
  title: 'Buat Kata Sandi Baru',
  subtitle: computed(() => `Masukkan kata sandi baru untuk ${email.value}`),
})

onMounted(() => {
  token.value = (route.query.token as string) ?? ''
  email.value = (route.query.email as string) ?? ''
})

const authStore = useAuthStore()
const isPending = ref(false)

async function submit() {
  isPending.value = true
  const result = await authStore.resetPassword({
    token: token.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
  })
  isPending.value = false

  if (result.success) {
    toast.success('Password berhasil direset. Silakan login.')
    router.push({ name: 'login' })
  } else {
    toast.error(result.message ?? 'Gagal mereset password.')
  }
}
</script>

<template>
  <form @submit.prevent="() => submit()" class="space-y-5">
    <div class="space-y-1.5">
      <Label class="text-[#1a1a2e] font-semibold text-[0.8125rem]">
        Kata Sandi Baru<span class="text-[#e94560]">*</span>
      </Label>
      <div class="relative">
        <Lock :size="17" class="field-icon" />
        <Input
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          required
          minlength="8"
          autocomplete="new-password"
          placeholder="••••••••"
          class="h-12 rounded-xl pl-10 pr-11 transition focus-visible:ring-2 focus-visible:ring-[#e94560]/20 focus-visible:border-[#e94560]"
        />
        <button
          type="button"
          @click="showPassword = !showPassword"
          class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 z-10"
        >
          <EyeOff v-if="showPassword" :size="18" />
          <Eye v-else :size="18" />
        </button>
      </div>
    </div>

    <div class="space-y-1.5">
      <Label class="text-[#1a1a2e] font-semibold text-[0.8125rem]">
        Konfirmasi Kata Sandi<span class="text-[#e94560]">*</span>
      </Label>
      <div class="relative">
        <Lock :size="17" class="field-icon" />
        <Input
          v-model="passwordConfirmation"
          :type="showPasswordConfirm ? 'text' : 'password'"
          required
          minlength="8"
          autocomplete="new-password"
          placeholder="••••••••"
          class="h-12 rounded-xl pl-10 pr-11 transition focus-visible:ring-2 focus-visible:ring-[#e94560]/20 focus-visible:border-[#e94560]"
        />
        <button
          type="button"
          @click="showPasswordConfirm = !showPasswordConfirm"
          class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 z-10"
        >
          <EyeOff v-if="showPasswordConfirm" :size="18" />
          <Eye v-else :size="18" />
        </button>
      </div>
    </div>

    <Button
      type="submit"
      :disabled="isPending || !token"
      class="w-full h-12 rounded-xl text-white font-bold shadow-lg shadow-[#e94560]/30 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[#e94560]/40 disabled:opacity-60"
      style="background: linear-gradient(135deg, #ff6b81 0%, #e94560 55%, #b5223f 100%)"
    >
      {{ isPending ? 'Memproses...' : 'Reset Kata Sandi' }}
    </Button>
  </form>
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
