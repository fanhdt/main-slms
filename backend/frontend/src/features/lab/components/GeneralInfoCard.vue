<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'

const labStore = useLabStore()

const form = ref({
  name: '',
  description: '',
  contact: { email: '', phone: '', address: '' },
})

watch(
  () => labStore.activeLab,
  (lab) => {
    if (lab) {
      form.value = {
        name: lab.name,
        description: lab.description ?? '',
        contact: {
          email: lab.contact?.email ?? '',
          phone: lab.contact?.phone ?? '',
          address: lab.contact?.address ?? '',
        },
      }
    }
  },
  { immediate: true },
)

const { mutate: save, isPending } = useMutation({
  mutationFn: async () => {
    if (!labStore.activeLab) throw new Error('Lab belum dimuat.')
    const res = await labApi.update(labStore.activeLab.uuid, form.value)
    return res.data.data
  },
  onSuccess: (updatedLab) => {
    labStore.activeLab = updatedLab
    toast.success('Info lab berhasil disimpan.')
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal menyimpan info lab.')
  },
})
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Info Umum</h3>
      <p class="text-xs text-gray-400 mt-0.5">
        Nama, deskripsi, dan kontak yang tampil di landing page publik.
      </p>
    </div>

    <div class="space-y-1.5">
      <label class="text-sm font-medium text-gray-700">Nama Lab</label>
      <input
        v-model="form.name"
        type="text"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="space-y-1.5">
      <label class="text-sm font-medium text-gray-700">Deskripsi</label>
      <textarea
        v-model="form.description"
        rows="3"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
      />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Email</label>
        <input
          v-model="form.contact.email"
          type="email"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Telepon</label>
        <input
          v-model="form.contact.phone"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Alamat</label>
        <input
          v-model="form.contact.address"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>

    <div class="flex justify-end">
      <button
        @click="save()"
        :disabled="isPending"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg transition-colors"
      >
        {{ isPending ? 'Menyimpan...' : 'Simpan Info' }}
      </button>
    </div>
  </div>
</template>
