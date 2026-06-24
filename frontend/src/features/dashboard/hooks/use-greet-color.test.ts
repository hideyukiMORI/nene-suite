import { act, renderHook } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { useGreetColor } from './use-greet-color'

describe('useGreetColor', () => {
  it('defaults to empty (brand accent)', () => {
    const { result } = renderHook(() => useGreetColor())
    expect(result.current.color).toBe('')
  })

  it('persists the chosen color', () => {
    const { result } = renderHook(() => useGreetColor())
    act(() => {
      result.current.setColor('#2563eb')
    })
    expect(result.current.color).toBe('#2563eb')
    expect(localStorage.getItem('nene-suite-greet-color')).toBe('#2563eb')
  })
})
