import { describe, expect, it } from 'vitest'
import { formatMoney } from './money'
import { whatsappUrl } from './store'

describe('formatMoney', () => {
  it('hides cents when they are zero', () => {
    expect(formatMoney({ amount: 145000, currency: 'MXN' })).toBe('$1,450')
  })

  it('shows cents when present', () => {
    expect(formatMoney({ amount: 145050, currency: 'MXN' })).toBe('$1,450.50')
  })
})

describe('whatsappUrl', () => {
  it('returns null without a number', () => {
    expect(whatsappUrl(null, 'hola')).toBeNull()
    expect(whatsappUrl('  ', 'hola')).toBeNull()
  })

  it('strips formatting and encodes the message', () => {
    expect(whatsappUrl('+52 1 55-1234-5678', 'Hola & adiós')).toBe('https://wa.me/5215512345678?text=Hola%20%26%20adi%C3%B3s')
  })
})
