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
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-100 rounded-lg">
              <MagnifyingGlassIcon class="w-6 h-6 text-blue-600" />
            </div>
            <h3 class="text-2xl font-bold text-blue-900">Identificar Riesgos Psicosociales</h3>
          </div>
          
          <!-- Mostrar gráficas de dominios si hay datos -->
          <div v-if="props.domainStatistics && Object.keys(props.domainStatistics.domains || {}).length > 0" class="bg-white rounded-lg p-6 mb-6">
            <DomainCharts 
              :domains="props.domainStatistics.domains"
              :total-evaluations="props.domainStatistics.total_evaluations"
              :colors="props.domainStatistics.colors"
              :labels="props.domainStatistics.labels"
            />
          </div>
          
          <!-- Mostrar gráficas de categorías -->
          <div class="bg-white rounded-lg p-6 mb-6">
            <CategoryCharts 
              :categories="props.categoryStatistics?.categories || {}"
              :total-evaluations="props.categoryStatistics?.total_evaluations || 0"
              :colors="props.categoryStatistics?.colors || {}"
              :labels="props.categoryStatistics?.labels || {}"
            />
          </div>
          
          <!-- Mostrar mensaje de desarrollo si no hay datos -->
          <div v-if="!props.domainStatistics || Object.keys(props.domainStatistics.domains || {}).length === 0" class="bg-white rounded-lg p-6">
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
          <div class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-purple-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-purple-400 mx-auto mb-3 animate-spin" />
                <p class="text-purple-700 font-medium">En desarrollo</p>
                <p class="text-sm text-purple-600 mt-1">Análisis estadístico y clasificación de niveles de riesgo</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ChartPieIcon class="w-6 h-6 text-purple-600" />
              <h4 class="font-bold text-slate-900">Estadísticas</h4>
            </div>
            <p class="text-sm text-slate-600">Análisis cuantitativo de los datos recopilados</p>
          </div>
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <DocumentChartBarIcon class="w-6 h-6 text-purple-600" />
              <h4 class="font-bold text-slate-900">Reportes</h4>
            </div>
            <p class="text-sm text-slate-600">Generación de informes con hallazgos y conclusiones</p>
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
import { ref } from 'vue';
import DomainCharts from './Charts/DomainCharts.vue';
import CategoryCharts from './Charts/CategoryCharts.vue';
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

interface Props {
  domainStatistics?: DomainStatistics;
  categoryStatistics?: CategoryStatistics;
}

const props = withDefaults(defineProps<Props>(), {
  domainStatistics: () => ({ domains: {}, total_evaluations: 0, colors: {}, labels: {} }),
  categoryStatistics: () => ({ categories: {}, total_evaluations: 0, colors: {}, labels: {} }),
});

const activeSubTab = ref('identificar');

const subTabs = [
  { key: 'identificar', label: 'Identificar', icon: MagnifyingGlassIcon },
  { key: 'analizar', label: 'Analizar', icon: ChartBarIcon },
  { key: 'prevenir', label: 'Prevenir', icon: ShieldCheckIcon },
];
</script>
