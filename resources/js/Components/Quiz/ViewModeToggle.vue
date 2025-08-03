<template>
    <div class="mb-6 flex justify-end">
        <div class="flex items-center space-x-2 text-sm text-slate-600">
            <span class="hidden sm:inline">Vista:</span>
            <button
                @click="$emit('update:modelValue', 'comfortable')"
                class="px-3 py-1 rounded-md transition-colors"
                :class="modelValue === 'comfortable' ? 'bg-slate-100 text-slate-900' : 'hover:bg-slate-50'"
            >
                <span class="hidden sm:inline">Cómoda</span>
                <span class="sm:hidden">C</span>
            </button>
            <button
                @click="$emit('update:modelValue', 'compact')"
                class="px-3 py-1 rounded-md transition-colors"
                :class="modelValue === 'compact' ? 'bg-slate-100 text-slate-900' : 'hover:bg-slate-50'"
            >
                <span class="hidden sm:inline">Compacta</span>
                <span class="sm:hidden">D</span>
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: String,
        required: true
    }
});

defineEmits(['update:modelValue']);
</script>
