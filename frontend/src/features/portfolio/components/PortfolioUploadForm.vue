<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { ImagePlus, X, UserSquare2 } from 'lucide-vue-next'

const props = defineProps<{
  labId: number
  /** Kalau diisi (mis. datang dari kartu fotografer tertentu), fotografer ini
   * otomatis terpilih dan picker-nya disembunyikan — admin langsung upload. */
  preselectedPhotographerId?: number | null
}>()

const { data: photographers } = useQuery({
  queryKey: ['photographers-admin', props.labId],
  queryFn: async () => {
    const res = await photographerApi.getAll({ lab_id: props.labId })
    return res.data.data
  },
})

const selectedPhotographerId = ref<number | null>(props.preselectedPhotographerId ?? null)

// Kalau cuma ada 1 fotografer, auto-pilih — tidak perlu tanya sama sekali.
watch(
  photographers,
  (list) => {
    if (!selectedPhotographerId.value && list?.length === 1) {
      selectedPhotographerId.value = list[0]!.id
    }
  },
  { immediate: true },
)

// Locked = fotografer sudah ditentukan dari luar (query/prop), picker disembunyikan.
const isLocked = computed(
  () => !!props.preselectedPhotographerId || photographers.value?.length === 1,
)

const activePhotographer = computed(() =>
  photographers.value?.find((p) => p.id === selectedPhotographerId.value),
)

const caption = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const queryClient = useQueryClient()

function selectPhotographer(id: number) {
  selectedPhotographerId.value = id
}

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
    class="space-y-4 bg-white p-4 rounded-xl border border-gray-200"
  >
    <!-- Picker visual — hanya tampil kalau memang perlu memilih -->
    <div v-if="!isLocked && photographers?.length" class="space-y-2">
      <p class="text-xs font-medium text-gray-500">Upload untuk fotografer:</p>
      <div class="flex gap-3 overflow-x-auto pb-1">
        <button
          v-for="p in photographers"
          :key="p.uuid"
          type="button"
          @click="selectPhotographer(p.id)"
          class="flex flex-col items-center gap-1.5 shrink-0 group"
        >
          <div
            class="w-14 h-14 rounded-full overflow-hidden border-2 transition-colors"
            :class="
              selectedPhotographerId === p.id
                ? 'border-blue-500'
                : 'border-transparent group-hover:border-gray-300'
            "
          >
            <img v-if="p.photo" :src="p.photo" :alt="p.name" class="w-full h-full object-cover" />
            <div
              v-else
              class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold text-sm"
            >
              {{ p.name.charAt(0) }}
            </div>
          </div>
          <span
            class="text-[11px] font-medium max-w-16 truncate"
            :class="selectedPhotographerId === p.id ? 'text-blue-600' : 'text-gray-500'"
          >
            {{ p.name }}
          </span>
        </button>
      </div>
      <p v-if="!photographers.length" class="text-xs text-amber-600">
        Belum ada fotografer. Tambahkan dulu di menu Fotografer.
      </p>
    </div>

    <!-- Konteks fotografer terpilih (locked / auto-pilih) -->
    <div
      v-else-if="activePhotographer"
      class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2.5"
    >
      <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 shrink-0">
        <img
          v-if="activePhotographer.photo"
          :src="activePhotographer.photo"
          :alt="activePhotographer.name"
          class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <UserSquare2 class="size-4" />
        </div>
      </div>
      <div class="min-w-0">
        <p class="text-xs text-gray-400">Upload untuk</p>
        <p class="text-sm font-semibold text-gray-900 truncate">{{ activePhotographer.name }}</p>
      </div>
    </div>

    <p v-else class="text-xs text-amber-600">
      Belum ada fotografer terdaftar. Tambahkan dulu di menu Fotografer.
    </p>

    <input
      v-model="caption"
      type="text"
      placeholder="Caption (opsional)"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
    />

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

    <Button
      v-else
      type="button"
      variant="outline"
      class="w-full"
      :disabled="!selectedPhotographerId"
      @click="openFilePicker"
    >
      <ImagePlus class="size-4" />
      Pilih Foto
    </Button>

    <Button type="submit" :disabled="isPending || !selectedPhotographerId" class="w-full">
      {{ isPending ? 'Mengupload...' : 'Upload Foto' }}
    </Button>
  </form>
</template>
