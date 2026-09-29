<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { serviceApi } from '@/features/labservice/api/serviceApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Service } from '@/types'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { defaultCreateServiceForm } from '@/features/labservice/types'
import ServiceOptionManager from './ServiceOptionManager.vue'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { ImageUpload } from '@/components/ui/image-upload'

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
const isUploadingImage = ref(false)

// Editing Custom: layanan editing dengan harga TETAP yang ditentukan admin,
// persis seperti layanan biasa (misal per jam). Cocok untuk pekerjaan unik
// (Edit Foto Rapor, Color Grading Prewedding) yang tidak masuk daftar pilihan
// editing dasar.
const isCustomPricing = ref(false)

// Editing Dasar: photo_editing yang BUKAN custom -> harga murni dari daftar
// pilihan (Retouch, Remove Background, dst) di ServiceOptionManager.
const isOptionOnly = computed(() => form.value.type === 'photo_editing' && !isCustomPricing.value)

const createdService = ref<Service | null>(null)
const effectiveService = computed(() => props.service ?? createdService.value)
const isEditingNow = computed(() => isEdit.value || !!createdService.value)

watch(
  () => props.show,
  (show) => {
    if (!show) {
      createdService.value = null
    }
  },
)

watch(
  () => props.service,
  (service) => {
    errors.value = {}
    isEdit.value = !!service
    createdService.value = null
    if (service) {
      form.value = {
        name: service.name,
        type: service.type?.value ?? 'photography',
        description: service.description ?? '',
        pricing_type: service.pricing_type?.value ?? 'per_session',
        price: service.price ?? '',
        duration: service.duration?.toString() ?? '',
        min_quantity: service.min_quantity,
        max_quantity: service.max_quantity?.toString() ?? '',
        includes_text: (service.includes ?? []).join('\n'),
        is_active: service.is_active,
        lab_id: service.lab_id,
        imagePreview: service.image ?? null,
      }
      isCustomPricing.value = (service as any).is_custom_pricing ?? false
    } else {
      form.value = defaultCreateServiceForm(labStore.activeLab?.id ?? 1)
      isCustomPricing.value = false
    }
  },
  { immediate: true },
)

