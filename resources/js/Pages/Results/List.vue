<template>
    <Dashboard>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Evaluaciones - {{ organization.name }}</h2>
                        <p class="text-gray-600 mt-2">Evaluaciones agrupadas por folio personal</p>
                    </div>

                    <div v-if="evaluationGroups.length === 0" class="text-center py-12">
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
                                        Guías de Referencia
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Puntaje Total (Ref. III)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="group in evaluationGroups" :key="group.personal_folio" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ group.personal_folio }}</div>
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
                                            :href="route('organization.results.detail', { organization: organization.id, personalFolio: group.personal_folio })"
                                            class="text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            Ver Detalles
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue";

defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluationGroups: {
        type: Array,
        required: true
    }
})

const formatEvaluationType = (type) => {
    const types = {
        'referencia_i': 'Guía I',
        'referencia_iii': 'Guía III',
        'referencia_v': 'Guía V',
        'cisneros': 'Cisneros'
    }
    return types[type] || type
}

const getBadgeClass = (type) => {
    const classes = {
        'referencia_i': 'bg-purple-100 text-purple-800',
        'referencia_iii': 'bg-blue-100 text-blue-800',
        'referencia_v': 'bg-green-100 text-green-800',
        'cisneros': 'bg-red-100 text-red-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}
</script>
