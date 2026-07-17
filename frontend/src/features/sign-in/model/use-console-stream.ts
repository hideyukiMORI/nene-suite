import { useEffect, useRef, type RefObject } from 'react'

/**
 * Decorative "live console" typewriter for the login hero. Types neutral system
 * logs one char at a time, holds, clears, and loops. Writes textContent directly
 * to a ref (no per-frame React re-render). With prefers-reduced-motion it shows
 * the first line statically. Log copy is latin/fixed — no compliance claims (ADR 0003).
 */
const LOG = [
  'auth.session · endpoint ready',
  'orchestrator · linking 6 apps',
  'nene.vault · channel established',
  'manifest · synced in 24ms',
  'db · connection pool 8/8 ready',
  'portfolio · awaiting operator',
] as const

const START_DELAY = 500
const CHAR_MIN = 34
const CHAR_JITTER = 26 // 34–60ms/char
const HOLD = 1600
const GAP = 240

export function useConsoleStream(): RefObject<HTMLSpanElement | null> {
  const ref = useRef<HTMLSpanElement>(null)

  useEffect(() => {
    const el = ref.current
    if (el === null) return

    const reduce =
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (reduce) {
      el.textContent = LOG[0]
      return
    }

    let timer: ReturnType<typeof setTimeout> | undefined
    let lineIndex = 0
    let charIndex = 0
    let alive = true

    const step = (): void => {
      const target = ref.current
      if (!alive || target === null) return

      const line = LOG[lineIndex] ?? ''
      charIndex += 1
      target.textContent = line.slice(0, charIndex)

      if (charIndex < line.length) {
        timer = setTimeout(step, CHAR_MIN + Math.random() * CHAR_JITTER)
      } else {
        timer = setTimeout(() => {
          lineIndex = (lineIndex + 1) % LOG.length
          charIndex = 0
          if (ref.current !== null) ref.current.textContent = ''
          timer = setTimeout(step, GAP)
        }, HOLD)
      }
    }

    timer = setTimeout(step, START_DELAY)

    return () => {
      alive = false
      if (timer !== undefined) clearTimeout(timer)
    }
  }, [])

  return ref
}
