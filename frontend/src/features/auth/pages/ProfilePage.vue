<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/features/auth/stores/useAuthStore'
import { toast } from 'vue-sonner'
import CustomerNavbar from '@/components/CustomerNavbar.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { CreditCard, User, ShieldAlert } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const backTarget = computed(() => {
  if (authStore.hasRole('super_admin')) return '/admin'
  if (authStore.hasRole('lab_admin')) return '/dashboard'
  return '/home'
})

const name = ref(authStore.user?.name ?? '')
const email = ref(authStore.user?.email ?? '')
const phone = ref(authStore.user?.phone ?? '')
const errors = ref<Record<string, string>>({})
const avatarInputRef = ref<HTMLInputElement | null>(null)
const isUploadingAvatar = ref(false)

const showDeleteConfirm = ref(false)
const deletePassword = ref('')
const isDeleting = ref(false)

const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirm = ref('')
const passwordErrors = ref<Record<string, string>>({})
const isChangingPassword = ref(false)

async function saveProfile() {
  errors.value = {}
  const result = await authStore.updateProfile({
    name: name.value,
    email: email.value,
    phone: phone.value || undefined,
  })
  if (result.success) {
    toast.success('Profil berhasil diupdate.')
  } else {
    if (result.errors) {
      errors.value = Object.fromEntries(
        Object.entries(result.errors).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
    }
    toast.error(result.message)
  }
}

async function handleAvatarChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  isUploadingAvatar.value = true
  const result = await authStore.updateAvatar(file)
  isUploadingAvatar.value = false
  input.value = ''

  if (result.success) {
    toast.success('Avatar berhasil diupdate.')
  } else {
    toast.error(result.message)
  }
}

async function confirmDeleteAccount() {
  if (!deletePassword.value) {
    toast.error('Masukkan password untuk konfirmasi.')
    return
  }
  isDeleting.value = true
  const result = await authStore.deleteAccount(deletePassword.value)
  isDeleting.value = false

  if (result.success) {
    toast.success('Akun berhasil dihapus.')
    router.push({ name: 'home' })
  } else {
    toast.error(result.message)
  }
}

