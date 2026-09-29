<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { reportApi } from '@/features/report/api/reportApi'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  FileSpreadsheet,
  FileText,
  ClipboardList,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const labStore = useLabStore()
const activeTab = ref<'daily' | 'weekly'>('daily')
const page = ref(1)

const { data, isLoading } = useQuery({
  queryKey: ['reports', labStore.activeLab?.id, activeTab, page],
  queryFn: async () => {
    const res = await reportApi.getAll({
      lab_id: labStore.activeLab?.id,
      type: activeTab.value,
      page: page.value,
    })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab?.id),
})

function formatPrice(price: string) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(price))
}

function formatPeriod(start: string, end: string, type: string) {
  const s = new Date(start).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
  if (type === 'daily') return s
  const e = new Date(end).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
  return `${s} – ${e}`
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-gray-900">Laporan</h1>
      <p class="text-sm text-gray-500 mt-0.5">
        Rekap otomatis booking harian & mingguan, dikirim juga ke email lab admin.
      </p>
    </div>

    <div class="flex gap-2">
      <button
        v-for="tab in ['daily', 'weekly'] as const"
        :key="tab"
        @click="((activeTab = tab), (page = 1))"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="
          activeTab === tab
            ? 'bg-gray-900 text-white'
            : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
        "
      >
        {{ tab === 'daily' ? 'Harian' : 'Mingguan' }}
      </button>
    </div>

    <div v-if="isLoading" class="space-y-3">
      <Skeleton v-for="i in 4" :key="i" class="h-20 w-full rounded-xl" />
    </div>

    <Card v-else-if="!data?.data?.length" class="p-0">
      <CardContent class="p-10 text-center">
        <ClipboardList class="size-8 mx-auto text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">Belum ada laporan untuk periode ini.</p>
      </CardContent>
    </Card>

    <div v-else class="space-y-3">
      <Card v-for="report in data.data" :key="report.uuid" class="p-0">
        <CardContent class="p-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="font-medium text-gray-900">
              {{ formatPeriod(report.period_start, report.period_end, report.type.value) }}
            </p>
            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
              <span>{{ report.total_bookings }} booking</span>
              <span>&middot;</span>
              <span>{{ formatPrice(report.total_revenue) }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Badge variant="outline" class="border-0 bg-blue-50 text-blue-700">
              {{ report.type.label }}
            </Badge>
            <a :href="report.excel_url" target="_blank" rel="noopener">
              <Button variant="outline" size="sm">
                <FileSpreadsheet class="size-3.5" />
                Excel
              </Button>
            </a>
            <a :href="report.pdf_url" target="_blank" rel="noopener">
              <Button variant="outline" size="sm">
                <FileText class="size-3.5" />
                PDF
              </Button>
            </a>
          </div>
        </CardContent>
      </Card>

      <div
        v-if="data?.meta && data.meta.last_page > 1"
        class="flex items-center justify-between text-sm text-gray-600 pt-2"
      >
        <span>Halaman {{ data.meta.current_page }} dari {{ data.meta.last_page }}</span>
        <div class="flex gap-2">
          <Button variant="outline" size="icon-sm" :disabled="page <= 1" @click="page--">
            <ChevronLeft class="size-4" />
          </Button>
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="page >= data.meta.last_page"
            @click="page++"
          >
            <ChevronRight class="size-4" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
