<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { PhotographerPortfolio } from '@/features/portfolio/types'
import { Button } from '@/components/ui/button'
import { ImageUpload } from '@/components/ui/image-upload'

const props = defineProps<{
  show: boolean
  portfolio: PhotographerPortfolio | null
}>()

const emit = defineEmits<{ close: [] }>()

const queryClient = useQueryClient()
const selectedPhotographerId = ref<number | null>(null)
const caption = ref('')
const order = ref(0)
const isUploadingImage = ref(false)
const currentImage = ref<string | null>(null)

const { data: photographerOptions } = useQuery({
  queryKey: ['photographers-admin', computed(() => props.portfolio?.lab_id)],
  queryFn: async () => {
    const res = await photographerApi.getAll({ lab_id: props.portfolio?.lab_id })
    return res.data.data
  },
  enabled: computed(() => !!props.portfolio?.lab_id),
})

watch(
  () => props.portfolio,
  (p) => {
    if (p) {
      selectedPhotographerId.value = p.photographer_id
      caption.value = p.caption ?? ''
      order.value = p.order
      currentImage.value = p.image
    }
  },
  { immediate: true },
)

const { mutate: save, isPending } = useMutation({
  mutationFn: () => {
    if (!props.portfolio) throw new Error('Portfolio tidak ditemukan')
    return portfolioApi.update(props.portfolio.uuid, {
      photographer_id: selectedPhotographerId.value,
      caption: caption.value || null,
      order: order.value,
    })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['portfolios'] })
    toast.success('Portofolio berhasil diupdate.')
    emit('close')
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal update portofolio.')
  },
})

async function handleImageSelect(file: File) {
  if (!props.portfolio) return
  isUploadingImage.value = true
  try {
    const res = await portfolioApi.updateImage(props.portfolio.uuid, file)
    currentImage.value = res.data.data.image
    queryClient.invalidateQueries({ queryKey: ['portfolios'] })
    toast.success('Foto berhasil diganti.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload foto.')
  } finally {
    isUploadingImage.value = false
  }
}

function handleImageRemoveClick() {
  // Foto portofolio wajib ada, jadi tombol "Hapus" di sini cuma mengingatkan
  // user untuk pilih foto pengganti — bukan menghapus permanen.
  toast.info('Klik "Ganti" untuk memilih foto baru. Foto portofolio tidak bisa dikosongkan.')
}
</script>

<template>
  <BaseModal :show="show" title="Edit Portofolio" size="md" @close="$emit('close')">
    <form @submit.prevent="() => save()" class="space-y-4">
      <ImageUpload
        v-model="currentImage"
        label="Foto Portofolio"
        aspect="video"
        :loading="isUploadingImage"
        @select="handleImageSelect"
        @remove="handleImageRemoveClick"
      />

      <div>
        <label class="text-sm font-medium text-gray-700">Fotografer</label>
        <select
          v-model.number="selectedPhotographerId"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        >
          <option v-for="p in photographerOptions" :key="p.uuid" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700">Caption</label>
        <input
          v-model="caption"
          type="text"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700">Urutan Tampil</label>
        <input
          v-model.number="order"
          type="number"
          min="0"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
        />
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <Button type="button" variant="outline" @click="$emit('close')">Batal</Button>
        <Button type="submit" :disabled="isPending">
          {{ isPending ? 'Menyimpan...' : 'Simpan' }}
        </Button>
      </div>
    </form>
  </BaseModal>
</template>
