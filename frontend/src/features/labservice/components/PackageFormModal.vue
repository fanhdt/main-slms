<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useMutation, useQueryClient, useQuery } from '@tanstack/vue-query'
import { packageApi } from '@/features/labservice/api/packageApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import api from '@/lib/axios'

const props = defineProps<{
  show: boolean
  pkg?: any | null
}>()

const emit = defineEmits<{ close: [] }>()

const queryClient = useQueryClient()
const labStore = useLabStore()

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
        lab_id: pkg.lab_id,
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
    // Validasi ringan di frontend dulu, biar pesan errornya jelas
    // sebelum sempat hit backend (backend tetap validasi ulang sebagai jaring pengaman kedua).
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
      // Tidak lagi bergantung pada `it.type` — kirim berdasarkan field mana
      // yang benar-benar terisi, supaya tidak ada key yang hilang dari payload.
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
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['packages'] })
    toast.success(isEdit.value ? 'Package berhasil diupdate.' : 'Package berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]]),
      )
      toast.error('Cek kembali item paket — ada yang belum lengkap.')
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit Package' : 'Tambah Package'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="savePackage" class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 space-y-1.5">
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
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Diskon (%)</label>
          <input
            v-model="form.discount"
            type="number"
            min="0"
            max="100"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>

        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>
      </div>

      <!-- Item Paket -->
      <div class="space-y-3 border-t border-gray-100 pt-4">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-gray-900">Item Paket (Jasa / Alat)</h4>
          <button type="button" @click="addItem" class="text-xs text-blue-600 hover:underline">
            + Tambah Item
          </button>
        </div>

        <div
          v-for="(item, index) in form.items"
          :key="index"
          class="border rounded-lg p-3 space-y-2 bg-gray-50"
          :class="!item.service_id && !item.asset_id ? 'border-red-300' : 'border-gray-200'"
        >
          <div class="flex items-center gap-2">
            <select
              v-model="item.type"
              @change="onTypeChange(item)"
              class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
            >
              <option value="service">Jasa</option>
              <option value="asset">Alat</option>
            </select>

            <select
              v-if="item.type === 'service'"
              :key="'service-' + index"
              v-model="item.service_id"
              class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
            >
              <option :value="null">-- Pilih Jasa --</option>
              <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>

            <select
              v-else
              :key="'asset-' + index"
              v-model="item.asset_id"
              class="flex-1 px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
            >
              <option :value="null">-- Pilih Alat --</option>
              <option v-for="a in assets" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>

            <button
              type="button"
              @click="removeItem(index)"
              class="text-red-500 hover:text-red-700 text-xs px-2"
            >
              Hapus
            </button>
          </div>

          <p v-if="!item.service_id && !item.asset_id" class="text-[11px] text-red-500">
            ⚠ Pilih {{ item.type === 'service' ? 'Jasa' : 'Alat' }} dulu, dropdown di atas masih
            kosong.
          </p>

          <div class="grid grid-cols-2 gap-2">
            <input
              v-model="item.quantity"
              type="number"
              min="1"
              placeholder="Qty"
              class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
            />
            <input
              v-model="item.duration_minutes"
              type="number"
              min="1"
              placeholder="Durasi (menit, opsional)"
              class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs"
            />
          </div>
        </div>

        <p v-if="services?.length === 0" class="text-xs text-amber-600">
          ⚠ Belum ada Jasa aktif di lab ini — tambahkan dulu lewat menu Layanan.
        </p>
        <p v-if="assets?.length === 0" class="text-xs text-amber-600">
          ⚠ Belum ada Alat yang bisa disewa di lab ini — tambahkan dulu lewat menu Aset (centang
          "rentable").
        </p>
      </div>

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="pkg_active"
          class="w-4 h-4 rounded border-gray-300"
        />
        <label for="pkg_active" class="text-sm font-medium text-gray-700"
          >Tampilkan ke customer (aktif)</label
        >
      </div>
    </form>

    <template #footer>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg"
      >
        Batal
      </button>
      <button
        @click="savePackage()"
        :disabled="isPending"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg"
      >
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </button>
    </template>
  </BaseModal>
</template>
