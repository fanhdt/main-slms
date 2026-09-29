<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { userApi } from '@/features/user/api/userApi'
import { labApi } from '@/features/lab/api/labApi'
import BaseModal from '@/components/BaseModal.vue'
import { toast } from 'vue-sonner'
import type { User } from '@/types'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { CreditCard, ScanLine, ShieldAlert } from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  user?: User | null
}>()

const emit = defineEmits<{
  close: []
}>()

const queryClient = useQueryClient()

// Deskripsi tugas & kewenangan tiap role — ditampilkan otomatis di bawah
// dropdown role supaya admin paham konsekuensi memilih role tertentu.
// super_admin SENGAJA tidak dimasukkan di sini karena tidak bisa dipilih
// lewat form ini sama sekali.
const ROLE_DESCRIPTIONS: Record<string, string> = {
  lab_admin:
    'Mengelola satu laboratorium penuh: booking, aset, layanan, paket, portofolio, dan pengaturan lab. Tidak berwenang mengelola akun/role pengguna secara global.',
  operator:
    'Operasional harian lab: menerima & memproses booking, memantau ketersediaan aset, dan check-in pelanggan.',
  photographer:
    'Mengunggah foto preview hasil sesi pemotretan dan melihat jadwal booking yang berkaitan.',
  editor:
    'Mengunggah hasil edit foto, menindaklanjuti revisi dari customer, dan menyiapkan file final.',
  customer:
    'Pengguna umum: membuat booking, melihat riwayat booking sendiri, memilih & mengunduh hasil foto.',
  guest: 'Hanya bisa melihat daftar layanan dan paket publik, tanpa membuat booking.',
}

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'customer',
  is_active: true,
})

const errors = ref<Record<string, string>>({})

const isSuperAdminUser = computed(() => props.user?.roles.includes('super_admin') ?? false)

watch(
  () => props.user,
  (user) => {
    if (user) {
      form.value = {
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        phone: user.phone ?? '',
        role: user.roles[0] ?? 'customer',
        is_active: user.is_active,
      }
    } else {
      form.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        role: 'customer',
        is_active: true,
      }
    }
  },
  { immediate: true },
)

const isEdit = ref(false)
watch(
  () => props.user,
  (user) => {
    isEdit.value = !!user
  },
  { immediate: true },
)

const LAB_SCOPED_ROLES = ['lab_admin', 'operator', 'photographer', 'editor']
const needsLabAccess = computed(() => LAB_SCOPED_ROLES.includes(form.value.role))
const isSingleLabRole = computed(() => form.value.role === 'lab_admin')

const { data: allLabs } = useQuery({
  queryKey: ['labs-for-user-form'],
  queryFn: async () => {
    const res = await labApi.getAll({ per_page: 100 })
    return res.data.data.data
  },
  enabled: computed(() => props.show),
})

const initialLabUuids = ref<string[]>([])
const selectedLabUuids = ref<string[]>([])

const { refetch: refetchUserLabs } = useQuery({
  queryKey: ['user-labs', props.user?.uuid],
  queryFn: async () => {
    if (!props.user) return []
    const res = await userApi.getUserLabs(props.user.uuid)
    return res.data.data.data
  },
  enabled: computed(() => isEdit.value && !!props.user),
})

watch(
  () => props.user,
  async (user) => {
    if (user) {
      const { data } = await refetchUserLabs()
      const uuids = (data ?? []).map((l) => l.uuid)
      initialLabUuids.value = uuids
      selectedLabUuids.value = [...uuids]
    } else {
      initialLabUuids.value = []
      selectedLabUuids.value = []
    }
  },
  { immediate: true },
)

function toggleLab(uuid: string) {
  if (isSingleLabRole.value) {
    selectedLabUuids.value = [uuid]
    return
  }
  const idx = selectedLabUuids.value.indexOf(uuid)
  if (idx === -1) {
    selectedLabUuids.value.push(uuid)
  } else {
    selectedLabUuids.value.splice(idx, 1)
  }
}

watch(
  () => form.value.role,
  () => {
    if (isSingleLabRole.value && selectedLabUuids.value.length > 1) {
      selectedLabUuids.value = [selectedLabUuids.value[0]!]
    }
  },
)

