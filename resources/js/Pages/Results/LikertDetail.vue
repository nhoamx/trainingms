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
                <div class="flex justify-between items-start mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Evaluación de Clima Laboral</h2>
                    <button
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar Datos
                    </button>
                    <button
                        @click="openAnswersModal()"
                        class="ml-3 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Editar Respuestas
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Total Score (Clima Laboral) -->
                    <div class="p-6 rounded-lg border-2" :class="getClimaLaboralBgClass(scores.total_score)">
                        <h3 class="text-lg font-semibold mb-2" :class="getClimaLaboralTextClass(scores.total_score)">Clima Laboral</h3>
                        <div class="text-5xl font-bold mb-2" :class="getClimaLaboralTextClass(scores.total_score)">
                            {{ scores.total_score }}
                        </div>
                        <div class="text-sm opacity-90" :class="getClimaLaboralTextClass(scores.total_score)">
                            Interpretación: <span class="font-semibold">{{ scores.interpretation }}</span>
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
                            <div class="text-3xl font-bold" :class="getScoreColorClass(name, dimension.score)">
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
                        <div class="w-8 h-8 rounded bg-blue-400 border-2 border-blue-400 flex items-center justify-center font-bold text-black">
                            A
                        </div>
                        <span class="text-sm text-gray-700">Totalmente de acuerdo (4 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-green-600 border-2 border-green-600 flex items-center justify-center font-bold text-white">
                            B
                        </div>
                        <span class="text-sm text-gray-700">De acuerdo (3 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-yellow-500 border-2 border-yellow-500 flex items-center justify-center font-bold text-black">
                            C
                        </div>
                        <span class="text-sm text-gray-700">En desacuerdo (2 pts)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded bg-red-600 border-2 border-red-600 flex items-center justify-center font-bold text-white">
                            D
                        </div>
                        <span class="text-sm text-gray-700">Totalmente en desacuerdo (1 pt)</span>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>

    <!-- Edit Demographic Data Modal -->
    <div
        v-if="showEditModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click="showEditModal = false"
    >
        <div
            class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-900">
                    Editar Datos Demográficos
                </h3>
                <button
                    @click="showEditModal = false"
                    class="text-gray-500 hover:text-gray-700 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-auto p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Evaluee Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del Evaluado
                        </label>
                        <input
                            v-model="formData.evaluee_name"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                            placeholder="Nombre completo"
                        />
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Género
                        </label>
                        <select
                            v-model="formData.gender"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Seleccionar género</option>
                            <option value="male">Masculino</option>
                            <option value="female">Femenino</option>
                        </select>
                    </div>

                    <!-- Work Schedule -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Turno
                        </label>
                        <select
                            v-model="formData.work_schedule"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Seleccionar turno</option>
                            <option value="morning">Matutino</option>
                            <option value="afternoon">Vespertino</option>
                            <option value="night">Nocturno</option>
                            <option value="morning_afternoon">Matutino-Vespertino</option>
                            <option value="afternoon_night">Vespertino-Nocturno</option>
                            <option value="rotating">Rotativo</option>
                        </select>
                    </div>

                    <!-- Contract Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Contrato
                        </label>
                        <select
                            v-model="formData.contract_type"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                        >
                            <option value="">Seleccionar tipo de contrato</option>
                            <option value="permanent">Tiempo indeterminado</option>
                            <option value="fixed_term">Por tiempo determinado</option>
                            <option value="project_based">Por obra o proyecto</option>
                            <option value="honorarios">Honorarios</option>
                            <option value="confidence">Confianza</option>
                            <option value="unionized">Sindicalizado</option>
                        </select>
                    </div>

                    <!-- Position -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Puesto
                        </label>
                        <input
                            v-model="formData.position"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                            placeholder="Puesto/Ocupación"
                        />
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Área/Departamento
                        </label>
                        <input
                            v-model="formData.department"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500 transition-colors"
                            placeholder="Área o departamento"
                        />
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-3 justify-end pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400 transition-colors font-medium"
                        >
                            <span v-if="!isSubmitting">Guardar Cambios</span>
                            <span v-else>Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
        <!-- Edit Answers Modal -->
        <div v-if="showAnswersModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="showAnswersModal = false">
            <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden" @click.stop>
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900">Editar Respuestas (A/B/C/D)</h3>
                    <button @click="showAnswersModal = false" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4 overflow-auto">
                    <div class="flex justify-end mb-2">
                        <button @click="setAllBlank" type="button" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded">Limpiar todas</button>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="q in questions" :key="q.number" class="border rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">P{{ q.number }}</span>
                                <span class="text-xs text-gray-500">{{ q.dimension }}</span>
                            </div>
                            <div class="text-sm text-gray-800 mb-3">{{ q.text }}</div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :name="`q-${q.number}`" value="A" v-model="editableAnswers[q.number]" />
                                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 font-semibold">A</span>
                                    <span class="text-xs text-gray-600">Totalmente de acuerdo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :name="`q-${q.number}`" value="B" v-model="editableAnswers[q.number]" />
                                    <span class="px-2 py-1 rounded bg-green-100 text-green-700 font-semibold">B</span>
                                    <span class="text-xs text-gray-600">De acuerdo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :name="`q-${q.number}`" value="C" v-model="editableAnswers[q.number]" />
                                    <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 font-semibold">C</span>
                                    <span class="text-xs text-gray-600">En desacuerdo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :name="`q-${q.number}`" value="D" v-model="editableAnswers[q.number]" />
                                    <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-semibold">D</span>
                                    <span class="text-xs text-gray-600">Totalmente en desacuerdo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :name="`q-${q.number}`" value="" v-model="editableAnswers[q.number]" />
                                    <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 font-semibold">Vaciar</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 p-6 border-t bg-gray-50">
                    <button type="button" @click="showAnswersModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded hover:bg-gray-200">Cancelar</button>
                    <button type="button" :disabled="answersSubmitting" @click="saveAnswers" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:bg-emerald-400">
                        <span v-if="!answersSubmitting">Guardar Respuestas</span>
                        <span v-else>Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
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
const showEditModal = ref(false)
const isSubmitting = ref(false)
const showAnswersModal = ref(false)
const answersSubmitting = ref(false)
const editableAnswers = ref({})

const formData = ref({
    evaluee_name: props.evaluation?.evaluee_name || '',
    // Initialize with normalized codes expected by backend
    gender: null,
    work_schedule: null,
    contract_type: null,
    position: props.demographic?.puesto || '',
    department: props.demographic?.area || '',
})

const formatDemographic = (field, value) => {
    if (!value) return 'No especificado'
    
    if (field === 'genero') {
        // Handle both Spanish and English values
        const genderMap = {
            'masculino': 'Masculino',
            'femenino': 'Femenino',
            'male': 'Masculino',
            'female': 'Femenino'
        }
        return genderMap[value.toLowerCase()] || value
    }
    
    if (field === 'turno') {
        // Handle both Spanish and English values for shift/work schedule
        const shiftMap = {
            'matutino': 'Matutino',
            'vespertino': 'Vespertino',
            'nocturno': 'Nocturno',
            'morning': 'Matutino',
            'afternoon': 'Vespertino',
            'night': 'Nocturno',
            'morning_afternoon': 'Matutino-Vespertino',
            'afternoon_night': 'Vespertino-Nocturno',
            'rotating': 'Rotativo'
        }
        return shiftMap[value.toLowerCase()] || value
    }
    
    if (field === 'tipo_contrato') {
        // Handle both Spanish and English values for contract type
        const contractMap = {
            'por_obra_o_proyecto': 'Por obra o proyecto',
            'por_tiempo_determinado': 'Por tiempo determinado',
            'tiempo_indeterminado': 'Tiempo indeterminado',
            'honorarios': 'Honorarios',
            'confianza': 'Confianza',
            'permanent': 'Tiempo indeterminado',
            'fixed_term': 'Por tiempo determinado',
            'project_based': 'Por obra o proyecto',
            'confidence': 'Confianza',
            'unionized': 'Sindicalizado'
        }
        return contractMap[value.toLowerCase()] || value
    }
    
    return value
}

// Reverse mappers: Spanish label/value -> normalized code used in DemographicData
const mapGenderToCode = (value) => {
    if (!value) return ''
    const v = String(value).toLowerCase()
    if (v === 'masculino' || v === 'male' || v === 'm') return 'male'
    if (v === 'femenino' || v === 'female' || v === 'f') return 'female'
    return ''
}

const mapShiftToCode = (value) => {
    if (!value) return ''
    const v = String(value).toLowerCase().replace(/\s+/g, '_')
    const map = {
        'matutino': 'morning',
        'vespertino': 'afternoon',
        'nocturno': 'night',
        'matutino-vespertino': 'morning_afternoon',
        'vespertino-nocturno': 'afternoon_night',
        'rotativo': 'rotating',
        // Already-coded values should pass through
        'morning': 'morning',
        'afternoon': 'afternoon',
        'night': 'night',
        'morning_afternoon': 'morning_afternoon',
        'afternoon_night': 'afternoon_night',
        'rotating': 'rotating',
    }
    return map[v] || ''
}

const mapContractToCode = (value) => {
    if (!value) return ''
    const v = String(value).toLowerCase().replace(/\s+/g, '_')
    const map = {
        'tiempo_indeterminado': 'permanent',
        'por_tiempo_determinado': 'fixed_term',
        'por_obra_o_proyecto': 'project_based',
        'honorarios': 'honorarios',
        'confianza': 'confidence',
        'sindicalizado': 'unionized',
        // Already-coded values should pass through
        'permanent': 'permanent',
        'fixed_term': 'fixed_term',
        'project_based': 'project_based',
        'confidence': 'confidence',
        'unionized': 'unionized',
    }
    return map[v] || ''
}

// Initialize select fields with normalized codes derived from incoming demographic labels
formData.value.gender = mapGenderToCode(props.demographic?.genero)
formData.value.work_schedule = mapShiftToCode(props.demographic?.turno)
formData.value.contract_type = mapContractToCode(props.demographic?.tipo_contrato)

// Helper: Get standardized color for answer value
// Standardized colors: 4 (A) = Azul cielo, 3 (B) = Verde, 2 (C) = Amarillo, 1 (D) = Rojo
const getAnswerColorClass = (answer) => {
    if (!answer) return 'text-gray-400'
    if (answer === 'A') return 'text-blue-400'      // 4 pts - Azul cielo
    if (answer === 'B') return 'text-green-600'     // 3 pts - Verde mayate
    if (answer === 'C') return 'text-yellow-500'    // 2 pts - Amarillo mostaza
    if (answer === 'D') return 'text-red-600'       // 1 pt - Rojo
    return 'text-gray-600'
}

const getQuestionBorderClass = (answer) => {
    if (!answer) return 'border-gray-300 bg-gray-50'
    if (answer === 'A') return 'border-blue-400 bg-blue-50'      // 4 pts - Azul cielo
    if (answer === 'B') return 'border-green-600 bg-green-50'    // 3 pts - Verde
    if (answer === 'C') return 'border-yellow-500 bg-yellow-50'  // 2 pts - Amarillo
    if (answer === 'D') return 'border-red-600 bg-red-50'        // 1 pt - Rojo
    return 'border-gray-300'
}

// Helper: Get color class based on dimension score and ranges from config
const getScoreColorClass = (dimensionName, score) => {
    // valorNiveles ranges from config/likert-value.php
    const ranges = {
        'Entorno Laboral Seguro': [
            { min: 6.6, max: 8, color: 'text-blue-400' },      // Totalmente de Acuerdo
            { min: 5.1, max: 6.5, color: 'text-green-600' },   // De Acuerdo
            { min: 3.6, max: 5, color: 'text-yellow-500' },    // Desacuerdo
            { min: 2, max: 3.5, color: 'text-red-600' },       // Totalmente Desacuerdo
        ],
        'Seguridad Laboral': [
            { min: 6.6, max: 8, color: 'text-blue-400' },
            { min: 5.1, max: 6.5, color: 'text-green-600' },
            { min: 3.6, max: 5, color: 'text-yellow-500' },
            { min: 2, max: 3.5, color: 'text-red-600' },
        ],
        'Compensación Justa': [
            { min: 3.26, max: 4, color: 'text-blue-400' },
            { min: 2.6, max: 3.25, color: 'text-green-600' },
            { min: 1.76, max: 2.5, color: 'text-yellow-500' },
            { min: 1, max: 1.75, color: 'text-red-600' },
        ],
        'Comunicación Abierta': [
            { min: 19.6, max: 24, color: 'text-blue-400' },
            { min: 15.1, max: 19.5, color: 'text-green-600' },
            { min: 10.6, max: 15, color: 'text-yellow-500' },
            { min: 6, max: 10.5, color: 'text-red-600' },
        ],
        'Participación de los Empleados': [
            { min: 9.76, max: 12, color: 'text-blue-400' },
            { min: 7.6, max: 9.75, color: 'text-green-600' },
            { min: 5.26, max: 7.5, color: 'text-yellow-500' },
            { min: 3, max: 5.25, color: 'text-red-600' },
        ],
        'Reconocimiento y Recompensa': [
            { min: 6.6, max: 8, color: 'text-blue-400' },
            { min: 5.1, max: 6.5, color: 'text-green-600' },
            { min: 3.6, max: 5, color: 'text-yellow-500' },
            { min: 2, max: 3.5, color: 'text-red-600' },
        ],
        'Capacitación y Desarrollo': [
            { min: 6.6, max: 8, color: 'text-blue-400' },
            { min: 5.1, max: 6.5, color: 'text-green-600' },
            { min: 3.6, max: 5, color: 'text-yellow-500' },
            { min: 2, max: 3.5, color: 'text-red-600' },
        ],
        'Equilibrio entre Vida Laboral y Personal': [
            { min: 6.6, max: 8, color: 'text-blue-400' },
            { min: 5.1, max: 6.5, color: 'text-green-600' },
            { min: 3.6, max: 5, color: 'text-yellow-500' },
            { min: 2, max: 3.5, color: 'text-red-600' },
        ],
        'Avance Profesional': [
            { min: 6.6, max: 8, color: 'text-blue-400' },
            { min: 5.1, max: 6.5, color: 'text-green-600' },
            { min: 3.6, max: 5, color: 'text-yellow-500' },
            { min: 2, max: 3.5, color: 'text-red-600' },
        ],
        'Apoyo al Empleado': [
            { min: 3.26, max: 4, color: 'text-blue-400' },
            { min: 2.6, max: 3.25, color: 'text-green-600' },
            { min: 1.76, max: 2.5, color: 'text-yellow-500' },
            { min: 1, max: 1.75, color: 'text-red-600' },
        ],
        'Clima Laboral': [
            { min: 75.6, max: 93, color: 'text-blue-400' },
            { min: 59, max: 75.5, color: 'text-green-600' },
            { min: 40.6, max: 58, color: 'text-yellow-500' },
            { min: 23, max: 40.5, color: 'text-red-600' },
        ],
    }

    const dimensionRanges = ranges[dimensionName] || ranges['Clima Laboral']
    
    for (const range of dimensionRanges) {
        if (score >= range.min && score <= range.max) {
            return range.color
        }
    }
    
    return 'text-gray-600'
}

// Helper: Get background class for Clima Laboral score
const getClimaLaboralBgClass = (score) => {
    if (score >= 75.6 && score <= 93) return 'bg-blue-400'      // Totalmente de Acuerdo
    if (score >= 59 && score <= 75.5) return 'bg-green-600'     // De Acuerdo
    if (score >= 40.6 && score <= 58) return 'bg-yellow-500'    // Desacuerdo
    if (score >= 23 && score <= 40.5) return 'bg-red-600'       // Totalmente Desacuerdo
    return 'bg-gray-400'
}

// Helper: Get text class for Clima Laboral score
const getClimaLaboralTextClass = (score) => {
    if (score >= 75.6 && score <= 93) return 'text-black'       // Totalmente de Acuerdo
    if (score >= 59 && score <= 75.5) return 'text-white'       // De Acuerdo
    if (score >= 40.6 && score <= 58) return 'text-black'       // Desacuerdo
    if (score >= 23 && score <= 40.5) return 'text-white'       // Totalmente Desacuerdo
    return 'text-white'
}

const submitForm = async () => {
    isSubmitting.value = true
    
    try {
        // Ensure payload uses normalized codes
        const payload = {
            ...formData.value,
            gender: mapGenderToCode(formData.value.gender) || formData.value.gender || '',
            work_schedule: mapShiftToCode(formData.value.work_schedule) || formData.value.work_schedule || '',
            contract_type: mapContractToCode(formData.value.contract_type) || formData.value.contract_type || '',
        }

        const response = await fetch(
            `/organizacion/${props.organization.id}/resultados/${props.personalFolio}/likert/update`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(payload),
            }
        )

        if (response.ok) {
            showEditModal.value = false
            // Reload page to show updated data
            window.location.reload()
        } else {
            const error = await response.json()
            alert('Error al guardar: ' + (error.message || 'Error desconocido'))
        }
    } catch (error) {
        console.error('Error:', error)
        alert('Error al guardar los datos: ' + error.message)
    } finally {
        isSubmitting.value = false
    }
}

// ===== Edit Answers Modal logic =====
const openAnswersModal = () => {
    // Initialize editable answers from current props.questions
    const map = {}
    for (const q of props.questions || []) {
        map[q.number] = q.answer || ''
    }
    editableAnswers.value = map
    showAnswersModal.value = true
}

const setAllBlank = () => {
    const map = { ...editableAnswers.value }
    Object.keys(map).forEach((k) => { map[k] = '' })
    editableAnswers.value = map
}

const saveAnswers = async () => {
    answersSubmitting.value = true
    try {
        const payload = { answers: editableAnswers.value }
        const response = await fetch(
            `/organizacion/${props.organization.id}/resultados/${props.personalFolio}/likert/answers`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(payload),
            }
        )
        if (!response.ok) {
            const err = await response.json().catch(() => ({}))
            throw new Error(err.message || 'No se pudieron guardar las respuestas')
        }
        // Close modal and refresh to reflect recalculated scores and answers
        showAnswersModal.value = false
        window.location.reload()
    } catch (e) {
        alert(e.message)
    } finally {
        answersSubmitting.value = false
    }
}
</script>
