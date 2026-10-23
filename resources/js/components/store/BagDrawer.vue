<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { X, Minus, Plus, Trash2 } from '@lucide/vue';
import { money } from '@/lib/store-format';
import { useBagStore, type BagItem } from '@/stores/bag';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();
const bag = useBagStore();
const closeButton = ref<HTMLButtonElement | null>(null);
const lastRemoved = ref<BagItem | null>(null);
let undoTimeout: number | undefined;
const reminderForm = useForm({
    email: '',
    consent: false,
    items: [] as Array<{ sku: string; quantity: number }>,
});
const reminderSaved = ref(false);
function requestReminder() {
    reminderForm.items = bag.items.map(({ sku, quantity }) => ({
        sku,
        quantity,
    }));
    reminderForm.post('/bolsa/recordar', {
        onSuccess: () => {
            reminderSaved.value = true;
        },
    });
}

function removeItem(sku: string) {
    const item = bag.items.find((line) => line.sku === sku);
    if (!item) return;

    lastRemoved.value = { ...item };
    bag.remove(sku);
    window.clearTimeout(undoTimeout);
    undoTimeout = window.setTimeout(() => {
        lastRemoved.value = null;
    }, 5000);
}

function decrementItem(item: BagItem) {
    if (item.quantity <= 1) {
        removeItem(item.sku);
        return;
    }

    bag.setQuantity(item.sku, item.quantity - 1);
}

function undoRemove() {
    if (!lastRemoved.value) return;

    const { quantity, ...item } = lastRemoved.value;
    bag.add(item);
    bag.setQuantity(item.sku, quantity);
    lastRemoved.value = null;
    window.clearTimeout(undoTimeout);
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.open) emit('close');
}

