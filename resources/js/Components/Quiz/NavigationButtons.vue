<template>
    <div class="px-4 py-4 sm:px-6 border-t border-slate-200 bg-slate-50 rounded-b-lg">
        <div class="flex justify-between">
            <button
                v-if="showPrevious"
                @click="$emit('previous')"
                class="inline-flex items-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500"
            >
                {{ previousLabel }}
            </button>
            <div v-else></div>

            <div class="flex items-center space-x-2">
                <button
                    v-if="showNext && !isLastSection"
                    @click="$emit('next')"
                    :disabled="nextDisabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-slate-800 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ nextLabel }}
                </button>
                
                <button
                    v-if="showSubmit"
                    @click="$emit('submit')"
                    :disabled="submitDisabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isSubmitting" class="mr-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    {{ submitLabel }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    showPrevious: {
        type: Boolean,
        default: false
    },
    showNext: {
        type: Boolean,
        default: true
    },
    showSubmit: {
        type: Boolean,
        default: false
    },
    isLastSection: {
        type: Boolean,
        default: false
    },
    previousLabel: {
        type: String,
        default: 'Anterior'
    },
    nextLabel: {
        type: String,
        default: 'Siguiente'
    },
    submitLabel: {
        type: String,
        default: 'Finalizar'
    },
    nextDisabled: {
        type: Boolean,
        default: false
    },
    submitDisabled: {
        type: Boolean,
        default: false
    },
    isSubmitting: {
        type: Boolean,
        default: false
    }
});

defineEmits(['previous', 'next', 'submit']);
</script>
