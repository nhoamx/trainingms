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

// NUEVO: Mapeo de niveles de riesgo a tipos de respuesta para visualización
const riskToType = {
    'Muy Alto': 'A',
    'Alto': 'B',
    'Medio': 'C',
    'Bajo': 'D',
    'Nulo': 'E',
};
const typeToRisk = {
    'A': 'Muy Alto',
    'B': 'Alto',
    'C': 'Medio',
    'D': 'Bajo',
    'E': 'Nulo',
};

// Estado para alternar entre vista de tabla y gráficas
const viewMode = ref('chart');

// Estado para manejar la carga de datos
const isLoading = ref(false);
// Adaptar la estructura de datos para la visualización (igual que dominios)
const dimensionData = ref(
    (props.dimensionDistribution || []).map((dim, idx) => {
        // dim: { dimension_name, risk_levels }
        const responses = {
            'A': dim.risk_levels['Muy Alto'] || 0,
            'B': dim.risk_levels['Alto'] || 0,
            'C': dim.risk_levels['Medio'] || 0,
            'D': dim.risk_levels['Bajo'] || 0,
            'E': dim.risk_levels['Nulo'] || 0,
        };
        const total = Object.values(responses).reduce((a, b) => a + b, 0);
        const percentages = {};
        Object.entries(responses).forEach(([type, count]) => {
            percentages[type] = total > 0 ? (count / total) * 100 : 0;
        });
        return {
            id: idx,
            name: dim.dimension_name,
            responses,
            percentages,
            total
        };
    })
);

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
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                            <!-- Resumen numérico por nivel de riesgo -->
                            <div class="grid grid-cols-5 gap-2 mb-4">
                                <div v-for="risk in ['Nulo','Bajo','Medio','Alto','Muy Alto']" :key="risk"
                                    :style="{
                                        backgroundColor: risk === 'Nulo' ? '#4DD0C6' :
                                                         risk === 'Bajo' ? '#8BC34A' :
                                                         risk === 'Medio' ? '#FFEB3B' :
                                                         risk === 'Alto' ? '#FFB300' :
                                                         '#F44336',
                                        color: risk === 'Medio' || risk === 'Nulo' ? '#000' : '#fff'
                                    }"
                                    class="text-center p-2 rounded-md">
                                    <div class="text-xs font-medium mb-1">{{ risk }}</div>
                                    <div class="font-bold">{{ dimension.responses[riskToType[risk]] || 0 }}</div>
                                    <div class="text-xs">{{ dimension.percentages[riskToType[risk]].toFixed(1) }}%</div>
                                </div>
                            </div>
                            <!-- Gráfico para dimensión individual -->
                            <div class="h-60 relative">
                                <DimensionBarChart :dimension="dimension" />
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
