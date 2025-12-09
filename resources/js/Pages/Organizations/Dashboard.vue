<template>
    <Dashboard>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
                        <!-- Logo -->
                        <div v-if="dashboardData.organization.logo" class="flex-shrink-0">
                            <img :src="dashboardData.organization.logo" :alt="`${dashboardData.organization.name} logo`"
                                class="h-20 w-auto object-contain max-w-xs rounded-lg" />
                        </div>
                        <div v-else class="flex-shrink-0">
                            <div>
                                <h1 class="text-4xl font-bold text-gray-900">{{ dashboardData.organization.name }}</h1>
                            </div>
                        </div>


                    </div>
                </div>

                <!-- Tabs Navigation - Improved Design -->
                <div class="mb-8">
                    <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                            activeTab === tab.key
                                ? 'bg-blue-600 text-white shadow-lg hover:bg-blue-700'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
                        ]" :aria-current="activeTab === tab.key ? 'page' : undefined">
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 sm:p-8">
                        <!-- Datos de Empresa -->
                        <div v-show="activeTab === 'company'" class="animate-fade-in">
                            <CompanyDataTab :company-data="dashboardData.company_data" />
                        </div>

                        <!-- Datos Demográficos -->
                        <div v-show="activeTab === 'demographic'" class="text-center py-16">
                            <div class="text-6xl mb-4">📊</div>
                            <p class="text-2xl font-semibold text-gray-900 mb-2">Próximamente</p>
                            <p class="text-gray-600">Esta sección se habilitará en la próxima versión</p>
                        </div>

                        <!-- Resultados -->
                        <div v-show="activeTab === 'results'" class="text-center py-16">
                            <div class="text-6xl mb-4">📈</div>
                            <p class="text-2xl font-semibold text-gray-900 mb-2">Próximamente</p>
                            <p class="text-gray-600">Esta sección se habilitará en la próxima versión</p>
                        </div>

                        <!-- Análisis -->
                        <div v-show="activeTab === 'analysis'" class="text-center py-16">
                            <div class="text-6xl mb-4">🔍</div>
                            <p class="text-2xl font-semibold text-gray-900 mb-2">Próximamente</p>
                            <p class="text-gray-600">Esta sección se habilitará en la próxima versión</p>
                        </div>

                        <!-- Informe -->
                        <div v-show="activeTab === 'report'" class="text-center py-16">
                            <div class="text-6xl mb-4">📄</div>
                            <p class="text-2xl font-semibold text-gray-900 mb-2">Próximamente</p>
                            <p class="text-gray-600">Esta sección se habilitará en la próxima versión</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Dashboard from '../../Layouts/Dashboard.vue';
import CompanyDataTab from '@/Components/Organization/CompanyDataTab.vue';

interface Tab {
    key: string;
    label: string;
}

interface DashboardData {
    organization: {
        id: string;
        name: string;
        logo: string | null;
    };
    company_data: {
        general: {
            name: string | null;
            razon_social: string | null;
            rfc: string | null;
            registro_patronal: string | null;
            actividad_principal: string | null;
            folio_organization: number | null;
        };
        address: {
            calle_numero: string | null;
            colonia: string | null;
            codigo_postal: string | null;
            municipio: string | null;
            estado: string | null;
        };
        contact: {
            nombre: string | null;
            puesto: string | null;
            email: string | null;
            movil: string | null;
        };
        responsible: {
            nombre: string | null;
            puesto: string | null;
            email: string | null;
            movil: string | null;
        };
    };
    demographic_summary: Record<string, unknown>;
}

interface Props {
    dashboardData: DashboardData;
}

defineProps<Props>();

const tabs: Tab[] = [
    { key: 'company', label: 'Datos de la Empresa' },
    { key: 'demographic', label: 'Datos Demográficos' },
    { key: 'results', label: 'Resultados' },
    { key: 'analysis', label: 'Análisis' },
    { key: 'report', label: 'Informe' },
];

const activeTab = ref<string>('company');
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in;
}
</style>
