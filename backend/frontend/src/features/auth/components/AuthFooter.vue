<script setup lang="ts">
defineProps<{
  text?: string
  linkText?: string
  to?: string
}>()
</script>

<template>
  <p class="text-center text-sm text-gray-500 mt-2">
    <template v-if="text">{{ text }} </template>
    <RouterLink
      v-if="to && linkText"
      :to="to"
      class="font-semibold text-[#e94560] hover:underline transition-colors"
    >
      {{ linkText }}
    </RouterLink>
    <slot />
  </p>
</template>
