<template>
    <Dashboard>
        <Head :title="`Evaluaciones - ${organization.name}`" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Evaluaciones</h2>
                                <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button
                                    @click="showBulkUpdateModal = true"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Actualizar Masivamente
                                </button>
                                <div class="text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-full">
                                    {{ evaluationGroups.length }} folios
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Warnings -->
                    <div v-if="hasWarnings" class="p-6 border-b border-gray-200">
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-md">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-sm font-medium text-amber-800">
                                        Se encontraron evaluaciones incompletas
                                    </h3>
                                    <div class="mt-2 text-sm text-amber-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li v-if="summary.missing_referencia_iii > 0">
                                                <strong>{{ summary.missing_referencia_iii }}</strong> {{ summary.missing_referencia_iii === 1 ? 'persona le falta' : 'personas les falta' }} la <strong>Guía de Referencia III</strong> (Factores de Riesgo Psicosocial)
                                            </li>
                                            <li v-if="summary.missing_referencia_v > 0">
                                                <strong>{{ summary.missing_referencia_v }}</strong> {{ summary.missing_referencia_v === 1 ? 'persona le falta' : 'personas les falta' }} la <strong>Guía de Referencia V</strong> (Datos Demográficos)
                                            </li>
                                            <li v-if="summary.with_missing_data > 0">
                                                <strong>{{ summary.with_missing_data }}</strong> {{ summary.with_missing_data === 1 ? 'persona tiene' : 'personas tienen' }} <strong>datos demográficos incompletos o nulos</strong>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-xs text-amber-600">
                                            💡 Revisa los folios marcados con el ícono de advertencia ⚠️ en la tabla para completar la información faltante.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missing Folios Accordion -->
                    <div v-if="missingFolios && missingFolios.length > 0" class="p-6 border-b border-gray-200">
                        <div class="bg-red-50 border border-red-200 rounded-lg overflow-hidden">
                            <button 
                                @click="showMissingFolios = !showMissingFolios"
                                class="w-full flex items-center justify-between p-4 text-left hover:bg-red-100 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-red-800">
                                            Faltan {{ totalMissingFoliosCount }} folios por procesar
                                        </h3>
                                        <p class="text-xs text-red-600 mt-0.5">
                                            Estos folios fueron creados pero no tienen evaluaciones registradas
                                        </p>
                                    </div>
                                </div>
                                <svg 
                                    class="h-5 w-5 text-red-500 transition-transform duration-200" 
                                    :class="{ 'rotate-180': showMissingFolios }"
                                    xmlns="http://www.w3.org/2000/svg" 
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div v-show="showMissingFolios" class="border-t border-red-200 p-4 space-y-4">
                                <div v-for="batch in missingFolios" :key="batch.batch_name" class="bg-white rounded-lg p-4 border border-red-100">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span 
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="batch.batch_type === 'presencial' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'"
                                        >
                                            {{ batch.batch_type === 'presencial' ? 'Presencial' : 'En línea' }}
                                        </span>
                                        <h4 class="text-sm font-medium text-gray-900">{{ batch.batch_name }}</h4>
                                        <span class="text-xs text-red-600 font-medium">
                                            ({{ batch.count }} {{ batch.count === 1 ? 'folio' : 'folios' }})
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span 
                                            v-for="folio in batch.folios.slice(0, showAllFolios[batch.batch_name] ? batch.folios.length : 20)" 
                                            :key="folio"
                                            class="px-2 py-1 text-xs font-mono bg-red-100 text-red-700 rounded border border-red-200"
                                        >
                                            {{ folio }}
                                        </span>
                                        <button 
                                            v-if="batch.folios.length > 20 && !showAllFolios[batch.batch_name]"
                                            @click="showAllFolios[batch.batch_name] = true"
                                            class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 hover:underline"
                                        >
                                            + {{ batch.folios.length - 20 }} más...
                                        </button>
                                        <button 
                                            v-if="batch.folios.length > 20 && showAllFolios[batch.batch_name]"
                                            @click="showAllFolios[batch.batch_name] = false"
                                            class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 hover:underline"
                                        >
                                            Ver menos
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Buscar por folio
                                </label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Buscar folio personal..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Género
                                </label>
                                <select
                                    v-model="filters.gender"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Todos</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
                                    <option value="sin_genero">Sin género</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de evaluación
                                </label>
                                <select
                                    v-model="filters.evaluationType"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Todos</option>
                                    <option value="referencia_i">Guía I</option>
                                    <option value="referencia_iii">Guía III</option>
                                    <option value="referencia_v">Guía V</option>
                                    <option value="cisneros">Cisneros</option>
                                        <option value="likert">Clima Laboral</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div v-if="filteredEvaluationGroups.length === 0" class="text-center py-12 px-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">No hay evaluaciones registradas</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Folio Personal
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fuente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Guías de Referencia
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Puntaje Total
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="group in filteredEvaluationGroups" :key="group.personal_folio" :class="hasIssues(group) ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-gray-50'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span v-if="hasIssues(group)" class="text-amber-500" title="Esta evaluación tiene datos incompletos">
                                                ⚠️
                                            </span>
                                            <div class="text-sm font-medium text-gray-900">{{ group.personal_folio }}</div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <!-- Nombre: mostrar evaluee_name si existe, si no '-' -->
                                        <div class="text-sm text-gray-900">{{ group.evaluee_name ? group.evaluee_name : '-' }}</div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="getSourceBadgeClass(group.source)"
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ formatSource(group.source) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <span 
                                                v-for="type in group.evaluation_types" 
                                                :key="type"
                                                :class="getBadgeClass(type)"
                                                class="px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ formatEvaluationType(type) }}
                                            </span>
                                            <!-- Missing evaluations warnings (only show for non-Likert-only evaluations) -->
                                            <span 
                                                v-if="!group.has_referencia_iii && !isLikertOnly(group)"
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 border border-red-300"
                                                title="Falta Guía III"
                                            >
                                                Falta Guía III
                                            </span>
                                            <span 
                                                v-if="!group.has_referencia_v && !isLikertOnly(group)"
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 border border-red-300"
                                                title="Falta Guía V"
                                            >
                                                Falta Guía V
                                            </span>
                                            <!-- Missing demographic data warning -->
                                            <span 
                                                v-if="group.missing_data && group.missing_data.length > 0"
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-300 cursor-help"
                                                :title="'Datos faltantes: ' + group.missing_data.join(', ')"
                                            >
                                                {{ group.missing_data.length }} {{ group.missing_data.length === 1 ? 'dato faltante' : 'datos faltantes' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ group.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">{{ group.total_score }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <Link
                                            :href="getDetailRoute(group)"
                                            class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Ver Detalles
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="mt-6">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center text-gray-600 hover:text-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver al Dashboard
                    </Link>
                </div>
            </div>
        </div>

        <!-- Bulk Update Modal -->
        <BulkUpdateModal
            :show="showBulkUpdateModal"
            :organization-id="organization.id"
            @close="showBulkUpdateModal = false"
            @success="handleBulkUpdateSuccess"
        />
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue"
import BulkUpdateModal from "../../Components/BulkUpdateModal.vue"
import { ref, computed, reactive } from 'vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluationGroups: {
        type: Array,
        required: true
    },
    missingFolios: {
        type: Array,
        default: () => []
    },
    summary: {
        type: Object,
        required: true
    }
})

