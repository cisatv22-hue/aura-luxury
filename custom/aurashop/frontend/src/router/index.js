import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../ui/features/home/HomePage.js'

/** @param {string} base */
export function createShopRouter(base) {
  return createRouter({
    history: createWebHistory(base),
    routes: [
      { path: '/', name: 'home', component: HomePage },
      { path: '/catalogo', name: 'catalog', component: () => import('../ui/features/catalog/CatalogPage.js') },
      {
        path: '/producto/:ref',
        name: 'product',
        component: () => import('../ui/features/product/ProductPage.js'),
        // "ref" is reserved in Vue, so the param is exposed as productRef.
        props: (route) => ({ productRef: String(route.params.ref) }),
      },
      { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../ui/pages/NotFoundPage.js') },
    ],
    scrollBehavior(to, from, saved) {
      if (saved) return saved
      if (to.name === from.name && to.name === 'catalog') return { top: 0, behavior: 'smooth' }
      return { top: 0 }
    },
  })
}

/** @param {{ categoria?: number | null, q?: string }} [query] */
export function catalogLink(query = {}) {
  const clean = {}
  if (query.categoria) clean.categoria = String(query.categoria)
  if (query.q) clean.q = query.q
  return { name: 'catalog', query: clean }
}

/** @param {string} ref */
export function productLink(ref) {
  return { name: 'product', params: { ref } }
}
