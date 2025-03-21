<script setup>
import Dashboard from "../Layouts/Dashboard.vue";
import { Link } from '@inertiajs/vue3';
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import DemographicCharts from '../Components/DemographicCharts.vue';
import AdvancedReports from '../Components/AdvancedReports.vue';
import { computed, ref } from 'vue';

import { defineProps } from 'vue';

const props = defineProps({
    evaluations: {
        type: Array,
        default: () => []
    },
    organizations: {
        type: Array,
        default: () => []
    },
    demographic_data: {
        type: Object,
        default: () => ({})
    },
    question_reports: {
        type: Object,
        default: () => ({})
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
});

// Convertir el objeto demographic_data a array
const demographicDataArray = computed(() => {
    return Object.values(props.demographic_data || {});
});

// Filtrar solo evaluaciones de guía III
const guideIIIEvaluations = computed(() => {
    return props.evaluations.filter(evaluation => evaluation.reference_guide === 'III');
});

// Filtrar organizaciones para mostrar solo evaluaciones de guía III
const organizationsWithGuideIII = computed(() => {
    if (!props.organizations) return [];

    return props.organizations.map(org => ({
        ...org,
        evaluations: org.evaluations.filter(evaluation => evaluation.reference_guide === 'III')
    }));
});

// Debug logs
console.log('demographic_data length:', demographicDataArray.value.length);
console.log('question_reports:', props.question_reports);

// Definición de tabs del dashboard
const dashboardTabs = computed(() => {
    if (props.isAdmin || props.isSuperAdmin) {
        return [
            { key: 'evaluations', label: 'Evaluaciones' }
        ];
    }
    return [
        { key: 'analysis', label: 'Análisis' },
        { key: 'evaluations', label: 'Evaluaciones' }
    ];
});

// Tab activo del dashboard
const currentTab = ref(props.isAdmin || props.isSuperAdmin ? 'evaluations' : 'analysis');

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Dashboard>
        <div class="space-y-6">
            <!-- Tabs de navegación -->
            <div class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="!isAdmin && !isSuperAdmin" class="border-b border-gray-200">
                        <nav class="-mb-px flex">
                            <button
                                v-for="tab in dashboardTabs"
                                :key="tab.key"
                                @click="currentTab = tab.key"
                                :class="[
                                    currentTab === tab.key
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Contenido de las tabs -->
                <div class="space-y-6">
                    <!-- Tab de Análisis (solo visible para usuarios normales) -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'analysis'" class="space-y-6">
                        <div v-if="question_reports && Object.keys(question_reports).length > 0">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Análisis por Datos Demográficos</h3>
                            <AdvancedReports :question-reports="question_reports" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            No hay datos suficientes para generar reportes
                        </div>
                    </div>

                    <!-- Tab de Evaluaciones -->
                    <div v-show="currentTab === 'evaluations' || isAdmin || isSuperAdmin" class="space-y-6">
                        <!-- Para usuarios de organización -->
                        <div v-if="!isAdmin && !isSuperAdmin">
                            <div v-if="guideIIIEvaluations.length > 0">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div v-for="evaluation in guideIIIEvaluations"
                                         :key="evaluation.id"
                                         class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-300">
                                        <div class="border-b border-gray-200 bg-sky-50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    Folio: {{ evaluation.folio }}
                                                </h3>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-blue-800">
                                                    Guía III
                                                </span>
                                            </div>
                                        </div>
                                        <div class="px-4 py-4 sm:px-6">
                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">Completada</dd>
                                                </div>
                                            </dl>
                                            <div class="mt-4 flex justify-end">
                                                <Link
                                                    :href="route('organization.results.detail', {
                                                        organization: evaluation.organization_id,
                                                        evaluation: evaluation.id
                                                    })"
                                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                >
                                                    <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-500" />
                                                    Ver detalle
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-center py-4">
                                No hay evaluaciones registradas
                            </div>
                        </div>

                        <!-- Para admin/superadmin -->
                        <div v-else class="space-y-8">
                            <div v-for="organization in organizationsWithGuideIII" :key="organization.id">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">{{ organization.name }}</h3>
                                <div v-if="organization.evaluations.length > 0">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div v-for="evaluation in organization.evaluations"
                                             :key="evaluation.id"
                                             class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-300">
                                            <div class="border-b border-gray-200 bg-sky-50 px-4 py-3">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-lg font-semibold text-gray-900">
                                                        Folio: {{ evaluation.folio }}
                                                    </h3>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-blue-800">
                                                        Guía III
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="px-4 py-4 sm:px-6">
                                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                    <div class="sm:col-span-1">
                                                        <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                                                    </div>
                                                    <div class="sm:col-span-1">
                                                        <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                                        <dd class="mt-1 text-sm text-gray-900">Completada</dd>
                                                    </div>
                                                </dl>
                                                <div class="mt-4 flex justify-end">
                                                    <Link
                                                        :href="route('organization.results.detail', {
                                                            organization: organization.id,
                                                            evaluation: evaluation.id
                                                        })"
                                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                    >
                                                        <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-500" />
                                                        Ver detalle
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 text-center py-4">
                                    No hay evaluaciones registradas para esta organización
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>

</style>
