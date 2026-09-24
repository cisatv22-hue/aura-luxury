import { formatMoney } from './money.js'

/** @typedef {{ storeName: string, currency: string, whatsapp: string | null }} StoreConfig */

/**
 * @param {string | null} number
 * @param {string} message
 * @returns {string | null}
 */
export function whatsappUrl(number, message) {
  const digits = (number ?? '').replace(/\D+/g, '')
  if (!digits) return null
  return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`
}

/**
 * @param {import('./catalog.js').ProductDetail} product
 * @param {import('./catalog.js').Variant} [variant]
 */
export function productInquiryMessage(product, variant) {
  const options = variant ? product.attributes.map((a) => `${a.label}: ${variant.options[a.code] ?? '-'}`).join(', ') : ''
  const price = formatMoney(variant?.price ?? product.price)
  const ref = variant?.ref ?? product.ref
  return [
    'Hola, me interesa este producto:',
    `${product.label}${options ? ` (${options})` : ''}`,
    `REF ${ref} · ${price}`,
    '¿Está disponible?',
  ].join('\n')
}
