<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import {
  Menu,
  X,
  ExternalLink,
  LayoutDashboard,
  CalendarDays,
  Package,
  Users,
  Bell as BellIcon,
  Settings,
  ClipboardList,
  UserSquare2,
  Image,
  QrCode,
  CreditCard,
  Images,
  Ticket,
  Settings2,
} from 'lucide-vue-next'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { useBranding } from '@/composables/useBranding'
import { useNotifications } from '@/composables/useNotifications'
import { serviceApi } from '@/features/labservice/api/serviceApi'
import NotificationBell from '@/components/NotificationBell.vue'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()
const route = useRoute()
const { primaryBgStyle, primaryTextStyle, labName } = useBranding()

const labSlug = computed(() => route.params.labSlug as string)
const mobileNavOpen = ref(false)

const hasPhotographyService = ref(false)

async function checkPhotographyFeature() {
  if (!labStore.activeLab?.id) {
    hasPhotographyService.value = false
    return
  }
  try {
    const res = await serviceApi.getAll({
      lab_id: labStore.activeLab.id,
      type: 'photography',
      per_page: 1,
    })
    hasPhotographyService.value = (res.data.data.data?.length ?? 0) > 0
  } catch {
    hasPhotographyService.value = false
  }
}

onMounted(async () => {
  await labStore.setActiveLab(labSlug.value)
  await checkPhotographyFeature()
  useNotifications().subscribeRealtime()
})

watch(labSlug, async () => {
  await checkPhotographyFeature()
})

watch(
  () => route.fullPath,
  () => {
    mobileNavOpen.value = false
  },
)

async function handleLogout() {
  await authStore.logout()
  toast.success('Logout berhasil')
  router.push({ name: 'login' })
}

function navLink(path: string) {
  return `/dashboard/lab/${labSlug.value}/${path}`
}

const backLink = computed(() => {
  if (authStore.hasRole('super_admin')) return '/admin'
  return '/dashboard'
})

const showBackButton = computed(() => {
  return authStore.hasRole('super_admin') || authStore.hasRole('lab_admin')
})

function openLandingPage() {
  window.open(`/lab/${labSlug.value}`, '_blank')
}

// Navigasi utama — struktur & path SAMA seperti sebelumnya, hanya icon berubah dari emoji ke Lucide
const mainNavItems = computed(() => [
  { to: `/dashboard/lab/${labSlug.value}`, label: 'Dashboard', icon: LayoutDashboard, exact: true },
  { to: navLink('bookings'), label: 'Booking', icon: CalendarDays },
  { to: navLink('assets'), label: 'Aset', icon: Package },
  { to: navLink('users'), label: 'Pengguna', icon: Users },
  { to: navLink('services'), label: 'Layanan', icon: ClipboardList },
  { to: navLink('settings'), label: 'Pengaturan Lab', icon: Settings },
  { to: navLink('packages'), label: 'Paket', icon: ClipboardList },
])

const photographyNavItems = computed(() => [
  { to: navLink('photographers'), label: 'Fotografer', icon: UserSquare2 },
  { to: navLink('portfolio'), label: 'Portofolio', icon: Image },
])

const checkinNavItems = computed(() => [
  { to: navLink('scan'), label: 'Scan QR', icon: QrCode },
  { to: navLink('rfid-checkin'), label: 'Cek Riwayat RFID', icon: CreditCard },
])
</script>

