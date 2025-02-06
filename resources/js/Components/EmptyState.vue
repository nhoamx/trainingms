<template>
    <div class="text-center">
        <FaceFrownIcon class="mx-auto h-12 w-12 text-gray-400" aria-hidden="true" />
        <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ title }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ text }}</p>

        <div class="mt-6" v-if="buttonText">
            <button
                v-if="buttonText"
                type="button"
                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                @click="buttonAction"
            >
                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                {{ buttonText }}
            </button>
        </div>

        <div class="mt-6" v-else>
            <slot />
        </div>
    </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/20/solid'
import { FaceFrownIcon } from '@heroicons/vue/24/outline'
import { defineProps } from 'vue'

// Props para el componente
const props = defineProps({
    title: {
        type: String,
        required: true,
        default: 'Sin información',
    },
    text: {
        type: String,
        required: true,
        default: 'Aun no tenemos información que mostrar',
    },
    buttonText: {
        type: String,
        default: null, // El botón será opcional
    },
    buttonAction: {
        type: Function,
        default: () => {}, // Acción del botón opcional
    }
})
</script>
