import { defineStore } from 'pinia';
import type { StoreProduct } from '@/components/store/ProductCard.vue';

const STORAGE_KEY = 'aura-recently-viewed-v1';

function readProducts(): StoreProduct[] {
    if (typeof window === 'undefined') return [];
    try {
        const value: unknown = JSON.parse(
            localStorage.getItem(STORAGE_KEY) || '[]',
        );
        return Array.isArray(value)
            ? (value as StoreProduct[]).slice(0, 8)
            : [];
    } catch {
        return [];
    }
}

export const useRecentlyViewedStore = defineStore('recentlyViewed', {
    state: () => ({ products: readProducts() as StoreProduct[] }),
    actions: {
        record(product: StoreProduct) {
            this.products = [
                product,
                ...this.products.filter((item) => item.id !== product.id),
            ].slice(0, 8);
            if (typeof window !== 'undefined') {
                localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify(this.products),
                );
            }
        },
    },
});
