import ProductCard from './ProductCard.js'

export default {
  name: 'ProductGrid',
  components: { ProductCard },
  props: { products: { type: Array, required: true } },
  template: `
    <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
      <ProductCard v-for="(product, i) in products" :key="product.ref" :product="product" :eager="i < 4" />
    </div>
  `,
}
