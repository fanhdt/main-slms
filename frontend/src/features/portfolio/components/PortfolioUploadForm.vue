<script setup lang="ts">
import { ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { ImagePlus, X } from 'lucide-vue-next'

const props = defineProps<{ labId: number }>()

const { data: photographers } = useQuery({
  queryKey: ['photographers-admin', props.labId],
  queryFn: async () => {
    const res = await photographerApi.getAll({ lab_id: props.labId })
    return res.data.data
  },
})

const selectedPhotographerId = ref<number | null>(null)
const caption = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const queryClient = useQueryClient()

function openFilePicker() {
  fileInput.value?.click()
}

function handleFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  selectedFile.value = file
  previewUrl.value = URL.createObjectURL(file)
}

function clearSelectedFile() {
  selectedFile.value = null
  previewUrl.value = null
  if (fileInput.value) fileInput.value.value = ''
}

const { mutate: upload, isPending } = useMutation({
  mutationFn: (file: File) =>
    portfolioApi.create({
      lab_id: props.labId,
      photographer_id: selectedPhotographerId.value as number,
      caption: caption.value || undefined,
      image: file,
    }),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['portfolios'] })
    toast.success('Foto portofolio ditambahkan.')
    caption.value = ''
    clearSelectedFile()
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal upload foto.')
  },
})

function handleSubmit() {
  if (!selectedFile.value || !selectedPhotographerId.value) {
    toast.error('Fotografer dan foto wajib dipilih.')
    return
  }
  upload(selectedFile.value)
}
</script>

<template>
  <form
    @submit.prevent="handleSubmit"
    class="space-y-3 bg-white p-4 rounded-xl border border-gray-200"
  >
    <select
      v-model.number="selectedPhotographerId"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
    >
      <option :value="null" disabled>Pilih fotografer</option>
      <option v-for="p in photographers" :key="p.uuid" :value="p.id">{{ p.name }}</option>
    </select>

    <input
      v-model="caption"
      type="text"
      placeholder="Caption (opsional)"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
    />

    <!-- Upload foto lewat tombol yang jelas, bukan input file polos -->
    <input
      ref="fileInput"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="handleFileChange"
    />

    <div v-if="previewUrl" class="flex items-center gap-3">
      <img
        :src="previewUrl"
        alt="Preview"
        class="w-16 h-16 rounded-lg object-cover border border-gray-200 shrink-0"
      />
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-600 truncate">{{ selectedFile?.name }}</p>
        <div class="flex gap-2 mt-1">
          <Button type="button" variant="outline" size="sm" @click="openFilePicker">
            Ganti Foto
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            class="text-red-600 hover:text-red-700 hover:bg-red-50"
            @click="clearSelectedFile"
          >
            <X class="size-3.5" />
            Hapus
          </Button>
        </div>
      </div>
    </div>

    <Button v-else type="button" variant="outline" class="w-full" @click="openFilePicker">
      <ImagePlus class="size-4" />
      Pilih Foto
    </Button>

    <Button type="submit" :disabled="isPending" class="w-full">
      {{ isPending ? 'Mengupload...' : 'Upload Foto' }}
    </Button>
  </form>
</template>
