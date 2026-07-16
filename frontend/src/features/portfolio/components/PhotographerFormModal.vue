<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Photographer } from '@/features/portfolio/types'
import { Button } from '@/components/ui/button'
import { UserSquare2, Loader2, Upload } from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  photographer: Photographer | null
  labId: number
}>()

const emit = defineEmits<{ close: [] }>()
const queryClient = useQueryClient()

const form = ref({ name: '', bio: '', instagram: '' })
const photoInput = ref<HTMLInputElement | null>(null)
const isUploadingPhoto = ref(false)
const currentPhoto = ref<string | null>(null)

watch(
  () => props.photographer,
  (p) => {
    form.value = { name: p?.name ?? '', bio: p?.bio ?? '', instagram: p?.instagram ?? '' }
    currentPhoto.value = p?.photo ?? null
  },
  { immediate: true },
)

const isEdit = () => !!props.photographer

const { mutate: save, isPending } = useMutation({
  mutationFn: async () => {
    if (isEdit()) {
      return photographerApi.update(props.photographer!.uuid, form.value)
    }
    return photographerApi.create({ lab_id: props.labId, ...form.value })
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['photographers-admin'] })
    toast.success(isEdit() ? 'Profil berhasil diupdate.' : 'Fotografer berhasil ditambahkan.')
    emit('close')
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan profil.')
  },
})

async function handlePhotoChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file || !props.photographer) return

  isUploadingPhoto.value = true
  try {
    const res = await photographerApi.updatePhoto(props.photographer.uuid, file)
    currentPhoto.value = res.data.data.photo
    queryClient.invalidateQueries({ queryKey: ['photographers-admin'] })
    toast.success('Foto profil berhasil diganti.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload foto.')
  } finally {
    isUploadingPhoto.value = false
    if (photoInput.value) photoInput.value.value = ''
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit() ? 'Edit Fotografer' : 'Tambah Fotografer'"
    size="md"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => save()" class="space-y-4">
      <div v-if="isEdit()" class="space-y-2">
        <div
          class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 mx-auto flex items-center justify-center"
        >
          <img v-if="currentPhoto" :src="currentPhoto" alt="" class="w-full h-full object-cover" />
          <UserSquare2 v-else class="size-8 text-gray-300" />
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="mx-auto flex"
          :disabled="isUploadingPhoto"
          @click="photoInput?.click()"
        >
          <Loader2 v-if="isUploadingPhoto" class="size-3.5 animate-spin" />
          <Upload v-else class="size-3.5" />
          {{ isUploadingPhoto ? 'Mengupload...' : currentPhoto ? 'Ganti Foto' : 'Pilih Foto' }}
        </Button>
        <input
          ref="photoInput"
          type="file"
          accept="image/jpeg,image/png,image/webp"
          :disabled="isUploadingPhoto"
          class="hidden"
          @change="handlePhotoChange"
        />
      </div>
      <p v-else class="text-xs text-gray-400 text-center -mt-2">
        Foto profil bisa diupload setelah profil disimpan.
      </p>

      <div>
        <label class="text-sm font-medium text-gray-700">Nama</label>
        <input
          v-model="form.name"
          type="text"
          required
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700">Bio</label>
        <textarea
          v-model="form.bio"
          rows="3"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700">Instagram (tanpa @)</label>
        <input
          v-model="form.instagram"
          type="text"
          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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
