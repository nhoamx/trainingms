<template>
    <Dashboard>
        <Head :title="`Evaluaciones En Línea - ${organization.name}`" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">
                                    Evaluaciones En Línea
                                </h2>
                                <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                            </div>
                            <div class="text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-full">
                                {{ evaluations.length }} evaluaciones
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
                                    placeholder="Buscar folio..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de quiz
                                </label>
                                <select
                                    v-model="filters.quizType"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Todos</option>
                                    <option value="Completo">Completo</option>
                                    <option value="Reducido">Reducido</option>
                                    <option value="Cisneros">Cisneros</option>
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
                                    <option value="Guía I">Guía I</option>
                                    <option value="Guía III">Guía III</option>
                                    <option value="Guía V">Guía V</option>
                                    <option value="Escala Cisneros">Escala Cisneros</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div v-if="filteredEvaluations.length === 0" class="text-center py-12 px-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-500 text-lg">No hay evaluaciones en línea registradas</p>
                        <p class="text-gray-400 text-sm mt-2">Las evaluaciones aparecerán aquí una vez que se completen</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Folio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Personal Folio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quiz
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Datos Básicos
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="evaluation in filteredEvaluations" :key="evaluation.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ evaluation.folio }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ evaluation.personal_folio }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ evaluation.quiz_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="getQuizTypeBadgeClass(evaluation.quiz_type)"
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{ evaluation.quiz_type }}
                                        </span>
                                        <div class="mt-1">
                                            <span 
                                                :class="getEvaluationTypeBadgeClass(evaluation.evaluation_type)"
                                                class="px-2 py-1 text-xs font-semibold rounded-full"
                                            >
                                                {{ evaluation.evaluation_type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <div>Sexo: {{ evaluation.sexo }}</div>
                                            <div>Edad: {{ evaluation.edad }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ evaluation.puesto }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ evaluation.completed_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <Link
                                            :href="route('organization.online-results.show', { 
                                                organizationId: organization.id, 
                                                id: evaluation.id 
                                            })"
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
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue"
import { ref, computed } from 'vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluations: {
        type: Array,
        required: true
    }
})

// Filters
const filters = ref({
    search: '',
    quizType: '',
    evaluationType: ''
})

// Computed filtered evaluations
const filteredEvaluations = computed(() => {
    return props.evaluations.filter(evaluation => {
        const matchesSearch = !filters.value.search || 
            evaluation.folio.toLowerCase().includes(filters.value.search.toLowerCase()) ||
            evaluation.personal_folio.toLowerCase().includes(filters.value.search.toLowerCase())
        
        const matchesQuizType = !filters.value.quizType || 
            evaluation.quiz_type === filters.value.quizType
        
        const matchesEvaluationType = !filters.value.evaluationType || 
            evaluation.evaluation_type === filters.value.evaluationType
        
        return matchesSearch && matchesQuizType && matchesEvaluationType
    })
})

const getQuizTypeBadgeClass = (type) => {
    const classes = {
        'Completo': 'bg-blue-100 text-blue-800',
        'Reducido': 'bg-yellow-100 text-yellow-800',
        'Cisneros': 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getEvaluationTypeBadgeClass = (type) => {
    const classes = {
        'Guía I': 'bg-green-100 text-green-800',
        'Guía III': 'bg-indigo-100 text-indigo-800',
        'Guía V': 'bg-pink-100 text-pink-800',
        'Escala Cisneros': 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}
</script>
