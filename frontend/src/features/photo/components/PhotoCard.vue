<script setup lang="ts">
import type { PhotoFile } from '../types'
import { Trash2, Expand } from 'lucide-vue-next'

defineProps<{
  file: PhotoFile
  selectable?: boolean
  selected?: boolean
}>()

const emit = defineEmits<{ toggle: [uuid: string] }>()
</script>

<template>
  <div
    class="relative rounded-xl overflow-hidden border-2 transition-colors group"
    :class="[
      selected ? 'border-blue-500' : 'border-transparent',
      selectable ? 'cursor-pointer' : '',
    ]"
    @click="selectable && emit('toggle', file.uuid)"
  >
    <img :src="file.url" :alt="file.filename" class="w-full h-40 object-cover" loading="lazy" />

    <div
      v-if="selectable"
      class="absolute top-2 right-2 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
      :class="selected ? 'bg-blue-500 text-white' : 'bg-white/80 text-gray-400'"
    >
      <span v-if="selected">✓</span>
    </div>

    <div
      class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-[11px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity truncate"
    >
      {{ file.filename }}
    </div>
  </div>
</template>
