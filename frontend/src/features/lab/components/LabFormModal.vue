<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Lab } from '@/types'
import { defaultCreateLabForm } from '@/features/lab/types'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { ImageUpload } from '@/components/ui/image-upload'

const props = defineProps<{
  show: boolean
  lab?: Lab | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()

const form = ref(defaultCreateLabForm())

const errors = ref<Record<string, string>>({})
const isEdit = ref(false)
const isUploadingImage = ref<'logo' | 'hero_image' | null>(null)

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
        student_price_per_hour: Number(lab.lab_rental_rates?.student_price_per_hour ?? 0),
        public_price_per_hour: Number(lab.lab_rental_rates?.public_price_per_hour ?? 0),
        contact: {
          email: lab.contact?.email ?? '',
          phone: lab.contact?.phone ?? '',
          address: lab.contact?.address ?? '',
        },
        logoPreview: lab.branding.logo ?? null,
        heroPreview: lab.branding.hero_image ?? null,
      }
    } else {
      form.value = defaultCreateLabForm()
    }
  },
  { immediate: true },
)

const { mutate: saveLab, isPending } = useMutation({
  mutationFn: async () => {
    const { student_price_per_hour, public_price_per_hour, ...labData } = form.value

    if (isEdit.value && props.lab) {
      const res = await labApi.update(props.lab.uuid, labData)
      await labApi.updateRentalRates(props.lab.uuid, {
        student_price_per_hour: student_price_per_hour ?? 0,
        public_price_per_hour: public_price_per_hour ?? 0,
      })
      return res
    } else {
      const res = await labApi.create(labData)
      const newUuid = (res.data.data as Lab).uuid
      await labApi.updateRentalRates(newUuid, {
        student_price_per_hour: student_price_per_hour ?? 0,
        public_price_per_hour: public_price_per_hour ?? 0,
      })
      return res
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
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})

async function handleImageSelect(file: File, type: 'logo' | 'hero_image') {
  if (!props.lab) return

  isUploadingImage.value = type
  try {
    const res = await labApi.updateImage(props.lab.uuid, type, file)
    const updatedLab = res.data.data as Lab
    if (type === 'logo') form.value.logoPreview = updatedLab.branding.logo
    if (type === 'hero_image') form.value.heroPreview = updatedLab.branding.hero_image
    queryClient.invalidateQueries({ queryKey: ['labs'] })
    toast.success(`${type === 'logo' ? 'Logo' : 'Hero image'} berhasil diupdate.`)
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = null
  }
}

async function handleImageRemove(type: 'logo' | 'hero_image') {
  if (!props.lab) return

  isUploadingImage.value = type
  try {
    const res = await labApi.removeImage(props.lab.uuid, type)
    const updatedLab = res.data.data as Lab
    if (type === 'logo') form.value.logoPreview = updatedLab.branding.logo
    if (type === 'hero_image') form.value.heroPreview = updatedLab.branding.hero_image
    queryClient.invalidateQueries({ queryKey: ['labs'] })
    toast.success(`${type === 'logo' ? 'Logo' : 'Hero image'} berhasil dihapus.`)
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus gambar.')
  } finally {
    isUploadingImage.value = null
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit Lab' : 'Tambah Lab'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => saveLab()" class="space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Nama -->
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
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
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">
            Slug <span class="text-gray-400 font-normal">(auto-generate dari nama)</span>
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
              class="w-10 h-10 shrink-0 rounded border border-gray-300 cursor-pointer"
            />
            <input
              v-model="form.primary_color"
              type="text"
              placeholder="#1a1a2e"
              class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
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
              class="w-10 h-10 shrink-0 rounded border border-gray-300 cursor-pointer"
            />
            <input
              v-model="form.secondary_color"
              type="text"
              placeholder="#e94560"
              class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
            />
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="col-span-1 sm:col-span-2 space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Deskripsi</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Deskripsi laboratorium..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <Separator />

      <!-- Harga Sewa -->
      <div class="space-y-3">
        <div>
          <p class="text-sm font-medium text-gray-700">Harga Sewa Lab (per jam)</p>
          <p class="text-xs text-gray-400 mt-0.5">
            Dipakai saat mahasiswa/organisasi meminjam lab di luar jam kuliah. Booking dengan
            keperluan "Akademik" (jam kuliah) selalu gratis otomatis.
          </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Mahasiswa / Organisasi (Rp/jam)</label>
            <input
              v-model.number="form.student_price_per_hour"
              type="number"
              min="0"
              step="1000"
              inputmode="numeric"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Umum / Publik (Rp/jam)</label>
            <input
              v-model.number="form.public_price_per_hour"
              type="number"
              min="0"
              step="1000"
              inputmode="numeric"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <Separator />

      <!-- Kontak -->
      <div class="space-y-3">
        <p class="text-sm font-medium text-gray-700">Kontak</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
          <div class="col-span-1 sm:col-span-2 space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Alamat</label>
            <input
              v-model="form.contact.address"
              type="text"
              placeholder="Gedung A, Lantai 2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Gambar Lab — cuma muncul saat edit -->
      <template v-if="isEdit">
        <Separator />
        <div class="space-y-4">
          <p class="text-sm font-medium text-gray-700">Gambar Lab</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Logo -->
            <ImageUpload
              v-model="form.logoPreview"
              label="Logo"
              shape="circle"
              aspect="square"
              :loading="isUploadingImage === 'logo'"
              @select="(file) => handleImageSelect(file, 'logo')"
              @remove="() => handleImageRemove('logo')"
            />

            <!-- Hero Image -->
            <ImageUpload
              v-model="form.heroPreview"
              label="Hero Image"
              aspect="video"
              :loading="isUploadingImage === 'hero_image'"
              @select="(file) => handleImageSelect(file, 'hero_image')"
              @remove="() => handleImageRemove('hero_image')"
            />
          </div>
        </div>
      </template>

      <Separator />

      <!-- Status -->
      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="lab_is_active"
          class="w-4 h-4 rounded border-gray-300 shrink-0"
        />
        <label for="lab_is_active" class="text-sm font-medium text-gray-700">Lab Aktif</label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" class="w-full sm:w-auto" @click="$emit('close')">Batal</Button>
      <Button class="w-full sm:w-auto" :disabled="isPending" @click="saveLab()">
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