watch(
    () => props.open,
    (open) => {
        document.body.classList.toggle('overflow-hidden', open);
        if (open) requestAnimationFrame(() => closeButton.value?.focus());
        else {
            lastRemoved.value = null;
            window.clearTimeout(undoTimeout);
        }
    },
);
onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
    window.clearTimeout(undoTimeout);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 bg-black/35"
            @click.self="emit('close')"
        >
            <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="bag-title"
                class="ml-auto flex h-full w-full max-w-md flex-col bg-aura-paper p-5 shadow-2xl sm:p-7"
            >
                <header
                    class="flex items-center justify-between border-b border-black/10 pb-4"
                >
                    <h2 id="bag-title" class="font-serif text-2xl">
                        Tu bolsa
                        <span class="font-sans text-sm text-aura-muted"
                            >({{ bag.count }})</span
                        >
                    </h2>
                    <button
                        ref="closeButton"
                        type="button"
                        class="grid size-10 place-items-center rounded-full hover:bg-white focus-visible:outline-2"
                        aria-label="Cerrar bolsa"
                        @click="emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </header>
                <div class="my-5 rounded-xl bg-aura-stone px-4 py-3">
                    <p class="text-xs leading-5 text-aura-muted" role="status">
                        El equipo confirma disponibilidad y costo de envío por WhatsApp antes de cerrar tu pedido.
                    </p>
                </div>
                <div
                    v-if="bag.items.length"
                    class="flex-1 space-y-4 overflow-y-auto pr-1"
                >
                    <article
                        v-for="item in bag.items"
                        :key="item.sku"
                        class="flex gap-4 border-b border-black/10 pb-4"
                    >
                        <img
                            v-if="item.image"
                            :src="item.image"
                            :alt="item.name"
                            class="size-24 rounded-lg bg-aura-stone object-cover"
                            width="96"
                            height="96"
                        />
                        <div v-else class="size-24 rounded-lg bg-aura-stone" />
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/producto/${item.slug}`"
                                class="font-medium hover:underline"
                                >{{ item.name }}</Link
                            >
                            <p class="mt-1 text-xs text-aura-muted">
                                {{ item.color }} · {{ item.size }}
                            </p>
                            <p class="mt-2 text-sm">
                                {{ money(item.priceCents) }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <button
                                    type="button"
                                    class="grid size-8 place-items-center rounded-full border border-black/15"
                                    :aria-label="`Quitar una unidad de ${item.name}`"
                                    @click="decrementItem(item)"
                                >
                                    <Minus :size="14" />
                                </button>
                                <span
                                    class="min-w-5 text-center text-sm"
                                    aria-live="polite"
                                    >{{ item.quantity }}</span
                                >
                                <button
                                    type="button"
                                    class="grid size-8 place-items-center rounded-full border border-black/15"
                                    :aria-label="`Agregar una unidad de ${item.name}`"
                                    @click="
                                        bag.setQuantity(
                                            item.sku,
                                            item.quantity + 1,
                                        )
                                    "
                                >
                                    <Plus :size="14" />
                                </button>
                                <button
                                    type="button"
                                    class="ml-auto inline-flex items-center gap-1 text-xs text-aura-muted hover:text-black"
                                    @click="removeItem(item.sku)"
                                >
                                    <Trash2 :size="14" /> Quitar
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="grid flex-1 place-items-center text-center">
                    <div>
                        <p class="font-serif text-xl">Tu bolsa está vacía</p>
                        <p class="mt-2 text-sm text-aura-muted">
                            Explora las piezas de AURA.
                        </p>
                        <Link
                            href="/tienda"
                            class="mt-5 inline-flex rounded-full bg-aura-ink px-5 py-3 text-sm text-white"
                            >Ver tienda</Link
                        >
                    </div>
                </div>
                <div
                    v-if="lastRemoved"
                    class="mt-3 flex items-center justify-between gap-3 rounded-lg bg-aura-ink px-4 py-3 text-sm text-white"
                    role="status"
                >
                    <span>{{ lastRemoved.name }} se quitó de la bolsa.</span>
                    <button
                        type="button"
                        class="shrink-0 font-semibold underline underline-offset-4"
                        @click="undoRemove"
                    >
                        Deshacer
                    </button>
                </div>
                <footer
                    v-if="bag.items.length"
                    class="border-t border-black/10 pt-5"
                >
                    <div class="mb-4 flex justify-between text-sm">
                        <span>Subtotal</span
                        ><strong>{{ money(bag.subtotalCents) }}</strong>
                    </div>
                    <p class="mb-4 text-xs text-aura-muted">
                        Envío e impuestos se confirman al enviar tu pedido.
                    </p>
                    <Link
                        href="/pedido"
                        class="flex w-full justify-center rounded-full bg-aura-ink px-5 py-3.5 text-sm font-medium text-white"
                        @click="emit('close')"
                        >Continuar con mi pedido</Link
                    >
                    <form
                        class="mt-5 space-y-2 border-t border-black/10 pt-4"
                        @submit.prevent="requestReminder"
                    >
                        <label class="block text-xs"
                            >¿Quieres un recordatorio si dejas la bolsa?<input
                                v-model="reminderForm.email"
                                type="email"
                                autocomplete="email"
                                required
                                maxlength="254"
                                placeholder="Tu correo electrónico"
                                class="mt-2 w-full rounded-lg border border-black/15 bg-white px-3 py-2 text-sm"
                        /></label>
                        <label
                            class="flex items-start gap-2 text-xs text-aura-muted"
                            ><input
                                v-model="reminderForm.consent"
                                type="checkbox"
                                required
                                class="mt-0.5"
                            />Acepto recibir un único recordatorio por correo y
                            entiendo que puedo darme de baja.</label
                        >
                        <p
                            v-if="
                                reminderForm.errors.email ||
                                reminderForm.errors.consent ||
                                reminderForm.errors.items
                            "
                            role="alert"
                            class="text-xs text-red-700"
                        >
                            {{
                                reminderForm.errors.email ||
                                reminderForm.errors.consent ||
                                reminderForm.errors.items
                            }}
                        </p>
                        <p
                            v-if="reminderSaved"
                            role="status"
                            class="text-xs text-green-800"
                        >
                            Recordatorio guardado. Te escribiremos una sola vez
                            si la bolsa sigue pendiente.
                        </p>
                        <button
                            :disabled="reminderForm.processing"
                            class="text-xs underline disabled:opacity-50"
                        >
                            {{
                                reminderForm.processing
                                    ? 'Guardando…'
                                    : 'Solicitar recordatorio'
                            }}
                        </button>
                    </form>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
