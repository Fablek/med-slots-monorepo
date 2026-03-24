import { useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { CheckCircle, X } from 'lucide-react'
import { useBookingMutation } from '../hooks/useBookingQueries'

interface BookingModalProps {
  isOpen: boolean
  slotId: string
  slotTime: string
  onClose: () => void
}

export function BookingModal({ isOpen, slotId, slotTime, onClose }: BookingModalProps) {
  const [email, setEmail] = useState('')
  const [error, setError] = useState('')
  const [success, setSuccess] = useState(false)

  const { mutate: bookSlot, isPending } = useBookingMutation()

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setSuccess(false)

    if (!email.trim()) {
      setError('Wpisz adres e-mail')
      return
    }

    if (!email.includes('@')) {
      setError('Wpisz poprawny adres e-mail')
      return
    }

    // Wywołanie mutacji z jednym obiektem jako argumentem
    bookSlot({ slotId, patientEmail: email }, { 
      onSuccess: () => {
        setSuccess(true)
        setTimeout(() => {
          onClose()
          setSuccess(false)
          setEmail('')
        }, 1500)
      },
      onError: (err: any) => { 
        if (err.response?.status === 409) {
          setError('Ten termin jest już zajęty')
        } else {
          setError('Wystąpił błąd podczas rezerwacji. Spróbuj ponownie.')
        }
      }
    })
  } // <--- TU BRAKOWAŁO TEJ KLAMERKI ZAMYKAJĄCEJ handleSubmit

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"
            onClick={onClose}
          />

          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: 10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: 10 }}
            transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
            className="relative w-full max-w-md rounded-3xl border border-white/20 bg-white/95 p-8 shadow-2xl shadow-slate-950/20"
            onClick={(e) => e.stopPropagation()}
          >
            {success ? (
              <div className="flex flex-col items-center justify-center py-8 text-center">
                <motion.div
                  initial={{ scale: 0.8, opacity: 0 }}
                  animate={{ scale: 1, opacity: 1 }}
                  transition={{ delay: 0.1, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
                  className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-sky-100"
                >
                  <CheckCircle className="h-10 w-10 text-sky-600" strokeWidth={2.5} />
                </motion.div>
                <motion.h3
                  initial={{ y: 10, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ delay: 0.2, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
                  className="text-2xl font-semibold text-slate-900"
                >
                  Zarezerwowano!
                </motion.h3>
                <motion.p
                  initial={{ y: 10, opacity: 0 }}
                  animate={{ y: 0, opacity: 1 }}
                  transition={{ delay: 0.3, duration: 0.4, ease: [0.22, 1, 0.36, 1] }}
                  className="mt-2 text-slate-600"
                >
                  Wysłaliśmy potwierdzenie na adres e-mail
                </motion.p>
              </div>
            ) : (
              <>
                <div className="flex items-start justify-between mb-6">
                  <div>
                    <h2 className="text-xl font-semibold text-slate-900">
                      Rezerwacja terminu
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                      Termin: {slotTime}
                    </p>
                  </div>
                  <motion.button
                    type="button"
                    onClick={onClose}
                    className="rounded-full bg-slate-100 p-2 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600 focus-visible:outline focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2"
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                  >
                    <X className="h-4 w-4" />
                  </motion.button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                  <div>
                    <label
                      htmlFor="email"
                      className="mb-2 block text-sm font-medium text-slate-700"
                    >
                      Adres e-mail pacjenta
                    </label>
                    <input
                      id="email"
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      disabled={isPending}
                      className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-sky-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                      placeholder="jan.kowalski@example.com"
                    />
                  </div>

                  {error && (
                    <motion.div
                      initial={{ opacity: 0, y: -10 }}
                      animate={{ opacity: 1, y: 0 }}
                      className="flex items-start gap-3 rounded-xl border border-red-200/80 bg-red-50 p-4 text-red-900"
                    >
                      <span className="text-red-600">⚠️</span>
                      <p className="text-sm">{error}</p>
                    </motion.div>
                  )}

                  <motion.button
                    type="submit"
                    disabled={isPending || !email.trim()}
                    className="relative w-full overflow-hidden rounded-xl bg-sky-600 px-6 py-4 font-semibold text-white shadow-md shadow-sky-600/30 transition-all hover:bg-sky-700 focus-visible:outline focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-sky-600"
                    whileHover={!isPending ? { scale: 1.02, boxShadow: '0 10px 30px -5px rgba(2, 132, 199, 0.4)' } : undefined}
                    whileTap={!isPending ? { scale: 0.98 } : undefined}
                  >
                    <span className="relative z-10 flex items-center justify-center gap-2">
                      {isPending ? 'Rezerwowanie...' : 'Zarezerwuj termin'}
                    </span>
                    {isPending && (
                      <div className="absolute inset-0 -z-10 animate-pulse bg-sky-600/20" />
                    )}
                  </motion.button>
                </form>
              </>
            )}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  )
}