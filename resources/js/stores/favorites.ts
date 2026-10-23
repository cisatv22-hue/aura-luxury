import { defineStore } from 'pinia';
import { router } from '@inertiajs/vue3';
import type { StoreProduct } from '@/components/store/ProductCard.vue';

const key = 'aura-store-favorites-v1';

function storedItems(): StoreProduct[] {
    if (typeof window === 'undefined') return [];
    try {
        return JSON.parse(
            window.localStorage.getItem(key) ?? '[]',
        ) as StoreProduct[];
    } catch {
        return [];
    }
}

export const useFavoritesStore = defineStore('favorites', {
    state: () => ({ items: storedItems(), serverIds: [] as number[] }),
    getters: {
        ids: (state) => state.items.map((product) => product.id),
        has: (state) => (id: number) =>
            state.items.some((product) => product.id === id) ||
            state.serverIds.includes(id),
    },
    actions: {
        setServerIds(ids: number[]) {
            this.serverIds = ids;
        },
        toggle(product: StoreProduct, syncToAccount = false) {
            const removing = this.has(product.id);
            this.items = removing
                ? this.items.filter((saved) => saved.id !== product.id)
                : [...this.items, product];
            this.serverIds = removing
                ? this.serverIds.filter((id) => id !== product.id)
                : [...new Set([...this.serverIds, product.id])];
            if (typeof window !== 'undefined')
                window.localStorage.setItem(key, JSON.stringify(this.items));
            if (syncToAccount) {
                if (removing) router.delete(`/cuenta/favoritos/${product.id}`);
                else router.post(`/cuenta/favoritos/${product.id}`);
            }
        },
    },
});
