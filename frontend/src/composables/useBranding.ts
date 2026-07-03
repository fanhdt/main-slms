import { computed } from 'vue'
import { useLabStore } from '@/features/lab/stores/useLabStore'

export function useBranding() {
  const labStore = useLabStore()

  const primaryColor = computed(() => labStore.primaryColor)
  const secondaryColor = computed(() => labStore.secondaryColor)
  const labName = computed(() => labStore.activeLab?.name ?? 'SLMS')
  const labLogo = computed(() => labStore.activeLab?.branding.logo ?? null)
  const labSlug = computed(() => labStore.activeLab?.slug ?? null)

  /**
   * Menghasilkan style object untuk dipakai di komponen
   * yang butuh warna dinamis via inline style.
   */
  const brandingStyle = computed(() => ({
    '--color-primary': primaryColor.value,
    '--color-secondary': secondaryColor.value,
  }))

  /**
   * Class Tailwind tidak bisa dinamis karena Tailwind
   * scan saat build. Gunakan inline style untuk warna dinamis.
   */
  const primaryBgStyle = computed(() => ({
    backgroundColor: primaryColor.value,
  }))

  const primaryTextStyle = computed(() => ({
    color: primaryColor.value,
  }))

  const primaryBorderStyle = computed(() => ({
    borderColor: primaryColor.value,
  }))

  return {
    primaryColor,
    secondaryColor,
    labName,
    labLogo,
    labSlug,
    brandingStyle,
    primaryBgStyle,
    primaryTextStyle,
    primaryBorderStyle,
  }
}
