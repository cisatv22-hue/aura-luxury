import { computed } from 'vue'
import { whatsappUrl } from '../../domain/store.js'
import { useShopStore } from '../../stores/shop.js'

export default {
  name: 'AppFooter',
  setup() {
    const shop = useShopStore()
    const whatsapp = computed(() => whatsappUrl(shop.config?.whatsapp ?? null, `Hola ${shop.storeName}, tengo una pregunta.`))
    return { shop, whatsapp, year: new Date().getFullYear() }
  },
  template: `
    <footer class="border-t border-zinc-800 bg-neutral-950 text-xs text-zinc-400">
      <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-16 sm:px-6 md:grid-cols-3 lg:px-8">
        <div class="space-y-4">
          <span class="silver-text block font-display text-2xl font-black tracking-widest">A U R A</span>
          <p class="max-w-xs leading-relaxed">Moda urbana de alta gama: oversized de algodón pesado y joyería maciza de Plata Ley .925.</p>
        </div>
        <div class="space-y-3">
          <h2 class="text-xs font-black uppercase tracking-wider text-white">Garantías</h2>
          <ul class="space-y-2">
            <li><i class="fa-solid fa-check mr-2 text-emerald-400" aria-hidden="true"></i>Plata Ley .925 auténtica</li>
            <li><i class="fa-solid fa-check mr-2 text-emerald-400" aria-hidden="true"></i>Sudaderas heavyweight</li>
            <li><i class="fa-solid fa-check mr-2 text-emerald-400" aria-hidden="true"></i>Envíos con rastreo</li>
          </ul>
        </div>
        <div class="space-y-3">
          <h2 class="text-xs font-black uppercase tracking-wider text-white">Atención</h2>
          <p>¿Dudas sobre tallas o existencias? Escríbenos.</p>
          <a v-if="whatsapp" :href="whatsapp" target="_blank" rel="noopener" class="btn-whatsapp w-full sm:w-auto">
            <i class="fa-brands fa-whatsapp text-base" aria-hidden="true"></i>WhatsApp
          </a>
        </div>
      </div>
      <div class="border-t border-zinc-900 py-6 text-center text-[11px] text-zinc-600">
        © {{ year }} {{ shop.storeName }}. Todos los derechos reservados.
      </div>
    </footer>
  `,
}
