<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import {
  LayoutDashboard,
  FlaskConical,
  Users,
  LogOut,
  Menu,
  X,
  ChevronRight,
  Ticket,
  ArrowLeftRight,
} from 'lucide-vue-next'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const labStore = useLabStore()
const router = useRouter()
const route = useRoute()

const mobileNavOpen = ref(false)

onMounted(async () => {
  await labStore.fetchManagedLabs()
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

function enterLab(slug: string) {
  router.push(`/dashboard/lab/${slug}`)
}

const navItems = [
  { to: '/admin', label: 'Dashboard', icon: LayoutDashboard, exact: true },
  { to: '/admin/labs', label: 'Laboratorium', icon: FlaskConical },
  { to: '/admin/users', label: 'Pengguna', icon: Users },
]

// Breadcrumb dinamis dari route yang sudah ada — tidak menambah route baru
const PAGE_TITLES: Record<string, string> = {
  'admin-dashboard': 'Dashboard',
  'admin-labs': 'Laboratorium',
  'admin-users': 'Pengguna',
}

const currentPageTitle = computed(() => PAGE_TITLES[route.name as string] ?? 'Dashboard')

/**
 * Vue Router 4 sudah tidak mendukung prop `exact` pada <RouterLink> (beda dengan
 * Vue Router 3). Menulis `:exact="true"` tidak error, tapi diabaikan begitu saja —
 * jadi active-class bisa nyala terus di menu yang path-nya jadi "leluhur" dari
 * halaman lain (misal Dashboard tetap gelap walau sudah pindah ke halaman lain).
 * Solusinya: tentukan status aktif sendiri dengan membandingkan route.path,
 * lalu bind lewat :class, bukan bergantung pada active-class bawaan.
 */
function isNavActive(path: string, exact = false) {
  if (exact) return route.path === path
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <!--
    Wrapper luar dikunci ke tinggi viewport (h-screen + overflow-hidden) supaya
    scroll TIDAK terjadi di level halaman/body. Scroll dipindah ke dalam kolom
    <main> saja, sementara <aside> tetap sticky/diam di tempatnya.
  -->
  <div class="h-screen flex overflow-hidden" style="background: var(--surface-0)">
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
      class="w-64 h-screen shrink-0 flex flex-col fixed inset-y-0 left-0 z-40 transition-transform duration-200 overflow-y-auto lg:sticky lg:top-0 lg:translate-x-0 admin-sidebar"
      :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
      style="background: var(--surface-2); border-right: 0.5px solid var(--border)"
    >
      <!-- Brand -->
      <div
        class="h-16 flex items-center justify-between px-5 shrink-0"
        style="border-bottom: 0.5px solid var(--border)"
      >
        <div class="flex items-center gap-2.5">
          <div class="flex items-center gap-2.5">
            <img
              src="/images/logo-lab.svg"
              alt="Logo SLMS"
              class="w-7 h-7 object-contain shrink-0"
            />
            <span class="h-6 w-px bg-gray-200 shrink-0" aria-hidden="true" />
            <img
              src="/images/logo-upi.svg"
              alt="Logo UPI"
              class="h-7 w-auto max-w-20 object-contain shrink-0"
            />
          </div>
          <div class="leading-tight"></div>
        </div>
        <button class="lg:hidden" @click="mobileNavOpen = false" aria-label="Tutup menu">
          <X :size="18" style="color: var(--text-secondary)" />
        </button>
      </div>

      <!-- Main nav -->
      <nav class="px-3 py-4 space-y-0.5">
        <p
          class="text-[11px] font-semibold uppercase tracking-wide px-3 mb-2"
          style="color: var(--text-muted)"
        >
          Global
        </p>
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :exact="item.exact"
          class="nav-item"
          active-class="nav-active"
        >
          <component :is="item.icon" :size="17" class="shrink-0" />
          <span class="truncate">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- Masuk ke lab -->
      <div class="px-3 py-2 flex-1 overflow-y-auto" style="border-top: 0.5px solid var(--border)">
        <p
          class="text-[11px] font-semibold uppercase tracking-wide px-3 mb-2 mt-3"
          style="color: var(--text-muted)"
        >
          Masuk ke Lab
        </p>
        <div v-if="labStore.managedLabs.length" class="space-y-0.5">
          <button
            v-for="lab in labStore.managedLabs"
            :key="lab.uuid"
            @click="enterLab(lab.slug)"
            class="nav-item w-full text-left"
          >
            <span
              class="w-3.5 h-3.5 rounded shrink-0"
              :style="{ backgroundColor: lab.branding.primary_color ?? 'var(--fill-accent)' }"
            />
            <span class="truncate">{{ lab.name }}</span>
          </button>
        </div>
        <p v-else class="text-xs px-3 py-2" style="color: var(--text-muted)">
          Belum ada lab yang bisa dimasuki.
        </p>
      </div>

      <!-- Footer: profile & actions -->
      <div class="p-3 space-y-0.5" style="border-top: 0.5px solid var(--border)">
        <RouterLink to="/booking" class="nav-item">
          <Ticket :size="17" class="shrink-0" />
          <span class="truncate">Booking Pribadi</span>
        </RouterLink>

        <RouterLink
          to="/profile"
          class="flex items-center gap-3 px-2.5 py-2 rounded-lg transition-colors hover:bg-(--surface-1)"
        >
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 overflow-hidden"
            style="background: var(--fill-accent)"
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
            <p class="text-sm font-medium truncate" style="color: var(--text-primary)">
              {{ authStore.user?.name }}
            </p>
            <p class="text-xs" style="color: var(--text-muted)">Super Admin</p>
          </div>
        </RouterLink>

        <button @click="handleLogout" class="nav-item w-full" style="color: var(--text-danger)">
          <LogOut :size="16" class="shrink-0" />
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <div class="flex-1 h-screen flex flex-col min-w-0 overflow-y-auto">
      <header
        class="h-16 shrink-0 flex items-center gap-3 px-4 lg:px-6 sticky top-0 z-20"
        style="background: var(--surface-2); border-bottom: 0.5px solid var(--border)"
      >
        <button class="lg:hidden" @click="mobileNavOpen = true" aria-label="Buka menu">
          <Menu :size="20" style="color: var(--text-secondary)" />
        </button>

        <div class="flex items-center gap-1.5 text-sm">
          <span style="color: var(--text-muted)">Admin</span>
          <ChevronRight :size="14" style="color: var(--text-muted)" />
          <span class="font-medium" style="color: var(--text-primary)">
            {{ currentPageTitle }}
          </span>
        </div>
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
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.nav-item:hover {
  background: var(--surface-1);
}

.nav-active {
  background: var(--bg-accent);
  color: var(--text-accent) !important;
}

@media (max-width: 1023px) {
  .admin-sidebar {
    background-color: #ffffff !important;
  }
}
</style>
