<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, ExternalLink } from '@lucide/vue';
import { money } from '@/lib/store-format';
import { useBagStore } from '@/stores/bag';

const props = defineProps<{
    order: {
        number: string;
        status: string;
        delivery_method: string;
        delivery_postal_code: string | null;
        subtotal_cents: number;
        shipping_cents: number;
        discount_cents: number;
        coupon_code: string | null;
        shipping_free: boolean;
        shipping_needs_confirmation: boolean;
        total_cents: number;
        items: Array<{
            name: string;
            sku: string;
            color: string | null;
            size: string | null;
            quantity: number;
            line_total_cents: number;
        }>;
    };
    whatsappUrl: string | null;
    canStartPayment: boolean;
    paymentMode: boolean;
    shipment: {
        service: string | null;
        tracking: string | null;
        label_url: string | null;
        status: string;
    } | null;
}>();
const bag = useBagStore();
const paymentForm = useForm({});

onMounted(() => {
    bag.clear();
    if (props.whatsappUrl)
        window.open(props.whatsappUrl, '_blank', 'noopener,noreferrer');
});
</script>

<template>
    <Head :title="`${order.number} · Pedido recibido · AURA`" />
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 md:py-16">
        <div
            class="grid size-14 place-items-center rounded-full bg-green-100 text-green-800"
        >
            <Check :size="25" />
        </div>
        <p class="mt-6 text-[10px] tracking-[0.18em] text-aura-muted uppercase">
            Pedido guardado
        </p>
        <h1 class="mt-2 font-serif text-4xl">Gracias por elegir AURA.</h1>
        <p class="mt-4 text-sm leading-6 text-aura-muted">
            Tu pedido
            <strong class="text-aura-ink">{{ order.number }}</strong> quedó
            registrado. Te confirmaremos el envío y la disponibilidad por
            WhatsApp. No se ha realizado ningún cobro.
        </p>
        <a
            v-if="whatsappUrl"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-7 inline-flex min-h-12 items-center gap-2 rounded-full bg-aura-ink px-6 text-sm font-medium text-white"
            >Continuar en WhatsApp <ExternalLink :size="15"
        /></a>
        <form
            v-if="canStartPayment"
            class="mt-5"
            @submit.prevent="paymentForm.post(`/pedido/${order.number}/pago`)"
        >
            <button
                :disabled="paymentForm.processing"
                class="rounded-full border border-aura-ink px-6 py-3 text-sm"
            >
                Probar checkout simulado
            </button>
            <p class="mt-2 text-xs text-aura-muted">
                Modo de prueba: no se realizará ningún cobro.
            </p>
        </form>
        <p
            v-if="paymentMode"
            class="mt-5 rounded border border-black/10 p-4 text-sm"
        >
            Checkout de prueba listo. El pago permanece pendiente hasta conectar
            y validar el webhook de Mercado Pago.
        </p>
        <div
            v-if="shipment"
            class="mt-5 rounded border border-black/10 p-4 text-sm"
        >
            <strong>Seguimiento:</strong> {{ shipment.tracking }} ·
            {{ shipment.service }}
            <p
                v-if="shipment.status === 'mock'"
                class="mt-1 text-xs text-aura-muted"
            >
                Referencia simulada; no es una guía de paquetería.
            </p>
        </div>
        <div
            class="mt-9 rounded-aura border border-black/10 bg-white p-5 sm:p-7"
        >
            <h2 class="font-serif text-2xl">Resumen del pedido</h2>
            <div class="mt-4 divide-y divide-black/10">
                <div
                    v-for="item in order.items"
                    :key="item.sku"
                    class="flex justify-between gap-4 py-3 text-sm"
                >
                    <div>
                        <p class="font-medium">{{ item.name }}</p>
                        <p class="mt-1 text-xs text-aura-muted">
                            {{ item.color }} · {{ item.size }} ·
                            {{ item.quantity }}
                        </p>
                    </div>
                    <span class="shrink-0">{{
                        money(item.line_total_cents)
                    }}</span>
                </div>
            </div>
            <div class="mt-4 space-y-2 border-t border-black/10 pt-4 text-sm">
                <div class="flex justify-between">
                    <span>Subtotal</span
                    ><span>{{ money(order.subtotal_cents) }}</span>
                </div>
                <div
                    v-if="order.discount_cents"
                    class="flex justify-between text-green-800"
                >
                    <span
                        >Descuento{{
                            order.coupon_code ? ` · ${order.coupon_code}` : ''
                        }}</span
                    ><span>−{{ money(order.discount_cents) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Envío</span
                    ><span>{{
                        order.delivery_method === 'pickup'
                            ? 'Recolección'
                            : order.shipping_cents
                              ? money(order.shipping_cents)
                              : order.shipping_free
                                ? 'Gratis'
                                : 'Por confirmar'
                    }}</span>
                </div>
                <div
                    v-if="order.delivery_postal_code"
                    class="flex justify-between"
                >
                    <span>Código postal</span>
                    <span>{{ order.delivery_postal_code }}</span>
                </div>
                <div
                    class="flex justify-between border-t border-black/10 pt-3 font-semibold"
                >
                    <span>Total estimado</span
                    ><span>{{ money(order.total_cents) }}</span>
                </div>
            </div>
        </div>
        <Link
            href="/tienda"
            class="mt-7 inline-flex text-sm underline underline-offset-4"
            >Seguir explorando la tienda</Link
        >
    </section>
</template>
