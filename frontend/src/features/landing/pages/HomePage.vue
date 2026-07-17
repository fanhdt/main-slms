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

// ==========================================================================
// UI-only helpers (do not affect business logic)
// ==========================================================================
const isMobileMenuOpen = ref(false)

/**
 * Ambil gambar sebuah lab dari data API (hero_image, lalu fallback ke logo).
 * Tidak ada hardcode gambar — murni dari branding lab yang sudah di-load lewat useQuery di atas.
 */
function getLabImage(lab: any) {
  return lab?.branding?.hero_image || lab?.branding?.logo || null
}

/**
 * Hero Carousel — showcase suasana lab (dekorasi, sama seperti galeri sebelumnya).
 * Ini bukan data dinamis dari API, jadi aman diubah/diperkaya tanpa menyentuh business logic.
 */
const carouselImages = [
  {
    src: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&q=80',
    name: 'Studio fotografi',
  },
  {
    src: 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?w=1200&q=80',
    name: 'Studio audio',
  },
  {
    src: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&q=80',
    name: 'Lab komputer',
  },
  {
    src: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1200&q=80',
    name: 'Ruang kerja',
  },
  {
    src: 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=1200&q=80',
    name: 'Peralatan lab',
  },
]

// digandakan supaya infinite loop terasa seamless
const carouselLoopImages = [...carouselImages, ...carouselImages]

// ---- Auto-loop + pause on hover + drag (mouse) + swipe (touch) ----
const carouselTrackRef = ref<HTMLElement | null>(null)
let carouselFrame: number | null = null
const CAROUSEL_SPEED_PX_PER_FRAME = 0.6

const isHovering = ref(false)
const isDragging = ref(false)
let dragStartX = 0
let dragStartScrollLeft = 0

function stepCarouselScroll() {
  const el = carouselTrackRef.value
  if (el && !isHovering.value && !isDragging.value && carouselLoopImages.length) {
    el.scrollLeft += CAROUSEL_SPEED_PX_PER_FRAME
    // loop balik ke awal begitu sudah melewati setengah track (karena list digandakan)
    if (el.scrollLeft >= el.scrollWidth / 2) {
      el.scrollLeft = 0
    }
  }
  carouselFrame = requestAnimationFrame(stepCarouselScroll)
}

function onCarouselMouseDown(e: MouseEvent) {
  const el = carouselTrackRef.value
  if (!el) return
  isDragging.value = true
  dragStartX = e.pageX
  dragStartScrollLeft = el.scrollLeft
}

function onCarouselMouseMove(e: MouseEvent) {
  const el = carouselTrackRef.value
  if (!el || !isDragging.value) return
  const delta = e.pageX - dragStartX
  el.scrollLeft = dragStartScrollLeft - delta
}

function endCarouselDrag() {
  isDragging.value = false
}

onMounted(() => {
  carouselFrame = requestAnimationFrame(stepCarouselScroll)
})

