<template>
    <Dashboard>
        <div class="py-8">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mis Centros de Trabajo</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Accede a los dashboards de NOM-035 de cada centro de trabajo asignado
                    </p>
                </div>

            <!-- Work Centers Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="workCenter in workCenters"
                    :key="workCenter.id"
                    class="bg-white overflow-hidden shadow-sm rounded-lg transition-all duration-200"
                    :class="workCenter.has_evaluations ? 'hover:shadow-lg hover:-translate-y-0.5' : 'hover:shadow-md'"
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
                            <h3 class="text-lg font-bold text-gray-900 mb-1 leading-tight">
                                {{ workCenter.name }}
                            </h3>
                            <div class="flex items-center gap-2 mb-3">
                                <p class="text-sm text-gray-500">
                                    {{ workCenter.code }}
                                </p>
                                <span v-if="workCenter.is_primary" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Principal
                                </span>
                            </div>

                            <!-- Organization -->
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="truncate">{{ workCenter.organization_name }}</span>
                            </div>

                            <!-- Stats Bar -->
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span>
                                            <span class="font-semibold text-gray-900">{{ workCenter.paper_evaluations_count }}</span>
                                            {{ workCenter.paper_evaluations_count === 1 ? 'evaluación' : 'evaluaciones' }}
                                        </span>
                                    </div>
                                    
                                    <!-- Status indicator -->
                                    <div v-if="workCenter.has_evaluations" class="flex items-center text-green-600">
                                        <span class="relative flex h-2 w-2 mr-1.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                        </span>
                                        <span class="text-xs font-medium">Activo</span>
                                    </div>
                                    <div v-else class="flex items-center text-gray-400">
                                        <span class="relative flex h-2 w-2 mr-1.5">
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-300"></span>
                                        </span>
                                        <span class="text-xs font-medium">Sin datos</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Primary Action -->
                            <div class="mt-4">
                                <button
                                    v-if="workCenter.has_evaluations"
                                    type="button"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    @click="handleViewDashboard(workCenter)"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Ver Dashboard NOM-035
                                </button>
                                <div
                                    v-else
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-50 text-gray-400 font-medium rounded-lg border-2 border-dashed border-gray-200"
                                >
                                    <svg class="w-5 h-5 mr-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <span class="text-xs">Sin evaluaciones disponibles</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="workCenters.length === 0" class="text-center py-16 px-4">
                <div class="max-w-md mx-auto">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        No tienes centros de trabajo asignados
                    </h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Para acceder a los dashboards de NOM-035 y visualizar las evaluaciones, 
                        necesitas que un administrador te asigne a uno o más centros de trabajo.
                    </p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                        <p class="text-sm text-blue-900">
                            <strong class="font-semibold">¿Qué puedes hacer?</strong><br>
                            Contacta a tu supervisor o administrador del sistema para solicitar acceso 
                            a los centros de trabajo correspondientes.
                        </p>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import Dashboard from '@/Layouts/Dashboard.vue';
import { router } from '@inertiajs/vue3';

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

const handleViewDashboard = (workCenter) => {
    router.visit(route('work-centers.dashboard.nom-035-index', workCenter.id));
};
</script>
