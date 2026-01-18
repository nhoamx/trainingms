<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-indigo-100 rounded-lg">
          <ChartBarIcon class="w-6 h-6 text-indigo-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Etapas del Cumplimiento NOM-035</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Proceso de implementación y ejecución de la norma</p>
    </div>

    <!-- Sub-tabs -->
    <div class="border-b border-slate-200">
      <nav class="-mb-px flex gap-6">
        <button
          v-for="subTab in subTabs"
          :key="subTab.key"
          @click="activeSubTab = subTab.key"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeSubTab === subTab.key
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <component :is="subTab.icon" class="w-5 h-5" />
            {{ subTab.label }}
          </div>
        </button>
      </nav>
    </div>

    <!-- Contenido de Sub-tabs -->
    <div>
      <!-- Identificar Tab -->
      <div v-if="activeSubTab === 'identificar'" class="space-y-6">
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
          <!-- <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-100 rounded-lg">
              <MagnifyingGlassIcon class="w-6 h-6 text-blue-600" />
            </div>
            <h3 class="text-2xl font-bold text-blue-900">Identificar Riesgos Psicosociales</h3>
          </div> -->
          
          <!-- Contenido si hay datos -->
          <div v-if="props.domainStatistics && Object.keys(props.domainStatistics.domains || {}).length > 0" class="space-y-6">
            <!-- Toggle Dominios/Categorías -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-slate-700">Vista:</span>
                <div class="flex gap-2">
                  <button
                    @click="identificarViewMode = 'domains'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                      identificarViewMode === 'domains'
                        ? 'bg-blue-600 text-white'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    ]"
                  >
                    Dominios
                  </button>
                  <button
                    @click="identificarViewMode = 'categories'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                      identificarViewMode === 'categories'
                        ? 'bg-blue-600 text-white'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    ]"
                  >
                    Categorías
                  </button>
                </div>
              </div>
            </div>

            <!-- Gráficas de dominios -->
            <div v-if="identificarViewMode === 'domains'" class="bg-white rounded-lg p-6">
              <DomainCharts 
                :domains="props.domainStatistics.domains"
                :total-evaluations="props.domainStatistics.total_evaluations"
                :colors="props.domainStatistics.colors"
                :labels="props.domainStatistics.labels"
              />
            </div>
            
            <!-- Gráficas de categorías -->
            <div v-if="identificarViewMode === 'categories'" class="bg-white rounded-lg p-6">
              <CategoryCharts 
                :categories="props.categoryStatistics?.categories || {}"
                :total-evaluations="props.categoryStatistics?.total_evaluations || 0"
                :colors="props.categoryStatistics?.colors || {}"
                :labels="props.categoryStatistics?.labels || {}"
              />
            </div>
          </div>
          
          <!-- Mostrar mensaje si no hay datos -->
          <div v-else class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-blue-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-blue-400 mx-auto mb-3 animate-spin" />
                <p class="text-blue-700 font-medium">Sin datos disponibles</p>
                <p class="text-sm text-blue-600 mt-1">No se han encontrado evaluaciones de Referencia III para mostrar estadísticas</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
              <h4 class="font-bold text-slate-900">Cuestionarios</h4>
            </div>
            <p class="text-sm text-slate-600">Instrumentos de evaluación para identificar factores de riesgo</p>
          </div>
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <UserGroupIcon class="w-6 h-6 text-blue-600" />
              <h4 class="font-bold text-slate-900">Entrevistas</h4>
            </div>
            <p class="text-sm text-slate-600">Conversaciones con trabajadores para detectar situaciones de riesgo</p>
          </div>
        </div>
      </div>

      <!-- Analizar Tab -->
      <div v-if="activeSubTab === 'analizar'" class="space-y-6">
        <div class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl p-8 border border-purple-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-purple-100 rounded-lg">
              <ChartBarIcon class="w-6 h-6 text-purple-600" />
            </div>
            <h3 class="text-2xl font-bold text-purple-900">Analizar Resultados</h3>
          </div>

          <!-- Filtros Demográficos -->
          <div v-if="props.analysisData && props.analysisData.evaluations.length > 0" class="space-y-6">
            <AnalysisFilters
              :demographics="props.analysisData.demographics"
              v-model="analysisFilters"
            />

            <!-- Toggle Dominios/Categorías -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-slate-700">Vista:</span>
                <div class="flex gap-2">
                  <button
                    @click="analysisViewMode = 'domains'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                      analysisViewMode === 'domains'
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    ]"
                  >
                    Dominios
                  </button>
                  <button
                    @click="analysisViewMode = 'categories'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                      analysisViewMode === 'categories'
                        ? 'bg-indigo-600 text-white'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    ]"
                  >
                    Categorías
                  </button>
                </div>
                <div class="ml-auto text-sm text-slate-600">
                  <span class="font-semibold">{{ filteredEvaluations.length }}</span> evaluaciones filtradas
                </div>
              </div>
            </div>

            <!-- Distribución y Gráfica -->
            <div class="bg-white rounded-lg p-6 border border-slate-200">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Cards de distribución -->
                <div>
                  <RiskDistributionCards
                    :distribution="filteredDistribution"
                    :colors="props.analysisData.colors"
                    :labels="props.analysisData.labels"
                  />
                </div>

                <!-- Gráfica de pastel -->
                <div>
                  <RiskPieChart
                    :distribution="filteredDistribution"
                    :colors="props.analysisData.colors"
                    :labels="props.analysisData.labels"
                    :title="analysisViewMode === 'domains' ? 'Distribución por Dominios' : 'Distribución por Categorías'"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Sin datos -->
          <div v-else class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-purple-300 rounded-lg">
              <div class="text-center">
                <ChartPieIcon class="w-12 h-12 text-purple-400 mx-auto mb-3" />
                <p class="text-purple-700 font-medium">Sin datos disponibles</p>
                <p class="text-sm text-purple-600 mt-1">No se han encontrado evaluaciones de Referencia III para analizar</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Prevenir Tab -->
      <div v-if="activeSubTab === 'prevenir'" class="space-y-6">
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-emerald-100 rounded-lg">
              <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
            </div>
            <h3 class="text-2xl font-bold text-emerald-900">Prevenir y Controlar Riesgos</h3>
          </div>
          <div class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-emerald-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-emerald-400 mx-auto mb-3 animate-spin" />
                <p class="text-emerald-700 font-medium">En desarrollo</p>
                <p class="text-sm text-emerald-600 mt-1">Acciones preventivas y planes de mejora continua</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <LightBulbIcon class="w-6 h-6 text-emerald-600" />
              <h4 class="font-bold text-slate-900">Medidas Preventivas</h4>
            </div>
            <p class="text-sm text-slate-600">Implementación de acciones para reducir riesgos identificados</p>
          </div>
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ArrowPathIcon class="w-6 h-6 text-emerald-600" />
              <h4 class="font-bold text-slate-900">Seguimiento</h4>
            </div>
            <p class="text-sm text-slate-600">Monitoreo continuo de la efectividad de las medidas implementadas</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import DomainCharts from './Charts/DomainCharts.vue';
