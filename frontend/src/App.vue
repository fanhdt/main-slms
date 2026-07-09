<script setup lang="ts">
import { RouterView } from 'vue-router'
import { Toaster } from '@/components/ui/sonner'
import { useKioskIdleWatcher } from '@/composables/useKioskSession'

useKioskIdleWatcher()
</script>

<template>
  <RouterView />
  <Toaster position="top-right" rich-colors />
</template>
