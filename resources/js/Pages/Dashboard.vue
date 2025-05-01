<script setup>
import Dashboard from "../Layouts/Dashboard.vue";
import { Link, usePage } from '@inertiajs/vue3';
import { DocumentTextIcon, ChartPieIcon, TableCellsIcon } from '@heroicons/vue/24/outline'
import CategoryQualificationTable from '../Components/CategoryQualificationTable.vue';
import CategoryDetailChart from '../Components/CategoryDetailChart.vue';
import DomainQualificationTable from '../Components/DomainQualificationTable.vue';
import DomainDetailChart from '../Components/DomainDetailChart.vue';
import DimensionQualificationTable from '../Components/DimensionQualificationTable.vue';
import DimensionDetailChart from '../Components/DimensionDetailChart.vue';
import DemographicChart from '../Components/DemographicChart.vue';
import CategoryStackedBarChart from '../Components/CategoryStackedBarChart.vue';
import CategoryResponseTable from '../Components/CategoryResponseTable.vue';
import CategoryBarChart from '../Components/CategoryBarChart.vue';
import CategoryTotalScoreChart from '../Components/CategoryTotalScoreChart.vue';
import CategoryTotalScoreTable from '../Components/CategoryTotalScoreTable.vue';
import DomainBarChart from '../Components/DomainBarChart.vue';
import DomainResponseTable from '../Components/DomainResponseTable.vue';
import DomainTotalScoreChart from '../Components/DomainTotalScoreChart.vue';
import DomainTotalScoreTable from '../Components/DomainTotalScoreTable.vue';
import DimensionBarChart from '../Components/DimensionBarChart.vue';
import DimensionResponseTable from '../Components/DimensionResponseTable.vue';
import DimensionTotalScoreChart from '../Components/DimensionTotalScoreChart.vue';
import DimensionTotalScoreTable from '../Components/DimensionTotalScoreTable.vue';
import { computed, ref, watch, onMounted } from 'vue';

