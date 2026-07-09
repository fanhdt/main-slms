<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { portfolioApi } from '@/features/portfolio/api/portfolioApi'
import { photographerApi } from '@/features/portfolio/api/photographerApi'
import type { Photographer } from '@/features/portfolio/types'

const props = defineProps<{ labId: number; primaryColor?: string; secondaryColor?: string }>()

const activePhotographer = ref<Photographer | null>(null)
const page = ref(1)

const { data: photographers } = useQuery({
  queryKey: ['portfolio-photographers-public', props.labId],
  queryFn: async () => {
    const res = await photographerApi.getPublicForLab(props.labId)
    return res.data.data
  },
})

watch(photographers, (list) => {
  if (list?.length && !activePhotographer.value) {
    activePhotographer.value = list[0] ?? null
  }
})

function selectPhotographer(p: Photographer) {
  activePhotographer.value = p
  page.value = 1
}

const { data: gallery, isLoading } = useQuery({
  queryKey: [
    'portfolio-gallery',
    props.labId,
    computed(() => activePhotographer.value?.uuid),
    page,
  ],
  queryFn: async () => {
    const res = await portfolioApi.getGalleryByPhotographer(
      props.labId,
      activePhotographer.value!.uuid,
      page.value,
    )
    return res.data.data
  },
  enabled: computed(() => !!activePhotographer.value),
})
</script>

<template>
  <section v-if="photographers?.length" id="portfolio" class="py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
      <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-900">Portofolio Fotografer</h2>
        <p class="text-gray-500 mt-2">Lihat hasil jepretan dan editan tiap fotografer kami</p>
      </div>

      <!-- Tab avatar fotografer -->
      <div class="flex flex-wrap justify-center gap-4 mb-6">
        <button
          v-for="p in photographers"
          :key="p.uuid"
          @click="selectPhotographer(p)"
          class="flex flex-col items-center gap-2 group"
        >
          <div
            class="w-16 h-16 rounded-full overflow-hidden border-2 transition-colors"
            :style="{
              borderColor:
                activePhotographer?.uuid === p.uuid ? (secondaryColor ?? '#e94560') : '#e5e7eb',
            }"
          >
            <img v-if="p.photo" :src="p.photo" :alt="p.name" class="w-full h-full object-cover" />
            <div
              v-else
              class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 font-bold"
            >
              {{ p.name.charAt(0) }}
            </div>
          </div>
          <span
            class="text-xs font-medium"
            :class="activePhotographer?.uuid === p.uuid ? 'text-gray-900' : 'text-gray-500'"
          >
            {{ p.name }}
          </span>
        </button>
      </div>

      <!-- Bio fotografer aktif -->
      <div
        v-if="activePhotographer?.bio"
        class="text-center max-w-md mx-auto mb-8 text-sm text-gray-500"
      >
        {{ activePhotographer.bio }}

        <a
          v-if="activePhotographer.instagram"
          :href="`https://instagram.com/${activePhotographer.instagram}`"
          target="_blank"
          class="block mt-1 text-xs font-medium"
          :style="{ color: secondaryColor ?? '#e94560' }"
        >
          @{{ activePhotographer.instagram }}
        </a>
      </div>

      <!-- Galeri milik fotografer aktif -->
      <div v-if="isLoading" class="text-center py-12 text-gray-400">Memuat galeri...</div>

      <template v-else-if="gallery">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="photo in gallery.data"
            :key="photo.uuid"
            class="aspect-square rounded-xl overflow-hidden bg-gray-200 group relative"
          >
            <img
              :src="photo.image"
              :alt="photo.caption ?? activePhotographer?.name ?? ''"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div
              v-if="photo.caption"
              class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-xs p-2 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              {{ photo.caption }}
            </div>
          </div>
        </div>

        <div v-if="gallery.data.length === 0" class="text-center py-12 text-gray-400">
          Belum ada foto dari {{ activePhotographer?.name }}.
        </div>

        <!-- Pagination per fotografer -->
        <div
          v-if="gallery.meta.last_page > 1"
          class="flex items-center justify-center gap-3 mt-8 text-sm text-gray-600"
        >
          <button
            :disabled="page <= 1"
            @click="page--"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            ← Sebelumnya
          </button>
          <span>Halaman {{ gallery.meta.current_page }} dari {{ gallery.meta.last_page }}</span>
          <button
            :disabled="page >= gallery.meta.last_page"
            @click="page++"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-50"
          >
            Selanjutnya →
          </button>
        </div>
      </template>
    </div>
  </section>
</template>