const { mutate: saveService, isPending } = useMutation({
  mutationFn: async () => {
    const payload = {
      lab_id: labStore.activeLab?.id,   // ← diambil fresh saat submit, bukan dari form.value.lab_id
      name: form.value.name,
      type: form.value.type,
      description: form.value.description || null,
      pricing_type: isOptionOnly.value ? null : form.value.pricing_type,
      price: isOptionOnly.value ? null : form.value.price,
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
      is_custom_pricing: isCustomPricing.value,
    }

    if (isEdit.value && props.service) {
      return serviceApi.update(props.service.uuid, payload)
    }
    return serviceApi.create(payload)
  },
  onSuccess: (res) => {
    queryClient.invalidateQueries({ queryKey: ['services'] })

    // Mode CREATE + Editing Dasar (option-only) -> jangan tutup modal,
    // biar admin langsung lanjut isi Daftar Pilihan Editing di bawah.
    if (!isEdit.value && isOptionOnly.value) {
      createdService.value = res.data.data as Service
      toast.success('Layanan dibuat. Sekarang tambahkan minimal 1 pilihan editing di bawah.')
      return
    }

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

async function handleImageSelect(file: File) {
  if (!effectiveService.value) return

  isUploadingImage.value = true
  try {
    const res = await serviceApi.updateImage(effectiveService.value.uuid, file)
    const updatedService = res.data.data as Service
    form.value.imagePreview = updatedService.image
    queryClient.invalidateQueries({ queryKey: ['services'] })
    toast.success('Gambar berhasil diupdate.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = false
  }
}

async function handleImageRemove() {
  if (!effectiveService.value) return

  isUploadingImage.value = true
  try {
    const res = await serviceApi.removeImage(effectiveService.value.uuid)
    const updatedService = res.data.data as Service
    form.value.imagePreview = updatedService.image
    queryClient.invalidateQueries({ queryKey: ['services'] })
    toast.success('Gambar berhasil dihapus.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus gambar.')
  } finally {
    isUploadingImage.value = false
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
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Nama Layanan</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Sewa Studio Foto (per jam)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.name }"
            :disabled="isEditingNow && !isEdit"
          />
          <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Tipe Layanan</label>
          <select
            v-model="form.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :disabled="isEditingNow && !isEdit"
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

        <!-- Toggle Editing Custom (harga tetap) vs Editing Dasar (daftar pilihan) -->
        <div
          v-if="form.type === 'photo_editing'"
          class="col-span-1 sm:col-span-2 flex items-start gap-3 p-3 rounded-lg border border-purple-200 bg-purple-50"
        >
          <input
            v-model="isCustomPricing"
            type="checkbox"
            id="is_custom_pricing"
            class="w-4 h-4 mt-0.5 rounded border-gray-300 shrink-0"
          />
          <label for="is_custom_pricing" class="text-sm">
            <span class="font-medium text-purple-900">Editing Custom</span>
            <p class="text-xs text-purple-700 mt-0.5 leading-relaxed">
              Aktifkan untuk layanan editing dengan harga tetap yang kamu tentukan sendiri (isi Tipe
              Harga & Harga seperti layanan lain, misal per jam). Cocok untuk pekerjaan unik seperti
              Edit Foto Rapor atau Color Grading Prewedding. Kalau tidak diaktifkan, layanan ini
              otomatis jadi <strong>Editing Dasar</strong> — customer memilih dari daftar pilihan
              (Retouch, Remove Background, dst) di bagian bawah.
            </p>
          </label>
        </div>

        <!-- Tipe Harga & Harga — tersembunyi HANYA untuk Editing Dasar (option-only) -->
        <div v-if="!isOptionOnly" class="space-y-1.5">
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

        <div v-if="!isOptionOnly" class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Harga (Rp)</label>
          <input
            v-model="form.price"
            type="number"
            inputmode="numeric"
            placeholder="100000"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.price }"
          />
          <p v-if="errors.price" class="text-xs text-red-500">{{ errors.price }}</p>
        </div>

        <div
          v-if="isOptionOnly"
          class="col-span-1 sm:col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800"
        >
          Editing Dasar tidak punya harga dasar tunggal. Customer memilih satu atau lebih pilihan
          editing (Retouch, Remove Background, dsb) di bagian
          <strong>Daftar Pilihan Editing</strong> di bawah, dan total booking dihitung murni dari
          pilihan yang dicentang.
        </div>

        <div
          v-if="isCustomPricing"
          class="col-span-1 sm:col-span-2 bg-purple-50 border border-purple-200 rounded-lg p-3 text-sm text-purple-800"
        >
          Editing Custom berlaku seperti layanan biasa — punya harga tetap sesuai Tipe Harga & Harga
          yang kamu isi di atas, dan langsung terlihat customer sebelum booking.
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Durasi (menit, opsional)</label>
          <input
            v-model="form.duration"
            type="number"
            inputmode="numeric"
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
            inputmode="numeric"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Maksimal Qty (opsional)</label>
          <input
            v-model="form.max_quantity"
            type="number"
            min="1"
            inputmode="numeric"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Deskripsi layanan..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-1 sm:col-span-2 space-y-1.5">
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

      <!-- Daftar Pilihan Editing — hanya untuk Editing Dasar -->
      <template v-if="isEditingNow && effectiveService && isOptionOnly">
        <Separator />
        <ServiceOptionManager
          :service-uuid="effectiveService.uuid"
          :service-type="effectiveService.type?.value ?? form.type"
          option-only
        />
      </template>

      <!-- Opsi Tambahan untuk layanan non-editing (tetap tersedia seperti sebelumnya) -->
      <template v-else-if="isEditingNow && effectiveService && form.type !== 'photo_editing'">
        <Separator />
        <ServiceOptionManager
          :service-uuid="effectiveService.uuid"
          :service-type="effectiveService.type?.value ?? form.type"
        />
      </template>

      <!-- Gambar Layanan — cuma muncul saat sudah ada service (edit / baru dibuat) -->
      <template v-if="isEditingNow && effectiveService">
        <Separator />
        <ImageUpload
          v-model="form.imagePreview"
          label="Gambar Layanan"
          aspect="video"
          :loading="isUploadingImage"
          @select="handleImageSelect"
          @remove="handleImageRemove"
        />
      </template>

      <Separator />

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="is_active"
          class="w-4 h-4 rounded border-gray-300 shrink-0"
        />
        <label for="is_active" class="text-sm font-medium text-gray-700">
          Tampilkan ke customer (aktif)
        </label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" class="w-full sm:w-auto" @click="$emit('close')">
        {{ createdService ? 'Selesai' : 'Batal' }}
      </Button>
      <Button
        v-if="!createdService"
        class="w-full sm:w-auto"
        :disabled="isPending"
        @click="saveService()"
      >
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
