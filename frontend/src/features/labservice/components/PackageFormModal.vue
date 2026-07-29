<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { packageApi } from '@/features/labservice/api/packageApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Plus, Trash2, TriangleAlert } from 'lucide-vue-next'
import { ImageUpload } from '@/components/ui/image-upload'

const props = defineProps<{
  show: boolean
  pkg?: any | null
}>()

const emit = defineEmits<{ close: [] }>()

const queryClient = useQueryClient()
const labStore = useLabStore()

const imagePreview = ref<string | null>(null)
const pendingImageFile = ref<File | null>(null)
const isUploadingImage = ref(false)

interface ItemRow {
  type: 'service' | 'asset'
  service_id: number | null
  asset_id: number | null
  quantity: number
  duration_minutes: number | null
  notes: string
}

function emptyItem(): ItemRow {
  return {
    type: 'service',
    service_id: null,
    asset_id: null,
    quantity: 1,
    duration_minutes: null,
    notes: '',
  }
}

function defaultForm() {
  return {
    lab_id: labStore.activeLab?.id ?? 1,
    name: '',
    description: '',
    price: '',
    discount: '',
    duration: '',
    is_active: true,
    is_custom: false,
    items: [emptyItem()] as ItemRow[],
  }
}

const form = ref(defaultForm())
const errors = ref<Record<string, string>>({})
const isEdit = ref(false)

