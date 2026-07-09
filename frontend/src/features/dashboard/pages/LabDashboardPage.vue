<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  CalendarDays,
  Wallet,
  Package,
  Image,
  Plus,
  ExternalLink,
  TrendingUp,
  TrendingDown,
  Users,
  FlaskConical,
  FileBarChart,
} from 'lucide-vue-next'
import { Line, Doughnut, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useRouter } from 'vue-router'
import StatCard from '@/components/common/StatCard.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import api from '@/lib/axios'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
)

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const labStore = useLabStore()

const labSlug = computed(() => route.params.labSlug as string)

// ============================================================
// Data — QUERY TIDAK DIUBAH, hanya reuse yang sudah ada
// ============================================================
const { data: bookings, isLoading: bookingsLoading } = useQuery({
  queryKey: ['lab-bookings-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/bookings', { params: { lab_id: lab.id, per_page: 100 } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const { data: assets, isLoading: assetsLoading } = useQuery({
  queryKey: ['lab-assets-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/assets', { params: { lab_id: lab.id } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const { data: photoProjects, isLoading: photoLoading } = useQuery({
  queryKey: ['lab-photo-stats', labSlug],
  queryFn: async () => {
    const lab = labStore.activeLab
    if (!lab) return null
    const res = await api.get('/photo-projects', { params: { lab_id: lab.id, per_page: 50 } })
    return res.data.data
  },
  enabled: computed(() => !!labStore.activeLab),
})

const isLoadingStats = computed(
  () => bookingsLoading.value || assetsLoading.value || photoLoading.value,
)

// ============================================================
// Stat computations — sama persis dengan sebelumnya
// ============================================================
const totalBookings = computed(() => bookings.value?.meta?.total ?? 0)

const paidRevenue = computed(() => {
  const items = bookings.value?.data ?? []
  return items
    .filter((b: any) => b.payment_status?.value === 'paid')
    .reduce((sum: number, b: any) => sum + Number(b.total_price), 0)
})

const paidRate = computed(() => {
  const items = bookings.value?.data ?? []
  if (!items.length) return 0
  const paid = items.filter((b: any) => b.payment_status?.value === 'paid').length
  return Math.round((paid / items.length) * 100)
})

const totalAssets = computed(() => assets.value?.meta?.total ?? 0)
const availableAssets = computed(
  () => assets.value?.data?.filter((a: any) => a.status?.value === 'available').length ?? 0,
)

const activePhotoProjects = computed(() => photoProjects.value?.meta?.total ?? 0)
const pendingApproval = computed(
  () => photoProjects.value?.data?.filter((p: any) => p.status?.value === 'approval').length ?? 0,
)

const recentBookings = computed(() => (bookings.value?.data ?? []).slice(0, 6))

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    notation: value >= 1_000_000 ? 'compact' : 'standard',
  }).format(value)
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// ============================================================
// Header — sapaan & tanggal
// ============================================================
const todayLabel = computed(() =>
  new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }),
)

const greetingName = computed(() => authStore.user?.name?.split(' ')[0] ?? 'Admin')

// ============================================================
// Stat cards config — badge indikator dummy (siap diganti API)
// TODO: replace dengan data perbandingan periode dari backend
// ============================================================
const statCards = computed(() => [
  {
    label: 'Total booking',
    value: totalBookings.value,
    hint: '100 data terakhir',
    icon: CalendarDays,
    change: 12,
    isDummy: true,
  },
  {
    label: 'Pendapatan (lunas)',
    value: formatCurrency(paidRevenue.value),
    hint: `${paidRate.value}% booking lunas`,
    icon: Wallet,
    change: 8,
    isDummy: true,
  },
  {
    label: 'Aset tersedia',
    value: `${availableAssets.value} / ${totalAssets.value}`,
    hint: 'Siap dipinjam',
    icon: Package,
    change: -3,
    isDummy: true,
  },
  {
    label: 'Photo project aktif',
    value: activePhotoProjects.value,
    hint:
      pendingApproval.value > 0 ? `${pendingApproval.value} menunggu approval` : 'Semua terkendali',
    icon: Image,
    change: 5,
    isDummy: true,
    hidden: !labStore.activeLab?.is_photography_lab,
  },
])

// ============================================================
// CHART DATA — dummy, struktur siap diganti data API
// TODO: ganti dengan endpoint /bookings/trend, /bookings/status-summary, /assets/usage-by-category
// ============================================================
const chartTextColor = '#6b7280'
const chartGridColor = 'rgba(0,0,0,0.05)'

const bookingTrendData = computed(() => ({
  labels: ['Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
  datasets: [
    {
      label: 'Booking',
      data: [12, 19, 14, 26, 22, totalBookings.value || 30],
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.08)',
      fill: true,
      tension: 0.35,
      pointRadius: 3,
      pointBackgroundColor: '#3b82f6',
    },
  ],
}))

const bookingTrendOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: chartTextColor } },
    y: { grid: { color: chartGridColor }, ticks: { color: chartTextColor }, beginAtZero: true },
  },
}

