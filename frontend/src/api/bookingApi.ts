import { api } from './client'
import type { Doctor, Slot } from '../types/api'

export async function fetchDoctors(): Promise<Doctor[]> {
  const { data } = await api.get<any>('/doctors')
  return data['member'] || data
}

export async function fetchAvailableSlots(doctorId: string): Promise<Slot[]> {
  const { data } = await api.get<any>('/slots', {
    params: {
      'doctor.id': doctorId,
      isBooked: false,
    },
  })
  return data['member'] || data
}

export async function bookSlot(slotId: string, patientEmail: string): Promise<void> {
  const uuid = slotId.includes('/') ? slotId.split('/').pop() : slotId;
  
  await api.post(`/slots/${uuid}/book`, { patientEmail });
}