<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Eye, EyeOff, Lock } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

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
  <AuthLayout title="Buat Kata Sandi Baru" :subtitle="`Masukkan kata sandi baru untuk ${email}`">
    <form @submit.prevent="() => submit()" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="reset-password">Kata Sandi Baru<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Lock :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            id="reset-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            required
            minlength="8"
            autocomplete="new-password"
            placeholder="••••••••"
            class="h-12 pl-10 pr-11"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
          >
            <EyeOff v-if="showPassword" :size="18" />
            <Eye v-else :size="18" />
          </button>
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="reset-password-confirm">Konfirmasi Kata Sandi<span class="text-red-500">*</span></Label>
        <div class="relative">
          <Lock :size="17" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <Input
            id="reset-password-confirm"
            v-model="passwordConfirmation"
            :type="showPasswordConfirm ? 'text' : 'password'"
            required
            minlength="8"
            autocomplete="new-password"
            placeholder="••••••••"
            class="h-12 pl-10 pr-11"
          />
          <button
            type="button"
            @click="showPasswordConfirm = !showPasswordConfirm"
            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
          >
            <EyeOff v-if="showPasswordConfirm" :size="18" />
            <Eye v-else :size="18" />
          </button>
        </div>
      </div>

      <Button type="submit" :disabled="isPending || !token" class="h-12 w-full">
        {{ isPending ? 'Memproses...' : 'Reset Kata Sandi' }}
      </Button>
    </form>
  </AuthLayout>
</template>