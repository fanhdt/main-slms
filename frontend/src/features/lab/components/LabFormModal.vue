<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { labApi } from '@/features/lab/api/labApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { Lab } from '@/types'
import { defaultCreateLabForm } from '@/features/lab/types'

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
const logoInputRef = ref<HTMLInputElement | null>(null)
const heroInputRef = ref<HTMLInputElement | null>(null)
const isUploadingImage = ref<string | null>(null) // 'logo' | 'hero_image' | null

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
    // Field harga sewa dikirim lewat endpoint khusus (rental-rates),
    // bukan ikut payload lab biasa — supaya settings.class_hours (RFID) tidak ketimpa.
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

async function handleImageChange(e: Event, type: 'logo' | 'hero_image') {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !props.lab) return

  isUploadingImage.value = type
  try {
    const res = await labApi.updateImage(props.lab.uuid, type, file)
    // Update branding di form.value biar preview langsung berubah tanpa perlu tutup modal
    const updatedLab = res.data.data as Lab
    if (type === 'logo') form.value.logoPreview = updatedLab.branding.logo
    if (type === 'hero_image') form.value.heroPreview = updatedLab.branding.hero_image
    queryClient.invalidateQueries({ queryKey: ['labs'] })
    toast.success(`${type === 'logo' ? 'Logo' : 'Hero image'} berhasil diupdate.`)
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    isUploadingImage.value = null
    input.value = ''
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
    <form @submit.prevent="() => saveLab()" class="space-y-4">
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

        <!-- Harga Sewa -->
        <div class="col-span-2 border-t border-gray-100 pt-4">
          <p class="text-sm font-medium text-gray-700 mb-1">Harga Sewa Lab (per jam)</p>
          <p class="text-xs text-gray-400 mb-3">
            Dipakai saat mahasiswa/organisasi meminjam lab di luar jam kuliah. Booking dengan
            keperluan "Akademik" (jam kuliah) selalu gratis otomatis.
          </p>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium text-gray-700">Mahasiswa / Organisasi (Rp/jam)</label>
          <input
            v-model.number="form.student_price_per_hour"
            type="number"
            min="0"
            step="1000"
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

        <!-- Gambar Lab — cuma muncul saat edit -->
        <div v-if="isEdit" class="col-span-2 border-t border-gray-100 pt-4 space-y-4">
          <p class="text-sm font-medium text-gray-700">Gambar Lab</p>

          <div class="flex gap-6">
            <!-- Logo -->
            <div class="text-center">
              <div
                class="w-20 h-20 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center mb-2 mx-auto"
              >
                <img
                  v-if="form.logoPreview"
                  :src="form.logoPreview"
                  alt="Logo"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-xs text-gray-400">No logo</span>
              </div>
              <button
                type="button"
                @click="logoInputRef?.click()"
                :disabled="isUploadingImage === 'logo'"
                class="text-xs text-blue-600 hover:underline disabled:opacity-50"
              >
                {{ isUploadingImage === 'logo' ? 'Mengupload...' : 'Ganti Logo' }}
              </button>
              <input
                ref="logoInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleImageChange($event, 'logo')"
              />
            </div>

            <!-- Hero Image -->
            <div class="text-center">
              <div
                class="w-32 h-20 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center mb-2 mx-auto"
              >
                <img
                  v-if="form.heroPreview"
                  :src="form.heroPreview"
                  alt="Hero"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-xs text-gray-400">No hero image</span>
              </div>
              <button
                type="button"
                @click="heroInputRef?.click()"
                :disabled="isUploadingImage === 'hero_image'"
                class="text-xs text-blue-600 hover:underline disabled:opacity-50"
              >
                {{ isUploadingImage === 'hero_image' ? 'Mengupload...' : 'Ganti Hero Image' }}
              </button>
              <input
                ref="heroInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                @change="handleImageChange($event, 'hero_image')"
              />
            </div>
          </div>
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