// Modal state
const showBulkUpdateModal = ref(false)

// Missing folios accordion state
const showMissingFolios = ref(false)
const showAllFolios = reactive({})

// Total missing folios count
const totalMissingFoliosCount = computed(() => {
    return props.missingFolios.reduce((total, batch) => total + batch.count, 0)
})

// Filters
const filters = ref({
    search: '',
    gender: '',
    evaluationType: ''
})

// Check if there are any warnings to show
const hasWarnings = computed(() => {
    return props.summary.missing_referencia_iii > 0 ||
           props.summary.missing_referencia_v > 0 ||
           props.summary.with_missing_data > 0
})

// Check if a specific group has issues
const hasIssues = (group) => {
    // For Likert-only evaluations, only check for missing data
    if (isLikertOnly(group)) {
        return group.missing_data && group.missing_data.length > 0
    }
    
    // For other evaluations, check for missing guides and data
    return !group.has_referencia_iii || 
           !group.has_referencia_v || 
           (group.missing_data && group.missing_data.length > 0)
}

// Check if evaluation is Likert-only (no Referencia III or V)
const isLikertOnly = (group) => {
    return group.has_likert && !group.has_referencia_iii && !group.has_referencia_v
}

// Get the appropriate detail route based on evaluation type
const getDetailRoute = (group) => {
    if (isLikertOnly(group)) {
        return route('organization.results.likert', { 
            organization: props.organization.id, 
            personalFolio: group.personal_folio 
        })
    }
    
    return route('organization.results.detail', { 
        organization: props.organization.id, 
        personalFolio: group.personal_folio 
    })
}

