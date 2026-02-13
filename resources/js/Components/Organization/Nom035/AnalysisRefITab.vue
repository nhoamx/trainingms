<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-indigo-100 rounded-lg">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Análisis Demográfico</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">
        Distribución de participantes por características demográficas
      </p>
    </div>

    <!-- Empty State -->
    <div v-if="!hasDemographicData" class="text-center py-16">
      <svg class="mx-auto h-16 w-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <p class="mt-4 text-lg font-medium text-slate-900">Sin datos demográficos</p>
      <p class="mt-2 text-sm text-slate-600">
        No se encontraron datos demográficos asociados a las evaluaciones de Referencia I.
      </p>
    </div>

    <!-- Distribución Demográfica -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Demographic Card Component (repeated per category) -->
      <DemographicCard
        v-for="category in demographicCategories"
        :key="category.key"
        :title="category.title"
        :data="getDemographicData(category.key)"
        :color="category.color"
        :total="aggregatedStats.total_participants"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import DemographicCard from './DemographicCard.vue';

interface Participant {
  id: string;
  personal_folio: string;
  folio: string;
  evaluation_type: string;
  created_at: string;
  demographics: Record<string, unknown> | null;
  answers: Record<string, unknown>;
  comments_count: number;
}

interface AggregatedStats {
  total_participants: number;
  total_questions: number;
  demographic_distribution: Record<string, Record<string, number>>;
  answer_distribution: Record<string, unknown>;
  questions_config: Record<string, string>;
}

const props = defineProps<{
  aggregatedStats: AggregatedStats;
  participants: Participant[];
}>();

const demographicCategories = [
  { key: 'by_gender', title: 'Por Género', color: 'blue' },
  { key: 'by_age_range', title: 'Por Rango de Edad', color: 'purple' },
  { key: 'by_department', title: 'Por Departamento/Área', color: 'green' },
  { key: 'by_position', title: 'Por Puesto', color: 'amber' },
  { key: 'by_work_schedule', title: 'Por Turno de Trabajo', color: 'orange' },
  { key: 'by_contract_type', title: 'Por Tipo de Contrato', color: 'indigo' },
];

const hasDemographicData = computed(() => {
  const dist = props.aggregatedStats?.demographic_distribution;
  if (!dist) {
    return false;
  }
  return Object.values(dist).some(
    (category) => Object.keys(category).length > 0
  );
});

const getDemographicData = (key: string): Record<string, number> => {
  return props.aggregatedStats?.demographic_distribution?.[key] ?? {};
};
</script>
