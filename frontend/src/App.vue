<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useNotifications } from '@/composables/useNotifications'
import { Toaster } from '@/components/ui/sonner'

const authStore = useAuthStore()
const { subscribeRealtime, unsubscribeRealtime } = useNotifications()


onMounted(() => {
  if (authStore.isAuthenticated) {
    subscribeRealtime()
  }
})


watch(
  () => authStore.isAuthenticated,
  (isAuth) => {
    if (isAuth) {
      subscribeRealtime()
    } else {
      unsubscribeRealtime()
    }
  },
)

onUnmounted(() => {
  unsubscribeRealtime()
})
</script>

<template>
  <RouterView />
  <Toaster position="top-right" richColors />
</template>