const { data: services } = useQuery({
  queryKey: ['services-for-package', labStore.activeLab?.id],
  queryFn: async () => {
    const res = await api.get('/services', {
      params: { lab_id: labStore.activeLab?.id, is_active: true },
    })
    return res.data.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

const { data: assets } = useQuery({
  queryKey: ['assets-for-package', labStore.activeLab?.id],
  queryFn: async () => {
    const res = await api.get('/assets', {
      params: { lab_id: labStore.activeLab?.id, is_rentable: 1 },
    })
    return res.data.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

watch(
  () => props.pkg,
  (pkg) => {
    errors.value = {}
    isEdit.value = !!pkg
    if (pkg) {
      form.value = {
        lab_id: labStore.activeLab?.id ?? 1,
        name: pkg.name,
        description: pkg.description ?? '',
        price: pkg.price,
        discount: pkg.discount ?? '',
        duration: pkg.duration?.toString() ?? '',
        is_active: pkg.is_active,
        is_custom: pkg.is_custom,
        items: (pkg.items ?? []).map((it: any) => ({
          type: it.type,
          service_id: it.service?.id ?? null,
          asset_id: it.asset?.id ?? null,
          quantity: it.quantity,
          duration_minutes: it.duration_minutes,
          notes: it.notes ?? '',
        })),
      }
    } else {
      form.value = defaultForm()
    }
    imagePreview.value = pkg?.image ?? null
    pendingImageFile.value = null
  },
  { immediate: true },
)

function addItem() {
  form.value.items.push(emptyItem())
}

function removeItem(index: number) {
  form.value.items.splice(index, 1)
}

function onTypeChange(item: ItemRow) {
  item.service_id = null
  item.asset_id = null
}

const { mutate: savePackage, isPending } = useMutation({
  mutationFn: async () => {
    const incomplete = form.value.items.some((it) => !it.service_id && !it.asset_id)
    if (incomplete) {
      throw {
        response: {
          data: {
            message: 'Ada item yang belum pilih Jasa atau Alat. Cek kembali tiap baris item.',
          },
        },
      }
    }

    const payload = {
      lab_id: form.value.lab_id,
      name: form.value.name,
      description: form.value.description || null,
      price: form.value.price,
      discount: form.value.discount || 0,
      duration: form.value.duration ? Number(form.value.duration) : null,
      is_active: form.value.is_active,
      is_custom: form.value.is_custom,
      items: form.value.items.map((it) => ({
        service_id: it.service_id || null,
        asset_id: it.asset_id || null,
        quantity: it.quantity,
        duration_minutes: it.duration_minutes,
        notes: it.notes || null,
      })),
    }

    if (isEdit.value && props.pkg) {
      return packageApi.update(props.pkg.uuid, payload)
    }
    return packageApi.create(payload)
  },
  onSuccess: async (res) => {
    const newUuid = isEdit.value ? props.pkg!.uuid : (res.data.data as any).uuid
    if (pendingImageFile.value) {
      await packageApi.updateImage(newUuid, pendingImageFile.value)
      pendingImageFile.value = null
    }
    queryClient.invalidateQueries({ queryKey: ['packages'] })
    toast.success(isEdit.value ? 'Package berhasil diupdate.' : 'Package berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
      toast.error('Cek kembali item paket — ada yang belum lengkap.')
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})
async function handleImageSelect(file: File) {
  if (!props.pkg) return
  isUploadingImage.value = true
  try {
    const res = await packageApi.updateImage(props.pkg.uuid, file)
    imagePreview.value = res.data.data.image
    queryClient.invalidateQueries({ queryKey: ['packages'] })
    toast.success('Gambar paket berhasil diupdate.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = false
  }
}

async function handleImageRemove() {
  if (!props.pkg) return
  isUploadingImage.value = true
  try {
    const res = await packageApi.removeImage(props.pkg.uuid)
    imagePreview.value = res.data.data.image
    queryClient.invalidateQueries({ queryKey: ['packages'] })
    toast.success('Gambar paket berhasil dihapus.')
  } finally {
    isUploadingImage.value = false
  }
}

function handleImageSelectPending(file: File) {
  pendingImageFile.value = file
  imagePreview.value = URL.createObjectURL(file) // preview lokal sebelum upload beneran
}

function onImageSelect(file: File) {
  if (isEdit.value) {
    handleImageSelect(file)
  } else {
    handleImageSelectPending(file)
  }
}

function onImageRemove() {
  if (isEdit.value) {
    handleImageRemove()
  } else {
    pendingImageFile.value = null
    imagePreview.value = null
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit Package' : 'Tambah Package'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => savePackage()" class="space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Nama Package</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Paket Foto Nikahan"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Harga Paket (Rp)</label>
          <input
            v-model="form.price"
            type="number"
            inputmode="numeric"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Diskon (%)</label>
          <input
            v-model="form.discount"
            type="number"
            min="0"
            max="100"
            inputmode="numeric"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <Separator />

      <!-- Item Paket -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-gray-900">Item Paket (Jasa / Alat)</h4>
          <Button type="button" variant="link" size="sm" class="px-0" @click="addItem">
            <Plus class="size-3.5" />
            Tambah Item
          </Button>
        </div>

        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="border rounded-lg p-3 space-y-2 bg-gray-50"
          :class="!item.service_id && !item.asset_id ? 'border-red-300' : 'border-gray-200'"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <div class="flex items-center gap-2">
              <select
                v-model="item.type"
                @change="onTypeChange(item)"
                class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 shrink-0"
              >
                <option value="service">Jasa</option>
                <option value="asset">Alat</option>
              </select>

              <button
                type="button"
                title="Hapus item"
                @click="removeItem(index)"
                class="sm:hidden p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0 ml-auto"
              >
                <Trash2 class="size-3.5" />
              </button>
            </div>

            <select
              v-if="item.type === 'service'"
              :key="'service-' + index"
              v-model="item.service_id"
              class="flex-1 min-w-0 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="null">-- Pilih Jasa --</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>

            <select
              v-else
              :key="'asset-' + index"
              v-model="item.asset_id"
              class="flex-1 min-w-0 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="null">-- Pilih Alat --</option>
              <option v-for="a in assets" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>

            <button
              type="button"
              title="Hapus item"
              @click="removeItem(index)"
              class="hidden sm:inline-flex p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0"
            >
              <Trash2 class="size-3.5" />
            </button>
          </div>

          <p
            v-if="!item.service_id && !item.asset_id"
            class="text-[11px] text-red-500 flex items-center gap-1"
          >
            <TriangleAlert class="size-3" />
            Pilih {{ item.type === 'service' ? 'Jasa' : 'Alat' }} dulu, dropdown di atas masih
            kosong.
          </p>

          <div class="grid grid-cols-2 gap-2">
            <input
              v-model="item.quantity"
              type="number"
              min="1"
              inputmode="numeric"
              placeholder="Qty"
              class="min-w-0 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <input
              v-model="item.duration_minutes"
              type="number"
              min="1"
              inputmode="numeric"
              placeholder="Durasi (menit)"
              class="min-w-0 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <p v-if="services?.length === 0" class="text-xs text-amber-600 flex items-center gap-1.5">
          <TriangleAlert class="size-3.5 shrink-0" />
          Belum ada Jasa aktif di lab ini — tambahkan dulu lewat menu Layanan.
        </p>
        <p v-if="assets?.length === 0" class="text-xs text-amber-600 flex items-center gap-1.5">
          <TriangleAlert class="size-3.5 shrink-0" />
          Belum ada Alat yang bisa disewa di lab ini — tambahkan dulu lewat menu Aset (centang
          "rentable").
        </p>
      </div>

      <Separator />
      <ImageUpload
        v-model="imagePreview"
        label="Gambar Paket"
        aspect="video"
        :loading="isUploadingImage"
        @select="onImageSelect"
        @remove="onImageRemove"
      />

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="pkg_active"
          class="w-4 h-4 rounded border-gray-300 shrink-0"
        />
        <label for="pkg_active" class="text-sm font-medium text-gray-700">
          Tampilkan ke customer (aktif)
        </label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" class="w-full sm:w-auto" @click="$emit('close')">Batal</Button>
      <Button class="w-full sm:w-auto" :disabled="isPending" @click="savePackage()">
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