const props = defineProps({
    evaluations: {
        type: Array,
        default: () => []
    },
    organizations: {
        type: Array,
        default: () => []
    },
    demographic_data: {
        type: Object,
        default: () => ({})
    },
    category_qualifications: {
        type: Array,
        default: () => []
    },
    domain_qualifications: {
        type: Array,
        default: () => []
    },
    demographic_distributions: {
        type: Array,
        default: () => ([])
    },
    isAdmin: {
        type: Boolean,
        default: false  
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
});

// Tab activo del dashboard - definido primero para evitar errores en los watchers y hooks
const currentTab = ref(props.isAdmin || props.isSuperAdmin ? 'evaluations' : 'globalAnalysis');

// Subtab activo para reportes de categoría
const categorySubTab = ref('distribution');

// State for selected category and domain data
const selectedCategoryId = ref(null);
const selectedCategoryName = ref('');
const categoryAnswerDistribution = ref({});
const isLoadingCategoryDetail = ref(false);

// Estado para el reporte de distribución de respuestas por categoría
const viewMode = ref('chart');
const isLoadingCategoryReport = ref(false);
const categoryReportData = ref([]);

// Estado para el reporte de puntuación total por categoría
const totalScoreViewMode = ref('chart');
const isLoadingCategoryTotalScores = ref(false);
const categoryTotalScores = ref([]);

// Subtab activo para reportes de dominio
const domainSubTab = ref('distribution');

// Estado para el reporte de distribución de respuestas por dominio
const domainViewMode = ref('chart');
const isLoadingDomainReport = ref(false);
const domainReportData = ref([]);

// Estado para el reporte de puntuación total por dominio
const domainTotalScoreViewMode = ref('chart');
const isLoadingDomainTotalScores = ref(false);
const domainTotalScores = ref([]);

// NEW: Subtab activo para reportes de dimensión
const dimensionSubTab = ref('distribution');

// Estado para el reporte de distribución de respuestas por dimensión
const dimensionViewMode = ref('chart');
const isLoadingDimensionReport = ref(false);
const dimensionDistributionData = ref([]);

// Estado para el reporte de puntuación total por dimensión
const dimensionTotalScoreViewMode = ref('chart');
const isLoadingDimensionTotalScores = ref(false);
const dimensionTotalScores = ref([]);

// NEW: Estado para el análisis global
const globalResponseSubTab = ref('globalCounts');

// Estado para el reporte de conteo global de respuestas
const globalViewMode = ref('chart');
const isLoadingGlobalCounts = ref(false);
const globalResponseCounts = ref({});

// Estado para el reporte de conteo por categoría
const categoryResponseViewMode = ref('chart');
const isLoadingCategoryResponseCounts = ref(false);
const categoryResponseCounts = ref([]);

// Función para cargar los datos de categorías para el nuevo reporte
const loadCategoryReportData = async () => {
    if (categoryReportData.value.length > 0) return;
    
    isLoadingCategoryReport.value = true;
    try {
        const response = await window.axios.get('/reports/category-distribution');
        categoryReportData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos del reporte de categorías:', error);
    } finally {
        isLoadingCategoryReport.value = false;
    }
};

// Función para cargar los datos del reporte de puntuación total por categoría
const loadCategoryTotalScores = async () => {
    if (categoryTotalScores.value.length > 0) return;
    
    isLoadingCategoryTotalScores.value = true;
    try {
        const response = await window.axios.get('/reports/category-total-scores');
        categoryTotalScores.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de puntuación total por categoría:', error);
    } finally {
        isLoadingCategoryTotalScores.value = false;
    }
};

// Función para cargar los datos de distribución de respuestas por dominio
const loadDomainReportData = async () => {
    if (domainReportData.value.length > 0) return;
    
    isLoadingDomainReport.value = true;
    try {
        const response = await window.axios.get('/reports/domain-distribution');
        domainReportData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos del reporte de dominios:', error);
    } finally {
        isLoadingDomainReport.value = false;
    }
};

// Función para cargar los datos del reporte de puntuación total por dominio
const loadDomainTotalScores = async () => {
    if (domainTotalScores.value.length > 0) return;
    
    isLoadingDomainTotalScores.value = true;
    try {
        const response = await window.axios.get('/reports/domain-total-scores');
        domainTotalScores.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de puntuación total por dominio:', error);
    } finally {
        isLoadingDomainTotalScores.value = false;
    }
};

// Función para cargar los datos de distribución de respuestas por dimensión
const loadDimensionReportData = async () => {
    if (dimensionDistributionData.value.length > 0) return;
    
    isLoadingDimensionReport.value = true;
    try {
        const response = await window.axios.get('/reports/dimension-distribution');
        dimensionDistributionData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos del reporte de dimensiones:', error);
    } finally {
        isLoadingDimensionReport.value = false;
    }
};

// Función para cargar los datos del reporte de puntuación total por dimensión
const loadDimensionTotalScores = async () => {
    if (dimensionTotalScores.value.length > 0) return;
    
    isLoadingDimensionTotalScores.value = true;
    try {
        const response = await window.axios.get('/reports/dimension-total-scores');
        dimensionTotalScores.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos de puntuación total por dimensión:', error);
    } finally {
        isLoadingDimensionTotalScores.value = false;
    }
};

// Función para cargar los datos del conteo global de respuestas
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

// Función para cargar los datos del conteo por categoría
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

// State for People List
const selectedAnswerKeyForList = ref(null); // 'A', 'B', 'C', 'D', 'E', 'INVALID'
const peopleList = ref([]);
const isLoadingPeopleList = ref(false);

const selectedDomainId = ref(null);
const selectedDomainName = ref('');
const domainAnswerDistribution = ref({});
const isLoadingDomainDetail = ref(false);
const dimensionReportData = ref([]);
const isLoadingDimensionData = ref(false);

// State for Domain Tab
const selectedCategoryIdForDomainTab = ref(null);

// NEW: State for Dimensions within Domain Tab
const dimensionQualifications = ref([]);
const selectedDimensionId = ref(null);
const selectedDimensionName = ref('');
const dimensionAnswerDistribution = ref({});
const isLoadingDimensionDetail = ref(false);

// Get category name from the initial report data
const getCategoryNameById = (id) => {
    const category = props.category_qualifications.find(cat => cat.id === id);
    return category ? category.name : 'Desconocida';
};

// Event handler for Category Button Clicks
const handleCategoryButtonClick = async (categoryId) => {
    console.log('Button clicked for Category ID:', categoryId);
    // Clear people list when category changes
    selectedAnswerKeyForList.value = null;
    peopleList.value = [];

    // If clicking the same category button, hide the detail chart & list
    if (selectedCategoryId.value === categoryId) {
        selectedCategoryId.value = null;
        selectedCategoryName.value = '';
        categoryAnswerDistribution.value = {};
        return;
    }

    selectedCategoryId.value = categoryId;
    selectedCategoryName.value = getCategoryNameById(categoryId);
    categoryAnswerDistribution.value = {};
    isLoadingCategoryDetail.value = true;

    // Reset lower level selections if they exist later
    // selectedDomainId.value = null;

    try {
        const apiUrl = `/dashboard/report/category-answer-distribution/${categoryId}`;
        const response = await window.axios.get(apiUrl);
        categoryAnswerDistribution.value = response.data;
        console.log('Fetched category answer distribution:', response.data);
    } catch (error) {
        console.error("Error fetching category distribution data:", error);
        selectedCategoryId.value = null; // Reset selection on error
    } finally {
        isLoadingCategoryDetail.value = false;
    }
};

// New handler for Buttons below the detail chart (shows people list)
const handleShowPeopleList = async (answerKey) => {
    if (!selectedCategoryId.value) return; // Need a category selected

    // If clicking the same button, hide the list
    if (selectedAnswerKeyForList.value === answerKey) {
        selectedAnswerKeyForList.value = null;
        peopleList.value = [];
        return;
    }

    selectedAnswerKeyForList.value = answerKey;
    peopleList.value = [];
    isLoadingPeopleList.value = true;

    try {
        const apiUrl = `/dashboard/report/people-with-answer/${selectedCategoryId.value}/${answerKey}`;
        const response = await window.axios.get(apiUrl);
        peopleList.value = response.data;
        console.log(`Fetched people for answer ${answerKey}:`, response.data);
    } catch (error) {
        console.error(`Error fetching people list for answer ${answerKey}:`, error);
        selectedAnswerKeyForList.value = null; // Reset on error
    } finally {
        isLoadingPeopleList.value = false;
    }
};

// Watch for category change to clear the people list view
watch(selectedCategoryId, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedAnswerKeyForList.value = null;
        peopleList.value = [];
    }
});

