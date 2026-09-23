import { onBeforeUnmount, ref, shallowRef, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { isAbort, userMessage } from '@/application/errors'
import { useContainer } from '@/di/container'
import type { ProductPage, ProductQuery } from '@/domain/catalog'

/** Loads a product page whenever the query changes, cancelling the previous request. */
export function useProductList(query: MaybeRefOrGetter<ProductQuery>) {
  const { catalog } = useContainer()
  const page = shallowRef<ProductPage | null>(null)
  const loading = ref(true)
  const error = ref<string | null>(null)
  let controller: AbortController | null = null

  async function load(): Promise<void> {
    controller?.abort()
    const current = new AbortController()
    controller = current
    loading.value = true
    error.value = null
    try {
      page.value = await catalog.browse(toValue(query), current.signal)
    } catch (e) {
      if (isAbort(e)) return
      error.value = userMessage(e)
    }
    if (controller === current) loading.value = false
  }

  const stop = watch(() => toValue(query), () => void load(), { immediate: true, deep: true })
  onBeforeUnmount(() => {
    stop()
    controller?.abort()
  })

  return { page, loading, error, reload: load }
}
