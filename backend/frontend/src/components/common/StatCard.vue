<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'

defineProps<{
  label: string
  value: string | number
  hint?: string
}>()
</script>

<template>
  <Card class="p-0">
    <CardContent class="px-4 py-3">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="text-xs font-medium truncate" style="color: var(--text-muted)">{{ label }}</p>
          <p class="text-lg font-semibold mt-0.5 truncate" style="color: var(--text-primary)">
            {{ value }}
          </p>
          <p v-if="hint" class="text-xs mt-0.5 truncate" style="color: var(--text-secondary)">
            {{ hint }}
          </p>
        </div>
        <div v-if="$slots.icon" class="shrink-0 mt-0.5" style="color: var(--text-muted)">
          <slot name="icon" />
        </div>
      </div>
    </CardContent>
  </Card>
</template>
