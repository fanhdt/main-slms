<script setup lang="ts">
import { ref, computed } from 'vue'
import { Loader2, Upload, ImageIcon, X, RefreshCw, TriangleAlert } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = withDefaults(
  defineProps<{
    modelValue?: string | null // current preview URL
    loading?: boolean
    disabled?: boolean
    accept?: string
    maxSizeMb?: number
    aspect?: 'square' | 'video' | 'auto'
    shape?: 'rounded' | 'circle'
    label?: string
  }>(),
  {
    modelValue: null,
    loading: false,
    disabled: false,
    accept: 'image/jpeg,image/png,image/webp',
    maxSizeMb: 5,
    aspect: 'auto',
    shape: 'rounded',
    label: 'Upload Image',
  },
)

const emit = defineEmits<{
  select: [file: File]
  remove: []
}>()

const inputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const errorMessage = ref<string | null>(null)

const ALLOWED_EXT = computed(() =>
  props.accept.split(',').map((t) => t.trim().split('/')[1]?.toUpperCase()),
)

const aspectClass = computed(() => {
  if (props.shape === 'circle') return 'aspect-square rounded-full'
  if (props.aspect === 'square') return 'aspect-square rounded-xl'
  if (props.aspect === 'video') return 'aspect-video rounded-xl'
  return 'h-40 rounded-xl'
})

function validate(file: File): string | null {
  const allowedTypes = props.accept.split(',').map((t) => t.trim())
  if (!allowedTypes.includes(file.type)) {
    return `Format harus salah satu dari: ${ALLOWED_EXT.value.join(', ')}.`
  }
  const maxBytes = props.maxSizeMb * 1024 * 1024
  if (file.size > maxBytes) {
    return `Ukuran file maksimal ${props.maxSizeMb}MB.`
  }
  return null
}

function handleFile(file: File | undefined | null) {
  if (!file || props.disabled || props.loading) return
  const error = validate(file)
  if (error) {
    errorMessage.value = error
    return
  }
  errorMessage.value = null
  emit('select', file)
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  if (props.disabled || props.loading) return
  handleFile(e.dataTransfer?.files?.[0])
}

function onSelect(e: Event) {
  const input = e.target as HTMLInputElement
  handleFile(input.files?.[0])
  input.value = ''
}

function openPicker() {
  if (props.disabled || props.loading) return
  inputRef.value?.click()
}

function remove() {
  if (props.disabled || props.loading) return
  errorMessage.value = null
  emit('remove')
}
</script>

<template>
  <div class="space-y-2">
    <p v-if="label" class="text-sm font-medium text-gray-700">{{ label }}</p>

    <!-- Has image: preview + actions -->
    <div v-if="modelValue && !loading" class="flex items-start gap-3">
      <div
        class="relative shrink-0 overflow-hidden bg-gray-100 border border-gray-200"
        :class="[shape === 'circle' ? 'w-20 h-20 rounded-full' : 'w-28 aspect-video rounded-xl']"
      >
        <img :src="modelValue" alt="Preview" class="w-full h-full object-cover" />
      </div>
      <div class="flex flex-col gap-1.5">
        <Button type="button" variant="outline" size="sm" :disabled="disabled" @click="openPicker">
          <RefreshCw class="size-3.5" />
          Ganti
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          class="text-red-600 hover:text-red-700 hover:bg-red-50"
          :disabled="disabled"
          @click="remove"
        >
          <X class="size-3.5" />
          Hapus
        </Button>
      </div>
    </div>

    <!-- Loading state -->
    <div
      v-else-if="loading"
      class="w-full flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 bg-gray-50 text-gray-400"
      :class="aspectClass"
    >
      <Loader2 class="size-6 animate-spin" />
      <span class="text-xs font-medium">Mengupload...</span>
    </div>

    <!-- Empty: dropzone -->
    <div
      v-else
      class="w-full flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-xl transition-colors cursor-pointer select-none"
      :class="[
        aspectClass,
        isDragging
          ? 'border-blue-500 bg-blue-50'
          : 'border-gray-300 hover:border-gray-400 bg-gray-50/50',
        disabled ? 'opacity-50 pointer-events-none' : '',
      ]"
      @click="openPicker"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
    >
      <ImageIcon class="size-6 text-gray-300" />
      <div class="text-center px-4">
        <p class="text-xs font-medium text-gray-600">
          <span class="text-blue-600">Klik untuk upload</span> atau drag & drop
        </p>
        <p class="text-[11px] text-gray-400 mt-0.5">
          {{ ALLOWED_EXT.join(', ') }} — maks {{ maxSizeMb }}MB
        </p>
      </div>
    </div>

    <p v-if="errorMessage" class="text-xs text-red-500 flex items-center gap-1">
      <TriangleAlert class="size-3.5 shrink-0" />
      {{ errorMessage }}
    </p>

    <input ref="inputRef" type="file" :accept="accept" class="hidden" @change="onSelect" />
  </div>
</template>
