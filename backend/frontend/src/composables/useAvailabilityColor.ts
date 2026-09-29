export function useAvailabilityColor() {
  const CELL_COLOR: Record<string, string> = {
    available: 'bg-green-50 hover:bg-green-100 text-green-700',
    partial: 'bg-yellow-50 hover:bg-yellow-100 text-yellow-700',
    full: 'bg-red-50 text-red-400 cursor-not-allowed',
  }

  function cellColor(status: string | null | undefined) {
    return CELL_COLOR[status ?? 'available'] ?? CELL_COLOR.available
  }

  return { cellColor }
}