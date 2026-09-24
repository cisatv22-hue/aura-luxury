import { toValue, watchEffect } from 'vue'
import { useShopStore } from '../../stores/shop.js'

/** @param {import('vue').MaybeRefOrGetter<string | null | undefined>} title */
export function usePageTitle(title) {
  const shop = useShopStore()
  watchEffect(() => {
    const page = toValue(title)
    document.title = page ? `${page} | ${shop.storeName}` : shop.storeName
  })
}
