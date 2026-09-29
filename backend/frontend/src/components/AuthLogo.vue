<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg' }>(), { size: 'md' })
</script>

<template>
  <div
    class="inline-flex items-center justify-center rounded-2xl bg-[#161a2b] shadow-lg shadow-black/10 ring-1 ring-white/10"
    :class="{
      'w-11 h-11': size === 'sm',
      'w-14 h-14': size === 'md',
      'w-16 h-16': size === 'lg',
    }"
  >
    <svg viewBox="0 0 48 54" class="w-2/3 h-2/3">
      <text
        x="24"
        y="24"
        text-anchor="middle"
        fill="#e94560"
        font-size="20"
        font-weight="800"
        font-family="inherit"
      >
        S
      </text>
      <text
        x="24"
        y="41"
        text-anchor="middle"
        fill="#f2c14e"
        font-size="13"
        font-weight="800"
        font-family="inherit"
      >
        LM
      </text>
    </svg>
  </div>
</template>
