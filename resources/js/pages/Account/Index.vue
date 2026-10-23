<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

type Order = {
    number: string;
    status: string;
    total_cents: number;
    created_at: string | null;
    items_count: number;
};
type Address = {
    id: number;
    label: string;
    recipient: string;
    street: string;
    exterior_number: string;
    neighborhood: string;
    city: string;
    state: string;
    postal_code: string;
    is_default: boolean;
};
type Favorite = { id: number; name: string; slug: string };
const props = defineProps<{
    customer: { name: string; email: string | null; phone: string | null };
    can_manage_store: boolean;
    orders: Order[];
    addresses: Address[];
    favorites: Favorite[];
}>();
const address = useForm({
    label: 'Casa',
    recipient: props.customer.name,
    phone: props.customer.phone ?? '',
    postal_code: '',
    state: '',
    city: '',
    neighborhood: '',
    street: '',
    exterior_number: '',
    interior_number: '',
    references: '',
    is_default: false,
});
const money = (cents: number) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(cents / 100);
</script>

<template>
    <Head title="Mi cuenta" />
    <main class="mx-auto max-w-5xl space-y-12 px-5 py-12">
        <header>
            <p class="text-xs tracking-[.25em] text-neutral-500 uppercase">
                AURA
            </p>
            <h1 class="mt-2 text-3xl">Hola, {{ customer.name }}</h1>
            <p class="mt-2 text-neutral-600">{{ customer.email }}</p>
            <a
                v-if="can_manage_store"
                href="/admin/products"
                class="mt-5 inline-flex rounded bg-black px-5 py-3 text-sm font-medium text-white"
            >
                Administrar tienda
            </a>
        </header>
        <section>
            <h2 class="mb-4 text-2xl">Mis pedidos</h2>
            <p v-if="!orders.length" class="text-neutral-600">
                Todavía no tienes pedidos asociados a tu cuenta.
            </p>
            <ul v-else class="divide-y">
                <li
                    v-for="order in orders"
                    :key="order.number"
                    class="flex flex-wrap justify-between gap-3 py-4"
                >
                    <a :href="`/pedido/${order.number}`" class="underline">{{
                        order.number
                    }}</a
                    ><span
                        >{{ order.items_count }} piezas ·
                        {{ money(order.total_cents) }}</span
                    ><span>{{ order.status }} · {{ order.created_at }}</span>
                </li>
            </ul>
        </section>
        <section>
            <h2 class="mb-4 text-2xl">Mis direcciones</h2>
            <ul class="mb-6 divide-y">
                <li v-for="item in addresses" :key="item.id" class="py-3">
                    {{ item.label
                    }}<span v-if="item.is_default"> · Predeterminada</span
                    ><br />{{ item.recipient }} · {{ item.street }}
                    {{ item.exterior_number }}, {{ item.neighborhood }},
                    {{ item.city }}, {{ item.state }}, CP {{ item.postal_code }}
                    <button
                        class="ml-3 underline"
                        @click="router.delete(`/cuenta/direcciones/${item.id}`)"
                    >
                        Eliminar
                    </button>
                </li>
            </ul>
            <form
                class="grid gap-3 sm:grid-cols-2"
                @submit.prevent="address.post('/cuenta/direcciones')"
            >
                <input
                    v-for="field in [
                        ['label', 'Etiqueta'],
                        ['recipient', 'Recibe'],
                        ['phone', 'Teléfono'],
                        ['postal_code', 'Código postal'],
                        ['state', 'Estado'],
                        ['city', 'Ciudad'],
                        ['neighborhood', 'Colonia'],
                        ['street', 'Calle'],
                        ['exterior_number', 'Número exterior'],
                        ['interior_number', 'Número interior'],
                    ] as const"
                    :key="field[0]"
                    v-model="address[field[0]]"
                    :placeholder="field[1]"
                    :required="!['interior_number'].includes(field[0])"
                    class="rounded border p-3"
                /><textarea
                    v-model="address.references"
                    placeholder="Referencias"
                    class="rounded border p-3 sm:col-span-2"
                /><label class="flex gap-2"
                    ><input v-model="address.is_default" type="checkbox" /> Usar
                    como predeterminada</label
                ><button class="rounded bg-black p-3 text-white sm:col-span-2">
                    Guardar dirección
                </button>
            </form>
        </section>
        <section>
            <h2 class="mb-4 text-2xl">Mis favoritos</h2>
            <p v-if="!favorites.length" class="text-neutral-600">
                Guarda productos para encontrarlos aquí.
            </p>
            <ul>
                <li v-for="item in favorites" :key="item.id" class="py-2">
                    <a :href="`/producto/${item.slug}`" class="underline">{{
                        item.name
                    }}</a>
                    <button
                        class="ml-3 underline"
                        @click="router.delete(`/cuenta/favoritos/${item.id}`)"
                    >
                        Quitar
                    </button>
                </li>
            </ul>
        </section>
    </main>
</template>
