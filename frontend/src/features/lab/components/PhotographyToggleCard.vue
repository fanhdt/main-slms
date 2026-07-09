<script setup lang="ts">
import { useMutation } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'

const labStore = useLabStore()

const { mutate: toggle, isPending } = useMutation({
  mutationFn: async () => {
    if (!labStore.activeLab) throw new Error('Lab belum dimuat.')
    const res = await labApi.update(labStore.activeLab.uuid, {
      is_photography_lab: !labStore.activeLab.is_photography_lab,
    })
    return res.data.data
  },
  onSuccess: (updatedLab) => {
    labStore.activeLab = updatedLab
    toast.success(
      updatedLab.is_photography_lab
        ? 'Fitur Portofolio Fotografer diaktifkan untuk lab ini.'
        : 'Fitur Portofolio Fotografer dinonaktifkan.',
    )
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal mengubah pengaturan.')
  },
})
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-sm font-semibold text-gray-900">Lab Fotografi</h3>
        <p class="text-xs text-gray-400 mt-0.5 max-w-md">
          Kalau aktif, menu <span class="font-medium">Portofolio</span> muncul di sidebar dan
          landing page publik lab ini menampilkan galeri hasil jepretan tiap fotografer.
        </p>
      </div>

      <button
        role="switch"
        :aria-checked="labStore.activeLab?.is_photography_lab"
        :disabled="isPending"
        @click="toggle()"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-50"
        :class="labStore.activeLab?.is_photography_lab ? 'bg-blue-600' : 'bg-gray-300'"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
          :class="labStore.activeLab?.is_photography_lab ? 'translate-x-6' : 'translate-x-1'"
        />
      </button>
    </div>
  </div>
</template>
