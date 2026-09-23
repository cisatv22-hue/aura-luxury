<script setup lang="ts">
import { computed } from 'vue'
import type { ProductSummary } from '@/domain/catalog'
import { productLink } from '@/router'
import AvailabilityBadge from '@/ui/design-system/AvailabilityBadge.vue'
import PriceTag from '@/ui/design-system/PriceTag.vue'
import ProductImage from '@/ui/design-system/ProductImage.vue'

const props = defineProps<{ product: ProductSummary; eager?: boolean }>()
const soldOut = computed(() => props.product.availability === 'out_of_stock')
</script>

<template>
  <RouterLink
    :to="productLink(product.ref)"
    class="metallic-border group flex flex-col overflow-hidden rounded-lg bg-neutral-950"
    :aria-label="`${product.label}, ver detalle`"
  >
    <div class="relative aspect-[4/5] overflow-hidden bg-zinc-900/60">
      <div class="h-full w-full transition-transform duration-700 ease-out group-hover:scale-105" :class="{ 'opacity-50 grayscale': soldOut }">
        <ProductImage :src="product.image?.card" :alt="product.label" :eager="eager" />
      </div>
      <span
        v-if="product.categories[0]"
        class="absolute left-3 top-3 rounded-sm bg-zinc-200 px-2.5 py-1 text-[9px] font-black uppercase tracking-widest text-black shadow-md"
      >
        {{ product.categories[0].label }}
      </span>
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
</template>
