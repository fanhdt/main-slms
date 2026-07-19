<script setup lang="ts">
import type { PhotoFile } from '../types'
import { Trash2, Expand } from 'lucide-vue-next'

defineProps<{
  file: PhotoFile
  selectable?: boolean
  selected?: boolean
  deletable?: boolean
}>()

const emit = defineEmits<{
  toggle: [uuid: string]
  delete: [uuid: string]
  open: [file: PhotoFile]
}>()
</script>

<template>
  <div
    class="relative rounded-xl overflow-hidden border-2 transition-colors group"
    :class="[
      selected ? 'border-blue-500' : 'border-transparent',
      selectable ? 'cursor-pointer' : '',
    ]"
    @click="selectable ? emit('toggle', file.uuid) : emit('open', file)"
  >
    <img
      :src="file.url"
      :alt="file.filename"
      class="w-full aspect-square object-cover"
      loading="lazy"
    />

    <div
      v-if="selectable"
      class="absolute top-2 right-2 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold z-10"
      :class="selected ? 'bg-blue-500 text-white' : 'bg-white/80 text-gray-400'"
    >
      <span v-if="selected">✓</span>
    </div>

    <!-- Expand (lightbox) — selalu ada, terpisah dari toggle select -->
    <button
      type="button"
      title="Lihat penuh"
      class="absolute top-2 left-2 p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-gray-900 shadow opacity-0 group-hover:opacity-100 transition-opacity z-10"
      @click.stop="emit('open', file)"
    >
      <Expand class="size-3.5" />
    </button>

    <!-- Delete — hanya kalau deletable -->
    <button
      v-if="deletable"
      type="button"
      title="Hapus foto"
      class="absolute p-1.5 rounded-md bg-white/90 text-gray-500 hover:text-red-600 shadow opacity-0 group-hover:opacity-100 transition-opacity z-10"
      :class="selectable ? 'top-9 right-2' : 'top-2 right-2'"
      @click.stop="emit('delete', file.uuid)"
    >
      <Trash2 class="size-3.5" />
    </button>

    <div
      class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-[11px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity truncate"
    >
      {{ file.filename }}
    </div>
  </div>
</template>