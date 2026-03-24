export interface Doctor {
  id: string
  firstName: string
  lastName: string
  specialty: string
}

export interface Slot {
  id: string
  startTime: string
  endTime: string
  isBooked: boolean
}
