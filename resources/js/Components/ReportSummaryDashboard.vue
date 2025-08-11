<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import BarChart from './BarChart.vue';
import DemographicChart from './DemographicChart.vue';
import RiskSummaryCards from './RiskSummaryCards.vue';
import RiskActionButtons from './RiskActionButtons.vue';
import FinalQualificationChart from './FinalQualificationChart.vue';
import ParticipantReport from './ParticipantReport.vue';

const props = defineProps({
    organizations: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    isSuperAdmin: { type: Boolean, default: false },
    currentOrganization: { type: [Object, Number, String], default: null },
});

console.log('currentOrganization', props.currentOrganization);

const tabs = [
    // { key: 'dimension', label: 'Dimensiones' },
    { key: 'domain', label: 'Dominios' },
    { key: 'category', label: 'Categorías' },
    { key: 'participants', label: 'Participantes' },
    { key: 'demographics', label: 'Datos Demográficos' },
    { key: 'final', label: 'Calificación Final' },
];
const riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
const activeTab = ref('domain');
const rawSummaryData = ref(null);
const isLoading = ref(false);
const showPdfMenu = ref(false);
// Función para extraer el ID de la organización desde diferentes formatos
const extractOrgId = (org) => {
    if (!org) return null;
    if (typeof org === 'object' && org.id) return org.id;
    return org; // Asumimos que es un ID directo (string o número)
};

const selectedOrgId = ref(extractOrgId(props.currentOrganization));

const canSelectOrg = computed(() => props.isAdmin || props.isSuperAdmin);

// Procesa los datos de dimensiones para el formato que espera BarChart
const processedDimensionData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_dimension) return null;
    
    // Agrupar por dimensión
    const dimensionGroups = {};
    
    rawSummaryData.value.grouped_by_dimension.forEach(item => {
        if (!item.dimension) return;
        
        if (!dimensionGroups[item.dimension]) {
            dimensionGroups[item.dimension] = {
                name: item.dimension,
                risk_levels: {
                    'Nulo': 0,
                    'Bajo': 0,
                    'Medio': 0,
                    'Alto': 0,
                    'Muy Alto': 0
                },
                personal_by_risk: {
                    'Nulo': [],
                    'Bajo': [],
                    'Medio': [],
                    'Alto': [],
                    'Muy Alto': []
                },
                total: 0
            };
        }
        
        if (item.nivel_riesgo && typeof item.conteo === 'number') {
            dimensionGroups[item.dimension].risk_levels[item.nivel_riesgo] = item.conteo;
            dimensionGroups[item.dimension].total += item.conteo;
            if (item.personal) {
                dimensionGroups[item.dimension].personal_by_risk[item.nivel_riesgo] = item.personal;
            }
        }
    });
    
    return Object.values(dimensionGroups);
});

// Procesa los datos de dominios para el formato que espera BarChart
const processedDomainData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_domain) return null;
    
    // Agrupar por dominio
    const domainGroups = {};
    
    rawSummaryData.value.grouped_by_domain.forEach(item => {
        if (!item.dominio) return;
        
        if (!domainGroups[item.dominio]) {
            domainGroups[item.dominio] = {
                name: item.dominio,
                risk_levels: {
                    'Nulo': 0,
                    'Bajo': 0,
                    'Medio': 0,
                    'Alto': 0,
                    'Muy Alto': 0
                },
                personal_by_risk: {
                    'Nulo': [],
                    'Bajo': [],
                    'Medio': [],
                    'Alto': [],
                    'Muy Alto': []
                },
                total: 0
            };
        }
        
        if (item.nivel_riesgo && typeof item.conteo === 'number') {
            domainGroups[item.dominio].risk_levels[item.nivel_riesgo] = item.conteo;
            domainGroups[item.dominio].total += item.conteo;
            if (item.personal) {
                domainGroups[item.dominio].personal_by_risk[item.nivel_riesgo] = item.personal;
            }
        }
    });
    
    return Object.values(domainGroups);
});

