<script setup lang="ts">
import PhotoCard from './PhotoCard.vue'
import type { PhotoFile } from '../types'

defineProps<{
  files: PhotoFile[]
  selectable?: boolean
  selectedUuids?: string[]
}>()

const emit = defineEmits<{ toggle: [uuid: string] }>()
</script>

<template>
  <div v-if="!files.length" class="text-center text-gray-400 text-sm py-10">Belum ada foto.</div>
  <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    <PhotoCard
      v-for="file in files"
      :key="file.uuid"
      :file="file"
      :selectable="selectable"
      :selected="selectedUuids?.includes(file.uuid)"
      @toggle="emit('toggle', $event)"
    />
  </div>
</template>
