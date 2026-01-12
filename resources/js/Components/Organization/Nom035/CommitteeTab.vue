<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-emerald-100 rounded-lg">
          <UserGroupIcon class="w-6 h-6 text-emerald-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Comité NOM-035</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Integrantes del comité para la prevención de riesgos psicosociales</p>
    </div>

    <!-- Resumen del Comité -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <StatCard label="Total" :value="companyData.committee?.comite_integrantes" icon="UserGroupIcon" color="emerald" />
      <StatCard label="Mujeres" :value="companyData.committee?.comite_mujeres" icon="UserGroupIcon" color="pink" />
      <StatCard label="Hombres" :value="companyData.committee?.comite_hombres" icon="UserGroupIcon" color="blue" />
    </div>

    <!-- Integrantes del Comité -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-emerald-100 rounded-lg">
          <UsersIcon class="w-6 h-6 text-emerald-600" />
        </div>
        <h3 class="text-2xl font-bold text-emerald-900">Integrantes</h3>
      </div>
      <div class="bg-white rounded-lg p-6">
        <div class="space-y-4">
          <InfoRow label="Nombre" :value="companyData.committee?.nombre_integrante" />
          <InfoRow label="Departamento" :value="companyData.committee?.departamento_integrante" />
          <InfoRow label="Puesto" :value="companyData.committee?.puesto_integrante" />
          <InfoRow label="Factor" :value="companyData.committee?.factor_integrante" />
        </div>
      </div>
    </div>

    <!-- Acta Constitutiva -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-blue-100 rounded-lg">
          <DocumentTextIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="text-2xl font-bold text-blue-900">Acta Constitutiva</h3>
      </div>
      <div class="bg-white rounded-lg p-6">
        <div class="flex items-center justify-center p-8 border-2 border-dashed border-slate-300 rounded-lg">
          <div class="text-center">
            <Cog6ToothIcon class="w-12 h-12 text-slate-400 mx-auto mb-3 animate-spin" />
            <p class="text-slate-600 font-medium">En desarrollo</p>
            <p class="text-sm text-slate-500 mt-1">Gestión de acta constitutiva del comité</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Información General del Comité -->
    <div class="bg-gradient-to-r from-slate-50 to-gray-50 rounded-xl p-8 border border-slate-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-slate-100 rounded-lg">
          <ShieldCheckIcon class="w-6 h-6 text-slate-600" />
        </div>
        <h3 class="text-2xl font-bold text-slate-900">Conformación del Comité</h3>
      </div>
      <div class="bg-white rounded-lg p-6 space-y-6">
        <!-- Requisitos Legales NOM-035 -->
        <div>
          <h4 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentListIcon class="w-5 h-5 text-emerald-600" />
            Requisitos Legales (NOM-035-STPS-2018)
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex gap-3 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
              <CheckCircleIcon class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-slate-900">Participación Paritaria</p>
                <p class="text-sm text-slate-600">Representación equilibrada de hombres y mujeres</p>
              </div>
            </div>
            <div class="flex gap-3 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
              <CheckCircleIcon class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-slate-900">Representación Sindical</p>
                <p class="text-sm text-slate-600">Inclusión de representantes de trabajadores</p>
              </div>
            </div>
            <div class="flex gap-3 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
              <CheckCircleIcon class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-slate-900">Representación de Dirección</p>
                <p class="text-sm text-slate-600">Integrantes designados por la empresa</p>
              </div>
            </div>
            <div class="flex gap-3 p-3 bg-emerald-50 rounded-lg border border-emerald-100">
              <CheckCircleIcon class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-medium text-slate-900">Funciones Claras</p>
                <p class="text-sm text-slate-600">Identificación de responsabilidades</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Funciones del Comité -->
        <div class="border-t border-slate-200 pt-6">
          <h4 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <StarIcon class="w-5 h-5 text-teal-600" />
            Funciones del Comité
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex gap-3 p-3 bg-teal-50 rounded-lg border border-teal-100">
              <CheckCircleIcon class="w-5 h-5 text-teal-600 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-slate-700">Coordinación de actividades de prevención</p>
            </div>
            <div class="flex gap-3 p-3 bg-teal-50 rounded-lg border border-teal-100">
              <CheckCircleIcon class="w-5 h-5 text-teal-600 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-slate-700">Supervisión de la evaluación de riesgos</p>
            </div>
            <div class="flex gap-3 p-3 bg-teal-50 rounded-lg border border-teal-100">
              <StarIcon class="w-5 h-5 text-teal-600 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-slate-700">Seguimiento de medidas preventivas</p>
            </div>
            <div class="flex gap-3 p-3 bg-teal-50 rounded-lg border border-teal-100">
              <StarIcon class="w-5 h-5 text-teal-600 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-slate-700">Promoción de una cultura de prevención</p>
            </div>
          </div>
        </div>

        <!-- Estadísticas de Composición -->
        <div v-if="companyData.committee" class="border-t border-slate-200 pt-6">
          <h4 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="w-5 h-5 text-purple-600" />
            Estadísticas de Composición
          </h4>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="font-medium text-slate-700">Porcentaje de Mujeres:</span>
              <div class="flex items-center gap-2">
                <div class="h-2 bg-gradient-to-r from-pink-300 to-pink-500 rounded-full w-32" :style="{ width: percentageFemenino + '%' }"></div>
                <span class="font-bold text-slate-900 w-12 text-right">{{ percentageFemenino }}%</span>
              </div>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
              <span class="font-medium text-slate-700">Porcentaje de Hombres:</span>
              <div class="flex items-center gap-2">
                <div class="h-2 bg-gradient-to-r from-blue-300 to-blue-500 rounded-full w-32" :style="{ width: percentageMasculino + '%' }"></div>
                <span class="font-bold text-slate-900 w-12 text-right">{{ percentageMasculino }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import StatCard from './StatCard.vue';
import {
  UserGroupIcon,
  ShieldCheckIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  StarIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';

interface CommitteeData {
  comite_integrantes?: number | null;
  comite_mujeres?: number | null;
  comite_hombres?: number | null;
}

interface Props {
  companyData: {
    committee?: CommitteeData;
  };
}

const props = defineProps<Props>();

const percentageFemenino = computed(() => {
  const total = props.companyData.committee?.comite_integrantes;
  const mujeres = props.companyData.committee?.comite_mujeres;
  if (!total || !mujeres || total === 0) return 0;
  return Math.round((mujeres / total) * 100);
});

const percentageMasculino = computed(() => {
  const total = props.companyData.committee?.comite_integrantes;
  const hombres = props.companyData.committee?.comite_hombres;
  if (!total || !hombres || total === 0) return 0;
  return Math.round((hombres / total) * 100);
});
</script>