// Procesa los datos de categorías para el formato que espera BarChart
const processedCategoryData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_category) return null;
    
    // Agrupar por categoría
    const categoryGroups = {};
    
    rawSummaryData.value.grouped_by_category.forEach(item => {
        if (!item.categoria) return;
        
        if (!categoryGroups[item.categoria]) {
            categoryGroups[item.categoria] = {
                name: item.categoria,
                risk_levels: {
                    'Nulo': 0,
                    'Bajo': 0,
                    'Medio': 0,
                    'Alto': 0,
                    'Muy Alto': 0
                },
                personal_by_risk: {
                    'Nulo': [],
                    'Bajo': [],
                    'Medio': [],
                    'Alto': [],
                    'Muy Alto': []
                },
                total: 0
            };
        }
        
        if (item.nivel_riesgo && typeof item.conteo === 'number') {
            categoryGroups[item.categoria].risk_levels[item.nivel_riesgo] = item.conteo;
            categoryGroups[item.categoria].total += item.conteo;
            if (item.personal) {
                categoryGroups[item.categoria].personal_by_risk[item.nivel_riesgo] = item.personal;
            }
        }
    });
    
    return Object.values(categoryGroups);
});

// Procesa los datos de calificación final
const processedFinalRiskData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.final_risk_levels) return null;
    
    // Inicializar estructura para niveles de riesgo
    const finalRiskData = {
        risk_levels: {
            'Nulo': 0,
            'Bajo': 0,
            'Medio': 0,
            'Alto': 0,
            'Muy Alto': 0
        },
        personal_by_risk: {
            'Nulo': [],
            'Bajo': [],
            'Medio': [],
            'Alto': [],
            'Muy Alto': []
        },
        total: 0
    };
    
    rawSummaryData.value.final_risk_levels.forEach(item => {
        if (item.nivel_riesgo && typeof item.conteo === 'number') {
            finalRiskData.risk_levels[item.nivel_riesgo] = item.conteo;
            finalRiskData.total += item.conteo;
            if (item.personal) {
                finalRiskData.personal_by_risk[item.nivel_riesgo] = item.personal;
            }
        }
    });
    
    return finalRiskData;
});

// Getters para los datos de calificación final
const finalRiskLevels = computed(() => processedFinalRiskData.value?.risk_levels || {});
const finalPersonalByRisk = computed(() => processedFinalRiskData.value?.personal_by_risk || {});

// Procesa los datos de calificaciones de participantes
const processedParticipantsData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.personalCalification) {
        console.log('No hay datos de personalCalification', rawSummaryData.value);
        return null;
    }
    console.log('Datos de participantes obtenidos:', rawSummaryData.value.personalCalification);
    return rawSummaryData.value.personalCalification;
});

// Estado para datos demográficos
const demographicData = ref([]);
const isLoadingDemographicData = ref(false);
const demographicChartType = ref('totals'); // 'totals' or 'risk_distribution'

// Función para cargar datos demográficos
const loadDemographicData = async () => {
    if (demographicData.value.length > 0) return;
    
    isLoadingDemographicData.value = true;
    let url = '/reports/demographic-distribution';
    
    // Incluir el ID de organización si está disponible
    if (selectedOrgId.value) {
        url += `?organization_id=${selectedOrgId.value}`;
    }
    
    try {
        console.log('Fetching demographic data from:', url);
        const response = await window.axios.get(url);
        demographicData.value = response.data;
        console.log('Demographic data received:', response.data);
    } catch (error) {
        console.error('Error al cargar los datos demográficos:', error);
        demographicData.value = [];
    } finally {
        isLoadingDemographicData.value = false;
    }
};

// Seleccionar el conjunto de datos correcto según el tab activo
const activeData = computed(() => {
    switch (activeTab.value) {
        case 'dimension':
            return processedDimensionData.value;
        case 'domain':
            return processedDomainData.value;
        case 'category':
            return processedCategoryData.value;
        case 'participants':
            return processedParticipantsData.value ? [{ name: 'Participantes', data: processedParticipantsData.value }] : null;
        case 'demographics':
            return demographicData.value;
        case 'final':
            return processedFinalRiskData.value ? [processedFinalRiskData.value] : null;
        default:
            return null;
    }
});

