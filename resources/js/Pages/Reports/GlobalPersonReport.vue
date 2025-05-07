<template>
    <Dashboard :title="title">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Subtabs para los diferentes informes -->
                <div class="border-b border-gray-200 mb-6">
                    <div class="flex flex-wrap -mb-px">
                        <button 
                            @click="currentTab = 'globalCounts'" 
                            :class="[
                                'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                currentTab === 'globalCounts' 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            Conteo Global de Personas
                        </button>
                        <button 
                            @click="currentTab = 'categoryResponses'" 
                            :class="[
                                'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                currentTab === 'categoryResponses' 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Personas por Categoría
                        </button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Vista de conteo global de personas -->
                    <div v-if="currentTab === 'globalCounts'">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Conteo Global de Personas por Tipo de Respuesta</h3>
                        
                        <div v-if="loadingGlobalPersons" class="flex justify-center items-center h-64">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                            <span class="ml-3 text-gray-600">Cargando datos...</span>
                        </div>
                        
                        <div v-else-if="globalPersonCounts">
                            <GlobalPersonChart :personData="globalPersonCounts" />
                            
                            <!-- Interpretación y Contexto -->
                            <div class="bg-white p-6 rounded-lg shadow mt-6">
                                <h4 class="text-md font-medium text-gray-700 mb-3">Interpretación</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    Este reporte muestra el número de personas únicas que han seleccionado cada tipo de respuesta (A-E) 
                                    en al menos una pregunta de la encuesta. Una misma persona puede haber elegido diferentes opciones 
                                    en distintas preguntas, por lo que la suma puede exceder el número total de personas evaluadas.
                                </p>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                                    <div class="flex">
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                Las opciones de respuesta representan distintos niveles de frecuencia: 
                                                Siempre (A), Casi siempre (B), Algunas veces (C), Casi nunca (D) y Nunca (E).
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                            No hay datos disponibles para mostrar.
                        </div>
                    </div>

                    <!-- Vista de personas por categoría -->
                    <div v-else-if="currentTab === 'categoryResponses'">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Análisis de Personas por Categoría</h3>
                        
                        <div v-if="loadingCategoryPersons" class="flex justify-center items-center h-64">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                            <span class="ml-3 text-gray-600">Cargando datos...</span>
                        </div>
                        
                        <div v-else-if="categoryPersonCounts && categoryPersonCounts.length > 0">
                            <CategoryPersonAnalysisChart :categoryData="categoryPersonCounts" />
                            
                            <!-- Interpretación y Contexto -->
                            <div class="bg-white p-6 rounded-lg shadow mt-6">
                                <h4 class="text-md font-medium text-gray-700 mb-3">Interpretación</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    Este reporte muestra el número de personas únicas que han seleccionado cada tipo de respuesta 
                                    en al menos una pregunta de cada categoría. Esto permite identificar en qué categorías hay un mayor 
                                    número de personas que experimentan situaciones de riesgo psicosocial.
                                </p>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                                    <div class="flex">
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                Las categorías con mayor número de personas seleccionando las opciones "Siempre" o "Casi siempre" 
                                                podrían requerir intervención prioritaria en el plan de acción.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                            No hay datos disponibles para mostrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import GlobalPersonChart from '../../Components/GlobalPersonChart.vue';
import CategoryPersonAnalysisChart from '../../Components/CategoryPersonAnalysisChart.vue';
import { onMounted, ref } from 'vue';

// Props desde el controlador
const props = defineProps({
    globalPersonCounts: {
        type: Object,
        required: false,
        default: null
    },
    categoryPersonCounts: {
        type: Array,
        required: false,
        default: () => []
    },
    title: {
        type: String,
        default: 'Análisis Global de Personas'
    }
});

// Estado para la tab actual
const currentTab = ref('globalCounts');

// Estado de carga
const loadingGlobalPersons = ref(false);
const loadingCategoryPersons = ref(false);

// Cargar datos actualizados cuando sea necesario
const loadGlobalPersonCounts = async () => {
    loadingGlobalPersons.value = true;
    try {
        const response = await window.axios.get('/reports/global-person-counts');
        globalPersonCounts.value = response.data;
    } catch (error) {
        console.error('Error al cargar el conteo global de personas:', error);
    } finally {
        loadingGlobalPersons.value = false;
    }
};

const loadCategoryPersonCounts = async () => {
    loadingCategoryPersons.value = true;
    try {
        const response = await window.axios.get('/reports/category-person-counts');
        categoryPersonCounts.value = response.data;
    } catch (error) {
        console.error('Error al cargar el conteo de personas por categoría:', error);
    } finally {
        loadingCategoryPersons.value = false;
    }
};

// Datos que recibiremos del controlador si hay valores iniciales
const globalPersonCounts = ref(props.globalPersonCounts);
const categoryPersonCounts = ref(props.categoryPersonCounts);

// Cargar datos cuando cambie la tab
const handleTabChange = () => {
    if (currentTab.value === 'globalCounts' && !globalPersonCounts.value) {
        loadGlobalPersonCounts();
    } else if (currentTab.value === 'categoryResponses' && (!categoryPersonCounts.value || categoryPersonCounts.value.length === 0)) {
        loadCategoryPersonCounts();
    }
};

// Vigilar cambios en la tab
import { watch } from 'vue';
watch(currentTab, handleTabChange);

// Al cargar el componente
onMounted(() => {
    // Cargar datos solo si no se proporcionaron desde el servidor
    if (!globalPersonCounts.value && currentTab.value === 'globalCounts') {
        loadGlobalPersonCounts();
    } else if ((!categoryPersonCounts.value || categoryPersonCounts.value.length === 0) && currentTab.value === 'categoryResponses') {
        loadCategoryPersonCounts();
    }
});
</script>
