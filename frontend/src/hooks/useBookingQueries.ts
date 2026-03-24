import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { fetchAvailableSlots, fetchDoctors, bookSlot } from '../api/bookingApi'

export function useDoctorsQuery() {
  return useQuery({
    queryKey: ['doctors'],
    queryFn: fetchDoctors,
  })
}

export function useAvailableSlotsQuery(doctorId: string | undefined, enabled: boolean) {
  return useQuery({
    queryKey: ['slots', doctorId],
    queryFn: () => fetchAvailableSlots(doctorId!),
    enabled: Boolean(doctorId && enabled),
  })
}

export function useBookingMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ slotId, patientEmail }: { slotId: string; patientEmail: string }) => 
      bookSlot(slotId, patientEmail),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['slots'] });
      queryClient.invalidateQueries({ queryKey: ['doctors'] });
    }
  });
}