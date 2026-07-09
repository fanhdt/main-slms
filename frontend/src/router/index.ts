import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { useLabStore } from '@/features/lab/stores/useLabStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // =========================================================
    // PUBLIC ROUTES
    // =========================================================

    // Landing Page Utama SLMS
    {
      path: '/',
      name: 'home',
      component: () => import('@/features/landing/pages/HomePage.vue'),
    },

    {
      path: '/lab/:slug/kiosk',
      name: 'lab-rfid-kiosk',
      component: () => import('@/features/user/pages/RfidKioskPage.vue'),
    },

    // Landing Page per Lab (public)
    {
      path: '/lab/:slug',
      name: 'lab-landing',
      component: () => import('@/features/landing/pages/LabLandingPage.vue'),
    },

    // Profile
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/features/auth/pages/ProfilePage.vue'),
      meta: { requiresAuth: true },
    },

    // Dashboard User
    {
      path: '/home',
      name: 'user-dashboard',
      component: () => import('@/features/dashboard/pages/UserDashboardPage.vue'),
      meta: { requiresAuth: true },
    },
    // Booking — untuk customer
    {
      path: '/booking',
      name: 'booking',
      component: () => import('@/features/booking/pages/BookingPage.vue'),
      meta: { requiresAuth: true },
    },

    {
      path: '/booking/:slug',
      name: 'booking-lab',
      component: () => import('@/features/booking/pages/BookingLabPage.vue'),
      meta: { requiresAuth: true },
    },

    {
      path: '/booking/:slug/form',
      name: 'booking-form',
      component: () => import('@/features/booking/pages/BookingFormPage.vue'),
      meta: { requiresAuth: true },
    },

    // Success page setelah booking dibuat
    {
      path: '/booking/:slug/success/:code',
      name: 'booking-success',
      component: () => import('@/features/booking/pages/BookingSuccessPage.vue'),
      meta: { requiresAuth: true },
    },

    // Asset Catalog — browse alat untuk disewa
    {
      path: '/booking/:slug/catalog',
      name: 'asset-catalog',
      component: () => import('@/features/booking/pages/AssetCatalogPage.vue'),
      meta: { requiresAuth: true },
    },

    // Cart — review keranjang sebelum checkout
    {
      path: '/booking/:slug/cart',
      name: 'booking-cart',
      component: () => import('@/features/booking/pages/CartPage.vue'),
      meta: { requiresAuth: true },
    },

    // My Bookings
    {
      path: '/my-bookings',
      name: 'my-bookings',
      component: () => import('@/features/booking/pages/MyBookingsPage.vue'),
      meta: { requiresAuth: true },
    },

    {
      path: '/my-bookings/photo/:uuid/select',
      name: 'photo-selection',
      component: () => import('@/features/photo/pages/PhotoSelectionPage.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/my-bookings/photo/:uuid/delivery',
      name: 'photo-delivery',
      component: () => import('@/features/photo/pages/PhotoDeliveryPage.vue'),
      meta: { requiresAuth: true },
    },

    // Auth
    {
      path: '/login',
      name: 'login',
      component: () => import('@/features/auth/pages/LoginPage.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/features/auth/pages/RegisterPage.vue'),
      meta: { requiresGuest: true },
    },

    {
      path: '/email-verified',
      name: 'email-verified',
      component: () => import('@/features/auth/pages/EmailVerifiedPage.vue'),
    },

    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/features/auth/pages/ForgotPasswordPage.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/features/auth/pages/ResetPasswordPage.vue'),
      meta: { requiresGuest: true },
    },

    // =========================================================
    // DASHBOARD — Pilih Lab
    // =========================================================
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/features/dashboard/pages/LabSelectorPage.vue'),
      meta: { requiresAuth: true, requiresStaff: true },
    },

    // =========================================================
    // SUPER ADMIN — Global Dashboard
    // =========================================================
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresRole: 'super_admin' },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/features/admin/pages/AdminDashboardPage.vue'),
        },
        {
          path: 'labs',
          name: 'admin-labs',
          component: () => import('@/features/lab/pages/LabListPage.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/features/user/pages/UserListPage.vue'),
        },
      ],
    },

    // =========================================================
    // LAB DASHBOARD — Per Lab
    // =========================================================
    {
      path: '/dashboard/lab/:labSlug',
      component: () => import('@/layouts/LabDashboardLayout.vue'),
      meta: { requiresAuth: true, requiresStaff: true, requiresLabAccess: true },
      children: [
        {
          path: '',
          name: 'lab-dashboard',
          component: () => import('@/features/dashboard/pages/LabDashboardPage.vue'),
        },
        {
          path: 'bookings',
          name: 'lab-bookings',
          component: () => import('@/features/booking/pages/BookingListPage.vue'),
        },
        {
          path: 'assets',
          name: 'lab-assets',
          component: () => import('@/features/asset/pages/AssetListPage.vue'),
        },
        {
          path: 'users',
          name: 'lab-users',
          component: () => import('@/features/user/pages/UserListPage.vue'),
        },
        {
          path: 'services',
          name: 'lab-services',
          component: () => import('@/features/labservice/pages/ServiceListPage.vue'),
        },
        {
          path: 'packages',
          name: 'lab-packages',
          component: () => import('@/features/labservice/pages/PackageListPage.vue'),
        },
        {
          path: 'scan',
          name: 'lab-scan',
          component: () => import('@/features/booking/pages/QRScanPage.vue'),
        },
        {
          path: 'photo-projects',
          name: 'lab-photo-projects',
          component: () => import('@/features/photo/pages/PhotoProjectListPage.vue'),
        },
        {
          path: 'photo-projects/:uuid',
          name: 'lab-photo-project-detail',
          component: () => import('@/features/photo/pages/PhotoProjectDetailPage.vue'),
        },

        {
          path: 'photographers',
          name: 'lab-photographers',
          component: () => import('@/features/portfolio/pages/PhotographerListPage.vue'),
        },

        // Portfolio
        {
          path: 'portfolio',
          name: 'lab-portfolio',
          component: () => import('@/features/portfolio/pages/PortfolioListPage.vue'),
        },

        {
          path: 'rfid-checkin',
          name: 'lab-rfid-checkin',
          component: () => import('@/features/user/pages/RfidLookupPage.vue'),
        },
        {
          path: 'settings',
          name: 'lab-settings',
          component: () => import('@/features/lab/pages/LabSettingsPage.vue'),
        },
      ],
    },

    // 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/features/dashboard/pages/NotFoundPage.vue'),
    },

    // RFID
  ],
})

