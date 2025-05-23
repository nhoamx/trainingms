<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import BarChart from './BarChart.vue';
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
});

// Observar cambios en el ID de organización seleccionada
watch(selectedOrgId, () => {
    fetchSummary();
});

// Observar cambios en la propiedad currentOrganization
watch(() => props.currentOrganization, (newOrg) => {
    selectedOrgId.value = extractOrgId(newOrg);
}, { deep: true });

</script>
<template>
    <div>
        <div class="flex space-x-4 mb-6">
            <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                :class="['px-4 py-2 rounded', activeTab === tab.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700']">
                {{ tab.label }}
            </button>
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
                <h2 class="text-lg font-semibold mb-2">Datos Demográficos</h2>
                <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-center p-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Próximamente</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Estamos desarrollando esta sección para mostrar información demográfica detallada.
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
            <div v-if="activeTab === 'demographics'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Datos Demográficos</h2>
                <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <div class="text-center p-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Próximamente</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Estamos desarrollando esta sección para mostrar información demográfica detallada.
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-10 text-gray-500">No hay datos para mostrar.</div>
        </div>
    </div>
</template>

<style scoped>
.tab-content {
    min-height: 400px;
    width: 100%;
}
</style>
