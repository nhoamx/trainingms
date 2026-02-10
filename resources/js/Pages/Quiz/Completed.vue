<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <!-- Icono de éxito -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <CheckCircleIcon class="h-10 w-10 text-green-600" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">¡Evaluación Completada!</h1>
                <p class="text-lg text-gray-600">
                    Su evaluación en línea ha sido enviada exitosamente
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="bg-white py-8 px-4 shadow-xl rounded-lg sm:px-10">
                <!-- Información de la evaluación -->
                <div class="space-y-4 mb-8">
                    <div class="text-center">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Detalles de su evaluación</h2>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Organización:</span>
                            <span class="text-sm text-gray-900">{{ quiz.organization?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-500">Fecha y hora:</span>
                            <span class="text-sm text-gray-900">{{ formatDate(new Date()) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Información importante -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Su evaluación ha sido guardada correctamente en el sistema</li>
                                    <li>No es necesario realizar ninguna acción adicional</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de agradecimiento -->
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">¡Gracias por su participación!</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Su colaboración es muy importante para mejorar las condiciones laborales en su organización.
                    </p>
                </div>

                <!-- Botón para cerrar -->
                <div class="text-center">
                    <button
                        @click="closeWindow"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                    >
                        Cerrar ventana
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                Sistema de Evaluación Psicosocial © {{ new Date().getFullYear() }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { CheckCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    quiz: Object,
    folio: String,
    personalId: String,
    message: String
})

const formatDate = (date) => {
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getQuizTypeLabel = () => {
    if (props.quiz.is_cisneros) {
        return 'Evaluación Cisneros'
    } else if (props.quiz.is_reduced) {
        return 'Evaluación Reducida'
    } else {
        return 'Evaluación Completa'
    }
}

const getQuizTypeClass = () => {
    if (props.quiz.is_cisneros) {
        return 'bg-purple-100 text-purple-800'
    } else if (props.quiz.is_reduced) {
        return 'bg-orange-100 text-orange-800'
    } else {
        return 'bg-blue-100 text-blue-800'
    }
}

const closeWindow = () => {
    // Intentar cerrar la ventana/pestaña
    if (window.opener) {
        window.close()
    } else {
        // Si no se puede cerrar, redirigir a una página de inicio o mostrar mensaje
        alert('Puede cerrar esta ventana/pestaña de forma segura.')
    }
}
</script>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>