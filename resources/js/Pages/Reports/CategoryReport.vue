<script setup>
import { ref, onMounted } from 'vue';
import Dashboard from "../../Layouts/Dashboard.vue";
import BarChart from '../../Components/BarChart.vue';
import CategoryResponseTable from '../../Components/CategoryResponseTable.vue';
import { ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    categoryDistribution: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: 'Reporte por Categorías'
    }
});

// Estado para alternar entre vista de tabla y gráficas
const viewMode = ref('chart');

// Estado para manejar la carga de datos
const isLoading = ref(false);
const categoryData = ref(props.categoryDistribution || []);

// Función para cargar los datos desde el API
const loadCategoryData = async () => {
    if (categoryData.value.length > 0) return;
    
    isLoading.value = true;
    try {
        const response = await window.axios.get('/reports/category-distribution');
        categoryData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de categorías:', error);
    } finally {
        isLoading.value = false;
    }
};

// Cargamos los datos al montar el componente
onMounted(() => {
    loadCategoryData();
});
</script>

<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ title }}</h1>
                <p class="text-gray-600">Visualización de la distribución de respuestas por categoría y tipo de respuesta.</p>
                
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
                    <div v-if="categoryData.length === 0" class="bg-white p-6 rounded-lg shadow-lg text-center text-gray-500">
                        No hay datos disponibles para mostrar.
                    </div>
                    
                    <!-- Gráfico general con todas las categorías -->
                    <BarChart :category-data="categoryData" />
                    
                    <!-- Gráficos individuales por categoría, 2 por fila -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <div v-for="(category, index) in categoryData" :key="index" class="bg-white p-6 rounded-lg shadow-lg">
                            <h3 class="text-lg font-semibold mb-4">{{ category.name }}</h3>
                            
                            <!-- Gráfico de barras para cada categoría individual -->
                            <div class="h-80">
                                <BarChart :data="category" type="category" />
                            </div>
                            
                            <!-- Resumen numérico -->
                            <div class="mt-4 grid grid-cols-5 gap-2">
                                <div v-for="(count, type) in category.responses" :key="type" 
                                    :class="[
                                        'text-center p-2 rounded-md',
                                        type === 'A' ? 'bg-red-100 text-red-800' : 
                                        type === 'B' ? 'bg-orange-100 text-orange-800' :
                                        type === 'C' ? 'bg-yellow-100 text-yellow-800' :
                                        type === 'D' ? 'bg-green-100 text-green-800' :
                                        'bg-blue-100 text-blue-800'
                                    ]">
                                    <div class="text-xs font-medium mb-1">
                                        {{ type === 'A' ? 'Siempre' : 
                                           type === 'B' ? 'Casi siempre' : 
                                           type === 'C' ? 'Algunas veces' : 
                                           type === 'D' ? 'Casi nunca' : 'Nunca' }}
                                    </div>
                                    <div class="font-bold">{{ count }}</div>
                                    <div class="text-xs">{{ category.percentages[type] }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vista de tabla -->
                <div v-else-if="viewMode === 'table'">
                    <CategoryResponseTable :category-data="categoryData" />
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
