<script setup>
import Dashboard from "../Layouts/Dashboard.vue";
import { Link, usePage } from '@inertiajs/vue3';
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import CategoryQualificationTable from '../Components/CategoryQualificationTable.vue';
import CategoryDetailChart from '../Components/CategoryDetailChart.vue';
import DomainQualificationTable from '../Components/DomainQualificationTable.vue';
import DomainDetailChart from '../Components/DomainDetailChart.vue';
import DimensionQualificationTable from '../Components/DimensionQualificationTable.vue';
import DimensionDetailChart from '../Components/DimensionDetailChart.vue';
import DemographicChart from '../Components/DemographicChart.vue';
import { computed, ref, watch } from 'vue';

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

// State for selected category and domain data
const selectedCategoryId = ref(null);
const selectedCategoryName = ref('');
const categoryAnswerDistribution = ref({});
const isLoadingCategoryDetail = ref(false);

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

// Reusable mapping from answer key to label (used for buttons)
// Keep order consistent with CategoryDetailChart
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

// Tab activo del dashboard
const currentTab = ref(props.isAdmin || props.isSuperAdmin ? 'evaluations' : 'categoryAnalysis');

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
                    <!-- TAB: Análisis por Categoría -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'categoryAnalysis'" class="space-y-6">
                        <div v-if="category_qualifications && Object.keys(category_qualifications).length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Calificación por Categoría</h3>
                            <CategoryQualificationTable :qualifications-data="category_qualifications" />

                            <!-- Buttons for Category Detail Drill-down -->
                            <div v-if="category_qualifications.length > 0" class="mt-6">
                                <h4 class="text-md font-semibold mb-2 text-gray-700">Ver detalle de respuestas por categoría:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="category in category_qualifications" :key="category.id"
                                            @click="handleCategoryButtonClick(category.id)"
                                            :class="[
                                                'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                selectedCategoryId === category.id
                                                    ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            ]">
                                        {{ category.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            No hay datos disponibles para el reporte por categoría.
                        </div>

                        <!-- Category Detail Chart Section (Conditional) -->
                        <div v-if="selectedCategoryId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                            <div v-if="isLoadingCategoryDetail" class="text-center text-gray-500 p-4">
                                Cargando detalle de respuestas...
                            </div>
                            <CategoryDetailChart
                                v-else
                                :answer-distribution="categoryAnswerDistribution"
                                :category-name="selectedCategoryName"
                            />
                            <!-- Buttons below chart to show people list -->
                            <div v-if="!isLoadingCategoryDetail && Object.keys(categoryAnswerDistribution).length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                 <h4 class="text-md font-semibold mb-2 text-gray-700">Ver lista de personal por tipo de respuesta:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <!-- Iterate over the answer keys used in the chart -->
                                    <a v-for="(count, key) in categoryAnswerDistribution" :key="key"
                                            :href="count > 0 ? route('reports.peopleList', { categoryId: selectedCategoryId, answerKey: key }) : '#'"
                                            target="_blank"
                                            :class="[
                                                'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                count === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' :
                                                'bg-white text-indigo-600 border-gray-300 hover:bg-indigo-50 hover:border-indigo-300'
                                            ]"
                                            :disabled="count === 0"
                                            :aria-disabled="count === 0"
                                       >
                                        {{ answerKeyToLabelMap[key] || key }} ({{ count }})
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- People List Section (Conditional) -->
                        <div v-if="selectedAnswerKeyForList" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
                            <h4 class="text-md font-semibold mb-3 text-gray-800">
                                Personal que respondió '{{ answerKeyToLabelMap[selectedAnswerKeyForList] || selectedAnswerKeyForList }}' en {{ selectedCategoryName }}:
                            </h4>
                             <div v-if="isLoadingPeopleList" class="text-center text-gray-500 p-3">Cargando lista...</div>
                             <div v-else-if="peopleList.length > 0" class="max-h-48 overflow-y-auto text-sm">
                                <ul class="list-disc list-inside space-y-1">
                                    <!-- Displaying personal_id for now -->
                                    <li v-for="personId in peopleList" :key="personId" class="text-gray-700">{{ personId }}</li>
                                </ul>
                             </div>
                             <div v-else class="text-center text-gray-500 p-3">No se encontró personal para esta respuesta.</div>
                        </div>
                    </div>

                    <!-- TAB: Análisis por Dominio -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'domainAnalysis'" class="space-y-6">
                        <!-- Step 1: Select Category -->
                        <div v-if="!selectedCategoryIdForDomainTab" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Selecciona una Categoría para ver sus Dominios</h3>
                            <div v-if="category_qualifications.length > 0" class="flex flex-wrap gap-3">
                                <button v-for="category in category_qualifications" :key="category.id"
                                        @click="handleSelectCategoryForDomains(category.id)"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 transition-colors">
                                    {{ category.name }}
                                </button>
                            </div>
                            <div v-else class="text-gray-500">No hay categorías disponibles.</div>
                        </div>

                        <!-- Step 2: Show Domains for Selected Category -->
                        <div v-if="selectedCategoryIdForDomainTab" class="space-y-6">
                            <!-- Button to go back -->
                            <div class="flex justify-start mb-4">
                                <button @click="selectedCategoryIdForDomainTab = null; selectedDomainId = null;" class="text-sm text-blue-600 hover:underline">
                                    &larr; Ver todas las categorías
                                </button>
                            </div>

                            <!-- Domain Qualification Table Section -->
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    Calificación por Dominio (Categoría: {{ getCategoryNameById(selectedCategoryIdForDomainTab) }})
                                </h3>
                                <!-- Pass the FILTERED data to the table -->
                                <DomainQualificationTable :qualifications-data="filteredDomainQualifications" />
                                <!-- Buttons for Domain Detail Chart -->
                                <div v-if="filteredDomainQualifications.length > 0" class="mt-6">
                                    <h4 class="text-md font-semibold mb-2 text-gray-700">Ver detalle de respuestas por dominio:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <!-- Iterate over FILTERED domains -->
                                        <button v-for="domain in filteredDomainQualifications" :key="domain.id"
                                                @click="handleDomainButtonClick(domain.id)"
                                                :class="[
                                                    'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                    selectedDomainId === domain.id
                                                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                ]">
                                            {{ domain.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Domain Detail Chart Section (shows when a domain button is clicked) -->
                            <div v-if="selectedDomainId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                                <div v-if="isLoadingDomainDetail">...</div>
                                <DomainDetailChart v-else :answer-distribution="domainAnswerDistribution" :domain-name="selectedDomainName" />
                                <!-- Links below chart to show people list for Domain -->
                                <div v-if="!isLoadingDomainDetail && Object.keys(domainAnswerDistribution).length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                     <h4 class="text-md font-semibold mb-2 text-gray-700">Ver lista de personal que respondió:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <a v-for="(count, key) in domainAnswerDistribution" :key="key"
                                                :href="count > 0 ? route('reports.peopleListDomain', { domainId: selectedDomainId, answerKey: key }) : '#'"
                                                target="_blank"
                                                :class="[
                                                    'inline-block px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                    count === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' :
                                                    'bg-white text-indigo-600 border-gray-300 hover:bg-indigo-50 hover:border-indigo-300'
                                                ]"
                                                :aria-disabled="count === 0"
                                           >
                                            {{ answerKeyToLabelMap[key] || key }} ({{ count }})
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- *** NEW: Dimension Section (shows below Domain Chart if a Domain is selected) *** -->
                            <div v-if="selectedDomainId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                                 <div v-if="isLoadingDimensionData" class="text-center text-gray-500 p-4">Cargando dimensiones...</div>
                                 <div v-else>
                                      <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                         Calificación por Dimensión (Dominio: {{ selectedDomainName }})
                                      </h3>
                                      <DimensionQualificationTable :qualifications-data="dimensionQualifications" />
                                     <!-- Dimension Buttons -->
                                      <div v-if="dimensionQualifications.length > 0" class="mt-6">
                                         <h4 class="text-md font-semibold mb-2 text-gray-700">Ver detalle de respuestas por dimensión:</h4>
                                         <div class="flex flex-wrap gap-2">
                                             <button v-for="dimension in dimensionQualifications" :key="dimension.id"
                                                     @click="handleDimensionButtonClick(dimension.id)"
                                                     :class="[
                                                         'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                         selectedDimensionId === dimension.id
                                                             ? 'bg-purple-600 text-white border-purple-600 hover:bg-purple-700'
                                                             : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                     ]">
                                                 {{ dimension.name }}
                                             </button>
                                         </div>
                                     </div>
                                       <div v-else class="text-gray-500 mt-4">No hay dimensiones para mostrar para este dominio.</div>
                                 </div>
                            </div>
                             <!-- Dimension Detail Chart Section -->
                             <div v-if="selectedDimensionId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                                 <div v-if="isLoadingDimensionDetail" class="text-center text-gray-500 p-4">Cargando detalle dimensión...</div>
                                 <DimensionDetailChart v-else :answer-distribution="dimensionAnswerDistribution" :dimension-name="selectedDimensionName" />
                                  <!-- Dimension People List Links -->
                                <div v-if="!isLoadingDimensionDetail && Object.keys(dimensionAnswerDistribution).length > 0" class="mt-4 pt-4 border-t">
                                    <h4 class="text-md font-semibold mb-2 text-gray-700">Ver lista de personal que respondió:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <a v-for="(count, key) in dimensionAnswerDistribution" :key="key"
                                            :href="count > 0 ? route('reports.peopleListDimension', { dimensionId: selectedDimensionId, answerKey: key }) : '#'"
                                            target="_blank"
                                            :class="[
                                                'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                count === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' :
                                                'bg-white text-indigo-600 border-gray-300 hover:bg-indigo-50 hover:border-indigo-300'
                                            ]"
                                            :disabled="count === 0"
                                            :aria-disabled="count === 0">
                                            {{ answerKeyToLabelMap[key] || key }} ({{ count }})
                                        </a>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>

                    <!-- TAB: Análisis por Dimensión -->
                    <div v-if="!isAdmin && !isSuperAdmin" v-show="currentTab === 'dimensionAnalysis'" class="space-y-6">
                        <!-- Step 1: Select Category -->
                         <div v-if="!selectedCategoryIdForDomainTab" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paso 1: Selecciona una Categoría</h3>
                             <div v-if="category_qualifications.length > 0" class="flex flex-wrap gap-3">
                                 <button v-for="category in category_qualifications" :key="category.id"
                                         @click="handleSelectCategoryForDomains(category.id)"
                                         class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 transition-colors">
                                     {{ category.name }}
                                 </button>
                             </div>
                             <div v-else class="text-gray-500">No hay categorías disponibles.</div>
                         </div>

                        <!-- Step 2: Select Domain (if Category is selected) -->
                        <div v-if="selectedCategoryIdForDomainTab && !selectedDomainId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                             <div class="flex justify-start mb-4">
                                 <button @click="selectedCategoryIdForDomainTab = null" class="text-sm text-blue-600 hover:underline">
                                     &larr; Volver a Categorías
                                 </button>
                             </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paso 2: Selecciona un Dominio (Categoría: {{ getCategoryNameById(selectedCategoryIdForDomainTab) }})</h3>
                            <div v-if="filteredDomainQualifications.length > 0" class="flex flex-wrap gap-3">
                                <button v-for="domain in filteredDomainQualifications" :key="domain.id"
                                        @click="handleDomainButtonClick(domain.id)"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 transition-colors">
                                     {{ domain.name }}
                                 </button>
                            </div>
                             <div v-else class="text-gray-500">No hay dominios para esta categoría.</div>
                        </div>

                        <!-- Step 3: Show Dimension Analysis (if Domain is selected) -->
                        <div v-if="selectedDomainId" class="space-y-6">
                            <!-- Back Button -->
                            <div class="flex justify-start mb-4">
                                <button @click="selectedDomainId = null; dimensionQualifications = []; selectedDimensionId = null; dimensionAnswerDistribution = {};" class="text-sm text-blue-600 hover:underline">
                                    &larr; Volver a Dominios ({{ getCategoryNameById(selectedCategoryIdForDomainTab) }})
                                </button>
                            </div>

                            <!-- Dimension Qualification Table -->
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Calificación por Dimensión (Dominio: {{ getDomainNameById(selectedDomainId) }})</h3>
                                <div v-if="isLoadingDimensionData" class="text-center text-gray-500 p-4">Cargando datos de dimensión...</div>
                                <DimensionQualificationTable v-else :qualifications-data="dimensionQualifications" />

                                <!-- Buttons for Dimension Detail -->
                                <div v-if="!isLoadingDimensionData && dimensionQualifications.length > 0" class="mt-6">
                                    <h4 class="text-md font-semibold mb-2 text-gray-700">Ver detalle de respuestas por dimensión:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <button v-for="dimension in dimensionQualifications" :key="dimension.id"
                                                @click="handleDimensionButtonClick(dimension.id)"
                                                :class="[
                                                    'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                    selectedDimensionId === dimension.id
                                                        ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                ]">
                                            {{ dimension.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Dimension Detail Chart (Conditional) -->
                            <div v-if="selectedDimensionId" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                                <div v-if="isLoadingDimensionDetail" class="text-center text-gray-500 p-4">Cargando detalle de dimensión...</div>
                                <DimensionDetailChart
                                    v-else
                                    :answer-distribution="dimensionAnswerDistribution"
                                    :dimension-name="getDimensionNameById(selectedDimensionId)" />
                                <!-- Links to People List for Dimension -->
                                <div v-if="!isLoadingDimensionDetail && Object.keys(dimensionAnswerDistribution).length > 0" class="mt-4 pt-4 border-t border-gray-200">
                                    <h4 class="text-md font-semibold mb-2 text-gray-700">Ver lista de personal por tipo de respuesta:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <a v-for="(count, key) in dimensionAnswerDistribution" :key="key"
                                            :href="count > 0 ? route('reports.peopleListDimension', { dimensionId: selectedDimensionId, answerKey: key }) : '#'"
                                            target="_blank"
                                            :class="[
                                                'px-3 py-1 border rounded-md shadow-sm text-sm font-medium transition-colors duration-150',
                                                count === 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed pointer-events-none' :
                                                'bg-white text-indigo-600 border-gray-300 hover:bg-indigo-50 hover:border-indigo-300'
                                            ]"
                                            :disabled="count === 0"
                                            :aria-disabled="count === 0">
                                            {{ answerKeyToLabelMap[key] || key }} ({{ count }})
                                        </a>
                                    </div>
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
