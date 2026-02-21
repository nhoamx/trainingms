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
      <StatCard label="Total" :value="companyData.committee?.comite_integrantes" icon="UserGroupIcon" color="green" />
      <StatCard label="Mujeres" :value="companyData.committee?.comite_mujeres" icon="UserGroupIcon" color="pink" />
      <StatCard label="Hombres" :value="companyData.committee?.comite_hombres" icon="UserGroupIcon" color="blue" />
    </div>

    <!-- Integrantes del Comité -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
        <div class="p-2 bg-emerald-100 rounded-lg">
          <UsersIcon class="w-6 h-6 text-emerald-600" />
        </div>
        <h3 class="text-2xl font-bold text-emerald-900">Integrantes</h3>
        </div>
        <button
          v-if="canManageCommitteeMembers"
          type="button"
          @click="emit('add-member')"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
          Agregar miembro
        </button>
      </div>
      <div class="bg-white rounded-lg p-6">
        <div v-if="committeeMembers.length > 0" class="overflow-hidden bg-white shadow ring-1 ring-gray-200 sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factor</th>
                <th v-if="canManageCommitteeMembers" scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Acciones</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="member in committeeMembers" :key="member.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ member.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.department_area }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.position }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.factor }}</td>
                <td v-if="canManageCommitteeMembers" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    type="button"
                    @click="emit('delete-member', member)"
                    class="text-red-600 hover:text-red-900 transition-colors"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else-if="companyData.committee?.nombre_integrante" class="space-y-4">
          <InfoRow label="Nombre" :value="companyData.committee?.nombre_integrante" />
          <InfoRow label="Departamento" :value="companyData.committee?.departamento_integrante" />
          <InfoRow label="Puesto" :value="companyData.committee?.puesto_integrante" />
          <InfoRow label="Factor" :value="companyData.committee?.factor_integrante" />
        </div>
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <p class="text-sm font-semibold text-gray-900">No hay miembros registrados</p>
          <p class="mt-1 text-sm text-gray-500">Agrega integrantes para este centro de trabajo.</p>
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
        <div class="space-y-5">
          <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-slate-900">Documento cargado por el centro de trabajo</p>
                <p class="text-xs text-slate-600 mt-1">Fecha: {{ formatDate(constitutiveAct.submitted_at) }}</p>
              </div>
              <a
                v-if="constitutiveAct.submitted_path && canViewSubmittedAct"
                :href="route('work-centers.constitutive-act.download-submitted', workCenterId)"
                class="inline-flex items-center rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
              >
                Descargar
              </a>
            </div>

            <div v-if="canUploadSubmittedAct" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ constitutiveAct.submitted_path ? 'Reemplazar documento del centro' : 'Subir documento del centro' }}
              </label>
              <input
                type="file"
                accept=".pdf,.doc,.docx"
                @change="handleSubmittedActUpload"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                :disabled="submittedActForm.processing"
              />
              <p v-if="submittedActForm.errors.constitutive_act_submitted" class="mt-1 text-xs text-red-500">
                {{ submittedActForm.errors.constitutive_act_submitted }}
              </p>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-slate-900">Versión administrativa vigente</p>
                <p class="text-xs text-slate-600 mt-1">Fecha: {{ formatDate(constitutiveAct.admin_at) }}</p>
              </div>
              <a
                v-if="constitutiveAct.admin_path"
                :href="route('work-centers.constitutive-act.download-admin', workCenterId)"
                class="inline-flex items-center rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200"
              >
                Descargar
              </a>
            </div>

            <div v-if="canUploadAdminAct" class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ constitutiveAct.admin_path ? 'Subir nueva versión administrativa' : 'Subir versión administrativa' }}
              </label>
              <input
                type="file"
                accept=".pdf,.doc,.docx"
                @change="handleAdminActUpload"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                :disabled="adminActForm.processing"
              />
              <p v-if="adminActForm.errors.constitutive_act_admin" class="mt-1 text-xs text-red-500">
                {{ adminActForm.errors.constitutive_act_admin }}
              </p>
            </div>
          </div>

          <div v-if="!constitutiveAct.submitted_path && !constitutiveAct.admin_path" class="text-center py-6 border-2 border-dashed border-slate-300 rounded-lg">
            <Cog6ToothIcon class="w-8 h-8 text-slate-400 mx-auto mb-2" />
            <p class="text-slate-600 font-medium">Aún no hay acta constitutiva cargada</p>
            <p class="text-sm text-slate-500 mt-1">El equipo del centro puede cargar la primera versión y administración puede publicar la versión vigente.</p>
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
import { useForm } from '@inertiajs/vue3';
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
  nombre_integrante?: string | null;
  departamento_integrante?: string | null;
  puesto_integrante?: string | null;
  factor_integrante?: string | null;
}

interface CommitteeMember {
  id: number;
  name: string;
  department_area: string;
  position: string;
  factor: string;
}

interface ConstitutiveActData {
  submitted_path: string | null;
  submitted_at: string | null;
  admin_path: string | null;
  admin_at: string | null;
}

interface Props {
  companyData: {
    committee?: CommitteeData;
  };
  committeeMembers?: CommitteeMember[];
  canManageCommitteeMembers?: boolean;
  workCenterId?: string;
  constitutiveAct?: ConstitutiveActData;
  canViewSubmittedAct?: boolean;
  canUploadSubmittedAct?: boolean;
  canUploadAdminAct?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'add-member'): void;
  (e: 'delete-member', member: CommitteeMember): void;
}>();

const committeeMembers = computed(() => props.committeeMembers ?? []);
const canManageCommitteeMembers = computed(() => props.canManageCommitteeMembers ?? false);
const workCenterId = computed(() => props.workCenterId ?? '');
const canUploadSubmittedAct = computed(() => props.canUploadSubmittedAct ?? false);
const canUploadAdminAct = computed(() => props.canUploadAdminAct ?? false);
const canViewSubmittedAct = computed(() => props.canViewSubmittedAct ?? false);
const constitutiveAct = computed(() => props.constitutiveAct ?? {
  submitted_path: null,
  submitted_at: null,
  admin_path: null,
  admin_at: null,
});

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const submittedActForm = useForm({
  constitutive_act_submitted: null as File | null,
});

const adminActForm = useForm({
  constitutive_act_admin: null as File | null,
});

const formatDate = (value: string | null): string => {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleDateString('es-MX');
};

const handleSubmittedActUpload = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;

  if (!file || !workCenterId.value) {
    return;
  }

  submittedActForm.constitutive_act_submitted = file;
  submittedActForm.post(route('work-centers.constitutive-act.upload-submitted', workCenterId.value), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      submittedActForm.reset();
      target.value = '';
    },
  });
};

const handleAdminActUpload = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;

  if (!file || !workCenterId.value) {
    return;
  }

  adminActForm.constitutive_act_admin = file;
  adminActForm.post(route('work-centers.constitutive-act.upload-admin', workCenterId.value), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      adminActForm.reset();
      target.value = '';
    },
  });
};

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
