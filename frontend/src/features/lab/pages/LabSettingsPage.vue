<script setup lang="ts">
import GeneralInfoCard from '@/features/lab/components/GeneralInfoCard.vue'
import BrandingCard from '@/features/lab/components/BrandingCard.vue'
import PhotographyToggleCard from '@/features/lab/components/PhotographyToggleCard.vue'
import RentalRatesCard from '@/features/lab/components/RentalRatesCard.vue'
import { useLabStore } from '@/features/lab/stores/useLabStore'

const labStore = useLabStore()
</script>

<template>
  <div class="space-y-6 max-w-3xl">
    <div>
      <h2 class="text-2xl font-bold text-gray-900">Pengaturan Lab</h2>
      <p class="text-gray-500 mt-1 text-sm">
        Pengaturan untuk
        <span class="font-medium text-gray-700">{{ labStore.activeLab?.name ?? 'lab ini' }}</span
        >.
      </p>
    </div>

    <GeneralInfoCard />
    <BrandingCard />
    <PhotographyToggleCard />
    <RentalRatesCard />
  </div>
</template>
