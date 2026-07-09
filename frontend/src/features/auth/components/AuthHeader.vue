<script setup lang="ts">
defineProps<{ title?: string; subtitle?: string }>()
</script>

<template>
  <div class="flex flex-col gap-4 mb-8">
    <slot name="logo" />
    <div v-if="title" class="space-y-1.5">
      <h1 class="text-2xl sm:text-[1.75rem] font-extrabold text-[#14162a] tracking-tight">
        {{ title }}
      </h1>
      <p v-if="subtitle" class="text-sm text-gray-500 leading-relaxed">
        {{ subtitle }}
      </p>
    </div>
  </div>
</template>