// Cargar datos cuando cambie la pestaña principal
watch(currentTab, (newTab) => {
    if (newTab === 'globalAnalysis') {
        // Cargamos los datos según la subtab actual de análisis global
        if (globalResponseSubTab.value === 'globalCounts') {
            loadGlobalResponseCounts();
        } else if (globalResponseSubTab.value === 'categoryResponses') {
            loadCategoryResponseCounts();
        }
    } else if (newTab === 'categoryAnalysis') {
        // Cargamos los datos según la subtab actual de categoría
        if (categorySubTab.value === 'distribution') {
            loadCategoryReportData();
        } else if (categorySubTab.value === 'totalScore') {
            loadCategoryTotalScores();
        }
    } else if (newTab === 'domainAnalysis') {
        // Cargamos los datos según la subtab actual de dominio
        if (domainSubTab.value === 'distribution') {
            loadDomainReportData();
        } else if (domainSubTab.value === 'totalScore') {
            loadDomainTotalScores();
        }
    } else if (newTab === 'dimensionAnalysis') {
        // Cargamos los datos según la subtab actual de dimensión
        if (dimensionSubTab.value === 'distribution') {
            loadDimensionReportData();
        } else if (dimensionSubTab.value === 'totalScore') {
            loadDimensionTotalScores();
        }
    } else if (newTab === 'demographicAnalysis') {
        // Cargar datos si es necesario
    }
});

// Cargar datos según la subtab de categoría seleccionada
watch(categorySubTab, (newSubTab) => {
    if (currentTab.value === 'categoryAnalysis') {
        if (newSubTab === 'distribution') {
            loadCategoryReportData();
        } else if (newSubTab === 'totalScore') {
            loadCategoryTotalScores();
        }
    }
});

// Cargar datos según la subtab de dominio seleccionada
watch(domainSubTab, (newSubTab) => {
    if (currentTab.value === 'domainAnalysis') {
        if (newSubTab === 'distribution') {
            loadDomainReportData();
        } else if (newSubTab === 'totalScore') {
            loadDomainTotalScores();
        }
    }
});

// Cargar datos según la subtab de dimensión seleccionada
watch(dimensionSubTab, (newSubTab) => {
    if (currentTab.value === 'dimensionAnalysis') {
        if (newSubTab === 'distribution') {
            loadDimensionReportData();
        } else if (newSubTab === 'totalScore') {
            loadDimensionTotalScores();
        }
    }
});

// Cargar datos según la subtab de análisis global seleccionada
watch(globalResponseSubTab, (newSubTab) => {
    if (currentTab.value === 'globalAnalysis') {
        if (newSubTab === 'globalCounts') {
            loadGlobalResponseCounts();
        } else if (newSubTab === 'categoryResponses') {
            loadCategoryResponseCounts();
        }
    }
});

// Cargar datos cuando se monte el componente según la pestaña activa
onMounted(() => {
    if (currentTab.value === 'globalAnalysis') {
        // Cargamos los datos según la subtab inicial de análisis global
        if (globalResponseSubTab.value === 'globalCounts') {
            loadGlobalResponseCounts();
        } else if (globalResponseSubTab.value === 'categoryResponses') {
            loadCategoryResponseCounts();
        }
    } else if (currentTab.value === 'categoryAnalysis') {
        // Cargamos los datos según la subtab inicial de categoría
        if (categorySubTab.value === 'distribution') {
            loadCategoryReportData();
        } else if (categorySubTab.value === 'totalScore') {
            loadCategoryTotalScores();
        }
    } else if (currentTab.value === 'domainAnalysis') {
        // Cargamos los datos según la subtab inicial de dominio
        if (domainSubTab.value === 'distribution') {
            loadDomainReportData();
        } else if (domainSubTab.value === 'totalScore') {
            loadDomainTotalScores();
        }
    } else if (currentTab.value === 'dimensionAnalysis') {
        // Cargamos los datos según la subtab inicial de dimensión
        if (dimensionSubTab.value === 'distribution') {
            loadDimensionReportData();
        } else if (dimensionSubTab.value === 'totalScore') {
            loadDimensionTotalScores();
        }
    } else if (currentTab.value === 'demographicAnalysis') {
        // Cargar datos si es necesario
    }
});

