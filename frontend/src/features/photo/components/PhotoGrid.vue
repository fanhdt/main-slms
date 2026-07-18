<script setup lang="ts">
import { ref, computed } from 'vue'
import PhotoCard from './PhotoCard.vue'
import type { PhotoFile } from '../types'
import { X, ChevronLeft, ChevronRight, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  files: PhotoFile[]
  selectable?: boolean
  selectedUuids?: string[]
  deletable?: boolean
}>()

const emit = defineEmits<{ toggle: [uuid: string]; delete: [uuid: string] }>()

const lightboxIndex = ref<number | null>(null)

const currentFile = computed(() =>
  lightboxIndex.value !== null ? props.files[lightboxIndex.value] : null,
)

function openLightbox(file: PhotoFile) {
  lightboxIndex.value = props.files.findIndex((f) => f.uuid === file.uuid)
}

function closeLightbox() {
  lightboxIndex.value = null
}

function next() {
  if (lightboxIndex.value === null || !props.files.length) return
  lightboxIndex.value = (lightboxIndex.value + 1) % props.files.length
}

function prev() {
  if (lightboxIndex.value === null || !props.files.length) return
  lightboxIndex.value = (lightboxIndex.value - 1 + props.files.length) % props.files.length
}

function handleDelete(uuid: string) {
  emit('delete', uuid)
  // Kalau foto yang lagi dibuka di lightbox itu yang dihapus, tutup lightbox
  if (currentFile.value?.uuid === uuid) closeLightbox()
}
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
      :deletable="deletable"
      @toggle="emit('toggle', $event)"
      @delete="handleDelete"
      @open="openLightbox"
    />
  </div>

  <!-- Lightbox -->
  <Teleport to="body">
    <div
      v-if="currentFile"
      class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
      @click.self="closeLightbox"
    >
      <button
        type="button"
        title="Tutup"
        class="absolute top-4 right-4 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
        @click="closeLightbox"
      >
        <X class="size-5" />
      </button>

      <button
        v-if="files.length > 1"
        type="button"
        title="Sebelumnya"
        class="absolute left-4 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
        @click="prev"
      >
        <ChevronLeft class="size-6" />
      </button>

      <button
        v-if="files.length > 1"
        type="button"
        title="Selanjutnya"
        class="absolute right-4 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
        @click="next"
      >
        <ChevronRight class="size-6" />
      </button>

      <div class="max-w-4xl max-h-[85vh] flex flex-col items-center gap-3">
        <img
          :src="currentFile.url"
          :alt="currentFile.filename"
          class="max-w-full max-h-[75vh] object-contain rounded-lg"
        />
        <div class="flex items-center gap-3 text-white/80 text-sm">
          <span class="truncate max-w-xs">{{ currentFile.filename }}</span>
          <button
            v-if="deletable"
            type="button"
            class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-300 transition-colors"
            @click="handleDelete(currentFile.uuid)"
          >
            <Trash2 class="size-3.5" />
            Hapus
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
