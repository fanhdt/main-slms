<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Lab } from '@/types'

const props = defineProps<{
  show: boolean
  lab?: Lab | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()

const form = ref({
  name: '',
  slug: '',
  description: '',
  primary_color: '#1a1a2e',
  secondary_color: '#e94560',
  is_active: true,
  contact: {
    email: '',
    phone: '',
    address: '',
  },
})

const errors = ref<Record<string, string>>({})
const isEdit = ref(false)

// Auto generate slug dari nama
function generateSlug(name: string) {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim()
}

watch(
  () => form.value.name,
  (name) => {
    if (!isEdit.value) {
      form.value.slug = generateSlug(name)
    }
  },
)

watch(
  () => props.lab,
  (lab) => {
    isEdit.value = !!lab
    if (lab) {
      form.value = {
        name: lab.name,
        slug: lab.slug,
        description: lab.description ?? '',
        primary_color: lab.branding.primary_color ?? '#1a1a2e',
        secondary_color: lab.branding.secondary_color ?? '#e94560',
        is_active: lab.is_active,
        contact: {
          email: lab.contact?.email ?? '',
          phone: lab.contact?.phone ?? '',
          address: lab.contact?.address ?? '',
        },
      }
    } else {
      form.value = {
        name: '',
        slug: '',
        description: '',
        primary_color: '#1a1a2e',
        secondary_color: '#e94560',
        is_active: true,
        contact: {
          email: '',
          phone: '',
          address: '',
        },
      }
    }
  },
  { immediate: true },
)

const { mutate: saveLab, isPending } = useMutation({
  mutationFn: async () => {
    if (isEdit.value && props.lab) {
      return labApi.update(props.lab.uuid, form.value)
    } else {
      return labApi.create(form.value)
    }
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['labs'] })
    toast.success(isEdit.value ? 'Lab berhasil diupdate.' : 'Lab berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]]),
      )
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit Lab' : 'Tambah Lab'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="saveLab" class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <!-- Nama -->
        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Nama Lab</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Laboratorium Fotografi"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-400': errors.name }"
          />
          <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
        </div>

        <!-- Slug -->
        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">
            Slug
            <span class="text-gray-400 font-normal">(auto-generate dari nama)</span>
          </label>
          <input
            v-model="form.slug"
            type="text"
            placeholder="laboratorium-fotografi"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            :class="{ 'border-red-400': errors.slug }"
          />
          <p v-if="errors.slug" class="text-xs text-red-500">{{ errors.slug }}</p>
        </div>

        <!-- Primary Color -->
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Warna Primer</label>
          <div class="flex gap-2 items-center">
            <input
              v-model="form.primary_color"
              type="color"
              class="w-10 h-10 rounded border border-gray-300 cursor-pointer"
            />
            <input
              v-model="form.primary_color"
              type="text"
              placeholder="#1a1a2e"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>
        </div>

        <!-- Secondary Color -->
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Warna Sekunder</label>
          <div class="flex gap-2 items-center">
            <input
              v-model="form.secondary_color"
              type="color"
              class="w-10 h-10 rounded border border-gray-300 cursor-pointer"
            />
            <input
              v-model="form.secondary_color"
              type="text"
              placeholder="#e94560"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Deskripsi laboratorium..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Kontak -->
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Email Kontak</label>
          <input
            v-model="form.contact.email"
            type="email"
            placeholder="lab@slms.local"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Telepon</label>
          <input
            v-model="form.contact.phone"
            type="tel"
            placeholder="08123456789"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Alamat</label>
          <input
            v-model="form.contact.address"
            type="text"
            placeholder="Gedung A, Lantai 2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Status -->
        <div class="col-span-2 flex items-center gap-3">
          <input
            v-model="form.is_active"
            type="checkbox"
            id="lab_is_active"
            class="w-4 h-4 rounded border-gray-300"
          />
          <label for="lab_is_active" class="text-sm font-medium text-gray-700"> Lab Aktif </label>
        </div>
      </div>
    </form>

    <template #footer>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
      >
        Batal
      </button>
      <button
        @click="saveLab()"
        :disabled="isPending"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg transition-colors"
      >
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </button>
    </template>
  </BaseModal>
</template>
