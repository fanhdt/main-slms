<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

onMounted(async () => {
  const token = route.query.token as string | undefined
  const error = route.query.error as string | undefined

  if (error) {
    toast.error(error === 'inactive' ? 'Akun kamu tidak aktif.' : 'Login Google gagal.')
    router.push({ name: 'login' })
    return
  }

  if (!token) {
    router.push({ name: 'login' })
    return
  }

  authStore.token = token
  localStorage.setItem('token', token)
  await authStore.fetchUser()
  toast.success('Login berhasil!')

  if (authStore.hasRole('super_admin')) {
    router.push({ name: 'admin-dashboard' })
  } else if (authStore.hasRole('customer')) {
    router.push({ name: 'booking' })
  } else {
    const labStore = useLabStore()
    await labStore.fetchManagedLabs()
    router.push(
      labStore.managedLabs.length === 1
        ? `/dashboard/lab/${labStore.managedLabs[0]?.slug ?? ''}`
        : { name: 'dashboard' },
    )
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center">
    <p class="text-gray-500 text-sm">Memproses login...</p>
  </div>
</template>
