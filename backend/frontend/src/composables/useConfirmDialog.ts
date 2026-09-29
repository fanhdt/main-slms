import Swal from 'sweetalert2'

interface ConfirmOptions {
  title?: string
  text?: string
  confirmText?: string
  cancelText?: string
  danger?: boolean
}

export function useConfirmDialog() {
  async function confirmDelete(options: ConfirmOptions = {}): Promise<boolean> {
    const result = await Swal.fire({
      title: options.title ?? 'Yakin?',
      text: options.text ?? 'Tindakan ini tidak bisa dibatalkan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: options.confirmText ?? 'Ya, Hapus',
      cancelButtonText: options.cancelText ?? 'Batal',
      confirmButtonColor: options.danger === false ? '#2563eb' : '#dc2626',
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
      focusCancel: true,
    })
    return result.isConfirmed
  }

  return { confirmDelete }
}
