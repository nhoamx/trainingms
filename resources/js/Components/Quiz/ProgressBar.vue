<template>
    <div class="mb-8">
        <div class="h-1 bg-slate-200 rounded-full">
            <div
                class="h-full bg-slate-800 rounded-full transition-all duration-300"
                :style="{ width: `${progress}%` }"
            ></div>
        </div>
        <div class="mt-2 text-sm text-slate-600">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    progress: {
        type: Number,
        required: true,
        default: 0
    }
});
</script>