async function changePassword() {
  passwordErrors.value = {}

  if (newPassword.value !== newPasswordConfirm.value) {
    passwordErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.'
    return
  }

  isChangingPassword.value = true
  const result = await authStore.changePassword({
    current_password: currentPassword.value,
    password: newPassword.value,
    password_confirmation: newPasswordConfirm.value,
  })
  isChangingPassword.value = false

  if (result.success) {
    toast.success('Password berhasil diubah. Sesi perangkat lain otomatis logout.')
    currentPassword.value = ''
    newPassword.value = ''
    newPasswordConfirm.value = ''
  } else {
    if (result.errors) {
      passwordErrors.value = Object.fromEntries(
        Object.entries(result.errors).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
    }
    toast.error(result.message)
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <CustomerNavbar :back-to="backTarget" title="Profil Saya" />
    <div class="max-w-2xl mx-auto p-6 space-y-6">
      <!-- Avatar -->
      <Card class="p-0">
        <CardContent class="p-6 flex items-center gap-5">
          <div
            class="w-20 h-20 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center shrink-0"
          >
            <img
              v-if="authStore.user?.avatar"
              :src="authStore.user.avatar"
              alt="Avatar"
              class="w-full h-full object-cover"
            />
            <User v-else class="size-8 text-gray-300" />
          </div>
          <div>
            <Button
              variant="link"
              size="sm"
              class="px-0 h-auto"
              :disabled="isUploadingAvatar"
              @click="avatarInputRef?.click()"
            >
              {{ isUploadingAvatar ? 'Mengupload...' : 'Ganti Foto Profil' }}
            </Button>
            <input
              ref="avatarInputRef"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="hidden"
              @change="handleAvatarChange"
            />
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WEBP. Maks 5MB.</p>
          </div>
        </CardContent>
      </Card>

      <!-- Update Profil -->
      <Card class="p-0">
        <CardHeader class="px-6 pt-6 pb-0">
          <CardTitle class="text-sm font-semibold">Informasi Akun</CardTitle>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input
              v-model="name"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-400': errors.name }"
            />
            <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-400': errors.email }"
            />
            <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">No. Telepon</label>
            <input
              v-model="phone"
              type="tel"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <Button :disabled="authStore.loading" @click="saveProfile">
            {{ authStore.loading ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </Button>
        </CardContent>
      </Card>

      <!-- Status Kartu RFID -->
      <Card class="p-0">
        <CardHeader class="px-6 pt-6 pb-0">
          <CardTitle class="text-sm font-semibold">Kartu RFID</CardTitle>
        </CardHeader>
        <CardContent class="p-6">
          <div v-if="authStore.user?.rfid_uid" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
              <CreditCard class="size-5 text-green-600" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 flex items-center gap-1.5">
                Kartu terdaftar
                <Badge variant="outline" class="border-0 bg-green-50 text-green-700">Aktif</Badge>
              </p>
              <p class="text-xs text-gray-400 font-mono mt-0.5">{{ authStore.user.rfid_uid }}</p>
            </div>
          </div>

          <div v-else class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
              <CreditCard class="size-5 text-gray-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-700">Belum ada kartu terdaftar</p>
              <p class="text-xs text-gray-500 mt-0.5">
                Kartu RFID kampus kamu perlu didaftarkan oleh admin lab agar bisa dipakai untuk
                check-in otomatis. Bawa kartu kamu ke admin lab terdekat.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Ganti Password -->
      <Card class="p-0">
        <CardHeader class="px-6 pt-6 pb-0">
          <CardTitle class="text-sm font-semibold">Keamanan</CardTitle>
        </CardHeader>
        <CardContent class="p-6 space-y-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Password Saat Ini</label>
            <input
              v-model="currentPassword"
              type="password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-400': passwordErrors.current_password }"
            />
            <p v-if="passwordErrors.current_password" class="text-xs text-red-500">
              {{ passwordErrors.current_password }}
            </p>
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Password Baru</label>
            <input
              v-model="newPassword"
              type="password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-400': passwordErrors.password }"
            />
            <p v-if="passwordErrors.password" class="text-xs text-red-500">
              {{ passwordErrors.password }}
            </p>
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
            <input
              v-model="newPasswordConfirm"
              type="password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-400': passwordErrors.password_confirmation }"
            />
            <p v-if="passwordErrors.password_confirmation" class="text-xs text-red-500">
              {{ passwordErrors.password_confirmation }}
            </p>
          </div>

          <Button
            :disabled="isChangingPassword || !currentPassword || !newPassword"
            @click="changePassword"
          >
            {{ isChangingPassword ? 'Menyimpan...' : 'Ubah Password' }}
          </Button>
        </CardContent>
      </Card>

      <!-- Danger Zone -->
      <Card class="p-0 border-red-200">
        <CardHeader class="px-6 pt-6 pb-0">
          <CardTitle class="text-sm font-semibold text-red-900 flex items-center gap-2">
            <ShieldAlert class="size-4" />
            Hapus Akun
          </CardTitle>
        </CardHeader>
        <CardContent class="p-6 space-y-3 bg-red-50/50 mt-4 rounded-b-xl">
          <p class="text-sm text-red-700">
            Setelah dihapus, kamu tidak bisa login lagi dengan akun ini. Tindakan ini tidak bisa
            dibatalkan sendiri.
          </p>

          <Button
            v-if="!showDeleteConfirm"
            variant="outline"
            class="border-red-300 text-red-700 hover:bg-red-100"
            @click="showDeleteConfirm = true"
          >
            Hapus Akun Saya
          </Button>

          <div v-else class="space-y-3">
            <label class="text-sm font-medium text-red-900"
              >Masukkan password untuk konfirmasi</label
            >
            <input
              v-model="deletePassword"
              type="password"
              class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
              placeholder="Password kamu"
            />
            <div class="flex gap-2">
              <Button variant="destructive" :disabled="isDeleting" @click="confirmDeleteAccount">
                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Akun Saya' }}
              </Button>
              <Button variant="ghost" @click="((showDeleteConfirm = false), (deletePassword = ''))">
                Batal
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
