<template>
    <div
        v-if="isVisible"
        :class="alertClasses"
        class="p-4 rounded-md flex items-start border-l-4"
    >
        <div class="flex-shrink-0">
            <component :is="iconComponent" class="h-5 w-5" :class="iconColor" aria-hidden="true" />
        </div>
        <div class="ml-3 flex-1">
            <div v-if="title" class="text-sm font-semibold" :class="textColor">
                {{ title }}
            </div>
            <p class="text-sm mt-1" :class="textColor">{{ message }}</p>
        </div>

        <button v-if="allowDismiss" @click="dismiss" class="ml-3 flex-shrink-0 rounded-md p-1.5 text-gray-400 hover:text-gray-500">
            <span class="sr-only">Dismiss</span>
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
        </button>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon, XMarkIcon, InformationCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/20/solid'

// Props
const props = defineProps({
    type: {
        type: String,
        default: 'info', // Tipos: success, warning, alert, info
        validator: (value) => ['success', 'warning', 'alert', 'info'].includes(value),
    },
    title: {
        type: String,
        default: null, // Título opcional
    },
    message: {
        type: String,
        required: true, // Mensaje es obligatorio
    },
    allowDismiss: {
        type: Boolean,
        default: true, // Permitir cerrar el alerta
    },
})

// Visibilidad del alerta
const isVisible = ref(true)

// Computed classes para el tipo de alerta
const alertClasses = computed(() => {
    const baseClasses = 'border-l-4 bg-opacity-50 shadow-md'
    const typeClasses = {
        success: 'border-green-400 bg-green-50',
        warning: 'border-yellow-400 bg-yellow-50',
        alert: 'border-red-400 bg-red-50',
        info: 'border-blue-400 bg-blue-50',
    }
    return `${baseClasses} ${typeClasses[props.type]}`
})

// Computed para el color del texto
const textColor = computed(() => {
    const typeColors = {
        success: 'text-green-700',
        warning: 'text-yellow-700',
        alert: 'text-red-700',
        info: 'text-blue-700',
    }
    return typeColors[props.type]
})

// Computed para los iconos según el tipo
const iconComponent = computed(() => {
    const icons = {
        success: CheckCircleIcon,
        warning: ExclamationTriangleIcon,
        alert: ExclamationCircleIcon,
        info: InformationCircleIcon,
    }
    return icons[props.type]
})

// Computed para el color de los iconos
const iconColor = computed(() => {
    const typeColors = {
        success: 'text-green-400',
        warning: 'text-yellow-400',
        alert: 'text-red-400',
        info: 'text-blue-400',
    }
    return typeColors[props.type]
})

// Función para ocultar el alerta
const dismiss = () => {
    isVisible.value = false
}
</script>

<style scoped>
/* Opcional: Estilos adicionales si es necesario */
</style>
