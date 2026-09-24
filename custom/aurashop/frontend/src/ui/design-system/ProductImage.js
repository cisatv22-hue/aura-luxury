import { ref, watch } from 'vue'

export default {
  name: 'ProductImage',
  props: {
    src: { type: String, default: null },
    alt: { type: String, required: true },
    eager: { type: Boolean, default: false },
  },
  setup(props) {
    const failed = ref(false)
    watch(
      () => props.src,
      () => (failed.value = false),
    )
    return { failed }
  },
  template: `
    <img
      v-if="src && !failed"
      :src="src"
      :alt="alt"
      :loading="eager ? 'eager' : 'lazy'"
      decoding="async"
      class="h-full w-full object-cover"
      @error="failed = true"
    />
    <div v-else class="flex h-full w-full flex-col items-center justify-center gap-2 text-zinc-600" role="img" :aria-label="alt">
      <i class="fa-regular fa-image text-3xl" aria-hidden="true"></i>
      <span class="text-[10px] font-bold uppercase tracking-wider">Sin imagen</span>
    </div>
  `,
}
