import axios from 'axios'

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { detail?: string; title?: string }
    if (typeof data?.detail === 'string') {
      return data.detail
    }
    if (typeof data?.title === 'string') {
      return data.title
    }
    if (error.message) {
      return error.message
    }
  }
  if (error instanceof Error) {
    return error.message
  }
  return 'Nieznany błąd'
}
