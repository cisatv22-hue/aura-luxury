<script setup lang="ts">
import { isOptionAvailable, type ProductDetail, type VariantSelection } from '@/domain/catalog'

const props = defineProps<{ product: ProductDetail; selection: VariantSelection }>()
const emit = defineEmits<{ select: [code: string, value: string] }>()

function available(code: string, value: string): boolean {
  return isOptionAvailable(props.product, props.selection, code, value)
}
</script>

<template>
  <div class="space-y-5">
    <fieldset v-for="attribute in product.attributes" :key="attribute.code">
      <legend class="mb-2 flex w-full items-baseline justify-between text-[11px] font-extrabold uppercase tracking-wider text-zinc-400">
        <span>{{ attribute.label }}</span>
        <span v-if="selection[attribute.code]" class="text-white">{{ selection[attribute.code] }}</span>
      </legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="value in attribute.values"
          :key="value"
          type="button"
          class="relative min-w-[3rem] rounded border px-4 py-2.5 text-xs font-black uppercase transition-all"
          :class="[
            selection[attribute.code] === value
              ? 'border-white bg-white text-black'
              : 'border-zinc-700 text-zinc-200 hover:border-zinc-400',
            available(attribute.code, value) ? '' : 'text-zinc-600 line-through decoration-zinc-500',
          ]"
          :aria-pressed="selection[attribute.code] === value"
          :aria-label="`${attribute.label} ${value}${available(attribute.code, value) ? '' : ', agotado'}`"
          @click="emit('select', attribute.code, value)"
        >
          {{ value }}
        </button>
      </div>
    </fieldset>
  </div>
</template>
