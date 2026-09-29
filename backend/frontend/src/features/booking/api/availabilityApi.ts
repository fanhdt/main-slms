import api from '@/lib/axios'

export interface OperationalHours {
  open: string
  close: string
}

export interface MonthAvailability {
  operational_hours: OperationalHours
  days: Record<string, 'available' | 'partial' | 'full'>
}

export interface DaySlot {
  start: string
  end: string
  available: boolean
}

export interface DayActivity {
  start: string
  end: string
  label: string
}

export interface DayAvailability {
  operational_hours: OperationalHours
  occupied_ranges: { start: string; end: string }[]
  activities: DayActivity[] 
  slots: DaySlot[]
}

export const availabilityApi = {
  getMonth(slug: string, month: string) {
    return api.get<{ data: MonthAvailability }>(`/labs/${slug}/availability`, {
      params: { month },
    })
  },

  getDay(slug: string, date: string) {
    return api.get<{ data: DayAvailability }>(`/labs/${slug}/availability/${date}`)
  },
}
