<template>
    <Dashboard>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mis Centros de Trabajo</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Selecciona un centro de trabajo para ver su información
                    </p>
                </div>

            <!-- Work Centers Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="workCenter in workCenters"
                    :key="workCenter.id"
                    class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition-shadow duration-200"
                >
                    <div class="p-6">
                        <!-- Work Center Icon/Type Badge -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getTypeColor(workCenter.work_center_type)"
                            >
                                {{ getTypeLabel(workCenter.work_center_type) }}
                            </span>
                        </div>

                        <!-- Work Center Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                {{ workCenter.name }}
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Código: {{ workCenter.code }}
                            </p>

                            <!-- Organization -->
                            <div class="flex items-center text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>{{ workCenter.organization_name }}</span>
                            </div>

                            <!-- Primary Badge -->
                            <div v-if="workCenter.is_primary" class="mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Centro Principal
                                </span>
                            </div>

                            <!-- Evaluations Count -->
                            <div class="mt-3 text-sm text-gray-500">
                                <span class="font-medium">{{ workCenter.paper_evaluations_count }}</span> 
                                {{ workCenter.paper_evaluations_count === 1 ? 'evaluación' : 'evaluaciones' }}
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 flex flex-col space-y-2">
                                <!-- Ver Evaluaciones -->
                                <a
                                    :href="workCenter.has_evaluations ? '#' : '#'"
                                    :class="[
                                        'inline-flex items-center justify-center px-4 py-2 border rounded-md text-sm font-medium transition-colors duration-200',
                                        workCenter.has_evaluations
                                            ? 'border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 cursor-pointer'
                                            : 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed'
                                    ]"
                                    :disabled="!workCenter.has_evaluations"
                                    @click.prevent="workCenter.has_evaluations && handleViewEvaluations(workCenter)"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    Ver Evaluaciones
                                </a>

                                <!-- Ver Reporte -->
                                <a
                                    :href="workCenter.has_evaluations ? '#' : '#'"
                                    :class="[
                                        'inline-flex items-center justify-center px-4 py-2 border rounded-md text-sm font-medium transition-colors duration-200',
                                        workCenter.has_evaluations
                                            ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100 cursor-pointer'
                                            : 'border-gray-200 text-gray-400 bg-gray-50 cursor-not-allowed'
                                    ]"
                                    :disabled="!workCenter.has_evaluations"
                                    @click.prevent="workCenter.has_evaluations && handleViewReport(workCenter)"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Ver Reporte
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="workCenters.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay centros de trabajo asignados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Contacta con el administrador para que te asigne centros de trabajo.
                </p>
            </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import Dashboard from '@/Layouts/Dashboard.vue';

const props = defineProps({
    workCenters: {
        type: Array,
        required: true,
    },
});

const workCenterTypes = {
    'headquarters': 'Matriz',
    'planta': 'Planta',
    'sucursal': 'Sucursal',
    'almacen': 'Almacén',
    'oficina': 'Oficina',
    'plaza': 'Plaza',
    'otro': 'Otro',
};

const getTypeLabel = (type) => {
    return workCenterTypes[type] || type;
};

const getTypeColor = (type) => {
    const colors = {
        'headquarters': 'bg-purple-100 text-purple-800',
        'planta': 'bg-blue-100 text-blue-800',
        'sucursal': 'bg-green-100 text-green-800',
        'almacen': 'bg-yellow-100 text-yellow-800',
        'oficina': 'bg-indigo-100 text-indigo-800',
        'plaza': 'bg-pink-100 text-pink-800',
        'otro': 'bg-gray-100 text-gray-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const handleViewEvaluations = (workCenter) => {
    // TODO: Implementar navegación a vista de evaluaciones
    console.log('Ver evaluaciones del centro:', workCenter.name);
};

const handleViewReport = (workCenter) => {
    // TODO: Implementar navegación a vista de reportes
    console.log('Ver reporte del centro:', workCenter.name);
};
</script>
