import { computed } from 'vue'

export default {
  name: 'PaginationNav',
  props: {
    page: { type: Number, required: true },
    totalPages: { type: Number, required: true },
  },
  emits: ['change'],
  setup(props) {
    // Compact window: 1 … 4 5 6 … 12
    const pages = computed(() => {
      const result = []
      for (let p = 1; p <= props.totalPages; p++) {
        if (p === 1 || p === props.totalPages || Math.abs(p - props.page) <= 1) result.push(p)
        else if (result[result.length - 1] !== '…') result.push('…')
      }
      return result
    })
    return { pages }
  },
  template: `
    <nav v-if="totalPages > 1" class="mt-12 flex items-center justify-center gap-2" aria-label="Paginación">
      <button type="button" class="chip-off" :disabled="page <= 1" aria-label="Página anterior" @click="$emit('change', page - 1)">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </button>
      <template v-for="(p, i) in pages" :key="i">
        <span v-if="p === '…'" class="px-2 text-zinc-600">…</span>
        <button
          v-else
          type="button"
          :class="p === page ? 'chip-on' : 'chip-off'"
          :aria-current="p === page ? 'page' : undefined"
          @click="$emit('change', p)"
        >{{ p }}</button>
      </template>
      <button type="button" class="chip-off" :disabled="page >= totalPages" aria-label="Página siguiente" @click="$emit('change', page + 1)">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </button>
    </nav>
  `,
}
