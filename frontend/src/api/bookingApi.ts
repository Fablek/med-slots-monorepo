import { api } from './client'
import type { Doctor, Slot } from '../types/api'

export async function fetchDoctors(): Promise<Doctor[]> {
  const { data } = await api.get<Doctor[]>('/doctors')
  return data
}

export async function fetchAvailableSlots(doctorId: string): Promise<Slot[]> {
  const { data } = await api.get<Slot[]>('/slots', {
    params: {
      'doctor.id': doctorId,
      isBooked: false,
    },
  })
  return data
}