const fetchSummary = async () => {
    isLoading.value = true;
    let url = '/reports/dimension-report-summary';
    
    // Siempre incluir el ID de organización en la solicitud si está disponible
    if (selectedOrgId.value) {
        url += `?organization_id=${selectedOrgId.value}`;
    }
    
    try {
        console.log('Fetching data from:', url);
        const res = await window.axios.get(url);
        rawSummaryData.value = res.data;
        console.log('Data received:', res.data);
        console.log('Propiedades del objeto recibido:', Object.keys(res.data));
        console.log('¿Existe personalCalification?', 'personalCalification' in res.data);
    } catch (e) {
        console.error('Error fetching summary data:', e);
        rawSummaryData.value = null;
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchSummary();
    // Cargar datos demográficos cuando el componente se monta
    if (activeTab.value === 'demographics') {
        loadDemographicData();
    }
    
    // Close PDF menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            showPdfMenu.value = false;
        }
    });
});

// Observar cambios en el ID de organización seleccionada
watch(selectedOrgId, () => {
    fetchSummary();
    // Recargar datos demográficos cuando cambia la organización
    if (activeTab.value === 'demographics') {
        demographicData.value = []; // Reset para forzar recarga
        loadDemographicData();
    }
});

// Observar cambios en la tab activa para cargar datos demográficos cuando sea necesario
watch(activeTab, (newTab) => {
    if (newTab === 'demographics') {
        loadDemographicData();
    }
});

// Observar cambios en la propiedad currentOrganization
watch(() => props.currentOrganization, (newOrg) => {
    selectedOrgId.value = extractOrgId(newOrg);
}, { deep: true });

