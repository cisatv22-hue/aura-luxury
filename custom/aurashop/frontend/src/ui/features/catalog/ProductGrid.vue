<script setup lang="ts">
import type { ProductSummary } from '@/domain/catalog'
import ProductCard from './ProductCard.vue'

defineProps<{ products: ProductSummary[] }>()
</script>

<template>
  <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
    <ProductCard v-for="(product, i) in products" :key="product.ref" :product="product" :eager="i < 4" />
  </div>
</template>
