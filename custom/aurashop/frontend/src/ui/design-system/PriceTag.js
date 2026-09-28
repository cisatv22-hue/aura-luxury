import { computed } from 'vue'
import { formatMoney } from '../../domain/money.js'

export default {
  name: 'PriceTag',
  props: {
    price: { type: Object, required: true },
    from: { type: Boolean, default: false },
    size: { type: String, default: 'sm' },
  },
  setup(props) {
    return { text: computed(() => formatMoney(props.price)) }
  },
  template: `
    <p class="font-black text-brand-price" :class="size === 'lg' ? 'text-2xl' : 'text-base'">
      <span v-if="from" class="mr-1 text-[0.7em] font-bold uppercase tracking-wider text-zinc-400">Desde</span>
      {{ text }}
      <span class="ml-0.5 text-[0.6em] font-bold text-zinc-500">{{ price.currency }}</span>
    </p>
  `,
}