async function syncLabAccess(userUuid: string) {
  if (!needsLabAccess.value) return

  const toAdd = selectedLabUuids.value.filter((u) => !initialLabUuids.value.includes(u))
  const toRemove = initialLabUuids.value.filter((u) => !selectedLabUuids.value.includes(u))

  for (const labUuid of toAdd) {
    await userApi.assignLab(userUuid, labUuid, form.value.role)
  }
  for (const labUuid of toRemove) {
    await userApi.revokeLab(userUuid, labUuid)
  }
}

const rfidInput = ref('')
const rfidInputRef = ref<HTMLInputElement | null>(null)
const showRfidSection = ref(false)

const { mutate: saveUser, isPending } = useMutation({
  mutationFn: async () => {
    if (isEdit.value && props.user) {
      const data: Record<string, unknown> = {
        name: form.value.name,
        phone: form.value.phone || null,
        role: form.value.role,
        is_active: form.value.is_active,
      }
      if (form.value.password) {
        data.password = form.value.password
        data.password_confirmation = form.value.password_confirmation
      }
      const res = await userApi.update(props.user.uuid, data)
      await syncLabAccess(props.user.uuid)
      return res
    } else {
      const res = await userApi.create(form.value)
      const newUuid = (res.data.data as User).uuid
      await syncLabAccess(newUuid)
      return res
    }
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['users'] })
    toast.success(isEdit.value ? 'User & akses lab berhasil diupdate.' : 'User berhasil dibuat.')
    emit('close')
  },
  onError: (error: any) => {
    const errs = error.response?.data?.errors
    if (errs) {
      errors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, (v as string[])[0] ?? '']),
      )
    } else {
      toast.error(error.response?.data?.message ?? 'Terjadi kesalahan.')
    }
  },
})

const { mutate: saveRfid, isPending: isSavingRfid } = useMutation({
  mutationFn: () => {
    if (!props.user) throw new Error('User belum dipilih.')
    return userApi.assignRfid(props.user.uuid, rfidInput.value)
  },
  onSuccess: () => {
    toast.success('Kartu RFID berhasil dikaitkan.')
    rfidInput.value = ''
    showRfidSection.value = false
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message ?? 'Gagal mengaitkan kartu.')
  },
})

function openRfidScan() {
  showRfidSection.value = true
  nextTick(() => rfidInputRef.value?.focus())
}

