import { formatMoney } from './money'
import type { ProductDetail, Variant } from './catalog'

export interface StoreConfig {
  storeName: string
  currency: string
  whatsapp: string | null
}

export function whatsappUrl(number: string | null, message: string): string | null {
  const digits = (number ?? '').replace(/\D+/g, '')
  if (!digits) return null
  return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`
}

export function productInquiryMessage(product: ProductDetail, variant?: Variant): string {
  const options = variant
    ? product.attributes.map((a) => `${a.label}: ${variant.options[a.code] ?? '-'}`).join(', ')
    : ''
  const price = formatMoney(variant?.price ?? product.price)
  const ref = variant?.ref ?? product.ref
  return [
    `Hola, me interesa este producto:`,
    `${product.label}${options ? ` (${options})` : ''}`,
    `REF ${ref} · ${price}`,
    `¿Está disponible?`,
  ].join('\n')
}