const bookingStatusData = computed(() => {
  const items = bookings.value?.data ?? []
  const count = (status: string) => items.filter((b: any) => b.status?.value === status).length

  const hasData = items.length > 0
  return {
    labels: ['Pending', 'Approved', 'Completed', 'Rejected/Canceled'],
    datasets: [
      {
        data: hasData
          ? [
              count('pending'),
              count('approved') + count('ongoing'),
              count('completed'),
              count('rejected') + count('canceled'),
            ]
          : [4, 6, 8, 2], // dummy fallback
        backgroundColor: ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444'],
        borderWidth: 0,
      },
    ],
  }
})

const bookingStatusOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { color: chartTextColor, boxWidth: 10, padding: 14 },
    },
  },
  cutout: '65%',
}

// TODO: ganti dengan data pemakaian per lab dari backend
const labUsageData = {
  labels: ['Lab Foto', 'Lab Komputer', 'Lab Audio'],
  datasets: [
    {
      label: 'Jam Pemakaian',
      data: [42, 28, 35, 18],
      backgroundColor: '#8b5cf6',
      borderRadius: 6,
      maxBarThickness: 36,
    },
  ],
}

const labUsageOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: chartTextColor } },
    y: { grid: { color: chartGridColor }, ticks: { color: chartTextColor }, beginAtZero: true },
  },
}

// ============================================================
// Recent Activity — dummy, siap diganti endpoint activity log
// TODO: ganti dengan GET /activities atau sejenis
// ============================================================
const recentActivities = [
  { icon: CalendarDays, text: 'Booking baru dibuat oleh mahasiswa', time: '5 menit lalu' },
  { icon: FlaskConical, text: 'Booking disetujui oleh admin', time: '32 menit lalu' },
  { icon: Users, text: 'User baru terdaftar', time: '1 jam lalu' },
  { icon: Package, text: 'Aset baru ditambahkan ke inventaris', time: '3 jam lalu' },
]

// ============================================================
// Quick Actions — mengarah ke route yang sudah ada, tidak ada route baru
// ============================================================
function createManualBooking() {
  router.push({
    name: 'booking-lab',
    params: { slug: labSlug.value },
    query: { mode: 'staff' },
  })
}

function openLandingPage() {
  window.open(`/lab/${labSlug.value}`, '_blank')
}

const quickActions = computed(() => [
  { label: 'Booking Baru', icon: Plus, action: createManualBooking },
  {
    label: 'Tambah Aset',
    icon: Package,
    action: () => router.push(`/dashboard/lab/${labSlug.value}/assets`),
  },
  {
    label: 'Tambah Pengguna',
    icon: Users,
    action: () => router.push(`/dashboard/lab/${labSlug.value}/users`),
  },
  { label: 'Lihat Landing Page', icon: FileBarChart, action: openLandingPage },
])
</script>

