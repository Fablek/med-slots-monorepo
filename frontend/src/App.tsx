import { useState } from 'react'
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion'
import {
  AlertCircle,
  ArrowLeft,
  CalendarOff,
  HeartPulse,
  Loader2,
  Stethoscope,
  User,
} from 'lucide-react'
import { getErrorMessage } from './api/errorMessage'
import { useAvailableSlotsQuery, useDoctorsQuery } from './hooks/useBookingQueries'
import { BookingModal } from './components/BookingModal'
import type { Doctor } from './types/api'

type Step = 1 | 2

function formatSlotRange(startIso: string, endIso: string): { date: string; time: string } {
  const start = new Date(startIso)
  const end = new Date(endIso)
  const dateFmt = new Intl.DateTimeFormat('pl-PL', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  })
  const timeFmt = new Intl.DateTimeFormat('pl-PL', {
    hour: '2-digit',
    minute: '2-digit',
  })
  return {
    date: dateFmt.format(start),
    time: `${timeFmt.format(start)} – ${timeFmt.format(end)}`,
  }
}

function doctorFullName(d: Doctor): string {
  return `${d.firstName} ${d.lastName}`
}

const easing = [0.22, 1, 0.36, 1] as const

export default function App() {
  const prefersReducedMotion = useReducedMotion()
  const [step, setStep] = useState<Step>(1)
  const [selectedDoctor, setSelectedDoctor] = useState<Doctor | null>(null)
  const [selectedSlotId, setSelectedSlotId] = useState<string | null>(null)
  const [selectedSlotTime, setSelectedSlotTime] = useState<string | null>(null)

  const stepTransition = prefersReducedMotion
    ? { duration: 0 }
    : { duration: 0.4, ease: easing }

  const doctorsQuery = useDoctorsQuery()
  const slotsQuery = useAvailableSlotsQuery(
    selectedDoctor?.id,
    step === 2 && Boolean(selectedDoctor),
  )

  const goToBooking = (doctor: Doctor) => {
    setSelectedDoctor(doctor)
    setStep(2)
  }

  const goBackToList = () => {
    setStep(1)
    setSelectedDoctor(null)
  }

  const handleSlotClick = (slotId: string, startTime: string, endTime: string) => {
    setSelectedSlotId(slotId)
    setSelectedSlotTime(formatSlotRange(startTime, endTime).time)
  }

  const closeModal = () => {
    setSelectedSlotId(null)
    setSelectedSlotTime(null)
  }

  return (
    <div className="relative min-h-screen w-full overflow-x-hidden bg-slate-50 bg-gradient-to-br from-blue-50 via-slate-50 to-indigo-50 text-slate-800">
      <div
        className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_85%_55%_at_50%_-15%,rgba(99,102,241,0.07),transparent)]"
        aria-hidden
      />

      <div className="relative mx-auto min-h-screen max-w-6xl px-4 py-8">
        <header className="mb-12 flex flex-col gap-6 border-b border-sky-100/90 pb-10 sm:flex-row sm:items-end sm:justify-between">
          <div className="space-y-3">
            <div className="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700 shadow-sm backdrop-blur-sm">
              <HeartPulse className="h-3.5 w-3.5 text-sky-500" aria-hidden />
              Telemedi
            </div>
            <h1 className="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl md:text-[2.35rem] md:leading-tight">
              Smart Booking
            </h1>
            <p className="max-w-lg text-base leading-relaxed text-slate-600 md:text-[17px]">
              Zarezerwuj teleporadę u wybranego specjalisty — szybko, przejrzyście
              i bez zbędnych kroków.
            </p>
          </div>
          <div className="flex h-16 w-16 items-center justify-center rounded-2xl border border-sky-100/80 bg-white shadow-md shadow-sky-900/5">
            <Stethoscope className="h-8 w-8 text-sky-600" strokeWidth={1.5} aria-hidden />
          </div>
        </header>

        <AnimatePresence mode="wait">
          {step === 1 && (
            <motion.section
              key="step-doctors"
              initial={{ opacity: 0, x: prefersReducedMotion ? 0 : -28 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: prefersReducedMotion ? 0 : -28 }}
              transition={stepTransition}
              aria-labelledby="doctors-heading"
              className="space-y-8"
            >
              <div className="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between">
                <h2
                  id="doctors-heading"
                  className="text-xl font-semibold text-slate-900 sm:text-2xl"
                >
                  Wybierz lekarza
                </h2>
                <p className="text-sm text-slate-500">Krok 1 z 2</p>
              </div>

              {doctorsQuery.isPending && (
                <div className="flex items-center gap-3 rounded-2xl border border-sky-100 bg-white/90 px-5 py-8 text-slate-600 shadow-sm">
                  <Loader2 className="h-6 w-6 shrink-0 animate-spin text-sky-600" />
                  <span>Ładowanie lekarzy…</span>
                </div>
              )}

              {doctorsQuery.isError && (
                <div
                  className="flex items-start gap-4 rounded-2xl border border-red-200/80 bg-red-50/90 p-5 text-red-950 shadow-sm"
                  role="alert"
                >
                  <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                  <div>
                    <p className="font-semibold">Nie możemy wczytać listy lekarzy.</p>
                    <p className="mt-1 text-sm text-red-900/80">
                      {getErrorMessage(doctorsQuery.error)}
                    </p>
                  </div>
                </div>
              )}

              {doctorsQuery.isSuccess && doctorsQuery.data.length === 0 && (
                <p className="rounded-2xl border border-slate-200 bg-white/80 px-5 py-10 text-center text-slate-600 shadow-sm">
                  Brak lekarzy w systemie.
                </p>
              )}

              {doctorsQuery.isSuccess && doctorsQuery.data.length > 0 && (
                <ul className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                  {doctorsQuery.data.map((doctor, index) => (
                    <motion.li
                      key={doctor.id}
                      initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 16 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{
                        delay: prefersReducedMotion ? 0 : index * 0.06,
                        duration: prefersReducedMotion ? 0 : 0.4,
                        ease: easing,
                      }}
                    >
                      <motion.article
                        className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-white/60 bg-white/95 p-6 shadow-md shadow-indigo-950/[0.06] ring-1 ring-slate-200/40 transition-shadow duration-300 hover:shadow-lg hover:shadow-indigo-950/10"
                        whileHover={prefersReducedMotion ? undefined : { y: -6 }}
                        transition={
                          prefersReducedMotion
                            ? { duration: 0 }
                            : { type: 'spring', stiffness: 420, damping: 28 }
                        }
                      >
                        <div className="min-w-0 flex-1 space-y-4">
                          <div className="flex items-start gap-3">
                            <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100/80">
                              <Stethoscope className="h-4 w-4" strokeWidth={2} aria-hidden />
                            </span>
                            <div className="min-w-0">
                              <p className="text-[10px] font-semibold uppercase leading-tight tracking-wider text-indigo-600/90 sm:text-xs">
                                Specjalizacja
                              </p>
                              <p className="mt-0.5 text-sm font-semibold leading-snug text-slate-800 sm:text-base">
                                {doctor.specialty}
                              </p>
                            </div>
                          </div>
                          <div className="flex items-start gap-3 border-t border-slate-100 pt-4">
                            <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200/80">
                              <User className="h-4 w-4" strokeWidth={2} aria-hidden />
                            </span>
                            <div className="min-w-0">
                              <p className="text-[10px] font-semibold uppercase leading-tight tracking-wider text-slate-500 sm:text-xs">
                                Lekarz
                              </p>
                              <h3 className="mt-0.5 text-lg font-semibold tracking-tight text-slate-900">
                                lek. {doctorFullName(doctor)}
                              </h3>
                            </div>
                          </div>
                        </div>
                        <div className="mt-6 flex flex-1 flex-col justify-end border-t border-slate-100 pt-5">
                          <motion.button
                            type="button"
                            className="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-slate-900/15 transition-colors hover:bg-sky-950 focus-visible:outline focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2"
                            whileTap={{ scale: 0.98 }}
                            onClick={() => goToBooking(doctor)}
                          >
                            Umów wizytę
                          </motion.button>
                        </div>
                      </motion.article>
                    </motion.li>
                  ))}
                </ul>
              )}
            </motion.section>
          )}

          {step === 2 && selectedDoctor && (
            <motion.section
              key="step-slots"
              initial={{ opacity: 0, x: prefersReducedMotion ? 0 : 28 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: prefersReducedMotion ? 0 : 28 }}
              transition={stepTransition}
              aria-labelledby="slots-heading"
              className="space-y-8"
            >
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p className="text-sm font-medium text-slate-500">Krok 2 z 2</p>
                  <h2
                    id="slots-heading"
                    className="mt-1 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl"
                  >
                    Wybierz termin u:{' '}
                    <span className="text-sky-800">
                      {doctorFullName(selectedDoctor)}
                    </span>
                  </h2>
                </div>
                <motion.button
                  type="button"
                  onClick={goBackToList}
                  className="inline-flex items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50/50 hover:text-sky-900 focus-visible:outline focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2 sm:self-auto"
                  whileHover={prefersReducedMotion ? undefined : { x: -2 }}
                  whileTap={{ scale: 0.98 }}
                >
                  <ArrowLeft className="h-4 w-4" aria-hidden />
                  Powrót do listy
                </motion.button>
              </div>

              <div className="rounded-2xl border border-sky-100/90 bg-white/90 p-6 shadow-md shadow-sky-900/[0.06] backdrop-blur-sm sm:p-8">
                {slotsQuery.isPending && (
                  <div className="flex items-center gap-3 text-slate-600">
                    <Loader2 className="h-6 w-6 shrink-0 animate-spin text-sky-600" />
                    Ładowanie dostępnych terminów…
                  </div>
                )}

                {slotsQuery.isError && (
                  <div
                    className="flex items-start gap-4 rounded-xl border border-red-200/80 bg-red-50/90 p-4 text-red-950"
                    role="alert"
                  >
                    <AlertCircle className="mt-0.5 h-5 w-5 shrink-0" />
                    <div>
                      <p className="font-semibold">Błąd przy pobieraniu terminów</p>
                      <p className="mt-1 text-sm opacity-90">
                        {getErrorMessage(slotsQuery.error)}
                      </p>
                    </div>
                  </div>
                )}

                {slotsQuery.isSuccess && slotsQuery.data.length === 0 && (
                  <motion.div
                    initial={{ opacity: 0, y: prefersReducedMotion ? 0 : 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{
                      duration: prefersReducedMotion ? 0 : 0.35,
                    }}
                    className="flex flex-col items-center justify-center py-12 text-center"
                  >
                    <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
                      <CalendarOff className="h-8 w-8" strokeWidth={1.5} aria-hidden />
                    </div>
                    <p className="max-w-md text-lg font-medium text-slate-800">
                      Brak dostępnych terminów w najbliższym czasie.
                    </p>
                    <p className="mt-2 max-w-sm text-sm leading-relaxed text-slate-500">
                      Spróbuj wybrać innego specjalistę lub wróć później — kalendarz
                      jest regularnie uzupełniany.
                    </p>
                  </motion.div>
                )}

                {slotsQuery.isSuccess && slotsQuery.data.length > 0 && (
                  <ul className="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-6 md:gap-3">
                    {slotsQuery.data.map((slot, i) => {
                      // Wyciągamy poprawne ID (standard API Platform to @id, fallback to id)
                      const currentId = slot['@id'] || slot.id;
                      const { date, time } = formatSlotRange(slot.startTime, slot.endTime)
                      
                      return (
                        <motion.li
                          key={currentId}
                          initial={{
                            opacity: 0,
                            scale: prefersReducedMotion ? 1 : 0.94,
                          }}
                          animate={{ opacity: 1, scale: 1 }}
                          transition={{
                            delay: prefersReducedMotion ? 0 : i * 0.04,
                            duration: prefersReducedMotion ? 0 : 0.25,
                            ease: easing,
                          }}
                        >
                          <motion.button
                            type="button"
                            onClick={() => handleSlotClick(currentId, slot.startTime, slot.endTime)}
                            className="group relative w-full overflow-hidden rounded-full border border-slate-200/90 bg-white px-3 py-3 text-left shadow-sm transition-colors hover:border-sky-300 hover:bg-gradient-to-br hover:from-sky-50 hover:to-white focus-visible:outline focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2 sm:px-4"
                            whileHover={
                              prefersReducedMotion
                                ? undefined
                                : {
                                    scale: 1.03,
                                    boxShadow:
                                      '0 12px 28px -8px rgba(14, 116, 144, 0.2)',
                                  }
                            }
                            whileTap={{ scale: 0.98 }}
                          >
                            <span className="block text-[10px] font-semibold uppercase tracking-wide text-slate-400 transition-colors duration-300 group-hover:text-sky-700 sm:text-xs">
                              {date}
                            </span>
                            <span
                              className={[
                                'mt-1 block text-sm font-semibold tabular-nums text-slate-800 transition-colors duration-300 group-hover:text-sky-700 sm:text-[15px]',
                                prefersReducedMotion ? '' : 'group-hover:animate-pulse',
                              ].join(' ')}
                            >
                              {time}
                            </span>
                          </motion.button>
                        </motion.li>
                      )
                    })}
                  </ul>
                )}
              </div>
            </motion.section>
          )}

          <BookingModal
            isOpen={Boolean(selectedSlotId && selectedSlotTime)}
            slotId={selectedSlotId!}
            slotTime={selectedSlotTime!}
            onClose={closeModal}
          />
        </AnimatePresence>
      </div>
    </div>
  )
}

export { formatSlotRange, doctorFullName }
