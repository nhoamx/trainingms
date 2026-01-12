<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-blue-100 rounded-lg">
          <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Evaluación de Riesgos Psicosociales</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Resultados e interpretación de instrumentos aplicados</p>
    </div>

    <!-- Resumen de Evaluaciones -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <StatCard label="Total de Evaluaciones" :value="totalEvaluations" icon="ClipboardDocumentListIcon" color="blue" />
      <StatCard label="Referencia I (TEPT)" :value="evaluationsByType.referencia_i" icon="DocumentIcon" color="red" />
      <StatCard label="Referencia III (Factores)" :value="evaluationsByType.referencia_iii" icon="AdjustmentsHorizontalIcon" color="orange" />
      <StatCard label="Escala Cisneros (Acoso)" :value="evaluationsByType.cisneros" icon="ShieldCheckIcon" color="purple" />
    </div>

    <!-- Instrumentos Aplicados -->
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-blue-100 rounded-lg">
          <SparklesIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="text-2xl font-bold text-blue-900">Instrumentos de Evaluación Utilizados</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg p-6 border-l-4 border-red-500 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-2 mb-3">
            <DocumentTextIcon class="w-5 h-5 text-red-600" />
            <h4 class="font-bold text-slate-900">Guía de Referencia I</h4>
          </div>
          <p class="text-sm text-slate-600 mb-4">Identificación de trabajadores con TEPT y eventos traumáticos severos</p>
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-red-600 bg-red-50 px-3 py-1.5 rounded-full">Evaluación TEPT</span>
            <span class="text-lg font-bold text-red-600">{{ evaluationsByType.referencia_i }}</span>
          </div>
        </div>
        <div class="bg-white rounded-lg p-6 border-l-4 border-amber-500 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-2 mb-3">
            <AdjustmentsHorizontalIcon class="w-5 h-5 text-amber-600" />
            <h4 class="font-bold text-slate-900">Guía de Referencia III</h4>
          </div>
          <p class="text-sm text-slate-600 mb-4">Identificación de factores de riesgo psicosocial en el trabajo</p>
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full">Factores de Riesgo</span>
            <span class="text-lg font-bold text-amber-600">{{ evaluationsByType.referencia_iii }}</span>
          </div>
        </div>
        <div class="bg-white rounded-lg p-6 border-l-4 border-orange-500 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-2 mb-3">
            <ShieldCheckIcon class="w-5 h-5 text-orange-600" />
            <h4 class="font-bold text-slate-900">Escala Cisneros</h4>
          </div>
          <p class="text-sm text-slate-600 mb-4">Evaluación de violencia laboral, acoso y mobbing</p>
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-orange-600 bg-orange-50 px-3 py-1.5 rounded-full">Violencia Laboral</span>
            <span class="text-lg font-bold text-orange-600">{{ evaluationsByType.cisneros }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado de Datos -->
    <div v-if="evaluations.length === 0" class="bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl p-8 border border-slate-200 text-center">
      <div class="flex justify-center mb-4">
        <div class="p-3 bg-slate-200 rounded-full">
          <DocumentIcon class="w-8 h-8 text-slate-600" />
        </div>
      </div>
      <h4 class="text-xl font-bold text-slate-900 mb-2">Sin Evaluaciones Registradas</h4>
      <p class="text-slate-600">No hay evaluaciones completadas aún para esta organización</p>
    </div>

    <div v-else class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-8 border border-green-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-4">
        <CheckCircleIcon class="w-6 h-6 text-green-600" />
        <h3 class="text-xl font-bold text-green-900">Evaluaciones Completadas</h3>
      </div>
      <p class="text-sm text-green-700">Se han procesado <span class="font-bold">{{ totalEvaluations }}</span> evaluaciones exitosamente</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import StatCard from './StatCard.vue';
import {
  ClipboardDocumentListIcon,
  DocumentIcon,
  AdjustmentsHorizontalIcon,
  ShieldCheckIcon,
  SparklesIcon,
  DocumentTextIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

interface Evaluation {
  id: string;
  evaluation_type?: string;
  personal_folio?: string;
}

interface Props {
  evaluations?: Evaluation[];
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
});

const totalEvaluations = computed(() => props.evaluations?.length || 0);

const evaluationsByType = computed(() => {
  const counts = {
    referencia_i: 0,
    referencia_iii: 0,
    cisneros: 0,
  };

  props.evaluations?.forEach((evaluation) => {
    if (evaluation.evaluation_type === 'referencia_i') counts.referencia_i++;
    if (evaluation.evaluation_type === 'referencia_iii') counts.referencia_iii++;
    if (evaluation.evaluation_type === 'cisneros') counts.cisneros++;
  });

  return counts;
});
</script>
