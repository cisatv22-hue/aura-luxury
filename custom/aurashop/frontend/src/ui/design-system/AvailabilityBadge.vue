<script setup lang="ts">
import { computed } from 'vue'
import { availabilityLabel, type Availability } from '@/domain/catalog'

const props = defineProps<{ availability: Availability }>()

const tone = computed(
  () =>
    ({
      in_stock: 'text-emerald-300 bg-emerald-400/10 border-emerald-400/20',
      low_stock: 'text-amber-200 bg-amber-400/10 border-amber-400/25',
      out_of_stock: 'text-zinc-400 bg-zinc-500/10 border-zinc-600/40',
    })[props.availability],
)
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider"
    :class="tone"
  >
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ availabilityLabel(availability) }}
  </span>
</template>
