<template>
    <Dashboard>
        <Head :title="`Reporte Agregado - ${organization.name}`" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">
                                    Reporte Agregado de Evaluaciones
                                </h2>
                                <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                            </div>
                            <Link 
                                :href="route('organization.online-results', { id: organization.id })"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                            >
                                ← Volver a Lista
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Evaluaciones
                                        </dt>
                                        <dd class="text-3xl font-bold text-gray-900">
                                            {{ stats.total }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Quiz Completo
                                        </dt>
                                        <dd class="text-3xl font-bold text-gray-900">
                                            {{ stats.por_tipo_quiz.completo }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Quiz Reducido
                                        </dt>
                                        <dd class="text-3xl font-bold text-gray-900">
                                            {{ stats.por_tipo_quiz.reducido }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Cisneros
                                        </dt>
                                        <dd class="text-3xl font-bold text-gray-900">
                                            {{ stats.por_tipo_quiz.cisneros }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acontecimientos Traumáticos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Acontecimientos Traumáticos Severos (NOM-035)
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <div class="text-sm text-amber-700 font-medium">Personal con eventos traumáticos</div>
                                <div class="text-3xl font-bold text-amber-900 mt-2">
                                    {{ stats.acontecimientos_traumaticos.total_con_eventos }} 
                                    <span class="text-lg text-amber-700">
                                        ({{ stats.acontecimientos_traumaticos.porcentaje }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="text-sm text-green-700 font-medium">Personal sin eventos traumáticos</div>
                                <div class="text-3xl font-bold text-green-900 mt-2">
                                    {{ stats.total - stats.acontecimientos_traumaticos.total_con_eventos }} 
                                    <span class="text-lg text-green-700">
                                        ({{ 100 - stats.acontecimientos_traumaticos.porcentaje }}%)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfica de barras de tipos de eventos -->
                        <div v-if="stats.acontecimientos_traumaticos.tipos_eventos.length > 0">
                            <h4 class="text-md font-semibold text-gray-700 mb-4">Tipos de Acontecimientos Reportados</h4>
                            <div class="space-y-3">
                                <div v-for="(evento, index) in stats.acontecimientos_traumaticos.tipos_eventos" :key="index" class="flex items-center">
                                    <div class="flex-1 mr-4">
                                        <div class="text-sm text-gray-700 mb-1">{{ evento.evento }}</div>
                                        <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                                            <div 
                                                class="bg-amber-500 h-6 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                                :style="{ width: `${evento.porcentaje}%` }"
                                            >
                                                <span v-if="evento.porcentaje > 10" class="text-xs font-semibold text-white">
                                                    {{ evento.cantidad }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm font-semibold text-gray-600 w-20 text-right">
                                        {{ evento.porcentaje }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No se reportaron acontecimientos traumáticos
                        </div>
                    </div>
                </div>

                <!-- Distribución Demográfica -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Por Género -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Distribución por Género</h3>
                            <div class="space-y-2">
                                <div v-for="(count, gender) in stats.por_genero" :key="gender" class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm font-medium text-gray-700">{{ gender }}</span>
                                    <span class="text-sm font-bold text-gray-900">{{ count }} ({{ getPercentage(count, stats.total) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Por Edad -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Distribución por Edad</h3>
                            <div class="space-y-2">
                                <div v-for="(count, age) in stats.por_edad" :key="age" class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm font-medium text-gray-700">{{ age }}</span>
                                    <span class="text-sm font-bold text-gray-900">{{ count }} ({{ getPercentage(count, stats.total) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Por Tipo de Puesto -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Distribución por Puesto</h3>
                            <div class="space-y-2">
                                <div v-for="(count, puesto) in stats.por_puesto" :key="puesto" class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span class="text-sm font-medium text-gray-700 truncate" :title="puesto">{{ puesto }}</span>
                                    <span class="text-sm font-bold text-gray-900 ml-2">{{ count }} ({{ getPercentage(count, stats.total) }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de exportación (futuro) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-600 mb-4">Funcionalidades adicionales próximamente</p>
                        <div class="flex justify-center gap-4">
                            <button 
                                class="px-6 py-2 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed"
                                disabled
                            >
                                📊 Exportar a Excel
                            </button>
                            <button 
                                class="px-6 py-2 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed"
                                disabled
                            >
                                📄 Generar PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from '@/Layouts/Dashboard.vue'

const props = defineProps({
    organization: Object,
    stats: Object
})

const getPercentage = (count, total) => {
    if (total === 0) return 0
    return Math.round((count / total) * 100)
}
</script>
