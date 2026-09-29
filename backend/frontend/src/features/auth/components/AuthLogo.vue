<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })
</script>

<template>
  <div class="flex items-center justify-center gap-3">
    <img
      src="/images/logo-upi.svg"
      alt="Logo UPI"
      class="object-contain"
      :class="{
        'h-9 w-9': size === 'sm',
        'h-11 w-11': size === 'md',
        'h-13 w-13': size === 'lg',
      }"
    />
    <span class="h-7 w-px bg-gray-200" aria-hidden="true" />
    <img
      src="/images/logo-lab.svg"
      alt="Logo Aplikasi"
      class="object-contain"
      :class="{
        'h-9 w-9': size === 'sm',
        'h-11 w-11': size === 'md',
        'h-13 w-13': size === 'lg',
      }"
    />
  </div>
</template>
