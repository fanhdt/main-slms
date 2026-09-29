<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { useStatusBadge } from '@/composables/useStatusBadge'
import type { EnumField } from '@/types'

const props = defineProps<{
  status: EnumField | string | undefined
  type: 'booking' | 'payment' | 'photo' | 'asset'
}>()

const { bookingStatusStyle, paymentStatusStyle, photoStatusStyle, assetStatusStyle } =
  useStatusBadge()

const style = computed(() => {
  switch (props.type) {
    case 'payment':
      return paymentStatusStyle(props.status)
    case 'photo':
      return photoStatusStyle(props.status)
    case 'asset':
      return assetStatusStyle(props.status)
    default:
      return bookingStatusStyle(props.status)
  }
})

const label = computed(() => {
  const value = typeof props.status === 'string' ? props.status : props.status?.value
  const display = typeof props.status === 'string' ? undefined : props.status?.label
  return display ?? value ?? '-'
})
</script>

<template>
  <Badge
    variant="outline"
    class="border-0 font-medium capitalize"
    :style="{ backgroundColor: style.bg, color: style.text }"
  >
    {{ label }}
  </Badge>
</template>
