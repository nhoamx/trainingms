<script setup>
import { ref, onMounted } from 'vue';
import Dashboard from "../../Layouts/Dashboard.vue";
import DomainTotalScoreChart from '../../Components/DomainTotalScoreChart.vue';
import DomainTotalScoreTable from '../../Components/DomainTotalScoreTable.vue';
import { ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    domainTotalScores: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Puntuación Total por Dominios'
    }
});

// Estado para alternar entre vista de tabla y gráficas
const viewMode = ref('chart');

// Estado para manejar la carga de datos
const isLoading = ref(false);
const domainData = ref(props.domainTotalScores || []);

// Función para cargar los datos desde la API
const loadDomainTotalScores = async () => {
    if (domainData.value.length > 0) return;
    
    isLoading.value = true;
    try {
        const response = await window.axios.get('/reports/domain-total-scores');
        domainData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de puntuación por dominio:', error);
    } finally {
        isLoading.value = false;
    }
};

// Cargamos los datos al montar el componente
onMounted(() => {
    loadDomainTotalScores();
});
</script>

<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ title }}</h1>
                <p class="text-gray-600">Visualización de la suma total de valores de respuesta por dominio ordenado por puntuación.</p>
                
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
                        Gráfica
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
                    <div v-if="domainData.length === 0" class="bg-white p-6 rounded-lg shadow-lg text-center text-gray-500">
                        No hay datos disponibles para mostrar.
                    </div>
                    
                    <!-- Gráfico de barras horizontales -->
                    <DomainTotalScoreChart v-else :domain-scores="domainData" />
                    
                    <!-- Interpretación y Contexto -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Interpretación</h3>
                        <p class="text-gray-700 mb-4">
                            Este reporte muestra la suma total de los valores de respuesta por cada dominio evaluado en la NOM-035-STPS-2018 (Guía III).
                            Los dominios están ordenados de mayor a menor puntuación, lo que permite identificar rápidamente aquellos que podrían
                            representar mayor riesgo psicosocial en el entorno laboral.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Una mayor puntuación indica un mayor nivel de riesgo percibido en ese dominio específico.
                                        Se recomienda prestar especial atención a los dominios con puntuaciones más altas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista de tabla -->
                <div v-else-if="viewMode === 'table'" class="my-4">
                    <DomainTotalScoreTable :domain-scores="domainData" />
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
