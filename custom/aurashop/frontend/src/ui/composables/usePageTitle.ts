import { watchEffect, type MaybeRefOrGetter, toValue } from 'vue'
import { useShopStore } from '@/stores/shop'

export function usePageTitle(title: MaybeRefOrGetter<string | null | undefined>): void {
  const shop = useShopStore()
  watchEffect(() => {
    const page = toValue(title)
    document.title = page ? `${page} | ${shop.storeName}` : shop.storeName
  })
}