<template>
  <!--
    Wrapper luar dikunci ke tinggi viewport (h-screen + overflow-hidden) supaya
    scroll TIDAK terjadi di level halaman/body. Scroll dipindah ke dalam kolom
    <main> saja, sementara <aside> tetap sticky/diam di tempatnya.
  -->
  <div class="h-screen bg-gray-50 flex overflow-hidden">
    <Transition
      enter-active-class="transition duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileNavOpen"
        class="fixed inset-0 z-30 lg:hidden"
        style="background: rgba(0, 0, 0, 0.4)"
        @click="mobileNavOpen = false"
      />
    </Transition>

    <!-- ============================================================
         SIDEBAR — sticky di desktop (diam saat konten discroll),
         overlay fixed di mobile (perilaku lama tetap dipertahankan).
    ============================================================= -->
    <aside
      class="w-64 h-screen bg-white border-r border-gray-200 flex flex-col shrink-0 fixed inset-y-0 left-0 z-40 transition-transform duration-200 overflow-y-auto lg:sticky lg:top-0 lg:translate-x-0"
      :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Header Lab -->
      <div
        class="h-16 flex items-center px-5 border-b border-gray-200 gap-3 shrink-0"
        :style="primaryBgStyle"
      >
        <div class="flex-1 min-w-0">
          <p class="text-white font-semibold text-sm truncate">{{ labName }}</p>
          <p class="text-white/60 text-xs">Lab Dashboard</p>
        </div>
        <RouterLink
          v-if="showBackButton"
          :to="backLink"
          class="text-white/70 hover:text-white transition-colors shrink-0"
          :title="authStore.hasRole('super_admin') ? 'Global Panel' : 'Ganti Lab'"
        >
          <Settings2 v-if="authStore.hasRole('super_admin')" :size="17" />
          <ArrowLeftRight v-else :size="17" />
        </RouterLink>
        <button
          class="lg:hidden text-white/80"
          @click="mobileNavOpen = false"
          aria-label="Tutup menu"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-4 overflow-y-auto">
        <div class="space-y-0.5">
          <RouterLink
            v-for="item in mainNavItems"
            :key="item.to"
            :to="item.to"
            :exact="item.exact"
            class="nav-item"
            active-class="nav-active"
          >
            <component :is="item.icon" :size="17" class="shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </RouterLink>
        </div>

        <div v-if="labStore.activeLab?.is_photography_lab" class="space-y-0.5">
          <p class="text-[11px] font-semibold uppercase tracking-wide px-3 mb-1.5 text-gray-400">
            Fotografi
          </p>
          <RouterLink
            v-for="item in photographyNavItems"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            active-class="nav-active"
          >
            <component :is="item.icon" :size="17" class="shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </RouterLink>
          <RouterLink
            v-if="hasPhotographyService"
            :to="navLink('photo-projects')"
            class="nav-item"
            active-class="nav-active"
          >
            <Images :size="17" class="shrink-0" />
            <span class="truncate">Photo Delivery</span>
          </RouterLink>
        </div>

        <div class="space-y-0.5">
          <p class="text-[11px] font-semibold uppercase tracking-wide px-3 mb-1.5 text-gray-400">
            Check-in
          </p>
          <RouterLink
            v-for="item in checkinNavItems"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            active-class="nav-active"
          >
            <component :is="item.icon" :size="17" class="shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </RouterLink>
        </div>
      </nav>

      <!-- User Info -->
      <div class="p-3 border-t border-gray-200 space-y-0.5 shrink-0">
        <RouterLink to="/booking" class="nav-item">
          <Ticket :size="17" class="shrink-0" />
          <span class="truncate">Booking Pribadi</span>
        </RouterLink>

        <RouterLink
          to="/profile"
          class="flex items-center gap-3 px-2.5 py-2 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 overflow-hidden"
            :style="!authStore.user?.avatar ? primaryBgStyle : {}"
          >
            <img
              v-if="authStore.user?.avatar"
              :src="authStore.user.avatar"
              :alt="authStore.user.name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xs font-medium text-white">
              {{ authStore.user?.name?.charAt(0).toUpperCase() }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">
              {{ authStore.user?.name }}
            </p>
            <p class="text-xs text-gray-500 truncate capitalize">
              {{ authStore.user?.roles[0]?.replace('_', ' ') }}
            </p>
          </div>
        </RouterLink>

        <button @click="handleLogout" class="nav-item w-full text-red-600 hover:bg-red-50">
          <LogOut :size="16" class="shrink-0" />
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- ============================================================
         MAIN — satu-satunya kolom yang scroll (h-screen + overflow-y-auto).
         Sidebar di sebelah kiri tetap diam karena posisinya sticky/fixed.
    ============================================================= -->
    <div class="flex-1 h-screen flex flex-col min-w-0 overflow-y-auto">
      <header
        class="h-16 bg-white border-b border-gray-200 flex items-center px-4 lg:px-6 gap-3 lg:gap-4 sticky top-0 z-20 shrink-0"
      >
        <button class="lg:hidden" @click="mobileNavOpen = true" aria-label="Buka menu">
          <Menu :size="20" class="text-gray-600" />
        </button>

        <h1 class="text-lg font-semibold text-gray-900 flex-1 truncate">
          {{ labName }}
        </h1>

        <button
          @click="openLandingPage"
          class="hidden sm:flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
          title="Buka landing page publik lab ini di tab baru"
        >
          <ExternalLink :size="13" />
          Lihat Landing Page
        </button>

        <NotificationBell />
        <span
          class="text-sm font-medium px-3 py-1 rounded-full bg-gray-100"
          :style="primaryTextStyle"
        >
          {{ authStore.user?.roles[0]?.replace('_', ' ') }}
        </span>
      </header>

      <main class="flex-1 p-4 lg:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.55rem 0.65rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151; /* gray-700 */
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.nav-item:hover {
  background: #f3f4f6; /* gray-100 */
}

.nav-active {
  background: #f3f4f6;
  font-weight: 600;
  color: #111827; /* gray-900 */
}
</style>