// Get gender from evaluation group's demographic data
const getGenderFromGroup = (group) => {
    // For Likert-only evaluations, gender comes from demographic_data model (in English)
    if (group.has_likert && !group.has_referencia_iii && !group.has_referencia_v) {
        // Check if demographic_data exists from DemographicData model
        if (group.likert_demographic_data) {
            const gender = group.likert_demographic_data.gender
            if (gender === 'male') return 'masculino'
            if (gender === 'female') return 'femenino'
            return 'sin_genero'
        }
        return 'sin_genero'
    }
    
    // For other evaluations, get from Referencia V demographic_data
    if (!group.demographic_data || !group.demographic_data.sexo) {
        return 'sin_genero'
    }
    
    const sexo = group.demographic_data.sexo.toLowerCase()
    
    if (sexo === 'masculino') {
        return 'masculino'
    } else if (sexo === 'femenino') {
        return 'femenino'
    }
    
    return 'sin_genero'
}


// Computed filtered evaluations
const filteredEvaluationGroups = computed(() => {
    return props.evaluationGroups.filter(group => {
        const folio = String(group.personal_folio || '')
        const matchesSearch = !filters.value.search || 
            folio.toLowerCase().includes(filters.value.search.toLowerCase())
        
        const matchesGender = !filters.value.gender || 
            getGenderFromGroup(group) === filters.value.gender
        
        const matchesEvaluationType = !filters.value.evaluationType || 
            group.evaluation_types.includes(filters.value.evaluationType)
        
        return matchesSearch && matchesGender && matchesEvaluationType
    })
})

const formatEvaluationType = (type) => {
    const types = {
        'referencia_i': 'Guía I',
        'referencia_iii': 'Guía III',
        'referencia_v': 'Guía V',
    'cisneros': 'Cisneros',
    'likert': 'Clima Laboral'
    }
    return types[type] || type
}

const formatSource = (source) => {
    const sources = {
        'paper': 'Papel',
        'online': 'En Línea'
    }
    return sources[source] || source
}

const getBadgeClass = (type) => {
    const classes = {
        'referencia_i': 'bg-purple-100 text-purple-800',
        'referencia_iii': 'bg-blue-100 text-blue-800',
        'referencia_v': 'bg-green-100 text-green-800',
        'cisneros': 'bg-red-100 text-red-800',
        'likert': 'bg-yellow-100 text-yellow-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getSourceBadgeClass = (source) => {
    const classes = {
        'paper': 'bg-gray-100 text-gray-800',
        'online': 'bg-emerald-100 text-emerald-800'
    }
    return classes[source] || 'bg-gray-100 text-gray-800'
}

const handleBulkUpdateSuccess = () => {
    // Reload the page to show updated data
    window.location.reload()
}
</script>
