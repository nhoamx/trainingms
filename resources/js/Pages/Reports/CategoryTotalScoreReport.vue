<script setup>
import { ref, onMounted } from 'vue';
import Dashboard from "../../Layouts/Dashboard.vue";
import CategoryTotalScoreChart from '../../Components/CategoryTotalScoreChart.vue';
import CategoryTotalScoreTable from '../../Components/CategoryTotalScoreTable.vue';
import { ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    categoryTotalScores: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Puntuación Total por Categorías'
    }
});

// Estado para alternar entre vista de tabla y gráficas
const viewMode = ref('chart');

// Estado para manejar la carga de datos
const isLoading = ref(false);
const categoryData = ref(props.categoryTotalScores || []);

// Función para cargar los datos desde la API
const loadCategoryTotalScores = async () => {
    if (categoryData.value.length > 0) return;
    
    isLoading.value = true;
    try {
        const response = await window.axios.get('/reports/category-total-scores');
        categoryData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de puntuación por categoría:', error);
    } finally {
        isLoading.value = false;
    }
};

// Cargamos los datos al montar el componente
onMounted(() => {
    loadCategoryTotalScores();
});
</script>

<template>
    <Dashboard>
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ title }}</h1>
                <p class="text-gray-600">Visualización de la suma total de valores de respuesta por categoría ordenado por puntuación.</p>
                
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
                    <div v-if="categoryData.length === 0" class="bg-white p-6 rounded-lg shadow-lg text-center text-gray-500">
                        No hay datos disponibles para mostrar.
                    </div>
                    
                    <!-- Gráfico de barras horizontales -->
                    <CategoryTotalScoreChart v-else :category-scores="categoryData" />
                    
                    <!-- Interpretación y Contexto -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Interpretación</h3>
                        <p class="text-gray-700 mb-4">
                            Este reporte muestra la suma total de los valores de respuesta por cada categoría evaluada en la NOM-035-STPS-2018 (Guía III).
                            Las categorías están ordenadas de mayor a menor puntuación, lo que permite identificar rápidamente aquellas áreas que podrían
                            representar mayor riesgo psicosocial en el entorno laboral.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Una mayor puntuación indica un mayor nivel de riesgo percibido en esa categoría específica.
                                        Se recomienda prestar especial atención a las categorías con puntuaciones más altas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista de tabla -->
                <div v-else-if="viewMode === 'table'">
                    <CategoryTotalScoreTable :category-scores="categoryData" />
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
