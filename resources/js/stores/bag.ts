import { defineStore } from 'pinia';

export interface BagItem {
    productId: number;
    slug: string;
    name: string;
    sku: string;
    color: string;
    size: string;
    priceCents: number;
    image: string | null;
    quantity: number;
}

const key = 'aura-store-bag-v1';

function storedItems(): BagItem[] {
    if (typeof window === 'undefined') return [];
    try {
        return JSON.parse(
            window.localStorage.getItem(key) ?? '[]',
        ) as BagItem[];
    } catch {
        return [];
    }
}

export const useBagStore = defineStore('bag', {
    state: () => ({
        items: storedItems(),
        freeShippingThresholdCents: 120_000,
    }),
    getters: {
        count: (state) =>
            state.items.reduce((sum, item) => sum + item.quantity, 0),
        subtotalCents: (state) =>
            state.items.reduce(
                (sum, item) => sum + item.priceCents * item.quantity,
                0,
            ),
        remainingForFreeShipping(): number {
            return Math.max(
                0,
                this.freeShippingThresholdCents - this.subtotalCents,
            );
        },
        shippingProgress(): number {
            return this.freeShippingThresholdCents > 0
                ? Math.min(
                      100,
                      (this.subtotalCents / this.freeShippingThresholdCents) *
                          100,
                  )
                : 100;
        },
    },
    actions: {
        setFreeShippingThreshold(value: number) {
            this.freeShippingThresholdCents = Math.max(0, value);
        },
        persist() {
            if (typeof window !== 'undefined')
                window.localStorage.setItem(key, JSON.stringify(this.items));
        },
        add(item: Omit<BagItem, 'quantity'>) {
            const existing = this.items.find((line) => line.sku === item.sku);
            if (existing) existing.quantity += 1;
            else this.items.push({ ...item, quantity: 1 });
            this.persist();
        },
        setQuantity(sku: string, quantity: number) {
            const item = this.items.find((line) => line.sku === sku);
            if (!item) return;
            if (quantity < 1)
                this.items = this.items.filter((line) => line.sku !== sku);
            else item.quantity = Math.min(quantity, 10);
            this.persist();
        },
        remove(sku: string) {
            this.items = this.items.filter((line) => line.sku !== sku);
            this.persist();
        },
        clear() {
            this.items = [];
            this.persist();
        },
    },
});
