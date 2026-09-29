<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card'

withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    hideHeader?: boolean
  }>(),
  { hideHeader: false },
)
</script>

<template>
  <Card class="w-full max-w-xl rounded-3xl border-gray-100 pt-16 pb-24 shadow-sm">
    <CardHeader v-if="!hideHeader && title" class="gap-2 px-10 pb-4 xl:px-12">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ title }}</h1>
      <p v-if="subtitle" class="text-base leading-relaxed text-gray-500">{{ subtitle }}</p>
    </CardHeader>
    <CardContent class="px-10 xl:px-12">
      <slot />
    </CardContent>
  </Card>
</template>