// Reusable mapping from answer key to label (used for buttons)
// Keep order consistent with with CategoryDetailChart
const answerKeyToLabelMap = {
    'E': 'Nulo',
    'D': 'Bajo',
    'C': 'Medio',
    'B': 'Alto',
    'A': 'Muy Alto',
    'INVALID': 'Inválido'
};

// Handle domain click event
const handleDomainButtonClick = async (domainId) => {
    console.log('Domain Button clicked:', domainId);
    if (selectedDomainId.value === domainId) {
        selectedDomainId.value = null;
        selectedDomainName.value = '';
        domainAnswerDistribution.value = {};
        dimensionQualifications.value = [];
        selectedDimensionId.value = null;
        dimensionAnswerDistribution.value = {};
        return;
    }
    selectedDomainId.value = domainId;
    selectedDomainName.value = getDomainNameById(domainId);
    domainAnswerDistribution.value = {};
    isLoadingDomainDetail.value = true;
    dimensionQualifications.value = [];
    isLoadingDimensionData.value = true;
    selectedDimensionId.value = null;
    dimensionAnswerDistribution.value = {};

    // Fetch domain answer distribution
    const distPromise = window.axios.get(`/dashboard/report/domain-answer-distribution/${domainId}`)
        .then(res => domainAnswerDistribution.value = res.data)
        .catch(err => console.error("Err fetch domain dist:", err))
        .finally(() => isLoadingDomainDetail.value = false);

    // Fetch dimension qualifications (for the table below domain chart)
    const dimQualPromise = window.axios.get(`/dashboard/report/dimension-qualifications/${domainId}`)
        .then(res => dimensionQualifications.value = res.data)
        .catch(err => console.error("Err fetch dim qual:", err))
        .finally(() => isLoadingDimensionData.value = false);

    try {
        await Promise.all([distPromise, dimQualPromise]); // Wait for both API calls
    } catch (error) {
        // Handle potential errors if needed, though individual catches exist
        selectedDomainId.value = null; // Reset on error
    }
};

// NEW: Handler for Dimension Button Click
const handleDimensionButtonClick = async (dimensionId) => {
    console.log('Dimension Button clicked:', dimensionId);
    if (selectedDimensionId.value === dimensionId) {
        selectedDimensionId.value = null;
        selectedDimensionName.value = '';
        dimensionAnswerDistribution.value = {};
        return;
    }
    selectedDimensionId.value = dimensionId;
    selectedDimensionName.value = getDimensionNameById(dimensionId);
    dimensionAnswerDistribution.value = {};
    isLoadingDimensionDetail.value = true;

    // Fetch dimension answer distribution
    try {
        const apiUrl = `/dashboard/report/dimension-answer-distribution/${dimensionId}`;
        const response = await window.axios.get(apiUrl);
        dimensionAnswerDistribution.value = response.data;
    } catch (error) {
        console.error("Error fetching dimension distribution:", error);
        selectedDimensionId.value = null;
    } finally {
        isLoadingDimensionDetail.value = false;
    }
};

// Convertir el objeto demographic_data a array
const demographicDataArray = computed(() => {
    return Object.values(props.demographic_data || {});
});

// Filtrar solo evaluaciones de guía III
const guideIIIEvaluations = computed(() => {
    return props.evaluations.filter(evaluation => evaluation.reference_guide === 'III');
});

// Filtrar organizaciones para mostrar solo evaluaciones de guía III
const organizationsWithGuideIII = computed(() => {
    if (!props.organizations) return [];

    return props.organizations.map(org => ({
        ...org,
        evaluations: org.evaluations.filter(evaluation => evaluation.reference_guide === 'III')
    }));
});

// Debug logs
console.log('demographic_data length:', demographicDataArray.value.length);
console.log('category_qualifications:', props.category_qualifications);
console.log('domain_qualifications:', props.domain_qualifications);

// Definición de tabs del dashboard
const dashboardTabs = computed(() => {
    if (props.isAdmin || props.isSuperAdmin) {
        return [
            { key: 'evaluations', label: 'Evaluaciones' }
        ];
    }
    return [
        { key: 'categoryAnalysis', label: 'Análisis por Categoría' },
        { key: 'domainAnalysis', label: 'Análisis por Dominio' },
        { key: 'dimensionAnalysis', label: 'Análisis por Dimensión' },
        { key: 'demographicAnalysis', label: 'Análisis Demográfico' },
        { key: 'evaluations', label: 'Evaluaciones' }
    ];
});

// Nota: currentTab ya está definido al inicio del script

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Helper functions to get names from data arrays
const getDomainNameById = (id) => props.domain_qualifications.find(dom => dom.id === id)?.name || '';

