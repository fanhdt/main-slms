<script setup lang="ts">
import { ref } from 'vue'

defineProps<{ disabled?: boolean }>()
const emit = defineEmits<{ upload: [files: File[]] }>()

const isDragging = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

function handleDrop(e: DragEvent) {
  isDragging.value = false
  const files = Array.from(e.dataTransfer?.files ?? [])
  if (files.length) emit('upload', files)
}

function handleSelect(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (files.length) emit('upload', files)
  input.value = ''
}
</script>

<template>
  <div
    class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
    :class="[
      isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
      disabled ? 'opacity-50 pointer-events-none' : 'cursor-pointer',
    ]"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="handleDrop"
    @click="inputRef?.click()"
  >
    <input
      ref="inputRef"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      multiple
      class="hidden"
      @change="handleSelect"
    />
    <div class="text-3xl mb-2">📁</div>
    <p class="text-sm text-gray-600 font-medium">Drag & drop foto di sini, atau klik untuk pilih</p>
    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — maks 20MB per file</p>
  </div>
</template>
