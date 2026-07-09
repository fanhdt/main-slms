<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { Images, CalendarCheck, ShieldCheck, ArrowRight, FlaskConical } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const { data: labs, isLoading } = useQuery({
  queryKey: ['public-labs'],
  queryFn: async () => {
    const res = await api.get('/labs', { params: { is_active: true, per_page: 100 } })
    return res.data.data.data
  },
})

function goToLab(slug: string) {
  router.push(`/lab/${slug}`)
}

function goToAuth() {
  if (authStore.isAuthenticated) {
    if (authStore.hasRole('super_admin')) router.push('/admin')
    else if (authStore.hasRole('customer')) router.push('/booking')
    else router.push('/dashboard')
  } else {
    router.push({ name: 'login' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- ================================================================
         NAVBAR
    ================================================================ -->
    <nav
      class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100"
    >
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-xl font-bold text-gray-900">SLMS</span>
        <div class="flex items-center gap-6">
          <a href="#layanan" class="text-gray-600 hover:text-gray-900 text-sm transition-colors">
            Layanan
          </a>
          <a href="#tentang" class="text-gray-600 hover:text-gray-900 text-sm transition-colors">
            Tentang
          </a>
          <Button size="sm" @click="goToAuth">
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>
      </div>
    </nav>

    <!-- ================================================================
         HERO
    ================================================================ -->
    <section
      class="min-h-screen flex items-center justify-center pt-16 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 relative overflow-hidden"
    >
      <div class="absolute inset-0 opacity-10">
        <div
          class="absolute inset-0"
          style="
            background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0);
            background-size: 40px 40px;
          "
        />
      </div>

      <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <div
          class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-6 bg-blue-500/20 text-blue-300"
        >
          ✦ Smart Lab Management System
        </div>

        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-6">
          Kelola Semua Lab<br />Dalam Satu Platform
        </h1>

        <p class="text-xl text-white/60 max-w-2xl mx-auto mb-10">
          Booking studio, sewa peralatan, dan kelola layanan laboratorium dengan mudah — dari
          fotografi hingga komputer, semua dalam satu sistem.
        </p>

        <div class="flex items-center justify-center gap-4">
          <a
            href="#layanan"
            class="px-8 py-4 rounded-xl font-semibold text-white text-lg bg-blue-600 hover:bg-blue-700 transition-all hover:scale-105"
          >
            Lihat Laboratorium
          </a>
          <button
            @click="goToAuth"
            class="px-8 py-4 rounded-xl font-semibold text-white/80 border border-white/20 hover:bg-white/10 transition-colors text-lg"
          >
            {{ authStore.isAuthenticated ? 'Ke Dashboard' : 'Masuk Akun' }}
          </button>
        </div>
      </div>

      <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/40 animate-bounce">↓</div>
    </section>

    <!-- ================================================================
         LAB GRID — LAYANAN
    ================================================================ -->
    <section id="layanan" class="py-20 bg-gray-50">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900">Laboratorium Kami</h2>
          <p class="text-gray-500 mt-2">Pilih laboratorium sesuai kebutuhanmu</p>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <Skeleton v-for="i in 3" :key="i" class="h-64 w-full rounded-2xl" />
        </div>

        <!-- Grid -->
        <div v-else-if="labs?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <button
            v-for="lab in labs"
            :key="lab.uuid"
            @click="goToLab(lab.slug)"
            class="group text-left bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-200"
          >
            <!-- Header dengan branding -->
            <div
              class="h-40 flex items-center justify-center relative overflow-hidden"
              :style="{ backgroundColor: lab.branding.primary_color ?? '#1a1a2e' }"
            >
              <span class="text-7xl font-black text-white/10 absolute">
                {{ lab.name.charAt(0) }}
              </span>
              <span class="text-4xl font-bold text-white relative z-10">
                {{ lab.name.charAt(0) }}
              </span>
            </div>

            <!-- Body -->
            <div class="p-6">
              <h3
                class="font-bold text-gray-900 text-lg group-hover:text-blue-600 transition-colors"
              >
                {{ lab.name }}
              </h3>
              <p class="text-sm text-gray-500 mt-2 line-clamp-2">
                {{ lab.description ?? 'Laboratorium profesional siap melayani kebutuhanmu.' }}
              </p>

              <div class="mt-5 flex items-center justify-between">
                <div class="flex gap-1.5">
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding.primary_color ?? '#ccc' }"
                  />
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding.secondary_color ?? '#ccc' }"
                  />
                </div>
                <span
                  class="text-sm text-blue-600 font-medium group-hover:underline flex items-center gap-1"
                >
                  Kunjungi <ArrowRight class="size-3.5" />
                </span>
              </div>
            </div>
          </button>
        </div>

        <!-- Empty -->
        <div v-else class="text-center py-12">
          <FlaskConical class="size-8 mx-auto text-gray-300 mb-2" />
          <p class="text-gray-400">Belum ada laboratorium tersedia.</p>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TENTANG
    ================================================================ -->
    <section id="tentang" class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Tentang SLMS</h2>
        <p class="text-gray-500 text-lg leading-relaxed">
          Smart Lab Management System adalah platform terpadu untuk mengelola berbagai laboratorium
          — mulai dari booking studio, sewa peralatan, hingga pengelolaan layanan, semuanya dalam
          satu sistem yang mudah digunakan.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mt-12">
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
              <Images class="size-6 text-blue-600" />
            </div>
            <h3 class="font-semibold text-gray-900">Multi Laboratorium</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola berbagai jenis lab dalam satu platform</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mb-3">
              <CalendarCheck class="size-6 text-purple-600" />
            </div>
            <h3 class="font-semibold text-gray-900">Booking Mudah</h3>
            <p class="text-sm text-gray-500 mt-1">Sistem booking yang cepat dan transparan</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center mb-3">
              <ShieldCheck class="size-6 text-green-600" />
            </div>
            <h3 class="font-semibold text-gray-900">Aman & Terpercaya</h3>
            <p class="text-sm text-gray-500 mt-1">Data dan transaksi terjamin keamanannya</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 py-8 text-center text-gray-500 text-sm">
      © {{ new Date().getFullYear() }} Smart Lab Management System
    </footer>
  </div>
</template>