// NEW: Helper for Dimension Name
const getDimensionNameById = (id) => dimensionQualifications.value.find(dim => dim.id === id)?.name || '';

// NEW: Handler for selecting a CATEGORY within the Domain Tab
const handleSelectCategoryForDomains = (categoryId) => {
    console.log('Category selected for domain filtering:', categoryId);
    selectedCategoryIdForDomainTab.value = categoryId;
    selectedDomainId.value = null;
    domainAnswerDistribution.value = {};
    dimensionQualifications.value = [];
    selectedDimensionId.value = null;
    dimensionAnswerDistribution.value = {};
};

// Computed property to filter domains based on selected category
const filteredDomainQualifications = computed(() => {
    if (!selectedCategoryIdForDomainTab.value) {
        return []; // Or return all domains if you prefer: props.domain_qualifications;
    }
    return props.domain_qualifications.filter(domain => domain.category_id === selectedCategoryIdForDomainTab.value);
});

// Update watcher to reset domain and dimension tab category/domain/dimension selection
watch(currentTab, (newTab) => {
    selectedCategoryId.value = null;
    categoryAnswerDistribution.value = {};
    // Reset Domain Tab state
    selectedCategoryIdForDomainTab.value = null;
    selectedDomainId.value = null;
    domainAnswerDistribution.value = {};
    // Reset Dimension Tab state (which depends on Domain Tab state)
    dimensionQualifications.value = [];
    selectedDimensionId.value = null;
    dimensionAnswerDistribution.value = {};
});
</script>

