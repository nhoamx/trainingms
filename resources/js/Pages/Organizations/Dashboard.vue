<template>
  <Dashboard>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <!-- Logo -->
            <div v-if="dashboardData.organization.logo" class="flex-shrink-0">
              <img
                :src="dashboardData.organization.logo"
                :alt="`${dashboardData.organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div v-else class="flex-shrink-0">
              <div>
              <h1 class="text-4xl font-bold text-gray-900">{{ dashboardData.organization.name }}</h1>
              <p class="mt-2 text-gray-600">{{ t('Organization Dashboard') }}</p>
            </div>
            </div>
            <!-- Language Switcher -->
            <div class="sm:ml-auto">
              <LanguageSwitcher />
            </div>
          </div>
        </div>

        <!-- Tabs Navigation - Improved Design -->
        <div class="mb-8">
          <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
            <button
              v-for="tab in translatedTabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                activeTab === tab.key
                  ? 'bg-blue-600 text-white shadow-lg hover:bg-blue-700'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
              ]"
              :aria-current="activeTab === tab.key ? 'page' : undefined"
            >
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
            <div v-show="activeTab === 'demographic'" class="animate-fade-in">
              <DemographicDataTab
                :demographic-details="dashboardData.demographic_details"
                :evaluations="evaluations"
              />
            </div>

            <!-- Resultados -->
            <div v-show="activeTab === 'results'" class="text-center py-16">
              <div class="text-6xl mb-4">📈</div>
              <p class="text-2xl font-semibold text-gray-900 mb-2">{{ t('Work Climate Report') }}</p>
              <p class="text-gray-600 mb-6">{{ t('View the detailed analysis of the evaluation results') }}</p>
              <a
                :href="`/organization/${dashboardData.organization.id}/likert/report`"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors"
              >
                {{ t('View Report') }} →
              </a>
            </div>

            <!-- Análisis -->
            <div v-show="activeTab === 'analysis'" class="animate-fade-in">
              <TopRiskFactorsTab
                :evaluations="evaluations"
                :demographic-details="dashboardData.demographic_details"
                :organization="dashboardData.organization"
              />

            </div>

            <div v-show="activeTab === 'recomendaciones'" class="animate-fade-in">
              <template v-if="dashboardData.organization.id === 'a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238'">
                <RecommendationsTab :evaluations="evaluations" />
              </template>

              <template v-else>
                <!-- Contenido alternativo -->
                <RecommendationsP3Tab :evaluations="evaluations" />
              </template>
            </div>


            <div v-show="activeTab === 'foda'" class="animate-fade-in">
              <FodaDataTab />
            </div>

            <div v-show="activeTab === 'conclusions'" class="animate-fade-in">
              <!-- Planta 1 Conclusions -->
              <template v-if="dashboardData.organization.id === 'a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238'">
                <div class="max-w-4xl mx-auto">
                  <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ t('Conclusions') }}</h2>
                    <p class="text-gray-600">JAROPAMEX S.A. DE C.V. - PLANTA 1</p>
                    <p class="text-sm text-gray-500 mt-2">
                      <span class="font-medium">{{ t('Evaluation period') }}:</span> {{ t('November') }} 2025 |
                      <span class="font-medium">{{ t('Issue date') }}:</span> 10/12/2025
                    </p>
                  </div>

                  <!-- Objetivo General -->
                  <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ t('General Objective') }}</h3>
                    <p class="text-blue-800">
                      {{ t('Evaluate the perception of employees regarding leadership, communication, recognition, work organization, and well-being, to guide decisions and improvement plans.') }}
                    </p>
                  </div>

                  <!-- Análisis -->
                  <h3 class="text-2xl font-bold text-gray-900 mb-6">{{ t('Analysis') }}</h3>

                  <!-- Factor 1: Reconocimiento -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">1) {{ t('Recognition and Reward') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('There is a strong perception of lack of recognition and clear rewards, which can impact motivation, discipline, and retention.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Priority Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Immediate recognition program for safety, quality, and attendance.') }}</li>
                        <li>{{ t('Transparent criteria for critical roles and rewards.') }}</li>
                        <li>{{ t('Train middle management in leadership best practices, avoiding violence.') }}</li>
                        <li>{{ t('Staff motivation by supervisors and leaders (they are not identified).') }}</li>
                      </ul>
                    </div>
                  </div>

                  <!-- Factor 2: Capacitación -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">2) {{ t('Training and Development') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('A significant gap is observed in effective training and educational resources, with direct risk to quality, rework, and safety.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Recommended Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Skills and gaps matrix by position/team.') }}</li>
                        <li>{{ t('Train middle management in leadership best practices, avoiding violence.') }}</li>
                        <li>{{ t('Certify internal instructors.') }}</li>
                        <li>{{ t('Measure effectiveness with competency tests for internal employee growth.') }}</li>
                      </ul>
                    </div>
                  </div>

                  <!-- Factor 3: Equilibrio -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">3) {{ t('Work-Life Balance') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('Responses point to fatigue and pressure from overtime/shifts, a probable trigger for absenteeism and psychosocial risks.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Recommended Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Clear overtime rules with equitable rotation.') }}</li>
                        <li>{{ t('Adjust shift patterns to reduce fatigue.') }}</li>
                        <li>{{ t('Clear and humane process for absenteeism and time off.') }}</li>
                        <li>{{ t('Pulse surveys and safety metrics for monitoring.') }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Planta 3 Conclusions -->
              <template v-else-if="dashboardData.organization.id === 'a06fe33d-6955-4d24-98d1-a375ecb55645'">
                <div class="max-w-4xl mx-auto">
                  <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ t('Conclusions') }}</h2>
                    <p class="text-gray-600">JAROPAMEX S.A. DE C.V. - PLANTA 3</p>
                    <p class="text-sm text-gray-500 mt-2">
                      <span class="font-medium">{{ t('Evaluation period') }}:</span> {{ t('November') }} 2025 |
                      <span class="font-medium">{{ t('Issue date') }}:</span> 10/12/2025
                    </p>
                  </div>

                  <!-- Objetivo General -->
                  <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ t('General Objective') }}</h3>
                    <p class="text-blue-800">
                      {{ t('General study objective') }}
                    </p>
                  </div>

                  <!-- Análisis -->
                  <h3 class="text-2xl font-bold text-gray-900 mb-6">{{ t('Analysis') }}</h3>

                  <!-- Factor 1: Equilibrio vida laboral-personal -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">1) {{ t('Work-Life Balance Critical') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('Responses indicate fatigue and pressure from overtime/shifts, a likely trigger for absenteeism and psychosocial risks.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Recommended Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Clear overtime rules with equitable rotation') }}</li>
                        <li>{{ t('Adjust shift patterns to reduce fatigue') }}</li>
                        <li>{{ t('Clear and humane process for absenteeism and time off') }}</li>
                        <li>{{ t('Pulse surveys and safety metrics for monitoring') }}</li>
                      </ul>
                    </div>
                  </div>

                  <!-- Factor 2: Avance profesional -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">2) {{ t('Professional Advancement Critical') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('Predominates perception of few clear growth paths; this tends to increase turnover and disengagement.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Recommended Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Career paths by job family') }}</li>
                        <li>{{ t('Verifiable promotion criteria') }}</li>
                        <li>{{ t('Team leader development program') }}</li>
                        <li>{{ t('Post vacancies internally first and measure mobility') }}</li>
                      </ul>
                    </div>
                  </div>

                  <!-- Factor 3: Reconocimiento y recompensa -->
                  <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">{{ t('CRITICAL') }}</span>
                      <h4 class="text-xl font-bold text-gray-900">3) {{ t('Recognition and Reward Critical') }}</h4>
                    </div>
                    <p class="text-gray-700 mb-4">
                      {{ t('Significant gap observed in effective training and educational resources, with direct risk to quality, rework, and safety.') }}
                    </p>
                    <div class="bg-white rounded-lg p-4">
                      <h5 class="font-semibold text-gray-900 mb-2">{{ t('Recommended Actions') }}:</h5>
                      <ul class="list-disc list-inside text-gray-700 space-y-1">
                        <li>{{ t('Skills and gaps matrix by position/team') }}</li>
                        <li>{{ t('Train middle management in leadership best practices, avoiding violence') }}</li>
                        <li>{{ t('Certify internal instructors') }}</li>
                        <li>{{ t('Measure effectiveness with competency tests for internal employee growth') }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Other organizations - Coming Soon -->
              <template v-else>
                <div class="text-center py-16">
                  <div class="text-6xl mb-4">🚧</div>
                  <p class="text-2xl font-semibold text-gray-900 mb-2">{{ t('Document in Preparation') }}</p>
                  <p class="text-gray-600">{{ t('We are working on the conclusions for your organization. It will be available soon.') }}</p>
                </div>
              </template>
            </div>

            <!-- Informe -->
            <div v-show="activeTab === 'report'" class="animate-fade-in">
              <ReportTab
                :organization-id="dashboardData.organization.id"
                :organization-name="dashboardData.organization.name"
              />
            </div>

            <div v-show="activeTab === 'evidence'" class="animate-fade-in">
              <EvidencesDataTab :organization-info="dashboardData.organization" />
            </div>


          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Dashboard from '../../Layouts/Dashboard.vue';
import CompanyDataTab from '@/Components/Organization/CompanyDataTab.vue';
import DemographicDataTab from '@/Components/Organization/DemographicDataTab.vue';
import TopRiskFactorsTab from '@/Components/Organization/TopRiskFactorsTab.vue';
import RecommendationsTab from '@/Components/Organization/RecommendationsTab.vue';
import EvidencesDataTab from '@/Components/Organization/EvidencesDataTab.vue';
import FodaDataTab from '@/Components/Organization/FodaDataTab.vue';
import RecommendationsP3Tab from '@/Components/Organization/RecommendationsP3Tab.vue';
import ReportTab from '@/Components/Organization/ReportTab.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

interface Tab {
  key: string;
  labelKey: string;
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  departments: string[];
  work_schedules: string[];
  total_evaluations: number;
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
  demographic_details: DemographicDetails;
}

interface Evaluation {
  id: string;
  demographic_data?: Record<string, unknown>;
  demographicData?: Record<string, unknown>;
  scores?: {
    total_score?: number;
    interpretation?: string;
  };
}

interface Props {
  dashboardData: DashboardData;
  evaluations?: Evaluation[];
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
});

const tabs: Tab[] = [
  { key: 'company', labelKey: 'Company Data' },
  { key: 'demographic', labelKey: 'Demographic Data' },
  { key: 'results', labelKey: 'Results' },
  { key: 'analysis', labelKey: 'Analysis' },
  { key: 'recomendaciones', labelKey: 'Recommendations' },
  { key: 'report', labelKey: 'Report' },
  { key: 'evidence', labelKey: 'Evidence' },
  { key: 'foda', labelKey: 'SWOT' },
  { key: 'conclusions', labelKey: 'Conclusions' },
];

const translatedTabs = computed(() =>
  tabs.map(tab => ({
    key: tab.key,
    label: t(tab.labelKey),
  }))
);

const activeTab = ref<string>('company');
const evaluations = ref<Evaluation[]>(props.evaluations || []);
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
