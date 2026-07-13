<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import api from '@/lib/axios'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Images,
  CalendarCheck,
  ShieldCheck,
  ArrowRight,
  FlaskConical,
  Menu,
  X,
  Star,
  Linkedin,
  Youtube,
  Instagram,
  MapPin,
  Phone,
  Mail,
} from 'lucide-vue-next'
import { ref, onMounted, onBeforeUnmount } from 'vue'

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

// UI-only helpers (do not affect business logic)
const isMobileMenuOpen = ref(false)

/**
 * Ambil gambar sebuah lab dari data API (hero_image, lalu fallback ke logo).
 * Tidak ada hardcode gambar — murni dari branding lab yang sudah di-load lewat useQuery di atas.
 */
function getLabImage(lab: any) {
  return lab?.branding?.hero_image || lab?.branding?.logo || null
}

/**
 * Galeri horizontal di hero — khusus bagian ini boleh pakai gambar Unsplash
 * (sekadar dekorasi suasana lab), beda dengan grid Laboratorium Kami yang
 * tetap murni dari data API.
 */
const galleryImages = [
  {
    src: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=800&q=80',
    name: 'Studio fotografi',
  },
  {
    src: 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?w=800&q=80',
    name: 'Studio audio',
  },
  {
    src: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80',
    name: 'Lab komputer',
  },
  {
    src: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800&q=80',
    name: 'Ruang kerja',
  },
  {
    src: 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=800&q=80',
    name: 'Peralatan lab',
  },
]

// digandakan supaya loop auto-scroll terasa mulus (seamless)
const galleryLoopImages = [...galleryImages, ...galleryImages]

// ---- Auto-scroll (carousel bergerak sendiri) untuk galeri horizontal ----
const galleryTrackRef = ref<HTMLElement | null>(null)
let galleryAnimationFrame: number | null = null
const GALLERY_SPEED_PX_PER_FRAME = 0.6

function stepGalleryScroll() {
  const el = galleryTrackRef.value
  if (el && galleryLoopImages.length) {
    el.scrollLeft += GALLERY_SPEED_PX_PER_FRAME
    // loop balik ke awal begitu sudah melewati setengah track (karena list digandakan)
    if (el.scrollLeft >= el.scrollWidth / 2) {
      el.scrollLeft = 0
    }
  }
  galleryAnimationFrame = requestAnimationFrame(stepGalleryScroll)
}

onMounted(() => {
  galleryAnimationFrame = requestAnimationFrame(stepGalleryScroll)
})

onBeforeUnmount(() => {
  if (galleryAnimationFrame) cancelAnimationFrame(galleryAnimationFrame)
})