// =========================================================
// Navigation Guard
// =========================================================
router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Redirect berdasarkan role
    if (authStore.hasRole('super_admin')) {
      return { name: 'admin-dashboard' }
    }
    if (authStore.hasRole('customer')) {
      return { name: 'user-dashboard' }
    }
    return { name: 'dashboard' }
  }

  if (to.meta.requiresRole === 'super_admin') {
    if (!authStore.hasRole('super_admin')) {
      return { name: 'dashboard' }
    }
  }

  if (to.meta.requiresRole === 'super_admin') {
    if (!authStore.hasRole('super_admin')) {
      return { name: 'dashboard' }
    }
  }

  // NEW — /dashboard (LabSelectorPage) & rute admin lab lain hanya untuk staff
  if (to.meta.requiresStaff) {
    if (!authStore.hasRole('lab_admin') && !authStore.hasRole('super_admin')) {
      return { name: 'user-dashboard' }
    }
  }

  if (to.meta.requiresLabAccess) {
    const labStore = useLabStore()
    if (!authStore.hasRole('super_admin')) {
      if (!labStore.managedLabs.length) await labStore.fetchManagedLabs()
      const allowed = labStore.managedLabs.some((l) => l.slug === to.params.labSlug)
      if (!allowed) return { name: 'dashboard' }
    }
  }
  // === SAMPAI SINI ===
})

export default router