onBeforeUnmount(() => {
  if (carouselFrame) cancelAnimationFrame(carouselFrame)
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
      class="fixed top-0 left-0 right-0 z-50 bg-[#0f1826]/70 backdrop-blur-xl border-b border-white/10 shadow-[0_4px_30px_-10px_rgba(0,0,0,0.3)]"
    >
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img src="/images/logo-lab.svg" alt="Logo SLMS" class="h-8 w-8 object-contain shrink-0" />
          <span class="h-6 w-px bg-white/15 shrink-0" aria-hidden="true" />
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
            class="relative text-white/70 hover:text-white text-sm font-medium transition-colors group"
          >
            Home
            <span
              class="absolute -bottom-1.5 left-0 h-px w-0 bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <a
            href="#layanan"
            class="relative text-white/70 hover:text-white text-sm font-medium transition-colors group"
          >
            Layanan
            <span
              class="absolute -bottom-1.5 left-0 h-px w-0 bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <a
            href="#tentang"
            class="relative text-white/70 hover:text-white text-sm font-medium transition-colors group"
          >
            Tentang
            <span
              class="absolute -bottom-1.5 left-0 h-px w-0 bg-blue-400 transition-all duration-300 group-hover:w-full"
            />
          </a>
          <Button
            size="sm"
            class="bg-blue-600 hover:bg-blue-500 rounded-full px-5 shadow-lg shadow-blue-600/20 transition-all duration-300 hover:shadow-blue-500/30 hover:-translate-y-0.5"
            @click="goToAuth"
          >
            {{ authStore.isAuthenticated ? 'Dashboard' : 'Masuk' }}
          </Button>
        </div>

        <!-- Mobile hamburger -->
        <button
          class="md:hidden text-white p-2 -mr-2 rounded-lg hover:bg-white/10 transition-colors"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <Menu v-if="!isMobileMenuOpen" class="size-6" />
          <X v-else class="size-6" />
        </button>
      </div>

      <!-- Mobile menu (drawer) -->
      <transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0 -translate-y-3"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-3"
      >
        <div
          v-if="isMobileMenuOpen"
          class="md:hidden bg-[#0f1826]/95 backdrop-blur-xl border-t border-white/10 px-6 py-5 flex flex-col gap-4"
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
            class="bg-blue-600 hover:bg-blue-500 rounded-full w-full mt-1"
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
      class="relative min-h-screen flex flex-col items-center justify-center pt-24 pb-10 bg-[#0f1826] overflow-hidden"
    >
      <!-- decorative gradient glows -->
      <div
        class="absolute -top-40 -left-40 w-[500px] h-[500px] rounded-full bg-blue-600/20 blur-[120px] pointer-events-none"
      />
      <div
        class="absolute -bottom-32 -right-32 w-[450px] h-[450px] rounded-full bg-indigo-500/15 blur-[120px] pointer-events-none"
      />
      <div
        class="absolute inset-0 opacity-[0.07] pointer-events-none"
        style="
          background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
          background-size: 30px 30px;
        "
      />

      <div class="max-w-4xl mx-auto px-6 text-center relative z-10 animate-[fadein_0.8s_ease-out]">
        <div
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-1.5 rounded-full mb-6 bg-blue-500/10 text-blue-300 border border-blue-400/20 backdrop-blur-sm"
        >
          ✦ Smart Lab Management System
        </div>

        <h1
          class="text-4xl sm:text-5xl md:text-7xl font-black text-white leading-[1.08] mb-6 tracking-tight"
        >
          Kelola Semua Lab<br />Dalam Satu Platform
        </h1>

        <p
          class="text-base sm:text-lg md:text-xl text-white/55 max-w-2xl mx-auto mb-10 leading-relaxed"
        >
          Booking studio, sewa peralatan,<br class="hidden sm:block" />
          hingga pengelolaan layanan laboratorium<br class="hidden sm:block" />
          dengan mudah.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a
            href="#layanan"
            class="w-full sm:w-auto text-center px-8 py-4 rounded-2xl font-semibold text-white text-base sm:text-lg bg-blue-600 hover:bg-blue-500 transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-600/30 shadow-lg shadow-blue-600/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0f1826]"
          >
            Lihat Laboratorium
          </a>
          <button
            @click="goToAuth"
            class="w-full sm:w-auto px-8 py-4 rounded-2xl font-semibold text-white border border-white/15 hover:bg-white/10 transition-all duration-300 ease-out hover:-translate-y-0.5 text-base sm:text-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0f1826]"
          >
            {{ authStore.isAuthenticated ? 'Ke Dashboard' : 'Dashboard' }}
          </button>
        </div>
      </div>

      <!-- spacer di bawah hero agar carousel punya ruang menjorok masuk -->
      <div class="h-[110px] sm:h-[170px] md:h-[220px] shrink-0" aria-hidden="true" />
    </section>

    <!-- ================================================================
         HERO CAROUSEL
         Layout overlap murni CSS (negative margin), TIDAK memakai JS
         untuk mengatur posisi. ~40-50% tinggi carousel berada di dalam
         Hero, sisanya menjorok ke section berikutnya.
    ================================================================ -->
    <div class="relative z-20 -mt-[240px] sm:-mt-[300px] md:-mt-[380px] px-0">
      <div
        ref="carouselTrackRef"
        class="relative flex gap-0 overflow-x-auto scrollbar-hide select-none cursor-grab active:cursor-grabbing h-[250px] sm:h-[420px] md:h-[560px] rounded-none"
        @mouseenter="isHovering = true"
        @mouseleave="
          isHovering = false
          endCarouselDrag()
        "
        @mousedown="onCarouselMouseDown"
        @mousemove="onCarouselMouseMove"
        @mouseup="endCarouselDrag"
      >
        <div
          v-for="(g, idx) in carouselLoopImages"
          :key="idx"
          class="relative flex-none w-full sm:w-1/2 lg:w-1/3 h-full overflow-hidden rounded-none group"
        >
          <img
            :src="g.src"
            :alt="g.name"
            draggable="false"
            class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 pointer-events-none"
          />
        </div>

        <!-- edge overlay gradients for smoother visual transition -->
        <div
          class="pointer-events-none absolute inset-y-0 left-0 w-16 sm:w-24 bg-gradient-to-r from-[#0f1826]/60 to-transparent z-10"
        />
        <div
          class="pointer-events-none absolute inset-y-0 right-0 w-16 sm:w-24 bg-gradient-to-l from-black/30 to-transparent z-10"
        />
      </div>
    </div>

    <!-- ================================================================
         LAB GRID — LAYANAN
    ================================================================ -->
    <section id="layanan" class="pt-16 sm:pt-24 pb-20 sm:pb-28 bg-white">
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
            class="group text-left bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 ease-out hover:-translate-y-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
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
          <p class="text-gray-400 text-base mb-1">Belum ada laboratorium tersedia.</p>
          <p class="text-gray-300 text-sm">Silakan cek kembali beberapa saat lagi.</p>
        </div>
      </div>
    </section>

    <!-- ================================================================
         TENTANG
    ================================================================ -->
    <section id="tentang" class="py-20 sm:py-28 bg-[#0f1826] relative overflow-hidden">
      <div
        class="absolute inset-0 opacity-[0.06] pointer-events-none"
        style="
          background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0);
          background-size: 30px 30px;
        "
      />
      <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 tracking-tight">Tentang SLMS</h2>
        <p class="text-white/55 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
          Smart Lab Management System adalah platform terpadu untuk mengelola berbagai laboratorium.
          Mulai dari booking studio, sewa peralatan, hingga pengelolaan layanan, semuanya dalam satu
          sistem yang mudah digunakan.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-14">
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:-translate-y-1"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <Images class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Multi Laboratorium</h3>
            <p class="text-sm text-white/45 mt-2 leading-relaxed">
              Kelola berbagai jenis lab dalam satu platform
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:-translate-y-1"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <CalendarCheck class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Booking Mudah</h3>
            <p class="text-sm text-white/45 mt-2 leading-relaxed">
              Sistem booking yang cepat dan transparan
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 flex flex-col items-center transition-all duration-300 ease-out hover:bg-white/[0.06] hover:-translate-y-1"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
              <ShieldCheck class="size-6 text-blue-400" />
            </div>
            <h3 class="font-semibold text-white">Aman &amp; Terpercaya</h3>
            <p class="text-sm text-white/45 mt-2 leading-relaxed">
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
            class="rounded-2xl bg-gray-50 border border-gray-100 p-8 flex flex-col items-center text-center transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
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
    <footer class="bg-[#0f1826] pt-16 pb-8">
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
