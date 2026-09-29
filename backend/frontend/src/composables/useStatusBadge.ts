import type { EnumField } from '@/types'

type StatusInput = EnumField | string | undefined

function getValue(status: StatusInput): string {
  if (!status) return ''
  return typeof status === 'string' ? status : status.value
}

export interface StatusStyle {
  bg: string
  text: string
}

const FALLBACK_STYLE: StatusStyle = { bg: '#f3f4f6', text: '#4b5563' } // gray-100 / gray-600

export function useStatusBadge() {
  // ==========================================================
  // Booking status — pending, approved, ongoing, completed, canceled, rejected
  // ==========================================================
  const BOOKING_STYLES: Record<string, StatusStyle> = {
    pending: { bg: '#fef9c3', text: '#a16207' }, // yellow-100 / yellow-700
    approved: { bg: '#dbeafe', text: '#1d4ed8' }, // blue-100 / blue-700
    ongoing: { bg: '#f3e8ff', text: '#7e22ce' }, // purple-100 / purple-700
    completed: { bg: '#dcfce7', text: '#15803d' }, // green-100 / green-700
    canceled: { bg: '#fee2e2', text: '#b91c1c' }, // red-100 / red-700
    rejected: { bg: '#fee2e2', text: '#b91c1c' }, // red-100 / red-700
  }

  // ==========================================================
  // Payment status — unpaid, paid, refunded
  // ==========================================================
  const PAYMENT_STYLES: Record<string, StatusStyle> = {
    unpaid: { bg: '#fee2e2', text: '#b91c1c' }, // red-100 / red-700
    pending: { bg: '#fef9c3', text: '#a16207' }, // yellow-100 / yellow-700
    paid: { bg: '#dcfce7', text: '#15803d' }, // green-100 / green-700
    refunded: { bg: '#f3f4f6', text: '#4b5563' }, // gray-100 / gray-600
  }

  // ==========================================================
  // Photo project status — sinkron dengan PhotoStatusBadge.vue
  // ==========================================================
  const PHOTO_STYLES: Record<string, StatusStyle> = {
    pending: { bg: '#f3f4f6', text: '#4b5563' }, // gray-100 / gray-600
    preview_uploaded: { bg: '#dbeafe', text: '#1d4ed8' }, // blue-100 / blue-700
    selection: { bg: '#fef9c3', text: '#a16207' }, // yellow-100 / yellow-700
    editing: { bg: '#f3e8ff', text: '#7e22ce' }, // purple-100 / purple-700
    approval: { bg: '#ffedd5', text: '#c2410c' }, // orange-100 / orange-700
    delivered: { bg: '#dcfce7', text: '#15803d' }, // green-100 / green-700
    expired: { bg: '#fee2e2', text: '#b91c1c' }, // red-100 / red-700
  }

  // ==========================================================
  // Asset status — sinkron dengan AssetListPage.vue
  // ==========================================================
  const ASSET_STYLES: Record<string, StatusStyle> = {
    available: { bg: '#dcfce7', text: '#15803d' }, // green-100 / green-700
    in_use: { bg: '#dbeafe', text: '#1d4ed8' }, // blue-100 / blue-700
    maintenance: { bg: '#fef9c3', text: '#a16207' }, // yellow-100 / yellow-700
    retired: { bg: '#fee2e2', text: '#b91c1c' }, // red-100 / red-700
  }

  function bookingStatusStyle(status: StatusInput): StatusStyle {
    return BOOKING_STYLES[getValue(status)] ?? FALLBACK_STYLE
  }

  function paymentStatusStyle(status: StatusInput): StatusStyle {
    return PAYMENT_STYLES[getValue(status)] ?? FALLBACK_STYLE
  }

  function photoStatusStyle(status: StatusInput): StatusStyle {
    return PHOTO_STYLES[getValue(status)] ?? FALLBACK_STYLE
  }

  function assetStatusStyle(status: StatusInput): StatusStyle {
    return ASSET_STYLES[getValue(status)] ?? FALLBACK_STYLE
  }

  return {
    bookingStatusStyle,
    paymentStatusStyle,
    photoStatusStyle,
    assetStatusStyle,
  }
}
