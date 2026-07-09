<script setup lang="ts">
import { ref } from 'vue'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { labApi } from '@/features/lab/api/labApi'
import { toast } from 'vue-sonner'

const labStore = useLabStore()
const uploadingLogo = ref(false)
const uploadingHero = ref(false)

async function handleUpload(e: Event, type: 'logo' | 'hero_image') {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !labStore.activeLab) return

  const flag = type === 'logo' ? uploadingLogo : uploadingHero
  flag.value = true
  try {
    const res = await labApi.updateImage(labStore.activeLab.uuid, type, file)
    labStore.activeLab = res.data.data
    toast.success(type === 'logo' ? 'Logo berhasil diganti.' : 'Foto sampul berhasil diganti.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal upload gambar.')
  } finally {
    flag.value = false
    input.value = ''
  }
}
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-5">
    <div>
      <h3 class="text-sm font-semibold text-gray-900">Branding & Gambar</h3>
      <p class="text-xs text-gray-400 mt-0.5">
        Logo dan foto sampul lab. Ini yang tampil di landing page — bukan inisial otomatis.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <!-- Logo -->
      <div class="space-y-2">
        <label class="text-sm font-medium text-gray-700">Logo</label>
        <div
          class="w-24 h-24 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center"
        >
          <img
            v-if="labStore.activeLab?.branding.logo"
            :src="labStore.activeLab.branding.logo"
            alt="Logo"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-xs text-gray-400">Belum ada</span>
        </div>
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          :disabled="uploadingLogo"
          @change="handleUpload($event, 'logo')"
          class="text-xs"
        />
        <p v-if="uploadingLogo" class="text-xs text-gray-400">Mengupload...</p>
      </div>

      <!-- Hero Image -->
      <div class="space-y-2">
        <label class="text-sm font-medium text-gray-700">Foto Sampul (Hero)</label>
        <div
          class="w-full aspect-video rounded-xl bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center"
        >
          <img
            v-if="labStore.activeLab?.branding.hero_image"
            :src="labStore.activeLab.branding.hero_image"
            alt="Hero"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-xs text-gray-400">Belum ada</span>
        </div>
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          :disabled="uploadingHero"
          @change="handleUpload($event, 'hero_image')"
          class="text-xs"
        />
        <p v-if="uploadingHero" class="text-xs text-gray-400">Mengupload...</p>
      </div>
    </div>
  </div>
</template>
