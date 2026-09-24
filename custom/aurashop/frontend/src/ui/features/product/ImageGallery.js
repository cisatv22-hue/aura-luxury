import { computed, ref, watch } from 'vue'
import ProductImage from '../../design-system/ProductImage.js'

export default {
  name: 'ImageGallery',
  components: { ProductImage },
  props: {
    images: { type: Array, required: true },
    alt: { type: String, required: true },
  },
  setup(props) {
    const active = ref(0)
    watch(
      () => props.images,
      () => (active.value = 0),
    )
    return { active, current: computed(() => props.images[active.value]) }
  },
  template: `
    <div class="space-y-3">
      <div class="aspect-[4/5] overflow-hidden rounded-lg border border-zinc-800 bg-zinc-900">
        <ProductImage :src="current?.full" :alt="alt" eager />
      </div>
      <div v-if="images.length > 1" class="grid grid-cols-5 gap-2" role="group" aria-label="Fotos del producto">
        <button
          v-for="(image, i) in images"
          :key="image.full"
          type="button"
          class="aspect-square overflow-hidden rounded border transition-colors"
          :class="i === active ? 'border-white' : 'border-zinc-800 hover:border-zinc-500'"
          :aria-label="'Ver foto ' + (i + 1)"
          :aria-pressed="i === active"
          @click="active = i"
        >
          <ProductImage :src="image.card" :alt="alt + ' ' + (i + 1)" />
        </button>
      </div>
    </div>
  `,
}
