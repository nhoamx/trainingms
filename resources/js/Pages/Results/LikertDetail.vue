<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
            <!-- Header with navigation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('organization.results.list', { organization: organization.id })"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Volver a Lista
                        </Link>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <div class="text-gray-600">
                            <p class="text-lg font-semibold">{{ organization.name }}</p>
                            <p>Folio Personal: {{ personalFolio }}</p>
                            <p>Nombre: {{ evaluation.evaluee_name || 'Sin nombre asignado' }}</p>
                            <p>Fecha: {{ evaluation.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scanned Form Image -->
            <div v-if="evaluation.scanned_image_url" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Formulario Escaneado</h2>
                <div class="flex justify-center items-center bg-gray-50 rounded-lg p-4 min-h-64">
                    <button
                        @click="showImageModal = true"
                        class="relative group cursor-pointer transform transition-transform hover:scale-105"
                    >
                        <img 
                            :src="evaluation.scanned_image_url" 
                            :alt="`Formulario escaneado - ${personalFolio}`"
                            class="max-w-full max-h-64 rounded border border-gray-200 shadow group-hover:shadow-lg transition-shadow"
                        />
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded transition-all flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-center text-sm text-gray-500 group-hover:text-blue-600 transition-colors">
                            Haz clic para ampliar
                        </div>
                    </button>
                </div>
            </div>

            <!-- Image Modal -->
            <div
                v-if="showImageModal"
                class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4"
                @click="showImageModal = false"
            >
                <div
                    class="bg-white rounded-lg shadow-2xl max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
                    @click.stop
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h3 class="text-xl font-bold text-gray-900">
                            Formulario Escaneado - Folio {{ personalFolio }}
                        </h3>
                        <button
                            @click="showImageModal = false"
                            class="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-auto p-6 flex items-center justify-center">
                        <img
                            :src="evaluation.scanned_image_url"
                            :alt="`Formulario escaneado - ${personalFolio}`"
                            class="max-w-full max-h-full rounded border border-gray-200 shadow-lg"
                        />
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
                        <div class="text-sm text-gray-600">
                            Presiona <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg">Esc</kbd> para cerrar
                        </div>
                        <a
                            :href="evaluation.scanned_image_url"
                            download
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Descargar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Score Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Evaluación de Clima Laboral</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Total Score -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border-2 border-blue-300">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Calificación Final</h3>
                        <div class="text-5xl font-bold text-blue-600 mb-2">
                            {{ scores.total_score }}
                        </div>
                        <div class="text-sm text-gray-600">
                            Interpretación: <span class="font-semibold text-blue-700">{{ scores.interpretation }}</span>
                        </div>
                    </div>

                    <!-- Demographic Data -->
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Datos Demográficos</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Género:</span>
                                <span class="font-medium">{{ formatDemographic('genero', demographic.genero) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Turno:</span>
                                <span class="font-medium">{{ formatDemographic('turno', demographic.turno) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tipo de Contrato:</span>
                                <span class="font-medium">{{ formatDemographic('tipo_contrato', demographic.tipo_contrato) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Puesto:</span>
                                <span class="font-medium">{{ demographic.puesto || 'No especificado' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Área:</span>
                                <span class="font-medium">{{ demographic.area || 'No especificado' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dimension Scores -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Calificación por Dimensiones</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(dimension, name) in scores.dimensions"
                        :key="name"
                        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                    >
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ name }}</h4>
                        <div class="flex items-center justify-between">
                            <div class="text-3xl font-bold" :class="getScoreColorClass(dimension.score)">
                                {{ dimension.score }}
                            </div>
                            <div class="text-xs text-gray-500 text-right">
                                <div>{{ dimension.interpretation }}</div>
                                <div class="mt-1 text-gray-400">
                                    {{ dimension.questions.length }} pregunta{{ dimension.questions.length > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions and Answers -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Preguntas y Respuestas</h3>
                
                <div class="space-y-4">
                    <div
                        v-for="question in questions"
                        :key="question.number"
                        class="border-l-4 pl-4 py-3"
                        :class="getQuestionBorderClass(question.answer)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        P{{ question.number }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ question.dimension }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ question.text }}</p>
                            </div>
                            <div class="flex items-center gap-4 flex-shrink-0">
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">Respuesta</div>
                                    <div class="text-lg font-bold" :class="getAnswerColorClass(question.answer)">
                                        {{ question.answer || '-' }}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 mb-1">Valor</div>
                                    <div class="text-lg font-bold text-gray-700">
                                        {{ question.value !== null ? question.value : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Escala de Respuestas</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-green-100 border-2 border-green-500 flex items-center justify-center font-bold text-green-700">
                            A
                        </div>
                        <span class="text-sm text-gray-700">Totalmente de acuerdo (4 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-blue-100 border-2 border-blue-500 flex items-center justify-center font-bold text-blue-700">
                            B
                        </div>
                        <span class="text-sm text-gray-700">De acuerdo (3 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-orange-100 border-2 border-orange-500 flex items-center justify-center font-bold text-orange-700">
                            C
                        </div>
                        <span class="text-sm text-gray-700">En desacuerdo (2 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-red-100 border-2 border-red-500 flex items-center justify-center font-bold text-red-700">
                            D
                        </div>
                        <span class="text-sm text-gray-700">Totalmente en desacuerdo (1 pt)</span>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import Dashboard from '@/Layouts/Dashboard.vue'

const props = defineProps({
    organization: Object,
    personalFolio: String,
    evaluation: Object,
    scores: Object,
    demographic: Object,
    questions: Array,
    isAdmin: Boolean,
})

const showImageModal = ref(false)

const formatDemographic = (field, value) => {
    if (!value) return 'No especificado'
    
    if (field === 'genero') {
        return value.charAt(0).toUpperCase() + value.slice(1)
    }
    
    if (field === 'turno') {
        return value.charAt(0).toUpperCase() + value.slice(1)
    }
    
    if (field === 'tipo_contrato') {
        return value.charAt(0).toUpperCase() + value.slice(1)
    }
    
    return value
}

const getScoreColorClass = (score) => {
    if (score === 0) return 'text-green-600'
    if (score <= 5) return 'text-blue-600'
    if (score <= 10) return 'text-orange-600'
    return 'text-red-600'
}

const getAnswerColorClass = (answer) => {
    if (!answer) return 'text-gray-400'
    if (answer === 'A') return 'text-green-600'
    if (answer === 'B') return 'text-blue-600'
    if (answer === 'C') return 'text-orange-600'
    if (answer === 'D') return 'text-red-600'
    return 'text-gray-600'
}

const getQuestionBorderClass = (answer) => {
    if (!answer) return 'border-gray-300 bg-gray-50'
    if (answer === 'A') return 'border-green-500 bg-green-50'
    if (answer === 'B') return 'border-blue-500 bg-blue-50'
    if (answer === 'C') return 'border-orange-500 bg-orange-50'
    if (answer === 'D') return 'border-red-500 bg-red-50'
    return 'border-gray-300'
}
</script>
