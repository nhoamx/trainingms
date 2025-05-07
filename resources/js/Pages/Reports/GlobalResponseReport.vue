<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import { ref, watch, onMounted } from 'vue';
import { ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline'
import GlobalResponseChart from '../../Components/GlobalResponseChart.vue';
import CategoryResponseAnalysisChart from '../../Components/CategoryResponseAnalysisChart.vue';

const props = defineProps({
  globalCounts: {
    type: Object,
    default: () => ({})
  },
  categoryCounts: {
    type: Array,
    default: () => []
  },
  title: {
    type: String,
    default: 'Análisis Global de Respuestas'
  }
});

// Subtabs para los reportes globales
const activeTab = ref('globalCounts'); // 'globalCounts' o 'categoryResponses'

// Estado para el reporte de conteo global de respuestas
const globalViewMode = ref('chart');
const isLoadingGlobalCounts = ref(false);
const globalResponseCounts = ref(props.globalCounts);

// Estado para el reporte de conteo por categoría
const categoryResponseViewMode = ref('chart');
const isLoadingCategoryResponseCounts = ref(false);
const categoryResponseCounts = ref(props.categoryCounts);

// Cargamos los datos nuevamente si son necesarios
const loadGlobalResponseCounts = async () => {
  if (Object.keys(globalResponseCounts.value).length > 0) return;
  
  isLoadingGlobalCounts.value = true;
  try {
    const response = await window.axios.get('/reports/global-response-counts');
    globalResponseCounts.value = response.data;
  } catch (error) {
    console.error('Error al cargar los datos del conteo global:', error);
  } finally {
    isLoadingGlobalCounts.value = false;
  }
};

// Cargamos los datos del conteo por categoría si son necesarios
const loadCategoryResponseCounts = async () => {
  if (categoryResponseCounts.value.length > 0) return;
  
  isLoadingCategoryResponseCounts.value = true;
  try {
    const response = await window.axios.get('/reports/category-response-counts');
    categoryResponseCounts.value = response.data;
  } catch (error) {
    console.error('Error al cargar los datos de conteo por categoría:', error);
  } finally {
    isLoadingCategoryResponseCounts.value = false;
  }
};

// Cargar datos cuando cambie la pestaña
watch(activeTab, (newTab) => {
  if (newTab === 'globalCounts') {
    loadGlobalResponseCounts();
  } else if (newTab === 'categoryResponses') {
    loadCategoryResponseCounts();
  }
});

// Cargar datos al montar el componente
onMounted(() => {
  if (activeTab.value === 'globalCounts') {
    loadGlobalResponseCounts();
  } else if (activeTab.value === 'categoryResponses') {
    loadCategoryResponseCounts();
  }
});
</script>

<template>
  <Dashboard>
    <div class="space-y-6">
      <!-- Título y descripción -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900">{{ title }}</h2>
        <p class="mt-2 text-sm text-gray-500">
          Visualización y análisis del conteo global de respuestas y su distribución por categoría.
        </p>
      </div>
      
      <!-- Tabs para los diferentes reportes -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex">
            <button
              @click="activeTab = 'globalCounts'"
              :class="[
                'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm',
                activeTab === 'globalCounts' 
                  ? 'border-blue-500 text-blue-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Conteo Global de Respuestas
            </button>
            <button
              @click="activeTab = 'categoryResponses'"
              :class="[
                'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm',
                activeTab === 'categoryResponses' 
                  ? 'border-blue-500 text-blue-600' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Respuestas por Categoría
            </button>
          </nav>
        </div>
      </div>

      <!-- Contenido según la tab activa -->
      <div>
        <!-- Conteo global de respuestas por opción -->
        <div v-if="activeTab === 'globalCounts'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Conteo Global de Respuestas por Opción</h3>
            <div class="mt-2 sm:mt-0">
              <button 
                @click="globalViewMode = 'chart'" 
                :class="[
                  'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                  globalViewMode === 'chart' 
                    ? 'bg-blue-600 text-white border-blue-600' 
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                ]"
              >
                <ChartPieIcon class="h-5 w-5 mr-2" />
                Gráfica
              </button>
            </div>
          </div>
          
          <!-- Estado de carga -->
          <div v-if="isLoadingGlobalCounts" class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-gray-600">Cargando datos...</span>
          </div>
          
          <!-- Contenido del reporte -->
          <div v-else>
            <GlobalResponseChart :response-data="globalResponseCounts" />
          </div>
        </div>
        
        <!-- Conteo de respuestas por categoría y opción -->
        <div v-if="activeTab === 'categoryResponses'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Conteo de Respuestas por Categoría y Opción</h3>
            <div class="mt-2 sm:mt-0">
              <button 
                @click="categoryResponseViewMode = 'chart'" 
                :class="[
                  'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                  categoryResponseViewMode === 'chart' 
                    ? 'bg-blue-600 text-white border-blue-600' 
                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                ]"
              >
                <ChartPieIcon class="h-5 w-5 mr-2" />
                Gráfica
              </button>
            </div>
          </div>
          
          <!-- Estado de carga -->
          <div v-if="isLoadingCategoryResponseCounts" class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            <span class="ml-3 text-gray-600">Cargando datos...</span>
          </div>
          
          <!-- Contenido del reporte -->
          <div v-else>
            <CategoryResponseAnalysisChart :category-data="categoryResponseCounts" />
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
