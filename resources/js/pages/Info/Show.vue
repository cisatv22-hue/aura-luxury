<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    page: {
        title: string;
        body: string;
        seo_title: string | null;
        seo_description: string | null;
    };
}>();
</script>

<template>
    <Head :title="page.seo_title || `${page.title} · AURA`">
        <meta
            v-if="page.seo_description"
            name="description"
            :content="page.seo_description"
            head-key="description"
        />
    </Head>
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 md:py-16">
        <h1 class="font-serif text-4xl sm:text-5xl">{{ page.title }}</h1>
        <div class="mt-7 text-sm leading-7 whitespace-pre-line text-aura-muted">
            {{ page.body }}
        </div>
    </article>
</template>
