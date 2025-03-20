<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: String
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-50">
            <header class="bg-slate-800 shadow-sm">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <img src="/favicon.ico" alt="Logo" class="h-8 w-auto inline-block mr-2">
                    <h1 class="text-xl font-medium text-gray-200 inline-block align-middle">
                        Evaluación de factores de riesgo psicosocial
                    </h1>
                </div>
            </header>
            <main>
                <div class="py-6">
                    <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                        <slot></slot>
                    </div>
                </div>
            </main>
            <footer class="bg-white border-t border-gray-200">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500">
                        Por favor responda todas las preguntas honestamente. Sus respuestas son confidenciales.
                    </p>
                </div>
            </footer>
        </div>
    </div>
</template>