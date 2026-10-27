<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { money } from '@/lib/store-format';
import { useBagStore } from '@/stores/bag';

const bag = useBagStore();
const form = useForm({
    name: '',
    phone: '',
    email: '',
    city: '',
    delivery_method: 'shipping',
    delivery_zone: '',
    delivery_postal_code: '',
    note: '',
    coupon_code: '',
    'cf-turnstile-response': '',
    items: [] as Array<{ sku: string; quantity: number }>,
});
const page = usePage();
const turnstileSiteKey = page.props.turnstileSiteKey as string | null;
const totalCount = computed(() =>
    bag.items.reduce((sum, item) => sum + item.quantity, 0),
);

function submit() {
    form.items = bag.items.map(({ sku, quantity }) => ({ sku, quantity }));
    form['cf-turnstile-response'] =
        (
            document.querySelector(
                'input[name="cf-turnstile-response"]',
            ) as HTMLInputElement | null
        )?.value ?? '';
    form.post('/pedido');
}
</script>

<template>
    <Head title="Enviar pedido · AURA" />
    <section class="mx-auto max-w-6xl px-4 py-9 sm:px-6 md:py-12">
        <p class="mb-3 text-[10px] tracking-[0.18em] text-aura-muted uppercase">
            AURA · Pedido
        </p>
        <h1 class="font-serif text-4xl">Tus datos y entrega</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-aura-muted">
            Guardaremos tu pedido con un folio. El equipo confirma por WhatsApp
            el envío y cualquier detalle antes de cobrarte.
        </p>

        <div
            v-if="!bag.items.length"
            class="mt-8 rounded-aura border border-black/10 bg-white p-7 text-center"
        >
            <p class="font-serif text-xl">Tu bolsa está vacía</p>
            <Link
                href="/tienda"
                class="mt-4 inline-flex rounded-full bg-aura-ink px-5 py-3 text-sm text-white"
                >Volver a la tienda</Link
            >
        </div>

        <div v-else class="mt-8 grid gap-9 lg:grid-cols-[1fr_0.72fr]">
            <form class="space-y-7" @submit.prevent="submit">
                <fieldset class="space-y-4">
                    <legend class="mb-2 text-sm font-semibold">
                        Datos de contacto
                    </legend>
                    <label class="grid gap-1.5 text-xs"
                        >Nombre completo
                        <input
                            v-model="form.name"
                            autocomplete="name"
                            required
                            maxlength="120"
                            class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                            :aria-invalid="Boolean(form.errors.name)"
                        />
                        <span v-if="form.errors.name" class="text-red-700">{{
                            form.errors.name
                        }}</span>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1.5 text-xs"
                            >Celular
                            <input
                                v-model="form.phone"
                                type="tel"
                                autocomplete="tel"
                                required
                                maxlength="24"
                                inputmode="tel"
                                placeholder="55 1234 5678"
                                class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                                :aria-invalid="Boolean(form.errors.phone)"
                            />
                            <span
                                v-if="form.errors.phone"
                                class="text-red-700"
                                >{{ form.errors.phone }}</span
                            >
                        </label>
                        <label class="grid gap-1.5 text-xs"
                            >Correo electrónico
                            <span class="text-aura-muted">(opcional)</span>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                maxlength="254"
                                class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                                :aria-invalid="Boolean(form.errors.email)"
                            />
                            <span
                                v-if="form.errors.email"
                                class="text-red-700"
                                >{{ form.errors.email }}</span
                            >
                        </label>
                    </div>
                    <label class="grid gap-1.5 text-xs"
                        >Ciudad
                        <input
                            v-model="form.city"
                            autocomplete="address-level2"
                            required
                            maxlength="120"
                            class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                            :aria-invalid="Boolean(form.errors.city)"
                        />
                        <span v-if="form.errors.city" class="text-red-700">{{
                            form.errors.city
                        }}</span>
                    </label>
                </fieldset>

                <fieldset class="space-y-2">
                    <label for="coupon-code" class="text-sm font-semibold"
                        >Cupón (opcional)</label
                    >
                    <input
                        id="coupon-code"
                        v-model="form.coupon_code"
                        autocomplete="off"
                        maxlength="40"
                        class="block w-full rounded-xl border border-black/15 bg-white px-4 py-3 text-sm uppercase"
                        placeholder="CÓDIGO"
                    />
                    <span
                        v-if="form.errors.coupon_code"
                        class="text-sm text-red-700"
                        >{{ form.errors.coupon_code }}</span
                    >
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="mb-2 text-sm font-semibold">
                        ¿Cómo quieres recibir tu pedido?
                    </legend>
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-black/15 bg-white p-4"
                    >
                        <input
                            v-model="form.delivery_method"
                            type="radio"
                            value="shipping"
                            class="mt-0.5 accent-black"
                        />
                        <span
                            ><span class="block text-sm font-medium"
                                >Envío a domicilio</span
                            ><span class="mt-1 block text-xs text-aura-muted"
                                >El equipo confirma cobertura y costo por WhatsApp antes de cobrar.</span
                            ></span
                        >
                    </label>
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-black/15 bg-white p-4"
                    >
                        <input
                            v-model="form.delivery_method"
                            type="radio"
                            value="pickup"
                            class="mt-0.5 accent-black"
                        />
                        <span
                            ><span class="block text-sm font-medium"
                                >Recolección</span
                            ><span class="mt-1 block text-xs text-aura-muted"
                                >Coordinaremos contigo el punto y horario por
                                WhatsApp.</span
                            ></span
                        >
                    </label>
                    <div
                        v-if="form.delivery_method === 'shipping'"
                        class="grid gap-2"
                    >
                        <label
                            for="delivery-postal-code"
                            class="grid gap-1.5 text-xs"
                            >Código postal
                            <input
                                id="delivery-postal-code"
                                v-model="form.delivery_postal_code"
                                autocomplete="postal-code"
                                inputmode="numeric"
                                pattern="[0-9]{5}"
                                maxlength="5"
                                required
                                class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                                :aria-invalid="
                                    Boolean(form.errors.delivery_postal_code)
                                "
                            />
                        </label>
                        <span
                            v-if="form.errors.delivery_postal_code"
                            role="alert"
                            class="text-sm text-red-700"
                            >{{ form.errors.delivery_postal_code }}</span
                        >
                        <p class="text-xs leading-5 text-aura-muted">
                            El código postal ayuda al equipo a confirmar cobertura. El envío todavía no se cotiza desde la tienda.
                        </p>
                    </div>
                    <label class="grid gap-1.5 text-xs"
                        >Nota para el equipo
                        <span class="text-aura-muted">(opcional)</span>
                        <textarea
                            v-model="form.note"
                            rows="3"
                            maxlength="1000"
                            class="rounded-xl border border-black/15 bg-white px-4 py-3 text-sm"
                            placeholder="Referencia de entrega o indicaciones"
                        ></textarea>
                    </label>
                </fieldset>

                <p class="text-xs leading-5 text-aura-muted">
                    Esta tienda está en modo de prueba. No se cobran pagos en línea; el equipo revisa cada solicitud por WhatsApp.
                </p>
                <div
                    v-if="turnstileSiteKey"
                    class="cf-turnstile"
                    :data-sitekey="turnstileSiteKey"
                    data-action="place_order"
                    data-response-field-name="cf-turnstile-response"
                />
                <span
                    v-if="form.errors['cf-turnstile-response']"
                    role="alert"
                    class="text-sm text-red-700"
                    >{{ form.errors['cf-turnstile-response'] }}</span
                >
                <div
                    v-if="form.errors.items"
                    role="alert"
                    class="rounded-lg bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ form.errors.items }}
                </div>
                <button
                    type="submit"
                    class="flex min-h-14 w-full items-center justify-center rounded-full bg-aura-ink px-6 text-sm font-semibold text-white disabled:opacity-50 sm:w-auto sm:min-w-64"
                    :disabled="form.processing"
                >
                    {{
                        form.processing
                            ? 'Guardando pedido…'
                            : 'Enviar pedido por WhatsApp'
                    }}
                </button>
            </form>

            <aside
                class="h-fit rounded-aura border border-black/10 bg-white p-5 sm:p-6"
            >
                <h2 class="font-serif text-2xl">
                    Resumen
                    <span class="font-sans text-sm text-aura-muted"
                        >({{ totalCount }})</span
                    >
                </h2>
                <div class="mt-5 divide-y divide-black/10">
                    <div
                        v-for="item in bag.items"
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
                            money(item.priceCents * item.quantity)
                        }}</span>
                    </div>
                </div>
                <div
                    class="mt-4 flex justify-between border-t border-black/10 pt-4 text-sm"
                >
                    <span>Subtotal estimado</span
                    ><strong>{{ money(bag.subtotalCents) }}</strong>
                </div>
                <p class="mt-3 text-xs leading-5 text-aura-muted">
                    El equipo confirmará el envío y el total final antes de
                    cobrar. Enviar el pedido no descuenta existencias.
                </p>
            </aside>
        </div>
    </section>
</template>
