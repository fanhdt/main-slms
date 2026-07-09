<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { PhotographerPortfolio } from '@/features/portfolio/types'

const props = defineProps<{
  show: boolean
  portfolio: PhotographerPortfolio | null
}>()

const emit = defineEmits<{ close: [] }>()

const queryClient = useQueryClient()
const selectedPhotographerId = ref<number | null>(null)
const caption = ref('')
const order = ref(0)
const fileInput = ref<HTMLInputElement | null>(null)
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

async function handleImageChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !props.portfolio) return

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
    input.value = ''
  }
}
</script>

<template>
  <BaseModal :show="show" title="Edit Portofolio" size="md" @close="$emit('close')">
    <form @submit.prevent="() => save()" class="space-y-4">
      <div v-if="currentImage" class="w-full aspect-video rounded-lg overflow-hidden bg-gray-100">
        <img :src="currentImage" alt="" class="w-full h-full object-cover" />
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700">Ganti Foto</label>
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          @change="handleImageChange"
          :disabled="isUploadingImage"
          class="mt-1 text-sm"
        />
        <p v-if="isUploadingImage" class="text-xs text-gray-400 mt-1">Mengupload...</p>
      </div>

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
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50"
        >
          Batal
        </button>
        <button
          type="submit"
          :disabled="isPending"
          class="px-4 py-2 text-sm rounded-lg bg-gray-900 text-white disabled:opacity-50"
        >
          {{ isPending ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>