<template>
  <div class="space-y-6">
    <!-- ============================================================
         HEADER
    ============================================================= -->
    <div class="flex flex-col gap-1">
      <p class="text-xs font-medium" style="color: var(--text-muted)">
        {{ labStore.activeLab?.name ?? 'Dashboard' }} · {{ todayLabel }}
      </p>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-semibold tracking-tight" style="color: var(--text-primary)">
            Selamat datang, {{ greetingName }} 👋
          </h1>
          <p class="text-sm mt-0.5" style="color: var(--text-secondary)">
            {{ labStore.activeLab?.description ?? 'Ringkasan aktivitas lab hari ini.' }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button size="sm" @click="createManualBooking">
            <Plus class="size-4" />
            Buat Booking Manual
          </Button>
          <Button size="sm" variant="outline" @click="openLandingPage">
            <ExternalLink class="size-4" />
            Lihat Landing Page
          </Button>
        </div>
      </div>
    </div>

    <Separator />

    <!-- ============================================================
         STAT CARDS
    ============================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <template v-if="isLoadingStats">
        <Skeleton v-for="i in 4" :key="i" class="h-[92px] w-full rounded-xl" />
      </template>

      <template v-else>
        <Card v-for="stat in statCards" v-show="!stat.hidden" :key="stat.label" class="p-0">
          <CardContent class="px-4 py-3.5">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-xs font-medium truncate" style="color: var(--text-muted)">
                  {{ stat.label }}
                </p>
                <p class="text-xl font-semibold mt-1 truncate" style="color: var(--text-primary)">
                  {{ stat.value }}
                </p>
                <div class="flex items-center gap-1.5 mt-1.5">
                  <Badge
                    variant="outline"
                    class="border-0 px-1.5 py-0 h-5 gap-0.5 text-[11px] font-medium"
                    :class="
                      stat.change >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'
                    "
                  >
                    <TrendingUp v-if="stat.change >= 0" class="size-3" />
                    <TrendingDown v-else class="size-3" />
                    {{ Math.abs(stat.change) }}%
                  </Badge>
                  <span class="text-[11px] truncate" style="color: var(--text-muted)">
                    {{ stat.hint }}
                  </span>
                </div>
              </div>
              <div
                class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center"
                style="background: var(--surface-1); color: var(--text-accent)"
              >
                <component :is="stat.icon" class="size-4.5" />
              </div>
            </div>
          </CardContent>
        </Card>
      </template>
    </div>

    <!-- ============================================================
         CHARTS
    ============================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Booking Trend -->
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Tren Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">6 bulan terakhir</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div class="h-[240px] w-full">
            <Line :data="bookingTrendData" :options="bookingTrendOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Booking Status Donut -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Status Booking</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">Distribusi status saat ini</p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div class="h-[240px] w-full">
            <Doughnut :data="bookingStatusData" :options="bookingStatusOptions" />
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Lab Usage Bar -->
      <Card class="lg:col-span-2 p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Pemakaian Lab</CardTitle>
          <p class="text-xs" style="color: var(--text-muted)">
            Total jam pemakaian per kategori lab
          </p>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4">
          <div class="h-[220px] w-full">
            <Bar :data="labUsageData" :options="labUsageOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Recent Activity -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aktivitas Terbaru</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-3">
          <ul class="space-y-3">
            <li
              v-for="(activity, idx) in recentActivities"
              :key="idx"
              class="flex items-start gap-3"
            >
              <div
                class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center mt-0.5"
                style="background: var(--surface-1); color: var(--text-accent)"
              >
                <component :is="activity.icon" class="size-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-sm truncate" style="color: var(--text-primary)">
                  {{ activity.text }}
                </p>
                <p class="text-xs" style="color: var(--text-muted)">{{ activity.time }}</p>
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>

    <!-- ============================================================
         RECENT BOOKING + QUICK ACTIONS
    ============================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Recent Booking table -->
      <Card class="lg:col-span-2 p-0 overflow-hidden">
        <CardHeader class="px-5 pt-5 pb-3 flex flex-row items-center justify-between space-y-0">
          <CardTitle class="text-sm font-semibold">Booking Terbaru</CardTitle>
          <RouterLink
            :to="`/dashboard/lab/${labSlug}/bookings`"
            class="text-xs font-medium"
            style="color: var(--text-accent)"
          >
            Lihat semua
          </RouterLink>
        </CardHeader>

        <div v-if="bookingsLoading" class="px-5 pb-5 space-y-2">
          <Skeleton v-for="i in 4" :key="i" class="h-10 w-full" />
        </div>

        <div
          v-else-if="!recentBookings.length"
          class="px-5 pb-8 text-center text-sm"
          style="color: var(--text-muted)"
        >
          Belum ada booking.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr style="border-bottom: 0.5px solid var(--border)">
                <th
                  class="text-left px-5 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Kode
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Customer
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Status
                </th>
                <th
                  class="text-left px-4 py-2 font-medium text-xs"
                  style="color: var(--text-muted)"
                >
                  Tanggal
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="booking in recentBookings"
                :key="booking.uuid"
                style="border-bottom: 0.5px solid var(--border)"
              >
                <td class="px-5 py-2.5 font-mono text-xs" style="color: var(--text-primary)">
                  {{ booking.booking_code }}
                </td>
                <td class="px-4 py-2.5 truncate" style="color: var(--text-primary)">
                  {{ booking.user?.name }}
                </td>
                <td class="px-4 py-2.5">
                  <StatusBadge :status="booking.status" type="booking" />
                </td>
                <td class="px-4 py-2.5 text-xs" style="color: var(--text-muted)">
                  {{ formatDate(booking.start_time) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- Quick Actions -->
      <Card class="p-0">
        <CardHeader class="px-5 pt-5 pb-0">
          <CardTitle class="text-sm font-semibold">Aksi Cepat</CardTitle>
        </CardHeader>
        <CardContent class="px-5 pb-5 pt-4 grid grid-cols-2 gap-2">
          <Button
            v-for="action in quickActions"
            :key="action.label"
            variant="outline"
            size="sm"
            class="h-auto flex-col items-start gap-1.5 py-3 px-3"
            @click="action.action"
          >
            <component :is="action.icon" class="size-4" style="color: var(--text-accent)" />
            <span class="text-xs font-medium text-left">{{ action.label }}</span>
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
