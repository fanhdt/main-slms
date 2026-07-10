<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { serviceApi } from '@/features/labservice/api/serviceApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Service } from '@/types'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { defaultCreateServiceForm } from '@/features/labservice/types'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Upload, Loader2, ImageIcon } from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  service?: Service | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()
const labStore = useLabStore()

const form = ref(defaultCreateServiceForm(labStore.activeLab?.id ?? 1))
const errors = ref<Record<string, string>>({})
const isEdit = ref(false)
const imageInputRef = ref<HTMLInputElement | null>(null)
const isUploadingImage = ref(false)

watch(
  () => props.service,
  (service) => {
    errors.value = {}
    isEdit.value = !!service
    if (service) {
      form.value = {
        name: service.name,
        type: service.type.value,
        description: service.description ?? '',
        pricing_type: service.pricing_type.value,
        price: service.price,
        duration: service.duration?.toString() ?? '',
        min_quantity: service.min_quantity,
        max_quantity: service.max_quantity?.toString() ?? '',
        includes_text: (service.includes ?? []).join('\n'),
        is_active: service.is_active,
        lab_id: service.lab_id,
        imagePreview: service.image ?? null,
      }
    } else {
      form.value = defaultCreateServiceForm(labStore.activeLab?.id ?? 1)
    }
  },
  { immediate: true },
)

const { mutate: saveService, isPending } = useMutation({
  mutationFn: async () => {
    const payload = {
      lab_id: form.value.lab_id,
      name: form.value.name,
      type: form.value.type,
      description: form.value.description || null,
      pricing_type: form.value.pricing_type,
      price: form.value.price,
      duration: form.value.duration ? Number(form.value.duration) : null,
      min_quantity: form.value.min_quantity,
      max_quantity: form.value.max_quantity ? Number(form.value.max_quantity) : null,
      includes: form.value.includes_text
        ? form.value.includes_text
            .split('\n')
            .map((s) => s.trim())
            .filter(Boolean)
        : [],
      is_active: form.value.is_active,
    }

    if (isEdit.value && props.service) {
      return serviceApi.update(props.service.uuid, payload)
    }
    return serviceApi.create(payload)
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['services'] })
    toast.success(isEdit.value ? 'Layanan berhasil diupdate.' : 'Layanan berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})

async function handleImageChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !props.service) return

  isUploadingImage.value = true
  try {
    const res = await serviceApi.updateImage(props.service.uuid, file)
    const updatedService = res.data.data as Service
    form.value.imagePreview = updatedService.image
    queryClient.invalidateQueries({ queryKey: ['services'] })
    toast.success('Gambar berhasil diupdate.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = false
    input.value = ''
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit Layanan' : 'Tambah Layanan'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => saveService()" class="space-y-5">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Nama Layanan</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Sewa Studio Foto (per jam)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.name }"
          />
          <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Tipe Layanan</label>
          <select
            v-model="form.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <optgroup label="Jasa">
              <option value="photography">Fotografi</option>
              <option value="photo_editing">Editing Foto</option>
              <option value="recording">Recording</option>
              <option value="training">Pelatihan</option>
              <option value="printing">Cetak Foto</option>
            </optgroup>
            <option value="other">Lainnya</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Tipe Harga</label>
          <select
            v-model="form.pricing_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="per_session">Per Sesi</option>
            <option value="per_hour">Per Jam</option>
            <option value="per_day">Per Hari</option>
            <option value="per_unit">Per Unit</option>
            <option value="per_person">Per Orang</option>
            <option value="per_photo">Per Foto</option>
            <option value="fixed">Harga Tetap</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Harga (Rp)</label>
          <input
            v-model="form.price"
            type="number"
            placeholder="100000"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.price }"
          />
          <p v-if="errors.price" class="text-xs text-red-500">{{ errors.price }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Durasi (menit, opsional)</label>
          <input
            v-model="form.duration"
            type="number"
            placeholder="60"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Minimal Qty</label>
          <input
            v-model="form.min_quantity"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Maksimal Qty (opsional)</label>
          <input
            v-model="form.max_quantity"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="2"
            placeholder="Deskripsi layanan..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">
            Termasuk (satu baris per item, opsional)
          </label>
          <textarea
            v-model="form.includes_text"
            rows="3"
            placeholder="Free 1x revisi&#10;File digital&#10;Cetak 4R 20 lembar"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Gambar Layanan — cuma muncul saat edit -->
      <template v-if="isEdit">
        <Separator />
        <div class="space-y-2">
          <p class="text-sm font-medium text-gray-700">Gambar Layanan</p>
          <div class="flex items-center gap-4">
            <div
              class="w-24 h-24 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center shrink-0"
            >
              <img
                v-if="form.imagePreview"
                :src="form.imagePreview"
                alt="Gambar Layanan"
                class="w-full h-full object-cover"
              />
              <ImageIcon v-else class="size-6 text-gray-300" />
            </div>
            <div>
              <Button
                type="button"
                variant="link"
                size="sm"
                class="px-0"
                :disabled="isUploadingImage"
                @click="imageInputRef?.click()"
              >
                <Loader2 v-if="isUploadingImage" class="size-3.5 animate-spin" />
                <Upload v-else class="size-3.5" />
                {{ isUploadingImage ? 'Mengupload...' : 'Ganti Gambar' }}
              </Button>
              <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WEBP. Maks 5MB.</p>
              <input
                ref="imageInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleImageChange"
              />
            </div>
          </div>
        </div>
      </template>

      <Separator />

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="is_active"
          class="w-4 h-4 rounded border-gray-300"
        />
        <label for="is_active" class="text-sm font-medium text-gray-700">
          Tampilkan ke customer (aktif)
        </label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" @click="$emit('close')">Batal</Button>
      <Button :disabled="isPending" @click="saveService()">
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