</script>
<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <div class="flex space-x-4">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    :class="['px-4 py-2 rounded', activeTab === tab.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700']">
                    {{ tab.label }}
                </button>
            </div>
            
            <!-- PDF Export Menu -->
            <div class="relative">
                <button @click="showPdfMenu = !showPdfMenu" 
                    class="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar PDF
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                <!-- PDF Dropdown Menu -->
                <div v-if="showPdfMenu" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="py-2">
                        <div class="px-4 py-2 text-sm text-gray-500 border-b border-gray-100">
                            Reportes en PDF
                        </div>
                        
                        <a :href="route('dashboard.pdf.category')" target="_blank"
                            class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Reporte de Categorías</div>
                                <div class="text-xs text-gray-500">Calificaciones y distribución por categoría</div>
                            </div>
                        </a>
                        
                        <a :href="route('dashboard.pdf.domain')" target="_blank"
                            class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 00-2 2v2a2 2 0 002 2m0 0h14m-14 0v4a2 2 0 002 2h10a2 2 0 002-2v-4"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Reporte de Dominios</div>
                                <div class="text-xs text-gray-500">Calificaciones y distribución por dominio</div>
                            </div>
                        </a>
                        
                        <a :href="route('dashboard.pdf.dimension')" target="_blank"
                            class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Reporte de Dimensiones</div>
                                <div class="text-xs text-gray-500">Distribución de riesgo por dimensión</div>
                            </div>
                        </a>
                        
                        <a :href="route('dashboard.pdf.demographic')" target="_blank"
                            class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Reporte Demográfico</div>
                                <div class="text-xs text-gray-500">Análisis de datos demográficos</div>
                            </div>
                        </a>
                        
                        <div class="border-t border-gray-100 my-1"></div>
                        
                        <a :href="route('dashboard.pdf.complete')" target="_blank"
                            class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div>
                                <div class="font-medium">Reporte Completo</div>
                                <div class="text-xs text-gray-500">Reporte consolidado del dashboard</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isLoading" class="text-center py-10">Cargando...</div>
        <div v-else-if="activeData">
            <div v-if="activeTab === 'dimension'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Resumen por Dimensión</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada dimensión.</p>
                </div>
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="item in activeData" :key="item.name" class="flex flex-col">
                        <h3 class="text-md font-medium mb-2">{{ item.name }}</h3>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <!-- Componente de tarjetas de resumen por nivel de riesgo -->
                            <RiskSummaryCards :itemData="item" />
                            
                            <BarChart :data="item" :type="activeTab" />
                            
                            <!-- Botones de acción por nivel de riesgo -->
                            <RiskActionButtons 
                                :itemData="item"
                                :rawData="rawSummaryData.value && rawSummaryData.value.grouped_by_dimension ? rawSummaryData.value.grouped_by_dimension : []"
                                :itemName="item.name"
                                :itemType="activeTab"
                                :organizationId="selectedOrgId"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="activeTab === 'domain'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Resumen por Dominio</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada dominio.</p>
                </div>
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="item in activeData" :key="item.name" class="flex flex-col">
                        <h3 class="text-md font-medium mb-2">{{ item.name }}</h3>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <!-- Componente de tarjetas de resumen por nivel de riesgo -->
                            <RiskSummaryCards :itemData="item" />
                            
                            <BarChart :data="item" :type="activeTab" />
                            
                            <!-- Botones de acción por nivel de riesgo -->
                            <RiskActionButtons 
                                :itemData="item"
                                :rawData="rawSummaryData.value && rawSummaryData.value.grouped_by_domain ? rawSummaryData.value.grouped_by_domain : []"
                                :itemName="item.name"
                                :itemType="activeTab"
                                :organizationId="selectedOrgId"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="activeTab === 'category'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Resumen por Categoría</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada categoría.</p>
                </div>
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="item in activeData" :key="item.name" class="flex flex-col">
                        <h3 class="text-md font-medium mb-2">{{ item.name }}</h3>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <!-- Componente de tarjetas de resumen por nivel de riesgo -->
                            <RiskSummaryCards :itemData="item" />
                            
                            <BarChart :data="item" :type="activeTab" />
                            
                            <!-- Botones de acción por nivel de riesgo -->
                            <RiskActionButtons 
                                :itemData="item"
                                :rawData="rawSummaryData.value && rawSummaryData.value.grouped_by_category ? rawSummaryData.value.grouped_by_category : []"
                                :itemName="item.name"
                                :itemType="activeTab"
                                :organizationId="selectedOrgId"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="activeTab === 'participants'">
                <h2 class="text-lg font-semibold mb-2">Resumen por Participantes</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada participante.</p>
                </div>
                <div class="mb-8">
                    <ParticipantReport :personalCalifications="processedParticipantsData" :organizationId="selectedOrgId" />
                </div>
            </div>
            <div v-else-if="activeTab === 'demographics'" class="tab-content">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-lg font-semibold mb-2">Datos Demográficos</h2>
                        <div class="bg-blue-50 p-3 rounded border border-blue-200">
                            <p class="text-sm">Mostrando distribución de personal según características demográficas y nivel de riesgo psicosocial.</p>
                        </div>
                    </div>
                    
                    <!-- Toggle para tipo de gráfica -->
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button 
                            @click="demographicChartType = 'totals'" 
                            :class="[
                                'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                                demographicChartType === 'totals' 
                                    ? 'bg-white text-gray-900 shadow-sm' 
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Totales Demográficos
                        </button>
                        <button 
                            @click="demographicChartType = 'risk_distribution'" 
                            :class="[
                                'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                                demographicChartType === 'risk_distribution' 
                                    ? 'bg-white text-gray-900 shadow-sm' 
                                    : 'text-gray-600 hover:text-gray-900'
                            ]"
                        >
                            Distribución por Riesgo
                        </button>
                    </div>
                </div>
                
                <!-- Estado de carga -->
                <div v-if="isLoadingDemographicData" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600">Cargando datos demográficos...</span>
                </div>
                
                <!-- Contenido de datos demográficos -->
                <div v-else-if="activeData && activeData.length > 0" class="space-y-8">
                    <div v-for="(section, sectionIndex) in activeData" :key="sectionIndex" class="bg-white rounded-lg border border-gray-200 shadow">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">{{ section.title }}</h3>
                        </div>
                        
                        <div class="p-6">
                            <!-- Gráfica comparativa para totales demográficos -->
                            <div v-if="demographicChartType === 'totals'" class="mb-6">
                                <h4 class="text-md font-medium text-gray-800 mb-3">Comparación de {{ section.title }}</h4>
                                <div class="h-80 bg-gray-50 p-4 rounded-lg">
                                    <DemographicChart 
                                        :title="section.title"
                                        :chartData="section.data.map(item => ({ label: item.name, count: item.total }))"
                                    />
                                </div>
                            </div>
                            
                            <!-- Grid de gráficas individuales por riesgo -->
                            <div v-if="demographicChartType === 'risk_distribution'" class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="(item, index) in section.data" :key="index" class="flex flex-col">
                                    <h4 class="text-md font-medium mb-2">{{ item.name }}</h4>
                                    <div class="bg-gray-50 p-4 rounded-lg shadow">
                                        <!-- Resumen por nivel de riesgo -->
                                        <RiskSummaryCards :itemData="item" />
                                        
                                        <!-- Gráfico de barras -->
                                        <BarChart :data="item" type="demographic" />
                                        
                                        <!-- Botones de acción por nivel de riesgo -->
                                        <RiskActionButtons 
                                            :itemData="item"
                                            :rawData="section.data"
                                            :itemName="item.name"
                                            :itemType="'demographic'"
                                            :organizationId="selectedOrgId"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabla resumen -->
                            <div class="mt-8">
                                <h4 class="text-md font-medium text-gray-800 mb-4">Tabla Resumen - {{ section.title }}</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                                                    {{ section.title }}
                                                </th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">Total</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #00CED1; color: #FFFFFF;">Nulo</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #28A745; color: #FFFFFF;">Bajo</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #FFFF00; color: #000000;">Medio</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #FFA500; color: #000000;">Alto</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #FF0000; color: #FFFFFF;">Muy Alto</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider border-r border-gray-300" style="background-color: #28A745; color: #FFFFFF;">Nu+Ba</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider" style="background-color: #DC3545; color: #FFFFFF;">Me+Al+MA</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(item, index) in section.data" :key="index" class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-sm text-gray-900 border-r border-gray-300 font-medium">{{ item.name }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-semibold">{{ item.total || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ item.risk_levels?.['Nulo'] || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ item.risk_levels?.['Bajo'] || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ item.risk_levels?.['Medio'] || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ item.risk_levels?.['Alto'] || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ item.risk_levels?.['Muy Alto'] || 0 }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 border-r border-gray-300 font-medium">{{ (item.risk_levels?.['Nulo'] || 0) + (item.risk_levels?.['Bajo'] || 0) }}</td>
                                                <td class="px-4 py-3 text-sm text-center text-gray-900 font-medium">{{ (item.risk_levels?.['Medio'] || 0) + (item.risk_levels?.['Alto'] || 0) + (item.risk_levels?.['Muy Alto'] || 0) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado vacío -->
                <div v-else class="flex items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-center p-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay datos demográficos</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No se encontraron datos demográficos para mostrar en esta organización.
                        </p>
                    </div>
                </div>
            </div>
            <div v-else-if="activeTab === 'final'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Calificación Final</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según el nivel de riesgo final basado en la calificación total del cuestionario.</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <RiskSummaryCards 
                        :itemData="{
                            name: 'Calificación Final',
                            risk_levels: finalRiskLevels,
                            personal_by_risk: finalPersonalByRisk
                        }"
                    />
                    
                    <div class="mb-4">
                        <FinalQualificationChart :risk-levels="finalRiskLevels" />
                    </div>
                    
                    <RiskActionButtons 
                        :itemData="{
                            name: 'Calificación Final',
                            risk_levels: finalRiskLevels,
                            personal_by_risk: finalPersonalByRisk
                        }"
                        :itemType="'final'"
                        :organizationId="selectedOrgId"
                    />
                </div>
            </div>
        </div>
        <div v-else >
            <div class="text-center py-10 text-gray-500">No hay datos para mostrar.</div>
        </div>
    </div>
</template>

<style scoped>
.tab-content {
    min-height: 400px;
    width: 100%;
}
</style>
