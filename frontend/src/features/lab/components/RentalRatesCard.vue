<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'

const labStore = useLabStore()

const form = ref({
  student_price_per_hour: 0,
  public_price_per_hour: 0,
})

watch(
  () => labStore.activeLab,
  (lab) => {
    if (lab) {
      form.value = {
        student_price_per_hour: Number(lab.lab_rental_rates?.student_price_per_hour ?? 0),
        public_price_per_hour: Number(lab.lab_rental_rates?.public_price_per_hour ?? 0),
      }
    }
  },
  { immediate: true },
)

const { mutate: saveRates, isPending } = useMutation({
  mutationFn: async () => {
    if (!labStore.activeLab) throw new Error('Lab belum dimuat.')
    const res = await labApi.updateRentalRates(labStore.activeLab.uuid, form.value)
    return res.data.data
  },
  onSuccess: (updatedLab) => {
    labStore.activeLab = updatedLab
    toast.success('Harga sewa lab berhasil disimpan.')
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal menyimpan harga sewa.')
  },
})
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Harga Sewa Lab (per jam)</h3>
      <p class="text-xs text-gray-400 mt-0.5">
        Dipakai saat mahasiswa/organisasi meminjam lab di luar jam kuliah. Booking dengan keperluan
        "Akademik" (jam kuliah) selalu gratis otomatis.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Mahasiswa / Organisasi (Rp / jam)</label>
        <input
          v-model.number="form.student_price_per_hour"
          type="number"
          min="0"
          step="1000"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Umum / Publik (Rp / jam)</label>
        <input
          v-model.number="form.public_price_per_hour"
          type="number"
          min="0"
          step="1000"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>

    <div class="flex justify-end">
      <button
        @click="saveRates()"
        :disabled="isPending"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg transition-colors"
      >
        {{ isPending ? 'Menyimpan...' : 'Simpan Harga' }}
      </button>
    </div>
  </div>
</template>
