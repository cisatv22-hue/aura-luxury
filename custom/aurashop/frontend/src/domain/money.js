/**
 * @typedef {object} Money Amount in minor units (cents), same contract as the API.
 * @property {number} amount
 * @property {string} currency
 */

/**
 * @param {Money} money
 * @param {string} [locale]
 * @returns {string}
 */
export function formatMoney(money, locale = 'es-MX') {
  const hasCents = money.amount % 100 !== 0
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: money.currency,
    currencyDisplay: 'narrowSymbol',
    minimumFractionDigits: hasCents ? 2 : 0,
    maximumFractionDigits: 2,
  }).format(money.amount / 100)
}
