<script setup>
import { ref, onMounted } from 'vue';
import Dashboard from "../../Layouts/Dashboard.vue";
import DimensionBarChart from '../../Components/DimensionBarChart.vue';
import DimensionResponseTable from '../../Components/DimensionResponseTable.vue';
import { ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    dimensionDistribution: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Reporte por Dimensiones'
    }
});

// Estado para alternar entre vista de tabla y gráficas
const viewMode = ref('chart');

// Estado para manejar la carga de datos
const isLoading = ref(false);
const dimensionData = ref(props.dimensionDistribution || []);

// Función para cargar los datos desde el API
const loadDimensionData = async () => {
    if (dimensionData.value.length > 0) return;
    
    isLoading.value = true;
    try {
        const response = await window.axios.get('/reports/dimension-distribution');
        dimensionData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de dimensiones:', error);
    } finally {
        isLoading.value = false;
    }
};

// Cargamos los datos al montar el componente
onMounted(() => {
    loadDimensionData();
});
</script>

<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ title }}</h1>
                <p class="text-gray-600">Visualización de la distribución de respuestas por dimensión y tipo de respuesta.</p>
                
                <!-- Botones para cambiar el modo de visualización -->
                <div class="flex items-center justify-end mt-4 space-x-2">
                    <button 
                        @click="viewMode = 'chart'" 
                        :class="[
                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                            viewMode === 'chart' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <ChartPieIcon class="h-5 w-5 mr-2" />
                        Gráficas
                    </button>
                    <button 
                        @click="viewMode = 'table'" 
                        :class="[
                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                            viewMode === 'table' 
                                ? 'bg-blue-600 text-white border-blue-600' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                        ]"
                    >
                        <TableCellsIcon class="h-5 w-5 mr-2" />
                        Tabla
                    </button>
                </div>
            </div>
            
            <!-- Estado de carga -->
            <div v-if="isLoading" class="flex justify-center items-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
            
            <!-- Contenido del reporte -->
            <div v-else>
                <!-- Vista de gráficas -->
                <div v-if="viewMode === 'chart'" class="space-y-8">
                    <div v-if="dimensionData.length === 0" class="bg-white p-6 rounded-lg shadow-lg text-center text-gray-500">
                        No hay datos disponibles para mostrar.
                    </div>
                    
                    <!-- Gráficos individuales por dimensión, 2 por fila -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <div v-for="(dimension, index) in dimensionData" :key="index" class="bg-white p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-semibold mb-4">{{ dimension.name }}</h3>
                            
                            <!-- Resumen numérico -->
                            <div class="grid grid-cols-5 gap-2 mb-4">
                                <div v-for="(count, type) in dimension.responses" :key="type" 
                                    :style="{
                                        backgroundColor: type === 'A' ? '#F44336' : 
                                                         type === 'B' ? '#FFB300' :
                                                         type === 'C' ? '#FFEB3B' :
                                                         type === 'D' ? '#8BC34A' : 
                                                         '#4DD0C6',
                                        color: ['C', 'E'].includes(type) ? '#000' : '#fff'
                                    }"
                                    class="text-center p-2 rounded-md">
                                    <div class="text-xs font-medium mb-1" :style="{ color: ['C', 'E'].includes(type) ? '#000' : '#fff' }">
                                        {{ type === 'A' ? 'Siempre' : 
                                           type === 'B' ? 'Casi siempre' : 
                                           type === 'C' ? 'Algunas veces' : 
                                           type === 'D' ? 'Casi nunca' : 'Nunca' }}
                                    </div>
                                    <div class="font-bold">{{ count }}</div>
                                    <div class="text-xs">{{ dimension.percentages[type].toFixed(1) }}%</div>
                                </div>
                            </div>
                            
                            <!-- Gráfico para dimensión individual -->
                            <div class="h-60 relative">
                                <DimensionBarChart :dimension="dimension" />
                            </div>
                            
                            <!-- Enlace a lista de personal -->
                            <div class="mt-4 text-right">
                                <a :href="route('reports.peopleListDimension', { dimensionId: dimension.id, answerKey: 'A' })" 
                                   target="_blank"
                                   class="text-sm text-blue-600 hover:underline">
                                    Ver personal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista de tabla -->
                <div v-else-if="viewMode === 'table'" class="my-4">
                    <DimensionResponseTable :dimension-data="dimensionData" />
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
