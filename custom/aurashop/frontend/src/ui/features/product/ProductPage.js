import { computed, onBeforeUnmount, reactive, ref, shallowRef, watch } from 'vue'
import { isAbort, NotFoundError, userMessage } from '../../../application/errors.js'
import { useContainer } from '../../../di/container.js'
import { findVariant, hasPriceRange, isPurchasable } from '../../../domain/catalog.js'
import { productInquiryMessage, whatsappUrl } from '../../../domain/store.js'
import { catalogLink } from '../../../router/index.js'
import { useShopStore } from '../../../stores/shop.js'
import { usePageTitle } from '../../composables/usePageTitle.js'
import AvailabilityBadge from '../../design-system/AvailabilityBadge.js'
import PriceTag from '../../design-system/PriceTag.js'
import StateMessage from '../../design-system/StateMessage.js'
import ImageGallery from './ImageGallery.js'
import VariantSelector from './VariantSelector.js'

export default {
  name: 'ProductPage',
  components: { AvailabilityBadge, PriceTag, StateMessage, ImageGallery, VariantSelector },
  props: { productRef: { type: String, required: true } },
  setup(props) {
    const { catalog } = useContainer()
    const shop = useShopStore()

    /** @type {import('vue').ShallowRef<import('../../../domain/catalog.js').ProductDetail | null>} */
    const product = shallowRef(null)
    const loading = ref(true)
    const error = ref(/** @type {string | null} */ (null))
    const notFound = ref(false)
    /** @type {import('../../../domain/catalog.js').VariantSelection} */
    const selection = reactive({})
    /** @type {AbortController | null} */
    let controller = null

    /** @param {string} productRef */
    async function load(productRef) {
      controller?.abort()
      const current = new AbortController()
      controller = current
      loading.value = true
      error.value = null
      notFound.value = false
      for (const key of Object.keys(selection)) delete selection[key]
      try {
        product.value = await catalog.viewProduct(productRef, current.signal)
      } catch (e) {
        if (isAbort(e)) return
        product.value = null
        if (e instanceof NotFoundError) notFound.value = true
        else error.value = userMessage(e)
      }
      if (controller === current) loading.value = false
    }

    watch(() => props.productRef, (productRef) => void load(productRef), { immediate: true })
    onBeforeUnmount(() => controller?.abort())

    usePageTitle(() => (notFound.value ? 'Producto no encontrado' : product.value?.label))

    /** @param {string} code @param {string} value */
    function select(code, value) {
      selection[code] = selection[code] === value ? '' : value
    }

    const variant = computed(() => (product.value ? findVariant(product.value, selection) : undefined))
    const needsSelection = computed(() => !!product.value && product.value.variants.length > 0 && !variant.value)
    const availability = computed(() => variant.value?.availability ?? product.value?.availability ?? 'out_of_stock')
    const canOrder = computed(() => !needsSelection.value && isPurchasable(availability.value))
    const showFrom = computed(() => !variant.value && !!product.value && hasPriceRange(product.value))
    const price = computed(() => variant.value?.price ?? product.value?.priceFrom)
    const category = computed(() => product.value?.categories[0])
    const whatsapp = computed(() =>
      product.value ? whatsappUrl(shop.config?.whatsapp ?? null, productInquiryMessage(product.value, variant.value)) : null,
    )
    const disabledLabel = computed(() => {
      if (needsSelection.value) return `Elige ${product.value?.attributes[0]?.label.toLowerCase() ?? 'una opción'}`
      return isPurchasable(availability.value) ? 'Compra en línea muy pronto' : 'Agotado'
    })

    return {
      product, loading, error, notFound, selection, load, select,
      variant, availability, canOrder, showFrom, price, category, whatsapp, disabledLabel, catalogLink,
    }
  },
  template: `
    <section class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
      <div v-if="loading && !product" class="grid gap-10 md:grid-cols-2" aria-busy="true">
        <div class="aspect-[4/5] animate-pulse rounded-lg bg-zinc-900"></div>
        <div class="space-y-4 pt-4">
          <div class="h-4 w-24 animate-pulse rounded bg-zinc-800"></div>
          <div class="h-8 w-3/4 animate-pulse rounded bg-zinc-800"></div>
          <div class="h-6 w-32 animate-pulse rounded bg-zinc-800"></div>
        </div>
      </div>

      <StateMessage v-else-if="notFound" icon="fa-magnifying-glass" title="Este producto ya no está disponible" text="Puede que se haya agotado de forma definitiva o que el enlace sea incorrecto.">
        <RouterLink :to="catalogLink()" class="btn-outline">Ver el catálogo</RouterLink>
      </StateMessage>

      <StateMessage v-else-if="error" icon="fa-plug-circle-exclamation" title="No pudimos cargar el producto" :text="error">
        <button type="button" class="btn-outline" @click="load(productRef)">Intentar de nuevo</button>
      </StateMessage>

      <template v-else-if="product">
        <nav class="mb-8 flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-zinc-500" aria-label="Ruta">
          <RouterLink :to="catalogLink()" class="hover:text-white">Catálogo</RouterLink>
          <template v-if="category">
            <span aria-hidden="true">/</span>
            <RouterLink :to="catalogLink({ categoria: category.id })" class="hover:text-white">{{ category.label }}</RouterLink>
          </template>
          <span aria-hidden="true">/</span>
          <span class="text-zinc-300" aria-current="page">{{ product.label }}</span>
        </nav>

        <div class="grid gap-10 md:grid-cols-2 lg:gap-16">
          <ImageGallery :images="product.images" :alt="product.label" />

          <div class="flex flex-col gap-6 md:pt-2">
            <div class="space-y-3">
              <span v-if="category" class="inline-block rounded-sm bg-zinc-200 px-3 py-1 text-[9px] font-black uppercase tracking-widest text-black">{{ category.label }}</span>
              <h1 class="font-display text-2xl font-black uppercase leading-tight text-white sm:text-3xl">{{ product.label }}</h1>
              <div class="flex flex-wrap items-center gap-4">
                <PriceTag v-if="price" :price="price" :from="showFrom" size="lg" />
                <AvailabilityBadge :availability="availability" />
              </div>
              <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">REF {{ variant?.ref ?? product.ref }} · IVA incluido</p>
            </div>

            <VariantSelector v-if="product.attributes.length" :product="product" :selection="selection" @select="select" />

            <div class="space-y-3 border-t border-zinc-800 pt-6">
              <a v-if="whatsapp && canOrder" :href="whatsapp" target="_blank" rel="noopener" class="btn-whatsapp w-full py-4">
                <i class="fa-brands fa-whatsapp text-lg" aria-hidden="true"></i>Pedir por WhatsApp
              </a>
              <button v-else type="button" class="btn-primary w-full py-4" disabled>{{ disabledLabel }}</button>
              <p class="text-center text-[11px] text-zinc-500">
                <i class="fa-solid fa-lock mr-1" aria-hidden="true"></i>La compra en línea con pago seguro llega muy pronto.
              </p>
            </div>

            <!-- descriptionHtml is sanitized server side (DolibarrHtmlSanitizer). -->
            <div
              v-if="product.descriptionHtml"
              class="border-t border-zinc-800 pt-6 text-sm leading-relaxed text-zinc-400 [&_a]:underline [&_li]:ml-5 [&_li]:list-disc [&_p]:mb-3 [&_strong]:text-zinc-200"
              v-html="product.descriptionHtml"
            ></div>

            <ul class="grid gap-3 border-t border-zinc-800 pt-6 text-xs text-zinc-400 sm:grid-cols-2">
              <li><i class="fa-solid fa-certificate mr-2 text-slate-200" aria-hidden="true"></i>Autenticidad garantizada</li>
              <li><i class="fa-solid fa-truck-fast mr-2 text-slate-200" aria-hidden="true"></i>Envío con rastreo a todo México</li>
            </ul>
          </div>
        </div>
      </template>
    </section>
  `,
}
