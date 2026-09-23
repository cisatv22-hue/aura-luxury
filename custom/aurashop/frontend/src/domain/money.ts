/** Amount in minor units (cents), same contract as the API. */
export interface Money {
  amount: number
  currency: string
}

export function formatMoney(money: Money, locale = 'es-MX'): string {
  const hasCents = money.amount % 100 !== 0
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: money.currency,
    currencyDisplay: 'narrowSymbol',
    minimumFractionDigits: hasCents ? 2 : 0,
    maximumFractionDigits: 2,
  }).format(money.amount / 100)
}

export function sameAmount(a: Money, b: Money): boolean {
  return a.currency === b.currency && a.amount === b.amount
}
