<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Button } from '@/components/ui/button'
import { CircleCheckBig, CircleAlert } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const status = computed(() => route.query.status as string)
const isSuccess = computed(() => status.value === 'success')
</script>

<template>
  <AuthLayout hide-header>
    <div class="space-y-4 text-center">
      <div
        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
        :class="isSuccess ? 'bg-green-50' : 'bg-red-50'"
      >
        <CircleCheckBig v-if="isSuccess" :size="26" class="text-green-500" />
        <CircleAlert v-else :size="26" class="text-red-500" />
      </div>

      <h1 class="text-xl font-bold text-gray-900">
        {{ isSuccess ? 'Email Berhasil Diverifikasi!' : 'Link Tidak Valid' }}
      </h1>

      <p class="text-sm text-gray-500">
        {{
          isSuccess
            ? 'Akun kamu sudah aktif. Silakan login untuk melanjutkan.'
            : 'Link verifikasi ini tidak valid atau sudah kadaluarsa.'
        }}
      </p>

      <Button class="h-12 w-full" @click="router.push({ name: 'login' })">
        {{ isSuccess ? 'Ke Halaman Login' : 'Kembali ke Login' }}
      </Button>
    </div>
  </AuthLayout>
</template>
