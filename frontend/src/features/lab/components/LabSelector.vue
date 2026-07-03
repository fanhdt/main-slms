<script setup lang="ts">
import { onMounted } from 'vue'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useBranding } from '@/composables/useBranding'

const labStore = useLabStore()
const { labName, primaryBgStyle } = useBranding()

onMounted(async () => {
  await labStore.fetchLabs()
  await labStore.restoreFromStorage()
})

async function handleSelect(event: Event) {
  const slug = (event.target as HTMLSelectElement).value
  if (slug) {
    await labStore.setActiveLab(slug)
  }
}
</script>

<template>
  <div class="px-4 py-3 border-b border-gray-200">
    <!-- Active Lab Badge -->
    <div v-if="labStore.activeLab" class="flex items-center gap-2 mb-2">
      <div class="w-3 h-3 rounded-full flex-shrink-0" :style="primaryBgStyle" />
      <span class="text-xs font-medium text-gray-700 truncate">
        {{ labName }}
      </span>
    </div>

    <!-- Selector -->
    <select
      :value="labStore.activeLab?.slug ?? ''"
      @change="handleSelect"
      class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
    >
      <option value="" disabled>Pilih Laboratorium</option>
      <option v-for="lab in labStore.labs" :key="lab.uuid" :value="lab.slug">
        {{ lab.name }}
      </option>
    </select>
  </div>
</template>
