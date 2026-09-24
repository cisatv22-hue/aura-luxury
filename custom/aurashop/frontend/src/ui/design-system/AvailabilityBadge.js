import { computed } from 'vue'
import { availabilityLabel } from '../../domain/catalog.js'

const TONES = {
  in_stock: 'text-emerald-300 bg-emerald-400/10 border-emerald-400/20',
  low_stock: 'text-amber-200 bg-amber-400/10 border-amber-400/25',
  out_of_stock: 'text-zinc-400 bg-zinc-500/10 border-zinc-600/40',
}

export default {
  name: 'AvailabilityBadge',
  props: { availability: { type: String, required: true } },
  setup(props) {
    return {
      tone: computed(() => TONES[props.availability]),
      label: computed(() => availabilityLabel(props.availability)),
    }
  },
  template: `
    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider" :class="tone">
      <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
      {{ label }}
    </span>
  `,
}