import CategoryCharts from './Charts/CategoryCharts.vue';
import AnalysisFilters from './Charts/AnalysisFilters.vue';
import RiskDistributionCards from './Charts/RiskDistributionCards.vue';
import RiskPieChart from './Charts/RiskPieChart.vue';
import {
  ChartBarIcon,
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  ClipboardDocumentListIcon,
  UserGroupIcon,
  ChartPieIcon,
  DocumentChartBarIcon,
  LightBulbIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

interface DomainStatistics {
  domains: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface CategoryStatistics {
  categories: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface Evaluation {
  id: string;
  folio: string;
  personal_folio: string;
  evaluee_name: string;
  demographics: {
    genero: string;
    puesto: string;
    area: string;
    turno: string;
  };
  domain_scores: Record<string, { score: number; risk_level: string }>;
  category_scores: Record<string, { score: number; risk_level: string; domain: string }>;
}

interface AnalysisData {
  evaluations: Evaluation[];
  demographics: {
    generos: string[];
    puestos: string[];
    areas: string[];
    turnos: string[];
  };
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface Props {
  domainStatistics?: DomainStatistics;
  categoryStatistics?: CategoryStatistics;
  analysisData?: AnalysisData;
}

const props = withDefaults(defineProps<Props>(), {
  domainStatistics: () => ({ domains: {}, total_evaluations: 0, colors: {}, labels: {} }),
  categoryStatistics: () => ({ categories: {}, total_evaluations: 0, colors: {}, labels: {} }),
  analysisData: () => ({ evaluations: [], demographics: { generos: [], puestos: [], areas: [], turnos: [] }, colors: {}, labels: {} }),
});

const activeSubTab = ref('identificar');

// Identificar state
const identificarViewMode = ref<'domains' | 'categories'>('domains');

// Analysis state
const analysisViewMode = ref<'domains' | 'categories'>('domains');
const analysisFilters = ref({
  genero: '',
  puesto: '',
  area: '',
  turno: '',
});

// Filtered evaluations based on demographic filters
const filteredEvaluations = computed(() => {
  if (!props.analysisData) return [];
  
  return props.analysisData.evaluations.filter(evaluation => {
    if (analysisFilters.value.genero && evaluation.demographics.genero !== analysisFilters.value.genero) {
      return false;
    }
    if (analysisFilters.value.puesto && evaluation.demographics.puesto !== analysisFilters.value.puesto) {
      return false;
    }
    if (analysisFilters.value.area && evaluation.demographics.area !== analysisFilters.value.area) {
      return false;
    }
    if (analysisFilters.value.turno && evaluation.demographics.turno !== analysisFilters.value.turno) {
      return false;
    }
    return true;
  });
});

// Recalculate distribution based on filtered evaluations
const filteredDistribution = computed(() => {
  const distribution: Record<string, number> = {
    nulo: 0,
    bajo: 0,
    medio: 0,
    alto: 0,
    muy_alto: 0,
  };

  if (analysisViewMode.value === 'domains') {
    // Count evaluations by their highest risk level across all domains
    filteredEvaluations.value.forEach(evaluation => {
      const riskLevels = Object.values(evaluation.domain_scores).map((score: any) => score.risk_level);
      const highestRisk = getHighestRiskLevel(riskLevels);
      distribution[highestRisk]++;
    });
  } else {
    // Count evaluations by their highest risk level across all categories
    filteredEvaluations.value.forEach(evaluation => {
      const riskLevels = Object.values(evaluation.category_scores).map((score: any) => score.risk_level);
      const highestRisk = getHighestRiskLevel(riskLevels);
      distribution[highestRisk]++;
    });
  }

  return distribution;
});

// Helper function to get the highest risk level from an array
const getHighestRiskLevel = (levels: string[]): string => {
  const hierarchy = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  let maxIndex = 0;
  
  levels.forEach(level => {
    const index = hierarchy.indexOf(level);
    if (index > maxIndex) {
      maxIndex = index;
    }
  });
  
  return hierarchy[maxIndex];
};

const subTabs = [
  { key: 'identificar', label: 'Identificar', icon: MagnifyingGlassIcon },
  { key: 'analizar', label: 'Analizar', icon: ChartBarIcon },
  { key: 'prevenir', label: 'Prevenir', icon: ShieldCheckIcon },
];
</script>
