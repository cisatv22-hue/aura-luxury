<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head title="Crear cuenta" />
    <main class="mx-auto flex min-h-screen max-w-md items-center px-6">
        <form class="w-full space-y-5" @submit.prevent="form.post('/register')">
            <div>
                <p class="text-xs tracking-[.25em] text-neutral-500 uppercase">
                    AURA
                </p>
                <h1 class="mt-2 text-3xl">Crear cuenta</h1>
                <p class="mt-2 text-sm text-neutral-600">
                    Consulta tus pedidos y guarda tus direcciones y favoritos.
                </p>
            </div>
            <label class="block text-sm"
                >Nombre<input
                    v-model="form.name"
                    autocomplete="name"
                    required
                    class="mt-1 block w-full rounded border p-3"
                /><small class="text-red-700">{{
                    form.errors.name
                }}</small></label
            >
            <label class="block text-sm"
                >Correo electrónico<input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    class="mt-1 block w-full rounded border p-3"
                /><small class="text-red-700">{{
                    form.errors.email
                }}</small></label
            >
            <label class="block text-sm"
                >Contraseña<input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1 block w-full rounded border p-3"
                /><small class="text-red-700">{{
                    form.errors.password
                }}</small></label
            >
            <label class="block text-sm"
                >Confirmar contraseña<input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="mt-1 block w-full rounded border p-3"
            /></label>
            <button
                :disabled="form.processing"
                class="w-full rounded bg-black p-3 text-white"
            >
                Crear cuenta
            </button>
            <a href="/login" class="block text-center text-sm underline"
                >Ya tengo cuenta</a
            >
        </form>
    </main>
</template>