function handleRfidScan() {
  if (rfidInput.value) {
    saveRfid()
  }
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="isEdit ? 'Edit User' : 'Tambah User'"
    size="lg"
    @close="$emit('close')"
  >
    <form @submit.prevent="() => saveUser()" class="space-y-5">
      <!-- Peringatan khusus kalau user ini super_admin -->
      <div
        v-if="isSuperAdminUser"
        class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-lg p-3"
      >
        <ShieldAlert class="size-4 text-amber-600 shrink-0 mt-0.5" />
        <p class="text-xs text-amber-800">
          User ini adalah <strong>Super Admin</strong>. Role Super Admin tidak bisa diubah lewat
          form ini demi keamanan — hanya bisa diatur langsung oleh developer di server.
        </p>
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Nama Lengkap</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="John Doe"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.name }"
        />
        <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</p>
      </div>

      <div v-if="!isEdit" class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          placeholder="john@example.com"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.email }"
        />
        <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email }}</p>
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">No. Telepon</label>
        <input
          v-model="form.phone"
          type="tel"
          placeholder="08123456789"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Role — super_admin SENGAJA tidak ada di daftar ini.
           Kalau ada kebutuhan menjadikan seseorang super_admin, itu harus
           dilakukan langsung oleh developer lewat CLI di server, bukan
           lewat aplikasi, supaya tidak ada celah privilege escalation. -->
      <div v-if="!isSuperAdminUser" class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Role</label>
        <select
          v-model="form.role"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="lab_admin">Lab Admin</option>
          <option value="operator">Operator</option>
          <option value="photographer">Photographer</option>
          <option value="editor">Editor</option>
          <option value="customer">Customer</option>
          <option value="guest">Guest</option>
        </select>
        <p class="text-xs text-gray-400 mt-1 leading-relaxed">
          {{ ROLE_DESCRIPTIONS[form.role] ?? '' }}
        </p>
      </div>

      <!-- Akses Laboratorium -->
      <template v-if="needsLabAccess && !isSuperAdminUser">
        <Separator />
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700">
            Akses Laboratorium
            <span v-if="isSingleLabRole" class="text-xs text-gray-400 font-normal"
              >(pilih 1 lab)</span
            >
          </label>
          <p class="text-xs text-gray-400">
            {{
              isSingleLabRole
                ? 'Lab Admin hanya bisa mengelola 1 laboratorium.'
                : 'Pilih lab mana saja yang boleh dikelola/diakses user ini.'
            }}
          </p>

          <div
            class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-3"
          >
            <label
              v-for="lab in allLabs"
              :key="lab.uuid"
              class="flex items-center gap-2 text-sm cursor-pointer"
            >
              <input
                v-if="isSingleLabRole"
                type="radio"
                name="lab-access-radio"
                :checked="selectedLabUuids.includes(lab.uuid)"
                @change="toggleLab(lab.uuid)"
                class="w-4 h-4 border-gray-300"
              />
              <input
                v-else
                type="checkbox"
                :checked="selectedLabUuids.includes(lab.uuid)"
                @change="toggleLab(lab.uuid)"
                class="w-4 h-4 rounded border-gray-300"
              />
              {{ lab.name }}
            </label>
            <p v-if="!allLabs?.length" class="text-xs text-gray-400 col-span-2">
              Belum ada lab terdaftar.
            </p>
          </div>
        </div>
      </template>

      <Separator />

      <!-- Password -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">
          Password
          <span v-if="isEdit" class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          placeholder="••••••••"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          :class="{ 'border-red-400': errors.password }"
        />
        <p v-if="errors.password" class="text-xs text-red-500">{{ errors.password }}</p>
        <p class="text-xs text-gray-400">
          Minimal 8 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol (misal: !@#$%).
        </p>
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-medium text-gray-700">Konfirmasi Password</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          placeholder="••••••••"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Kaitkan Kartu RFID — cuma muncul saat edit -->
      <template v-if="isEdit">
        <Separator />
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700 flex items-center gap-1.5">
              <CreditCard class="size-4 text-gray-400" />
              Kartu RFID
              <Badge
                v-if="props.user?.rfid_uid"
                variant="outline"
                class="border-0 bg-green-50 text-green-700"
              >
                Terdaftar
              </Badge>
            </label>
            <Button type="button" variant="link" size="sm" class="px-0" @click="openRfidScan">
              {{ props.user?.rfid_uid ? 'Ganti Kartu' : 'Kaitkan Kartu' }}
            </Button>
          </div>

          <div v-if="showRfidSection" class="space-y-2">
            <div class="relative">
              <ScanLine class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-blue-400" />
              <input
                ref="rfidInputRef"
                v-model="rfidInput"
                @keyup.enter="handleRfidScan"
                type="text"
                placeholder="Tempelkan kartu ke reader..."
                class="w-full pl-9 pr-3 py-2 border-2 border-dashed border-blue-300 rounded-lg text-sm text-center focus:outline-none focus:border-blue-500"
              />
            </div>
            <p class="text-xs text-gray-400 text-center">
              {{ isSavingRfid ? 'Menyimpan...' : 'Tempelkan kartu RFID mahasiswa ke reader' }}
            </p>
          </div>
        </div>
      </template>

      <Separator />

      <div class="flex items-center gap-3">
        <input
          v-model="form.is_active"
          type="checkbox"
          id="is_active"
          class="w-4 h-4 rounded border-gray-300"
        />
        <label for="is_active" class="text-sm font-medium text-gray-700">Akun Aktif</label>
      </div>
    </form>

    <template #footer>
      <Button variant="ghost" @click="$emit('close')">Batal</Button>
      <Button :disabled="isPending" @click="saveUser()">
        {{ isPending ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
      </Button>
    </template>
  </BaseModal>
</template>
