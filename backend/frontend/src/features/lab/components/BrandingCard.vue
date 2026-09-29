<script setup lang="ts">
import { ref } from 'vue'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { labApi } from '@/features/lab/api/labApi'
import { toast } from 'vue-sonner'
import { ImageUpload } from '@/components/ui/image-upload'

const labStore = useLabStore()
const uploadingLogo = ref(false)
const uploadingHero = ref(false)

async function handleSelect(file: File, type: 'logo' | 'hero_image') {
  if (!labStore.activeLab) return
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
  }
}

async function handleRemove(type: 'logo' | 'hero_image') {
  if (!labStore.activeLab) return
  const flag = type === 'logo' ? uploadingLogo : uploadingHero
  flag.value = true
  try {
    const res = await labApi.removeImage(labStore.activeLab.uuid, type)
    labStore.activeLab = res.data.data
    toast.success(type === 'logo' ? 'Logo berhasil dihapus.' : 'Foto sampul berhasil dihapus.')
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal menghapus gambar.')
  } finally {
    flag.value = false
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
      <ImageUpload
        :model-value="labStore.activeLab?.branding.logo ?? null"
        label="Logo"
        shape="circle"
        aspect="square"
        :loading="uploadingLogo"
        @select="(file) => handleSelect(file, 'logo')"
        @remove="() => handleRemove('logo')"
      />

      <ImageUpload
        :model-value="labStore.activeLab?.branding.hero_image ?? null"
        label="Foto Sampul (Hero)"
        aspect="video"
        :loading="uploadingHero"
        @select="(file) => handleSelect(file, 'hero_image')"
        @remove="() => handleRemove('hero_image')"
      />
    </div>
  </div>
</template>
