import { isOptionAvailable } from '../../../domain/catalog.js'

export default {
  name: 'VariantSelector',
  props: {
    product: { type: Object, required: true },
    selection: { type: Object, required: true },
  },
  emits: ['select'],
  setup(props) {
    /** @param {string} code @param {string} value */
    function available(code, value) {
      return isOptionAvailable(props.product, props.selection, code, value)
    }
    return { available }
  },
  template: `
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
              selection[attribute.code] === value ? 'border-white bg-white text-black' : 'border-zinc-700 text-zinc-200 hover:border-zinc-400',
              available(attribute.code, value) ? '' : 'text-zinc-600 line-through decoration-zinc-500',
            ]"
            :aria-pressed="selection[attribute.code] === value"
            :aria-label="attribute.label + ' ' + value + (available(attribute.code, value) ? '' : ', agotado')"
            @click="$emit('select', attribute.code, value)"
          >{{ value }}</button>
        </div>
      </fieldset>
    </div>
  `,
}
