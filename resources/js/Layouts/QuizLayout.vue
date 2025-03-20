<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    title: String
});
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100">
            <main>
                <div class="py-6">
                    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <slot></slot>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>