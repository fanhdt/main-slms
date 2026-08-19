<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { assetApi } from '@/features/asset/api/assetApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Asset } from '@/types'
import { Button } from '@/components/ui/button'
import { ImageUpload } from '@/components/ui/image-upload'
import { Separator } from '@/components/ui/separator'

const props = defineProps<{
  show: boolean
  asset?: Asset | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()
const labStore = useLabStore()

const form = ref({
  name: '',
  code: '',
  category: 'camera',
  brand: '',
  model: '',
  description: '',
  serial_number: '',
  status: 'available',
  purchase_price: '',
  purchase_date: '',
  is_rentable: true,
  rental_price: '',
  quantity: 1,
  lab_id: labStore.activeLab?.id ?? 1,
})

const errors = ref<Record<string, string>>({})
const isEdit = ref(false)

const imagePreview = ref<string | null>(null)
const isUploadingImage = ref(false)

// File gambar yang dipilih SEBELUM aset dibuat (mode create).
// Diupload otomatis setelah asset berhasil dibuat & dapat uuid.
const pendingImageFile = ref<File | null>(null)

watch(
  () => props.show,
  (show) => {
    if (show) {
      // reset state pending setiap modal dibuka
      pendingImageFile.value = null
    }
  },
)

watch(
  () => props.asset,
  (asset) => {
    isEdit.value = !!asset
    imagePreview.value = asset?.image ?? null
    pendingImageFile.value = null
    if (asset) {
      form.value = {
        name: asset.name,
        code: asset.code,
        category: asset.category.value,
        brand: asset.brand ?? '',
        model: asset.model ?? '',
        description: asset.description ?? '',
        serial_number: asset.serial_number ?? '',
        status: asset.status.value,
        purchase_price: asset.purchase_price ?? '',
        purchase_date: asset.purchase_date ?? '',
        is_rentable: asset.is_rentable,
        rental_price: asset.rental_price ?? '',
        quantity: asset.quantity ?? 1,
        lab_id: asset.lab_id,
      }
    } else {
      form.value = {
        name: '',
        code: '',
        category: 'camera',
        brand: '',
        model: '',
        description: '',
        serial_number: '',
        status: 'available',
        purchase_price: '',
        purchase_date: '',
        is_rentable: true,
        rental_price: '',
        quantity: 1,
        lab_id: labStore.activeLab?.id ?? 1,
      }
    }
  },
  { immediate: true },
)

const { mutate: saveAsset, isPending } = useMutation({
  mutationFn: async () => {
    const payload = { ...form.value, lab_id: labStore.activeLab?.id ?? form.value.lab_id }

    if (isEdit.value && props.asset) {
      return assetApi.update(props.asset.uuid, payload)
    } else {
      return assetApi.create(payload)
    }
  },
  onSuccess: async (res) => {
    queryClient.invalidateQueries({ queryKey: ['assets'] })

    // Mode create + ada gambar yang sudah dipilih sebelumnya -> upload sekarang
    if (!isEdit.value && pendingImageFile.value) {
      const newAsset = res.data.data as Asset
      try {
        await assetApi.updateImage(newAsset.uuid, pendingImageFile.value)
        queryClient.invalidateQueries({ queryKey: ['assets'] })
      } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Aset dibuat, tapi gagal upload gambar.')
      }
    }

    toast.success(isEdit.value ? 'Aset berhasil diupdate.' : 'Aset berhasil dibuat.')
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

// Dipakai saat mode EDIT — langsung upload ke aset yang sudah ada
async function handleImageSelect(file: File) {
  if (!isEdit.value) {
    // Mode CREATE — belum ada aset, simpan dulu filenya + tampilkan preview lokal
    pendingImageFile.value = file
    const reader = new FileReader()
    reader.onload = () => {
      imagePreview.value = reader.result as string
    }
    reader.readAsDataURL(file)
    return
  }

  if (!props.asset) return
  isUploadingImage.value = true
  try {
    const res = await assetApi.updateImage(props.asset.uuid, file)
    imagePreview.value = res.data.data.image
    queryClient.invalidateQueries({ queryKey: ['assets'] })
    toast.success('Gambar aset berhasil diupdate.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = false
  }
}

async function handleImageRemove() {
  if (!isEdit.value) {
    // Mode CREATE — cukup hapus dari state lokal, belum ada apa-apa di server
    pendingImageFile.value = null
    imagePreview.value = null
    return
  }

  if (!props.asset) return
  isUploadingImage.value = true
  try {
    const res = await assetApi.removeImage(props.asset.uuid)
    imagePreview.value = res.data.data.image
    queryClient.invalidateQueries({ queryKey: ['assets'] })
    toast.success('Gambar aset berhasil dihapus.')
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
    :title="isEdit ? 'Edit Aset' : 'Tambah Aset'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => saveAsset()" class="space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Nama Aset</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Canon EOS R5"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.name }"
          />
          <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Kode Aset</label>
          <input
            v-model="form.code"
            type="text"
            placeholder="CAM-001"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.code }"
          />
          <p v-if="errors.code" class="text-xs text-red-500">{{ errors.code }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Kategori</label>
          <select
            v-model="form.category"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="camera">Kamera</option>
            <option value="lens">Lensa</option>
            <option value="lighting">Lighting</option>
            <option value="drone">Drone</option>
            <option value="tripod">Tripod</option>
            <option value="computer">Komputer</option>
            <option value="projector">Proyektor</option>
            <option value="audio">Audio</option>
            <option value="microphone">Mikrofon</option>
            <option value="printer">Printer</option>
            <option value="backdrop">Backdrop / Properti</option>
            <option value="costume">Kostum / Aksesoris</option>
            <option value="other">Lainnya</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Brand</label>
          <input
            v-model="form.brand"
            type="text"
            placeholder="Canon"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Model</label>
          <input
            v-model="form.model"
            type="text"
            placeholder="EOS R5"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="form.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="available">Tersedia</option>
            <option value="in_use">Sedang Dipakai</option>
            <option value="maintenance">Dalam Perbaikan</option>
            <option value="retired">Tidak Aktif</option>
          </select>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Serial Number</label>
          <input
            v-model="form.serial_number"
            type="text"
            placeholder="SN123456"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Harga Beli</label>
          <input
            v-model="form.purchase_price"
            type="number"
            inputmode="numeric"
            placeholder="45000000"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Tanggal Beli</label>
          <input
            v-model="form.purchase_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Harga Sewa</label>
          <input
            v-model="form.rental_price"
            type="number"
            inputmode="numeric"
            placeholder="500000"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Jumlah Stok (unit)</label>
          <input
            v-model.number="form.quantity"
            type="number"
            min="1"
            inputmode="numeric"
            placeholder="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p class="text-xs text-gray-400">Berapa unit fisik alat ini yang dimiliki lab.</p>
        </div>

        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Deskripsi aset..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <Separator />

      <!-- Sekarang muncul baik di mode Tambah maupun Edit -->
      <ImageUpload
        v-model="imagePreview"
        label="Gambar Aset"
        aspect="video"
        :loading="isUploadingImage"
        @select="handleImageSelect"
        @remove="handleImageRemove"
      />
      <p v-if="!isEdit && pendingImageFile" class="text-xs text-blue-600 -mt-3">
        Gambar akan diupload otomatis setelah aset disimpan.
      </p>

      <Separator />

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_rentable"
          type="checkbox"
          id="is_rentable"
          class="w-4 h-4 rounded border-gray-300 shrink-0"
        />
        <label for="is_rentable" class="text-sm font-medium text-gray-700">
          Bisa Disewa Customer
        </label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" class="w-full sm:w-auto" @click="$emit('close')">Batal</Button>
      <Button class="w-full sm:w-auto" :disabled="isPending" @click="saveAsset()">
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
