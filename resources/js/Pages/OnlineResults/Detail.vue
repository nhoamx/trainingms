<template>
    <Dashboard>
        <Head :title="`Detalle Evaluación - ${evaluation.folio}`" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">
                                    Detalle de Evaluación en Línea
                                </h2>
                                <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Folio</div>
                                <div class="text-lg font-bold text-gray-900">{{ evaluation.folio }}</div>
                                <div class="text-xs text-gray-500 mt-1">Personal: {{ evaluation.personal_folio }}</div>
                            </div>
                        </div>

                        <!-- Evaluation Info -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-xs text-gray-500 uppercase">Quiz</div>
                                <div class="text-sm font-medium text-gray-900 mt-1">{{ evaluation.quiz_name }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-xs text-gray-500 uppercase">Tipo de Quiz</div>
                                <div class="mt-1">
                                    <span 
                                        :class="getQuizTypeBadgeClass(evaluation.quiz_type)"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ formatQuizType(evaluation.quiz_type) }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-xs text-gray-500 uppercase">Tipo de Evaluación</div>
                                <div class="mt-1">
                                    <span 
                                        :class="getEvaluationTypeBadgeClass(evaluation.evaluation_type)"
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ evaluation.evaluation_type }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-xs text-gray-500 uppercase">Completada</div>
                                <div class="text-sm font-medium text-gray-900 mt-1">{{ evaluation.completed_at }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Demographic Data (Referencia V) -->
                <div v-if="evaluation.has_referencia_v" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Datos Demográficos (Guía V)
                        </h3>
                        <!-- Basic Demographic Data -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="(value, key) in demographicDataFormatted" :key="key" class="border-b border-gray-200 pb-2">
                                <div class="text-xs text-gray-500 uppercase">{{ formatFieldName(key) }}</div>
                                <div class="text-sm font-medium text-gray-900 mt-1">{{ formatValue(value) }}</div>
                            </div>
                        </div>

                        <!-- Datos Laborales Section -->
                        <div v-if="hasDatosLaborales" class="mt-6 border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Datos Laborales</h4>
                            
                            <!-- Experiencia Laboral Subsection -->
                            <div v-if="experienciaLaboral" class="mb-4">
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Experiencia</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 ml-4">
                                    <div v-for="(value, key) in experienciaLaboral" :key="key" class="border-l-2 border-blue-300 pl-3">
                                        <div class="text-xs text-gray-500">{{ formatFieldName(key) }}</div>
                                        <div class="text-sm font-medium text-gray-900 mt-1">{{ formatValue(value) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Labor Data -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <template v-for="(value, key) in datosLaborales" :key="key">
                                    <div v-if="key !== 'experiencia'" class="border-b border-gray-200 pb-2">
                                        <div class="text-xs text-gray-500 uppercase">{{ formatFieldName(key) }}</div>
                                        <div class="text-sm font-medium text-gray-900 mt-1">{{ formatValue(value) }}</div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- INE Images -->
                        <div v-if="hasIneImages" class="mt-6 border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Imágenes de Identificación</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="ine_images.ine_frente" class="border rounded-lg p-4">
                                    <div class="text-sm font-medium text-gray-700 mb-2">INE Frente</div>
                                    <img 
                                        v-if="ine_images.ine_frente.exists" 
                                        :src="ine_images.ine_frente.url" 
                                        alt="INE Frente" 
                                        class="w-full h-auto rounded border"
                                    />
                                    <div v-else class="text-sm text-red-500">Imagen no encontrada</div>
                                </div>
                                <div v-if="ine_images.ine_reverso" class="border rounded-lg p-4">
                                    <div class="text-sm font-medium text-gray-700 mb-2">INE Reverso</div>
                                    <img 
                                        v-if="ine_images.ine_reverso.exists" 
                                        :src="ine_images.ine_reverso.url" 
                                        alt="INE Reverso" 
                                        class="w-full h-auto rounded border"
                                    />
                                    <div v-else class="text-sm text-red-500">Imagen no encontrada</div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Fields in Demographic Section -->
                        <div v-if="hasCustomFields" class="mt-6 border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Campos Personalizados</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div v-for="(value, key) in answers.custom_fields" :key="key" class="border-b border-gray-200 pb-2">
                                    <div class="text-xs text-gray-500 uppercase">Campo {{ key }}</div>
                                    <div class="text-sm font-medium text-gray-900 mt-1">{{ formatValue(value) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referencia I - Solo mostrar si tiene datos -->
                <div v-if="hasReferenciaI" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Guía I - Acontecimientos Traumáticos Severos
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(value, key) in answers.referencia_i" :key="key" class="flex items-start gap-3 p-3 bg-gray-50 rounded">
                                <div class="flex-1">
                                    <div class="text-sm text-gray-700">{{ getQuestionText(key, 'referencia_i') }}</div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span 
                                        :class="getBooleanBadgeClass(value)"
                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ formatBooleanValue(value) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CITSAT (Referencia III Conditional) - Acontecimientos Traumáticos -->
                <div v-if="hasCitsat" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Acontecimientos Traumáticos (CITSAT)
                        </h3>
                        <div class="space-y-2">
                            <template v-for="(section, sectionKey) in answers.citsat" :key="sectionKey">
                                <!-- If section is an object (nested), iterate through it -->
                                <template v-if="typeof section === 'object' && section !== null">
                                    <div class="mt-4 mb-2">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ formatFieldName(sectionKey) }}</h4>
                                        <div class="space-y-2 ml-4">
                                            <div v-for="(value, key) in section" :key="key" class="flex items-start gap-3 p-3 bg-gray-50 rounded border-l-2 border-indigo-300">
                                                <div class="flex-1">
                                                    <div class="text-sm text-gray-700">{{ getQuestionText(key, 'traumatic') }}</div>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <span 
                                                        :class="getBooleanBadgeClass(value)"
                                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                                    >
                                                        {{ formatBooleanValue(value) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <!-- If it's a simple value, display directly -->
                                <div v-else class="flex items-start gap-3 p-3 bg-gray-50 rounded">
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-700">{{ getQuestionText(sectionKey, 'traumatic') }}</div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span 
                                            :class="getBooleanBadgeClass(section)"
                                            class="px-3 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ formatBooleanValue(section) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Referencia III -->
                <div v-if="evaluation.has_referencia_iii && hasReferenciaIIIAnswers" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Guía III - Factores de Riesgo Psicosocial
                        </h3>
                        <div class="space-y-2">
                            <div v-for="(value, key) in answers.referencia_iii" :key="key" class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                                <span class="text-gray-700">{{ formatFieldName(key) }}</span>
                                <span class="font-medium text-gray-900">{{ formatValue(value) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cisneros -->
                <div v-if="evaluation.has_cisneros" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Escala Cisneros - Violencia Laboral
                        </h3>
                        <div class="space-y-2">
                            <div v-for="(value, key) in answers.cisneros" :key="key" class="flex items-start gap-3 p-3 bg-gray-50 rounded">
                                <div class="flex-1">
                                    <div class="text-sm text-gray-700">{{ getQuestionText(key, 'cisneros') }}</div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800">
                                        {{ formatValue(value) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="mt-6">
                    <Link
                        :href="route('organization.online-results', { id: organization.id })"
                        class="inline-flex items-center text-gray-600 hover:text-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a la lista
                    </Link>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue"
import { computed } from 'vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluation: {
        type: Object,
        required: true
    },
    answers: {
        type: Object,
        required: true
    },
    ine_images: {
        type: Object,
        default: () => ({})
    },
    questions_config: {
        type: Object,
        default: () => ({})
    }
})

// Computed properties
const demographicDataFormatted = computed(() => {
    const data = { ...props.answers.demographic_data }
    // Remove INE images, custom_fields and datos_laborales from display (shown separately)
    delete data.ine_frente
    delete data.ine_reverso
    delete data.custom_fields
    delete data.datos_laborales
    return data
})

const datosLaborales = computed(() => {
    return props.answers.demographic_data?.datos_laborales || null
})

const experienciaLaboral = computed(() => {
    return datosLaborales.value?.experiencia || null
})

const hasIneImages = computed(() => {
    return Object.keys(props.ine_images).length > 0
})

const hasCitsat = computed(() => {
    return props.answers.citsat && Object.keys(props.answers.citsat).length > 0
})

const hasReferenciaIIIAnswers = computed(() => {
    return props.answers.referencia_iii && Object.keys(props.answers.referencia_iii).length > 0
})

const hasCustomFields = computed(() => {
    return props.answers.custom_fields && Object.keys(props.answers.custom_fields).length > 0
})

const hasReferenciaI = computed(() => {
    const answers = props.answers.referencia_i
    // Check if it's not empty array or empty object
    if (Array.isArray(answers)) {
        return answers.length > 0
    }
    return answers && Object.keys(answers).length > 0
})

const hasDatosLaborales = computed(() => {
    return datosLaborales.value && Object.keys(datosLaborales.value).length > 0
})

// Helper functions
const formatFieldName = (key) => {
    return key
        .replace(/_/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'N/A'
    }
    if (typeof value === 'boolean') {
        return value ? 'Sí' : 'No'
    }
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2)
    }
    return value
}

const formatBooleanValue = (value) => {
    if (value === true || value === '1' || value === 1 || value === 'true') {
        return 'Sí'
    }
    if (value === false || value === '0' || value === 0 || value === 'false') {
        return 'No'
    }
    return value
}

const formatQuizType = (type) => {
    const types = {
        'completo': 'Completo',
        'reducido': 'Reducido',
        'cisneros': 'Cisneros'
    }
    return types[type] || type
}

const getQuestionText = (key, type) => {
    // Try to get question text from config
    if (type === 'referencia_i' && props.questions_config.referencia_i_questions) {
        // referencia_i keys come as "Category_index" (e.g., "Afectación (durante el último mes)_0")
        // Split by the last underscore to get category and index
        const lastUnderscoreIndex = key.lastIndexOf('_')
        if (lastUnderscoreIndex !== -1) {
            const category = key.substring(0, lastUnderscoreIndex)
            const index = parseInt(key.substring(lastUnderscoreIndex + 1))
            
            if (props.questions_config.referencia_i_questions[category] && 
                Array.isArray(props.questions_config.referencia_i_questions[category])) {
                const questions = props.questions_config.referencia_i_questions[category]
                if (questions[index]) {
                    return questions[index]
                }
            }
        }
        return formatFieldName(key)
    }
    
    if (type === 'traumatic' && props.questions_config.traumatic_questions) {
        // traumatic_questions is an array
        if (Array.isArray(props.questions_config.traumatic_questions)) {
            const question = props.questions_config.traumatic_questions.find(q => q.key === key)
            return question?.text || formatFieldName(key)
        }
        return formatFieldName(key)
    }
    
    if (type === 'cisneros' && props.questions_config.escala_cisneros_questions) {
        // escala_cisneros_questions might be an array or object
        if (Array.isArray(props.questions_config.escala_cisneros_questions)) {
            const question = props.questions_config.escala_cisneros_questions.find(q => q.key === key)
            return question?.text || formatFieldName(key)
        }
        return formatFieldName(key)
    }
    
    return formatFieldName(key)
}

const getQuizTypeBadgeClass = (type) => {
    const classes = {
        'completo': 'bg-blue-100 text-blue-800',
        'reducido': 'bg-yellow-100 text-yellow-800',
        'cisneros': 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getEvaluationTypeBadgeClass = (type) => {
    const classes = {
        'referencia_i': 'bg-green-100 text-green-800',
        'referencia_iii': 'bg-indigo-100 text-indigo-800',
        'referencia_v': 'bg-pink-100 text-pink-800',
        'cisneros': 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getBooleanBadgeClass = (value) => {
    if (value === true || value === '1' || value === 1 || value === 'true') {
        return 'bg-green-100 text-green-800'
    }
    return 'bg-gray-100 text-gray-800'
}
</script>
