import { useQuery } from '@tanstack/react-query'
import { fetchAvailableSlots, fetchDoctors } from '../api/bookingApi'

export function useDoctorsQuery() {
  return useQuery({
    queryKey: ['doctors'],
    queryFn: fetchDoctors,
  })
}

export function useAvailableSlotsQuery(
  doctorId: string | undefined,
  enabled: boolean,
) {
  return useQuery({
    queryKey: ['slots', doctorId],
    queryFn: () => fetchAvailableSlots(doctorId!),
    enabled: Boolean(doctorId && enabled),
  })
}