<template>
    <Dashboard>
        <div class="space-y-6">
            <!-- Tabs de navegación -->
            <div class="space-y-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="!isAdmin && !isSuperAdmin" class="border-b border-gray-200">
                        <nav class="-mb-px flex">
                            <button
                                v-for="tab in dashboardTabs"
                                :key="tab.key"
                                @click="currentTab = tab.key"
                                :class="[
                                    currentTab === tab.key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Contenido de las tabs -->
                <div class="space-y-6">
                    <!-- TAB: Análisis por Categoría - Nuevo Reporte -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'categoryAnalysis'" class="space-y-6">
                        <!-- Subtabs para los diferentes reportes de categorías -->
                        <div class="border-b border-gray-200">
                            <div class="flex flex-wrap -mb-px">
                                <button 
                                    @click="categorySubTab = 'distribution'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        categorySubTab === 'distribution' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Distribución por Tipo
                                </button>
                                <button 
                                    @click="categorySubTab = 'totalScore'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        categorySubTab === 'totalScore' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                    Puntuación Total
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subtab: Distribución de respuestas por categoría -->
                        <div v-if="categorySubTab === 'distribution'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Distribución de Respuestas por Categoría</h3>
                                </div>
                                <div class="flex items-center space-x-2">
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
                            <div v-if="isLoadingCategoryReport" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="viewMode === 'chart'" class="space-y-8">
                                    <div v-if="categoryReportData.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráficos por categoría -->
                                    <div v-else>
                                        <!-- Gráficos individuales por categoría, 2 por fila -->
                                        <h4 class="text-md font-medium text-gray-700 mb-4">Distribución por categoría</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div v-for="(category, index) in categoryReportData" :key="index" class="bg-white p-6 rounded-lg shadow">
                                                <h3 class="text-lg font-semibold mb-4">{{ category.name }}</h3>
                                                
                                                <!-- Resumen numérico -->
                                                <div class="grid grid-cols-5 gap-2 mb-4">
                                                    <div v-for="(count, type) in category.responses" :key="type" 
                                                        :class="[
                                                            'text-center p-2 rounded-md',
                                                            type === 'A' ? 'bg-red-100 text-red-800' : 
                                                            type === 'B' ? 'bg-amber-100 text-amber-800' :
                                                            type === 'C' ? 'bg-yellow-100 text-yellow-800' :
                                                            type === 'D' ? 'bg-green-100 text-green-800' :
                                                            'bg-teal-100 text-teal-800'
                                                        ]"
                                                        :style="{
                                                            backgroundColor: type === 'A' ? '#F44336' : 
                                                                             type === 'B' ? '#FFB300' :
                                                                             type === 'C' ? '#FFEB3B' :
                                                                             type === 'D' ? '#8BC34A' : 
                                                                             '#4DD0C6',
                                                            color: ['C', 'E'].includes(type) ? '#000' : '#fff'
                                                        }">
                                                        <div class="text-xs font-medium mb-1" :style="{ color: ['C', 'E'].includes(type) ? '#000' : '#fff' }">
                                                            {{ type === 'A' ? 'Siempre' : 
                                                               type === 'B' ? 'Casi siempre' : 
                                                               type === 'C' ? 'Algunas veces' : 
                                                               type === 'D' ? 'Casi nunca' : 'Nunca' }}
                                                        </div>
                                                        <div class="font-bold">{{ count }}</div>
                                                        <div class="text-xs">{{ category.percentages[type].toFixed(1) }}%</div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Gráfico para categoría individual -->
                                                <div class="h-60 relative">
                                                    <CategoryBarChart :category="category" />
                                                </div>
                                                
                                                <!-- Enlace a lista de personal -->
                                                <div class="mt-4 text-right">
                                                    <a :href="route('reports.peopleList', { categoryId: category.id, answerKey: 'A' })" 
                                                       target="_blank"
                                                       class="text-sm text-blue-600 hover:underline">
                                                        Ver personal
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Vista de tabla -->
                                <div v-else-if="viewMode === 'table'" class="my-4">
                                    <CategoryResponseTable :category-data="categoryReportData" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subtab: Puntuación Total por categoría -->
                        <div v-if="categorySubTab === 'totalScore'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Puntuación Total por Categoría</h3>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="totalScoreViewMode = 'chart'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            totalScoreViewMode === 'chart' 
                                                ? 'bg-blue-600 text-white border-blue-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        <ChartPieIcon class="h-5 w-5 mr-2" />
                                        Gráfica
                                    </button>
                                    <button 
                                        @click="totalScoreViewMode = 'table'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            totalScoreViewMode === 'table' 
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
                            <div v-if="isLoadingCategoryTotalScores" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="totalScoreViewMode === 'chart'" class="space-y-8">
                                    <div v-if="categoryTotalScores.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráfico de barras horizontales -->
                                    <div v-else>
                                        <CategoryTotalScoreChart :category-scores="categoryTotalScores" />
                                        
                                        <!-- Interpretación y Contexto -->
                                        <div class="bg-white p-6 rounded-lg shadow mt-6">
                                            <h4 class="text-md font-medium text-gray-700 mb-3">Interpretación</h4>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Este reporte muestra la suma total de los valores de respuesta por cada categoría evaluada.
                                                Las categorías están ordenadas de mayor a menor puntuación, lo que permite identificar rápidamente
                                                aquellas áreas que podrían representar mayor riesgo psicosocial en el entorno laboral.
                                            </p>
                                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                                                <div class="flex">
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
                                </div>
                                
                                <!-- Vista de tabla -->
                                <div v-else-if="totalScoreViewMode === 'table'" class="my-4">
                                    <CategoryTotalScoreTable :category-scores="categoryTotalScores" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: Análisis por Dominio -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'domainAnalysis'" class="space-y-6">
                        <!-- Subtabs para los diferentes reportes de dominios -->
                        <div class="border-b border-gray-200">
                            <div class="flex flex-wrap -mb-px">
                                <button 
                                    @click="domainSubTab = 'distribution'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        domainSubTab === 'distribution' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Distribución por Tipo
                                </button>
                                <button 
                                    @click="domainSubTab = 'totalScore'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        domainSubTab === 'totalScore' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                    Puntuación Total
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subtab: Distribución de respuestas por dominio -->
                        <div v-if="domainSubTab === 'distribution'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Distribución de Respuestas por Dominio</h3>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="domainViewMode = 'chart'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            domainViewMode === 'chart' 
                                                ? 'bg-blue-600 text-white border-blue-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        <ChartPieIcon class="h-5 w-5 mr-2" />
                                        Gráficas
                                    </button>
                                    <button 
                                        @click="domainViewMode = 'table'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            domainViewMode === 'table' 
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
                            <div v-if="isLoadingDomainReport" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="domainViewMode === 'chart'" class="space-y-8">
                                    <div v-if="domainReportData.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráficos individuales por dominio, 2 por fila -->
                                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div v-for="(domain, index) in domainReportData" :key="index" class="bg-white p-6 rounded-lg shadow">
                                            <h3 class="text-lg font-semibold mb-4">{{ domain.name }}</h3>
                                            
                                            <!-- Resumen numérico -->
                                            <div class="grid grid-cols-5 gap-2 mb-4">
                                                <div v-for="(count, type) in domain.responses" :key="type" 
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
                                                    <div class="text-xs">{{ domain.percentages[type].toFixed(1) }}%</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Gráfico para dominio individual -->
                                            <div class="h-60 relative">
                                                <DomainBarChart :domain="domain" />
                                            </div>
                                            
                                            <!-- Enlace a lista de personal -->
                                            <div class="mt-4 text-right">
                                                <a :href="route('reports.peopleListDomain', { domainId: domain.id, answerKey: 'A' })" 
                                                   target="_blank"
                                                   class="text-sm text-blue-600 hover:underline">
                                                    Ver personal
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Vista de tabla -->
                                <div v-else-if="domainViewMode === 'table'" class="my-4">
                                    <DomainResponseTable :domain-data="domainReportData" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subtab: Puntuación Total por dominio -->
                        <div v-if="domainSubTab === 'totalScore'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Puntuación Total por Dominio</h3>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="domainTotalScoreViewMode = 'chart'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            domainTotalScoreViewMode === 'chart' 
                                                ? 'bg-blue-600 text-white border-blue-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        <ChartPieIcon class="h-5 w-5 mr-2" />
                                        Gráfica
                                    </button>
                                    <button 
                                        @click="domainTotalScoreViewMode = 'table'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            domainTotalScoreViewMode === 'table' 
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
                            <div v-if="isLoadingDomainTotalScores" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="domainTotalScoreViewMode === 'chart'" class="space-y-8">
                                    <div v-if="domainTotalScores.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráfico de barras horizontales -->
                                    <div v-else>
                                        <DomainTotalScoreChart :domain-scores="domainTotalScores" />
                                        
                                        <!-- Interpretación y Contexto -->
                                        <div class="bg-white p-6 rounded-lg shadow mt-6">
                                            <h4 class="text-md font-medium text-gray-700 mb-3">Interpretación</h4>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Este reporte muestra la suma total de los valores de respuesta por cada dominio evaluado.
                                                Los dominios están ordenados de mayor a menor puntuación, lo que permite identificar rápidamente
                                                aquellos que podrían representar mayor riesgo psicosocial en el entorno laboral.
                                            </p>
                                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
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
                                </div>
                                
                                <!-- Vista de tabla -->
                                <div v-else-if="domainTotalScoreViewMode === 'table'" class="my-4">
                                    <DomainTotalScoreTable :domain-scores="domainTotalScores" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: Análisis por Dimensión -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'dimensionAnalysis'" class="space-y-6">
                        <!-- Subtabs para los diferentes reportes de dimensiones -->
                        <div class="border-b border-gray-200">
                            <div class="flex flex-wrap -mb-px">
                                <button 
                                    @click="dimensionSubTab = 'distribution'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        dimensionSubTab === 'distribution' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Distribución por Tipo
                                </button>
                                <button 
                                    @click="dimensionSubTab = 'totalScore'" 
                                    :class="[
                                        'inline-flex items-center py-4 px-4 text-sm font-medium border-b-2 whitespace-nowrap',
                                        dimensionSubTab === 'totalScore' 
                                            ? 'border-blue-500 text-blue-600' 
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                    Puntuación Total
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subtab: Distribución de respuestas por dimensión -->
                        <div v-if="dimensionSubTab === 'distribution'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Distribución de Respuestas por Dimensión</h3>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="dimensionViewMode = 'chart'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            dimensionViewMode === 'chart' 
                                                ? 'bg-blue-600 text-white border-blue-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        <ChartPieIcon class="h-5 w-5 mr-2" />
                                        Gráficas
                                    </button>
                                    <button 
                                        @click="dimensionViewMode = 'table'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            dimensionViewMode === 'table' 
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
                            <div v-if="isLoadingDimensionReport" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="dimensionViewMode === 'chart'" class="space-y-8">
                                    <div v-if="dimensionDistributionData.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráficos individuales por dimensión, 2 por fila -->
                                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div v-for="(dimension, index) in dimensionDistributionData" :key="index" class="bg-white p-6 rounded-lg shadow">
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
                                <div v-else-if="dimensionViewMode === 'table'" class="my-4">
                                    <DimensionResponseTable :dimension-data="dimensionDistributionData" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subtab: Puntuación Total por dimensión -->
                        <div v-if="dimensionSubTab === 'totalScore'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Puntuación Total por Dimensión</h3>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button 
                                        @click="dimensionTotalScoreViewMode = 'chart'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            dimensionTotalScoreViewMode === 'chart' 
                                                ? 'bg-blue-600 text-white border-blue-600' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        <ChartPieIcon class="h-5 w-5 mr-2" />
                                        Gráfica
                                    </button>
                                    <button 
                                        @click="dimensionTotalScoreViewMode = 'table'" 
                                        :class="[
                                            'inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium',
                                            dimensionTotalScoreViewMode === 'table' 
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
                            <div v-if="isLoadingDimensionTotalScores" class="flex justify-center items-center h-64">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                                <span class="ml-3 text-gray-600">Cargando datos...</span>
                            </div>
                            
                            <!-- Contenido del reporte -->
                            <div v-else>
                                <!-- Vista de gráficas -->
                                <div v-if="dimensionTotalScoreViewMode === 'chart'" class="space-y-8">
                                    <div v-if="dimensionTotalScores.length === 0" class="bg-gray-50 p-6 rounded-lg text-center text-gray-500">
                                        No hay datos disponibles para mostrar.
                                    </div>
                                    
                                    <!-- Gráfico de barras horizontales -->
                                    <div v-else>
                                        <DimensionTotalScoreChart :dimension-scores="dimensionTotalScores" />
                                        
                                        <!-- Interpretación y Contexto -->
                                        <div class="bg-white p-6 rounded-lg shadow mt-6">
                                            <h4 class="text-md font-medium text-gray-700 mb-3">Interpretación</h4>
                                            <p class="text-sm text-gray-600 mb-4">
                                                Este reporte muestra la suma total de los valores de respuesta por cada dimensión evaluada.
                                                Las dimensiones están ordenadas de mayor a menor puntuación, lo que permite identificar rápidamente
                                                aquellas que podrían representar mayor riesgo psicosocial en el entorno laboral.
                                            </p>
                                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3">
                                                <div class="flex">
                                                    <div class="ml-3">
                                                        <p class="text-sm text-blue-700">
                                                            Una mayor puntuación indica un mayor nivel de riesgo percibido en esa dimensión específica.
                                                            Se recomienda prestar especial atención a las dimensiones con puntuaciones más altas.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Vista de tabla -->
                                <div v-else-if="dimensionTotalScoreViewMode === 'table'" class="my-4">
                                    <DimensionTotalScoreTable :dimension-scores="dimensionTotalScores" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NEW TAB: Análisis Demográfico -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'demographicAnalysis'" class="space-y-8">
                         <div v-if="demographic_distributions && demographic_distributions.length > 0" class="space-y-8">
                             <h2 class="text-xl font-semibold text-gray-900 mb-0 text-center">Análisis Demográfico General</h2>

                            <div v-for="category in demographic_distributions" :key="category.key" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-800 mb-4 text-center">{{ category.label }}</h3>
                                <DemographicChart :title="category.label" :chart-data="category.data" />

                                 <div v-if="category.data.length > 0" class="mt-6 pt-4 border-t border-gray-200">
                                     <h4 class="text-md font-semibold mb-3 text-gray-700 text-center">Ver lista de personal por respuesta:</h4>
                                     <div class="flex flex-wrap justify-center gap-2">
                                         <!-- Iterate over the data points for this category -->
                                         <a v-for="item in category.data" :key="item.identifier"
                                                :href="item.count > 0 ? route('reports.peopleListDemographic', { fieldKey: category.key, identifier: item.identifier }) : '#'"
                                                target="_blank"
                                                :class="[
                                                    'inline-block px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                    item.count === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' :
                                                    'bg-white text-indigo-600 border-gray-300 hover:bg-indigo-50 hover:border-indigo-300'
                                                ]"
                                                :aria-disabled="item.count === 0"
                                           >
                                             {{ item.label }} ({{ item.count }})
                                         </a>
                                     </div>
                                 </div>
                             </div>
                         </div>
                         <div v-else class="text-gray-500 text-center py-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                             No hay datos demográficos disponibles para mostrar.
                        </div>
                    </div>

                    <!-- Tab de Evaluaciones -->
                    <div v-show="currentTab === 'evaluations' || isAdmin || isSuperAdmin" class="space-y-6">
                        <!-- Para usuarios de organización -->
                        <div v-if="!isAdmin && !isSuperAdmin">
                            <div v-if="guideIIIEvaluations.length > 0">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div v-for="evaluation in guideIIIEvaluations"
                                         :key="evaluation.id"
                                         class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-300">
                                        <div class="border-b border-gray-200 bg-sky-50 px-4 py-3">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    Folio: {{ evaluation.folio }}
                                                </h3>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-blue-800">
                                                    Guía III
                                                </span>
                                            </div>
                                        </div>
                                        <div class="px-4 py-4 sm:px-6">
                                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                                    <dd class="mt-1 text-sm text-gray-900">Completada</dd>
                                                </div>
                                            </dl>
                                            <div class="mt-4 flex justify-end">
                                                <Link
                                                    :href="route('organization.results.detail', {
                                                        organization: evaluation.organization_id,
                                                        evaluation: evaluation.id
                                                    })"
                                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                >
                                                    <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-500" />
                                                    Ver detalle
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-center py-4">
                                No hay evaluaciones registradas
                            </div>
                        </div>

                        <!-- Para admin/superadmin -->
                        <div v-else class="space-y-8">
                            <div v-for="organization in organizationsWithGuideIII" :key="organization.id">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">{{ organization.name }}</h3>
                                <div v-if="organization.evaluations.length > 0">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div v-for="evaluation in organization.evaluations"
                                             :key="evaluation.id"
                                             class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-300">
                                            <div class="border-b border-gray-200 bg-sky-50 px-4 py-3">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="text-lg font-semibold text-gray-900">
                                                        Folio: {{ evaluation.folio }}
                                                    </h3>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-blue-800">
                                                        Guía III
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="px-4 py-4 sm:px-6">
                                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                                    <div class="sm:col-span-1">
                                                        <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                                                    </div>
                                                    <div class="sm:col-span-1">
                                                        <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                                        <dd class="mt-1 text-sm text-gray-900">Completada</dd>
                                                    </div>
                                                </dl>
                                                <div class="mt-4 flex justify-end">
                                                    <Link
                                                        :href="route('organization.results.detail', {
                                                            organization: organization.id,
                                                            evaluation: evaluation.id
                                                        })"
                                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                    >
                                                        <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-500" />
                                                        Ver detalle
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 text-center py-4">
                                    No hay evaluaciones registradas para esta organización
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>

</style>
