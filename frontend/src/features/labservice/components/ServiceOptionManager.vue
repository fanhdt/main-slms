<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/lib/axios'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps<{
  serviceUuid: string
  serviceType?: string
  // true = layanan yang harganya SEPENUHNYA dari daftar pilihan ini
  // (misal Edit Foto) — mengubah teks supaya tidak kesan "tambahan".
  optionOnly?: boolean
}>()

const queryClient = useQueryClient()

const { data: options, isLoading } = useQuery({
  queryKey: ['service-options', props.serviceUuid],
  queryFn: async () => {
    const res = await api.get(`/services/${props.serviceUuid}/options`)
    return res.data.data
  },
  enabled: computed(() => !!props.serviceUuid),
})

function emptyOption() {
  return {
    name: '',
    description: '',
    price_type: 'flat',
    price: 0,
    extra_minutes: null as number | null,
  }
}

const newOption = ref(emptyOption())
const isAdding = ref(false)

const isCustomOption = computed(() => newOption.value.price_type === 'custom')

function invalidate() {
  queryClient.invalidateQueries({ queryKey: ['service-options', props.serviceUuid] })
}

const { mutate: addOption, isPending: isSavingOption } = useMutation({
  mutationFn: () =>
    api.post(`/services/${props.serviceUuid}/options`, {
      ...newOption.value,
      // Opsi custom: harga real ditentukan staff/customer saat booking,
      // jadi kolom price di sini tidak relevan — kirim 0 sebagai placeholder.
      price: isCustomOption.value ? 0 : newOption.value.price,
      description: newOption.value.description || null,
    }),
  onSuccess: () => {
    toast.success('Pilihan berhasil ditambahkan.')
    newOption.value = emptyOption()
    isAdding.value = false
    invalidate()
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal menambah pilihan.')
  },
})

const { mutate: deleteOption } = useMutation({
  mutationFn: (optionUuid: string) =>
    api.delete(`/services/${props.serviceUuid}/options/${optionUuid}`),
  onSuccess: () => {
    toast.success('Pilihan berhasil dihapus.')
    invalidate()
  },
  onError: (err: any) => {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus pilihan.')
  },
})

function confirmDelete(option: any) {
  if (confirm(`Hapus pilihan "${option.name}"?`)) {
    deleteOption(option.uuid)
  }
}

function formatPrice(price: string | number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

const PRICE_TYPE_LABELS: Record<string, string> = {
  flat: 'Harga Tetap',
  per_hour: 'Per Jam',
  per_photo: 'Per Foto',
  custom: 'Custom (saat booking)',
}

// Tombol simpan hanya aktif kalau syarat per tipe harga terpenuhi:
// - custom: wajib ada deskripsi (biar customer tidak lihat "Rp 0" tanpa penjelasan)
// - lainnya: wajib ada harga > 0
const canSaveOption = computed(() => {
  if (!newOption.value.name) return false
  if (isCustomOption.value) return !!newOption.value.description
  return !!newOption.value.price
})
</script>

<template>
  <div class="space-y-3">
    <div>
      <p class="text-sm font-medium text-gray-700">
        {{ optionOnly ? 'Daftar Pilihan Editing' : 'Opsi Tambahan' }}
      </p>
      <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">
        <template v-if="optionOnly">
          Ini daftar layanan editing yang bisa dipilih customer (boleh lebih dari satu sekaligus).
          Total booking dihitung murni dari yang dicentang — pastikan setiap pilihan punya harga
          jelas dan deskripsi yang menjelaskan cakupannya.
        </template>
        <template v-else>
          Misal untuk layanan Edit Foto: Retouch, Remove Background, Color Grading. Customer bisa
          memilih beberapa opsi sekaligus saat booking, di luar harga dasar layanan.
        </template>
      </p>
    </div>

    <div v-if="isLoading" class="text-sm text-gray-400">Memuat pilihan...</div>

    <div v-else class="space-y-2">
      <div
        v-for="option in options"
        :key="option.uuid"
        class="flex items-start justify-between gap-3 border border-gray-200 rounded-lg px-3 py-2"
      >
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-900 truncate">{{ option.name }}</p>
          <p class="text-xs text-gray-400">
            {{ PRICE_TYPE_LABELS[option.price_type?.value] ?? option.price_type }}
            <template v-if="option.price_type?.value !== 'custom'">
              &middot; {{ formatPrice(option.price) }}
            </template>
            <span v-if="option.extra_minutes"> &middot; +{{ option.extra_minutes }} menit</span>
          </p>
          <p v-if="option.description" class="text-xs text-gray-500 mt-1 leading-relaxed">
            {{ option.description }}
          </p>
        </div>
        <button
          type="button"
          title="Hapus pilihan"
          class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0"
          @click="confirmDelete(option)"
        >
          <Trash2 class="size-3.5" />
        </button>
      </div>

      <p v-if="!options?.length" class="text-xs text-amber-600 py-2">
        {{
          optionOnly
            ? 'Belum ada pilihan editing sama sekali — layanan ini belum bisa dibooking customer sampai minimal 1 pilihan ditambahkan.'
            : 'Belum ada opsi tambahan. Layanan ini akan tampil sebagai satuan biasa tanpa pilihan.'
        }}
      </p>
    </div>

    <!-- Form tambah pilihan baru -->
    <div v-if="isAdding" class="border border-dashed border-gray-300 rounded-lg p-3 space-y-2">
      <input
        v-model="newOption.name"
        type="text"
        placeholder="Nama pilihan (misal: Retouch)"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <textarea
        v-model="newOption.description"
        rows="2"
        placeholder="Jelaskan apa saja yang termasuk di pilihan ini, supaya customer tidak bingung..."
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
      />
      <div class="grid grid-cols-2 gap-2">
        <select
          v-model="newOption.price_type"
          class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="flat">Harga Tetap</option>
          <option value="per_hour">Per Jam</option>
          <option value="per_photo">Per Foto</option>
          <option value="custom">Custom (saat booking)</option>
        </select>
        <input
          v-if="!isCustomOption"
          v-model.number="newOption.price"
          type="number"
          min="0"
          placeholder="Harga (Rp)"
          class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <input
        v-if="newOption.price_type === 'per_hour'"
        v-model.number="newOption.extra_minutes"
        type="number"
        min="0"
        placeholder="Tambahan waktu pengerjaan (menit, opsional)"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />

      <p v-if="isCustomOption" class="text-xs text-amber-600">
        Harga custom ditentukan staff/customer saat proses booking (tidak ditampilkan sebagai angka
        tetap). Wajib isi deskripsi di atas supaya customer tahu persis cakupan pilihan ini — jangan
        dibiarkan kosong, karena "custom" tanpa penjelasan akan terlihat mencurigakan.
      </p>
      <p v-else-if="!newOption.price" class="text-xs text-amber-600">
        Harga masih 0 — customer akan melihat pilihan ini seperti gratis. Isi harga sebenarnya
        sebelum disimpan.
      </p>

      <div class="flex gap-2">
        <Button size="sm" :disabled="!canSaveOption || isSavingOption" @click="addOption()">
          {{ isSavingOption ? 'Menyimpan...' : 'Simpan Pilihan' }}
        </Button>
        <Button
          size="sm"
          variant="ghost"
          @click="((isAdding = false), (newOption = emptyOption()))"
        >
          Batal
        </Button>
      </div>
    </div>

    <Button v-else type="button" variant="outline" size="sm" @click="isAdding = true">
      <Plus class="size-3.5" />
      Tambah Pilihan
    </Button>
  </div>
</template>
