import { computed } from 'vue'
import { productLink } from '../../../router/index.js'
import AvailabilityBadge from '../../design-system/AvailabilityBadge.js'
import PriceTag from '../../design-system/PriceTag.js'
import ProductImage from '../../design-system/ProductImage.js'

export default {
  name: 'ProductCard',
  components: { AvailabilityBadge, PriceTag, ProductImage },
  props: {
    product: { type: Object, required: true },
    eager: { type: Boolean, default: false },
  },
  setup(props) {
    return {
      soldOut: computed(() => props.product.availability === 'out_of_stock'),
      productLink,
    }
  },
  template: `
    <RouterLink
      :to="productLink(product.ref)"
      class="metallic-border group flex flex-col overflow-hidden rounded-lg bg-neutral-950"
      :aria-label="product.label + ', ver detalle'"
    >
      <div class="relative aspect-[4/5] overflow-hidden bg-zinc-900/60">
        <div class="h-full w-full transition-transform duration-700 ease-out group-hover:scale-105" :class="{ 'opacity-50 grayscale': soldOut }">
          <ProductImage :src="product.image?.card" :alt="product.label" :eager="eager" />
        </div>
        <span
          v-if="product.categories[0]"
          class="absolute left-3 top-3 rounded-sm bg-zinc-200 px-2.5 py-1 text-[9px] font-black uppercase tracking-widest text-black shadow-md"
        >{{ product.categories[0].label }}</span>
        <div class="absolute bottom-3 left-3">
          <AvailabilityBadge v-if="product.availability !== 'in_stock'" :availability="product.availability" />
        </div>
      </div>
      <div class="flex flex-grow flex-col justify-between gap-3 bg-zinc-900/40 p-4 sm:p-5">
        <h3 class="text-xs font-black uppercase leading-snug tracking-tight text-white transition-colors group-hover:text-zinc-300">
          {{ product.label }}
        </h3>
        <div class="flex items-end justify-between gap-2">
          <PriceTag :price="product.price" />
          <span v-if="product.hasVariants" class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">Tallas</span>
        </div>
      </div>
    </RouterLink>
  `,
}