const testimonials = [
  {
    name: 'Ayu Lestari',
    role: 'Mahasiswa DKV',
    rating: 5,
    comment:
      'Ruangan serbaguna digunakan untuk berbagai macam kegiatan yang dapat disesuaikan dengan fasilitas ruangan dan kapasitas ruangan.',
  },
  {
    name: 'Rizky Pratama',
    role: 'Mahasiswa Ilmu Komputer',
    rating: 5,
    comment:
      'Proses booking sangat mudah dan cepat. Fasilitas lab lengkap serta staf yang membantu.',
  },
  {
    name: 'Dinda Amelia',
    role: 'Mahasiswa Broadcasting',
    rating: 4,
    comment: 'Kualitas peralatan sangat baik dan terawat. Sangat membantu kebutuhan produksi kami.',
  },
]
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- ================================================================
         NAVBAR
    ================================================================ -->
    <nav
      class="fixed top-0 left-0 right-0 z-50 bg-[#172233]/80 backdrop-blur-md border-b border-white/10"
    >
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img src="/images/logo-lab.svg" alt="Logo SLMS" class="h-8 w-8 object-contain shrink-0" />
          <span class="h-6 w-px bg-white/20 shrink-0" aria-hidden="true" />
          <img
            src="/images/logo-upi.svg"
            alt="Logo UPI"
            class="h-8 w-auto max-w-24 object-contain shrink-0"
          />
        </div>

        <!-- Desktop menu -->
        <div class="hidden md:flex items-center gap-8">
          <a
            href="#home"
            class="text-white/70 hover:text-white text-sm font-medium transition-colors"
            >Home</a
          >
          <a
            href="#layanan"
            class="text-white/70 hover:text-white text-sm font-medium transition-colors"
            >Layanan</a
          >
          <a
            href="#tentang"
            class="text-white/70 hover:text-white text-sm font-medium transition-colors"
            >Tentang</a
          >
          <Button size="sm" class="bg-blue-600 hover:bg-blue-700 rounded-xl" @click="goToAuth">
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>

        <!-- Mobile hamburger -->
        <button class="md:hidden text-white" @click="isMobileMenuOpen = !isMobileMenuOpen">
          <Menu v-if="!isMobileMenuOpen" class="size-6" />
          <X v-else class="size-6" />
        </button>
      </div>

      <!-- Mobile menu -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="isMobileMenuOpen"
          class="md:hidden bg-[#172233] border-t border-white/10 px-6 py-4 flex flex-col gap-4"
        >
          <a href="#home" class="text-white/80 text-sm" @click="isMobileMenuOpen = false">Home</a>
          <a href="#layanan" class="text-white/80 text-sm" @click="isMobileMenuOpen = false"
            >Layanan</a
          >
          <a href="#tentang" class="text-white/80 text-sm" @click="isMobileMenuOpen = false"
            >Tentang</a
          >
          <Button
            size="sm"
            class="bg-blue-600 hover:bg-blue-700 rounded-md w-full"
            @click="goToAuth"
          >
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>
      </transition>
    </nav>

    <!-- ================================================================
         HERO
    ================================================================ -->
    <section
      id="home"
      class="min-h-screen flex flex-col items-center justify-center pt-24 pb-16 bg-[#172233] relative overflow-hidden"
    >
      <div
        class="absolute inset-0 opacity-[0.15] pointer-events-none"
        style="
          background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
          background-size: 28px 28px;
        "
      />

      <div class="max-w-4xl mx-auto px-6 text-center relative z-10 animate-[fadein_0.8s_ease-out]">
        <div
          class="inline-block text-xs font-semibold px-4 py-1.5 rounded-full mb-6 bg-blue-500/15 text-blue-300 border border-blue-400/20"
        >
          ✦ Smart Lab Management System
        </div>

        <h1
          class="text-4xl sm:text-5xl md:text-7xl font-black text-white leading-[1.1] mb-6 tracking-tight"
        >
          Kelola Semua Lab<br />Dalam Satu Platform
        </h1>

        <p
          class="text-base sm:text-lg md:text-xl text-white/60 max-w-2xl mx-auto mb-10 leading-relaxed"
        >
          Booking studio, sewa peralatan,<br class="hidden sm:block" />
          hingga pengelolaan layanan laboratorium<br class="hidden sm:block" />
          dengan mudah.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a
            href="#layanan"
            class="w-full sm:w-auto text-center px-8 py-4 rounded-xl font-semibold text-white text-base sm:text-lg bg-blue-600 hover:bg-blue-700 transition-all duration-300 ease-out hover:scale-105 shadow-lg shadow-blue-600/20"
          >
            Lihat Laboratorium
          </a>
          <button
            @click="goToAuth"
            class="w-full sm:w-auto px-8 py-4 rounded-xl font-semibold text-white border border-white/20 hover:bg-white/10 transition-all duration-300 ease-out hover:scale-105 text-base sm:text-lg"
          >
            {{ authStore.isAuthenticated ? 'Ke Dashboard' : 'Dashboard' }}
          </button>
        </div>
      </div>

      <!-- Horizontal Gallery (bergerak otomatis, gambar dekorasi dari Unsplash) -->
      <div class="w-full mt-16 relative z-10">
        <div ref="galleryTrackRef" class="flex gap-5 overflow-x-auto px-6 pb-4 scrollbar-hide">
          <div
            v-for="(g, idx) in galleryLoopImages"
            :key="idx"
            class="flex-none w-[280px] sm:w-[320px] h-[160px] sm:h-[180px] rounded-xl overflow-hidden group cursor-pointer shadow-lg"
          >
            <img
              :src="g.src"
              :alt="g.name"
              class="w-full h-full object-cover transition-transform duration-300 ease-out group-hover:scale-110"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         LAB GRID — LAYANAN
    ================================================================ -->
    <section id="layanan" class="py-20 sm:py-28 bg-white">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
            Laboratorium Kami
          </h2>
          <p class="text-gray-500 mt-3 text-base sm:text-lg">
            Pilih laboratorium sesuai kebutuhanmu
          </p>
        </div>

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          <div v-for="i in 6" :key="i" class="rounded-2xl overflow-hidden border border-gray-100">
            <Skeleton class="h-48 w-full rounded-none" />
            <div class="p-6 space-y-3">
              <Skeleton class="h-5 w-2/3 rounded-md" />
              <Skeleton class="h-4 w-full rounded-md" />
              <Skeleton class="h-4 w-1/3 rounded-md" />
            </div>
          </div>
        </div>

        <!-- Grid -->
        <div
          v-else-if="labs?.length"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8"
        >
          <button
            v-for="lab in labs"
            :key="lab.uuid"
            @click="goToLab(lab.slug)"
            class="group text-left bg-white rounded-2xl sm:rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 ease-out hover:-translate-y-2"
          >
            <!-- Image -->
            <div class="h-48 relative overflow-hidden bg-gray-100 flex items-center justify-center">
              <img
                v-if="getLabImage(lab)"
                :src="getLabImage(lab)"
                :alt="lab.name"
                class="w-full h-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
              />
              <span v-else class="text-5xl font-black text-gray-300">{{ lab.name.charAt(0) }}</span>
              <span
                class="absolute top-3 left-3 text-xs font-semibold px-3 py-1 rounded-full bg-white/90 backdrop-blur text-gray-800 shadow-sm"
              >
                {{ lab.name.split(' ')[0] }}
              </span>
            </div>

            <!-- Body -->
            <div class="p-6">
              <h3
                class="font-bold text-gray-900 text-lg group-hover:text-blue-600 transition-colors"
              >
                {{ lab.name }}
              </h3>
              <p class="text-sm text-gray-500 mt-2 line-clamp-2 leading-relaxed">
                {{
                  lab.description ?? 'Lighting, Backdrop, Aksesoris lainnya dan ruangan editorial'
                }}
              </p>

              <div class="mt-5 flex items-center justify-between">
                <div class="flex gap-1.5">
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding?.primary_color ?? '#ccc' }"
                  />
                  <div
                    class="w-3 h-3 rounded-full border border-gray-200"
                    :style="{ backgroundColor: lab.branding?.secondary_color ?? '#ccc' }"
                  />
                </div>
                <span
                  class="text-sm text-blue-600 font-semibold group-hover:underline flex items-center gap-1"
                >
                  Kunjungi
                  <ArrowRight
                    class="size-3.5 transition-transform duration-300 group-hover:translate-x-1"
                  />
                </span>
              </div>
            </div>
          </button>
        </div>

        <!-- Empty -->
        <div v-else class="text-center py-20">
          <div
            class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4"
          >
            <FlaskConical class="size-8 text-gray-300" />
          </div>
          <p class="text-gray-400 text-base">Belum ada laboratorium tersedia.</p>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TENTANG
    ================================================================ -->
    <section id="tentang" class="py-20 sm:py-28 bg-[#172233] relative overflow-hidden">
      <div
        class="absolute inset-0 opacity-[0.08] pointer-events-none"
        style="
          background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
          background-size: 28px 28px;
        "
      />
      <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight">Tentang SLMS</h2>
        <p class="text-white/60 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
          Smart Lab Management System adalah platform terpadu untuk mengelola berbagai laboratorium.
          Mulai dari booking studio, sewa peralatan, hingga pengelolaan layanan, semuanya dalam satu
          sistem yang mudah digunakan.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-14">
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:shadow-[0_0_40px_-10px_rgba(59,130,246,0.4)]"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <Images class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Multi Laboratorium</h3>
            <p class="text-sm text-white/50 mt-2 leading-relaxed">
              Kelola berbagai jenis lab dalam satu platform
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:shadow-[0_0_40px_-10px_rgba(59,130,246,0.4)]"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <CalendarCheck class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Booking Mudah</h3>
            <p class="text-sm text-white/50 mt-2 leading-relaxed">
              Sistem booking yang cepat dan transparan
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:shadow-[0_0_40px_-10px_rgba(59,130,246,0.4)]"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <ShieldCheck class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Aman & Terpercaya</h3>
            <p class="text-sm text-white/50 mt-2 leading-relaxed">
              Data dan transaksi terjamin keamanannya
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TESTIMONI
    ================================================================ -->
    <section class="py-20 sm:py-28 bg-white">
      <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-14">
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">Kata Mereka</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8">
          <div
            v-for="(t, idx) in testimonials"
            :key="idx"
            class="rounded-2xl sm:rounded-3xl bg-gray-50 border border-gray-100 p-8 flex flex-col items-center text-center transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl"
          >
            <p class="text-gray-600 leading-relaxed mb-6">
              {{ t.comment }}
            </p>
            <div class="flex gap-1 mb-3">
              <Star v-for="s in t.rating" :key="s" class="size-4 fill-orange-400 text-orange-400" />
            </div>
            <p class="font-semibold text-gray-900">{{ t.name }}</p>
            <p class="text-xs text-gray-400">{{ t.role }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ================================================================
         FOOTER
    ================================================================ -->
    <footer class="bg-[#172233] pt-16 pb-8">
      <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-10 pb-10 border-b border-white/10">
          <div>
            <span class="text-lg font-bold text-white">SLMS</span>
            <p class="text-white font-medium mt-3">Smart Lab Management System</p>
            <p class="text-white/50 text-sm mt-1">Kampus di Cibiru</p>

            <div class="flex items-start gap-2 text-white/50 text-sm mt-4">
              <MapPin class="size-4 mt-0.5 flex-none" />
              <span
                >Jl. Pendidikan No.15, Cibiru Wetan, Kec. Cileunyi, Kabupaten Bandung, Jawa Barat
                40625</span
              >
            </div>
            <div class="flex items-center gap-2 text-white/50 text-sm mt-2">
              <Phone class="size-4 flex-none" />
              <span>+62 812-2277-7849</span>
            </div>
            <div class="flex items-center gap-2 text-white/50 text-sm mt-2">
              <Mail class="size-4 flex-none" />
              <span>upicibiru@gmail.com</span>
            </div>
          </div>

          <div class="sm:text-right">
            <p class="text-white/70 text-sm mb-4">Ikuti kami</p>
            <div class="flex sm:justify-end gap-3">
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Linkedin class="size-4 text-white/70" />
              </a>
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Youtube class="size-4 text-white/70" />
              </a>
              <a
                href="#"
                class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors"
              >
                <Instagram class="size-4 text-white/70" />
              </a>
            </div>
          </div>
        </div>

        <p class="text-center text-white/40 text-xs pt-6">
          © {{ new Date().getFullYear() }} Smart Lab Management System. All Rights Reserved.
        </p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes fadein {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
