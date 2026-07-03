<script setup lang="ts">
defineProps<{ status: { value: string; label: string } }>()

const colors: Record<string, string> = {
  pending: 'bg-gray-100 text-gray-600',
  preview_uploaded: 'bg-blue-100 text-blue-700',
  selection: 'bg-yellow-100 text-yellow-700',
  editing: 'bg-purple-100 text-purple-700',
  approval: 'bg-orange-100 text-orange-700',
  delivered: 'bg-green-100 text-green-700',
  expired: 'bg-red-100 text-red-700',
}
</script>

<template>
  <span
    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
    :class="colors[status.value] ?? 'bg-gray-100 text-gray-600'"
  >
    {{ status.label }}
  </span>
</template>
