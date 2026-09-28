import { onBeforeUnmount, ref, shallowRef, toValue, watch } from 'vue'
import { isAbort, userMessage } from '../../application/errors.js'
import { useContainer } from '../../di/container.js'

/**
 * Loads a product page whenever the query changes, cancelling the previous request.
 * @param {import('vue').MaybeRefOrGetter<import('../../domain/catalog.js').ProductQuery>} query
 */
export function useProductList(query) {
  const { catalog } = useContainer()
  /** @type {import('vue').ShallowRef<import('../../domain/catalog.js').ProductPage | null>} */
  const page = shallowRef(null)
  const loading = ref(true)
  const error = ref(/** @type {string | null} */ (null))
  /** @type {AbortController | null} */
  let controller = null

  async function load() {
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
