<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import BarChart from './BarChart.vue';
import DemographicChart from './DemographicChart.vue';
import RiskSummaryCards from './RiskSummaryCards.vue';
import RiskActionButtons from './RiskActionButtons.vue';
import FinalQualificationChart from './FinalQualificationChart.vue';
import ParticipantReport from './ParticipantReport.vue';
import type {
    ReportSummaryDashboardProps,
    ReportSummaryData,
    DemographicDistribution,
    GroupedReportItem,
    Tab,
    CategoryReportItem,
    DomainReportItem,
    DimensionReportItem,
    RiskLevels,
    PersonalByRisk,
} from '../types/reports';

const props = withDefaults(defineProps<ReportSummaryDashboardProps>(), {
    organizations: () => [],
    isAdmin: false,
    isSuperAdmin: false,
    currentOrganization: null,
});

// Tab configuration
const tabs: Tab[] = [
    { key: 'domain', label: 'Dominios' },
    { key: 'category', label: 'Categorías' },
    { key: 'participants', label: 'Participantes' },
    { key: 'demographics', label: 'Datos Demográficos' },
    { key: 'final', label: 'Calificación Final' },
];

const activeTab = ref<string>('domain');
const rawSummaryData = ref<ReportSummaryData | null>(null);
const isLoading = ref<boolean>(false);

// Función para extraer el ID de la organización desde diferentes formatos
const extractOrgId = (org: any): string | null => {
    if (!org) return null;
    if (typeof org === 'object' && org.id) return org.id;
    return String(org);
};

const selectedOrgId = ref<string | null>(extractOrgId(props.currentOrganization));

const canSelectOrg = computed(() => props.isAdmin || props.isSuperAdmin);

/**
 * Download PDF reports
 */
const downloadPdfReport = async (reportType: 'demographic' | 'diagnostic' | 'executive') => {
    if (!selectedOrgId.value) {
        alert('Por favor selecciona una organización');
        return;
    }

    const routes = {
        demographic: `/reportes/pdf/demografico/${selectedOrgId.value}`,
        diagnostic: `/reportes/pdf/diagnostico/${selectedOrgId.value}`,
        executive: `/reportes/pdf/ejecutivo/${selectedOrgId.value}`,
    };

    try {
        // Use fetch API with credentials to maintain session
        const response = await fetch(routes[reportType], {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/pdf',
            }
        });

        if (!response.ok) {
            // If response is JSON, it's an error message
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const errorData = await response.json();
                throw new Error(errorData.error || errorData.message || `Error: ${response.status}`);
            }
            throw new Error(`Error: ${response.status}`);
        }

        // Get the blob from response
        const blob = await response.blob();
        
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        
        // Extract filename from Content-Disposition header or use default
        const contentDisposition = response.headers.get('content-disposition');
        let filename = `reporte-${reportType}-${selectedOrgId.value}.pdf`;
        if (contentDisposition) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
            if (matches != null && matches[1]) {
                filename = matches[1].replace(/['"]/g, '');
            }
        }
        
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        
        // Cleanup
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error descargando el reporte:', error);
        alert(error instanceof Error ? error.message : 'Error al generar el reporte. Por favor intenta de nuevo.');
    }
};

/**
 * Process raw category data into grouped format for charts
 */
const processedCategoryData = computed<GroupedReportItem[]>(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_category) return [];

    return processGroupedData(rawSummaryData.value.grouped_by_category, 'categoria');
});

/**
 * Process raw domain data into grouped format for charts
 */
const processedDomainData = computed<GroupedReportItem[]>(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_domain) return [];

    return processGroupedData(rawSummaryData.value.grouped_by_domain, 'dominio');
});

/**
 * Process raw dimension data into grouped format for charts
 */
const processedDimensionData = computed<GroupedReportItem[]>(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.grouped_by_dimension) return [];

    return processGroupedData(rawSummaryData.value.grouped_by_dimension, 'dimension');
});

/**
 * Generic function to process grouped report data
 */
function processGroupedData(
    items: CategoryReportItem[] | DomainReportItem[] | DimensionReportItem[],
    typeKey: 'categoria' | 'dominio' | 'dimension'
): GroupedReportItem[] {
    const groups: Record<string, GroupedReportItem> = {};

    items.forEach((item) => {
        const name = item[typeKey];
        if (!name) return;

        if (!groups[name]) {
            groups[name] = {
                name,
                risk_levels: {
                    'Nulo': 0,
                    'Bajo': 0,
                    'Medio': 0,
                    'Alto': 0,
                    'Muy Alto': 0,
                },
                personal_by_risk: {
                    'Nulo': [],
                    'Bajo': [],
                    'Medio': [],
                    'Alto': [],
                    'Muy Alto': [],
                },
                total: 0,
            };
        }

        if (item.nivel_riesgo && typeof item.conteo === 'number') {
            groups[name].risk_levels[item.nivel_riesgo] = item.conteo;
            groups[name].total += item.conteo;
            if (item.personal) {
                groups[name].personal_by_risk[item.nivel_riesgo] = item.personal;
            }
        }
    });

    return Object.values(groups);
}

