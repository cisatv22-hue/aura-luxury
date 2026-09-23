import { createRouter, createWebHistory, type RouteLocationRaw } from 'vue-router'
import HomePage from '@/ui/features/home/HomePage.vue'

export function createShopRouter(base: string) {
  return createRouter({
    history: createWebHistory(base),
    routes: [
      { path: '/', name: 'home', component: HomePage },
      { path: '/catalogo', name: 'catalog', component: () => import('@/ui/features/catalog/CatalogPage.vue') },
      {
        path: '/producto/:ref',
        name: 'product',
        component: () => import('@/ui/features/product/ProductPage.vue'),
        // "ref" is reserved in Vue, so the param is exposed as productRef.
        props: (route) => ({ productRef: String(route.params.ref) }),
      },
      { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/ui/pages/NotFoundPage.vue') },
    ],
    scrollBehavior(to, from, saved) {
      if (saved) return saved
      if (to.name === from.name && to.name === 'catalog') return { top: 0, behavior: 'smooth' }
      return { top: 0 }
    },
  })
}

export function catalogLink(query: { categoria?: number | null; q?: string } = {}): RouteLocationRaw {
  const clean: Record<string, string> = {}
  if (query.categoria) clean.categoria = String(query.categoria)
  if (query.q) clean.q = query.q
  return { name: 'catalog', query: clean }
}

export function productLink(ref: string): RouteLocationRaw {
  return { name: 'product', params: { ref } }
}
