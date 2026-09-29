import {
  inject,
  watchEffect,
  type InjectionKey,
  type MaybeRefOrGetter,
  type Ref,
  toValue,
} from 'vue'

export interface AuthLayoutContext {
  title: Ref<string | undefined>
  subtitle: Ref<string | undefined>
  hideHeader: Ref<boolean>
}

export const authLayoutKey: InjectionKey<AuthLayoutContext> = Symbol('authLayout')

interface UseAuthLayoutOptions {
  title?: MaybeRefOrGetter<string | undefined>
  subtitle?: MaybeRefOrGetter<string | undefined>
  hideHeader?: MaybeRefOrGetter<boolean>
}

/**
 * Dipanggil dari setiap halaman auth (Login, Register, dst) untuk
 * mengisi title/subtitle/hideHeader pada <AuthLayout> yang mem-parent-i
 * halaman ini lewat nested route. Terima ref/getter supaya reaktif
 * (misal RegisterPage bisa sembunyikan header setelah submit sukses).
 */
export function useAuthLayout(options: UseAuthLayoutOptions) {
  const layout = inject(authLayoutKey)

  if (!layout) {
    throw new Error(
      'useAuthLayout() harus dipanggil di dalam halaman yang di-render oleh <AuthLayout>.',
    )
  }

  watchEffect(() => {
    layout.title.value = toValue(options.title)
    layout.subtitle.value = toValue(options.subtitle)
    layout.hideHeader.value = toValue(options.hideHeader) ?? false
  })
}