/**
 * Process final risk data for the final qualification tab
 */
const processedFinalRiskData = computed(() => {
    if (!rawSummaryData.value || !rawSummaryData.value.final_risk_levels) return null;

    const finalRiskData: GroupedReportItem = {
        name: 'Calificación Final',
        risk_levels: {
            'Nulo': 0,
            'Bajo': 0,
            'Medio': 0,
            'Alto': 0,
            'Muy Alto': 0,
        },
        personal_by_risk: {
            'Nulo': [],
            'Bajo': [],
            'Medio': [],
            'Alto': [],
            'Muy Alto': [],
        },
        total: 0,
    };

    rawSummaryData.value.final_risk_levels.forEach((item) => {
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
const finalRiskLevels = computed<RiskLevels>(() => processedFinalRiskData.value?.risk_levels || {
    'Nulo': 0,
    'Bajo': 0,
    'Medio': 0,
    'Alto': 0,
    'Muy Alto': 0,
});

const finalPersonalByRisk = computed<PersonalByRisk>(() => processedFinalRiskData.value?.personal_by_risk || {
    'Nulo': [],
    'Bajo': [],
    'Medio': [],
    'Alto': [],
    'Muy Alto': [],
});

// Participant data
const processedParticipantsData = computed(() => {
    return rawSummaryData.value?.personalCalification || null;
});

// Demographic data
const demographicData = ref<DemographicDistribution>([]);
const isLoadingDemographicData = ref<boolean>(false);
const demographicChartType = ref<'totals' | 'risk_distribution'>('totals');

/**
 * Load demographic data from API
 */
const loadDemographicData = async (): Promise<void> => {
    if (demographicData.value.length > 0) return;

    isLoadingDemographicData.value = true;
    let url = '/reports/demographic-distribution';

    if (selectedOrgId.value) {
        url += `?organization_id=${selectedOrgId.value}`;
    }

    try {
        const response = await window.axios.get<DemographicDistribution>(url);
        demographicData.value = response.data;
    } catch (error) {
        console.error('Error al cargar los datos demográficos:', error);
        demographicData.value = [];
    } finally {
        isLoadingDemographicData.value = false;
    }
};

/**
 * Select the active dataset based on current tab
 */
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

/**
 * Fetch summary report data from API
 */
const fetchSummary = async (): Promise<void> => {
    isLoading.value = true;
    let url = '/reports/dimension-report-summary';

    if (selectedOrgId.value) {
        url += `?organization_id=${selectedOrgId.value}`;
    }

    try {
        const res = await window.axios.get<ReportSummaryData>(url);
        rawSummaryData.value = res.data;
    } catch (e) {
        console.error('Error fetching summary data:', e);
        rawSummaryData.value = null;
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchSummary();
    if (activeTab.value === 'demographics') {
        loadDemographicData();
    }
});

// Watch for organization changes
watch(selectedOrgId, () => {
    fetchSummary();
    if (activeTab.value === 'demographics') {
        demographicData.value = [];
        loadDemographicData();
    }
});

// Watch for tab changes
watch(activeTab, (newTab) => {
    if (newTab === 'demographics') {
        loadDemographicData();
    }
});

// Watch for currentOrganization prop changes
watch(() => props.currentOrganization, (newOrg) => {
    selectedOrgId.value = extractOrgId(newOrg);
}, { deep: true });
</script>

<template>
    <div>
        <!-- PDF Download Buttons -->
        <div v-if="(isAdmin || isSuperAdmin) && selectedOrgId" class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar Reportes en PDF
            </h3>
            <div class="flex gap-3 flex-wrap">
                <button
                    @click="downloadPdfReport('demographic')"
                    class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Informe Demográfico
                </button>
                <button
                    @click="downloadPdfReport('diagnostic')"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Informe de Diagnóstico
                </button>
                <button
                    @click="downloadPdfReport('executive')"
                    class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors shadow-sm opacity-50 cursor-not-allowed"
                    disabled
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Informe Ejecutivo (Próximamente)
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="flex gap-2 mb-6 flex-wrap">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'px-4 py-2 rounded transition-colors',
                    activeTab === tab.key
                        ? 'bg-blue-600 text-white shadow-md'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-10">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            <p class="mt-4 text-gray-600">Cargando datos...</p>
        </div>

        <!-- Content -->
        <div v-else-if="activeData">
            <!-- Domain Tab -->
            <div v-if="activeTab === 'domain'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Resumen por Dominio</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada dominio.</p>
                </div>
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="item in (activeData as GroupedReportItem[])" :key="item.name" class="flex flex-col">
                        <h3 class="text-md font-medium mb-2">{{ item.name }}</h3>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <RiskSummaryCards :itemData="item" />
                            <BarChart :data="item" type="domain" />
                            <RiskActionButtons
                                :itemData="item"
                                :rawData="rawSummaryData?.grouped_by_domain || []"
                                :itemName="item.name"
                                itemType="domain"
                                :organizationId="selectedOrgId"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Tab -->
            <div v-else-if="activeTab === 'category'" class="tab-content">
                <h2 class="text-lg font-semibold mb-2">Resumen por Categoría</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada categoría.</p>
                </div>
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="item in (activeData as GroupedReportItem[])" :key="item.name" class="flex flex-col">
                        <h3 class="text-md font-medium mb-2">{{ item.name }}</h3>
                        <div class="bg-white p-4 rounded-lg shadow">
                            <RiskSummaryCards :itemData="item" />
                            <BarChart :data="item" type="category" />
                            <RiskActionButtons
                                :itemData="item"
                                :rawData="rawSummaryData?.grouped_by_category || []"
                                :itemName="item.name"
                                itemType="category"
                                :organizationId="selectedOrgId"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Participants Tab -->
            <div v-else-if="activeTab === 'participants'">
                <h2 class="text-lg font-semibold mb-2">Resumen por Participantes</h2>
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-200">
                    <p class="text-sm">Mostrando distribución de personal según nivel de riesgo por cada participante.</p>
                </div>
                <div class="mb-8">
                    <ParticipantReport
                        :personalCalifications="processedParticipantsData"
                        :organizationId="selectedOrgId"
                    />
                </div>
            </div>

            <!-- Demographics Tab -->
            <div v-else-if="activeTab === 'demographics'" class="tab-content">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-lg font-semibold mb-2">Datos Demográficos</h2>
                        <div class="bg-blue-50 p-3 rounded border border-blue-200">
                            <p class="text-sm">Mostrando distribución de personal según características demográficas y nivel de riesgo psicosocial.</p>
                        </div>
                    </div>

                    <!-- Chart Type Toggle -->
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

                <!-- Loading State -->
                <div v-if="isLoadingDemographicData" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600">Cargando datos demográficos...</span>
                </div>

                <!-- Demographic Content -->
                <div v-else-if="activeData && (activeData as DemographicDistribution).length > 0" class="space-y-8">
                    <div v-for="(section, sectionIndex) in (activeData as DemographicDistribution)" :key="sectionIndex" class="bg-white rounded-lg border border-gray-200 shadow">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">{{ section.title }}</h3>
                        </div>

                        <div class="p-6">
                            <!-- Totals Chart -->
                            <div v-if="demographicChartType === 'totals'" class="mb-6">
                                <h4 class="text-md font-medium text-gray-800 mb-3">Comparación de {{ section.title }}</h4>
                                <div class="h-80 bg-gray-50 p-4 rounded-lg">
                                    <DemographicChart
                                        :title="section.title"
                                        :chartData="section.data.map(item => ({ label: item.name, count: item.total }))"
                                    />
                                </div>
                            </div>

                            <!-- Risk Distribution Grid -->
                            <div v-if="demographicChartType === 'risk_distribution'" class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="(item, index) in section.data" :key="index" class="flex flex-col">
                                    <h4 class="text-md font-medium mb-2">{{ item.name }}</h4>
                                    <div class="bg-gray-50 p-4 rounded-lg shadow">
                                        <RiskSummaryCards :itemData="item" />
                                        <BarChart :data="item" type="demographic" />
                                        <RiskActionButtons
                                            :itemData="item"
                                            :rawData="section.data"
                                            :itemName="item.name"
                                            itemType="demographic"
                                            :organizationId="selectedOrgId"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Table -->
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

                <!-- Empty State -->
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

            <!-- Final Tab -->
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
                            personal_by_risk: finalPersonalByRisk,
                            total: processedFinalRiskData?.total || 0
                        }"
                    />

                    <div class="mb-4">
                        <FinalQualificationChart :risk-levels="finalRiskLevels" />
                    </div>

                    <RiskActionButtons
                        :itemData="{
                            name: 'Calificación Final',
                            risk_levels: finalRiskLevels,
                            personal_by_risk: finalPersonalByRisk,
                            total: processedFinalRiskData?.total || 0
                        }"
                        itemType="final"
                        :organizationId="selectedOrgId"
                    />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else>
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
