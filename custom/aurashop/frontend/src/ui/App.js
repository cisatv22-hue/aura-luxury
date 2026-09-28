import { onMounted } from 'vue'
import { useShopStore } from '../stores/shop.js'
import AppFooter from './layout/AppFooter.js'
import AppHeader from './layout/AppHeader.js'

export default {
  name: 'App',
  components: { AppHeader, AppFooter },
  setup() {
    const shop = useShopStore()
    onMounted(() => void shop.load())
  },
  template: `
    <div class="flex min-h-screen flex-col">
      <AppHeader />
      <main class="flex-grow">
        <RouterView />
      </main>
      <AppFooter />
    </div>
  `,
}
