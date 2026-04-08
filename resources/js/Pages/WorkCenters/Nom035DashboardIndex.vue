<template>
  <Dashboard>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <div v-if="organization.logo" class="flex-shrink-0">
              <img
                :src="organization.logo"
                :alt="`${organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div class="flex-shrink-0">
              <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ workCenter.name }}</h1>
                <p class="mt-1 text-gray-600">NOM-035-STPS-2018</p>
              </div>
            </div>
          </div>

          <!-- Breadcrumb -->
          <nav class="flex items-center text-sm text-gray-500 mt-2" aria-label="Breadcrumb">
            <Link
              :href="route('my-work-centers')"
              class="hover:text-blue-600 transition-colors"
            >
              Mis Centros de Trabajo
            </Link>
            <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-gray-900">{{ workCenter.name }}</span>
          </nav>
        </div>

        <!-- Summary Banner -->
        <div class="mb-8 overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-r from-blue-600 via-indigo-600 to-indigo-700 p-4 text-white shadow-lg sm:p-6 lg:p-7">
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-center">
            <div class="lg:col-span-2">
              <h2 class="text-xl font-bold leading-tight sm:text-2xl">Dashboard NOM-035-STPS-2018</h2>
              <p class="mt-1 text-sm leading-relaxed text-blue-100 sm:text-base">
                Elige una fuente para enfocar métricas e instrumentos sin perder contexto general.
              </p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
              <div class="rounded-xl border border-white/25 bg-white/15 px-3 py-2.5 backdrop-blur-sm sm:text-center lg:text-left xl:text-center">
                <div class="flex items-center justify-between gap-2 sm:block">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-100">Total</p>
                  <p class="text-2xl font-bold sm:mt-1">{{ sourceCount('all') }}</p>
                </div>
              </div>
              <div class="rounded-xl border border-white/25 bg-white/15 px-3 py-2.5 backdrop-blur-sm sm:text-center lg:text-left xl:text-center">
                <div class="flex items-center justify-between gap-2 sm:block">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-100">En línea</p>
                  <p class="text-2xl font-bold sm:mt-1">{{ sourceCount('online') }}</p>
                </div>
              </div>
              <div class="rounded-xl border border-white/25 bg-white/15 px-3 py-2.5 backdrop-blur-sm sm:text-center lg:text-left xl:text-center">
                <div class="flex items-center justify-between gap-2 sm:block">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-100">Presencial</p>
                  <p class="text-2xl font-bold sm:mt-1">{{ sourceCount('paper') }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 rounded-xl border border-white/25 bg-white/10 p-3 backdrop-blur-sm sm:p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-100">Fuente de datos</p>
              <p class="text-xs leading-relaxed text-blue-100 sm:max-w-sm sm:text-right">{{ sourceDescription }}</p>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
              <Link
                v-for="option in sourceOptions"
                :key="option.key"
                :href="sourceHref(option.key)"
                class="group rounded-xl border px-3 py-3 transition-all sm:px-4"
                :class="selectedSource === option.key
                  ? 'border-white bg-white text-slate-900 shadow-md'
                  : 'border-white/30 bg-transparent text-white hover:border-white/60 hover:bg-white/15'"
              >
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold leading-tight">{{ option.label }}</p>
                    <p class="mt-0.5 text-xs" :class="selectedSource === option.key ? 'text-slate-600' : 'text-blue-100'">{{ option.caption }}</p>
                  </div>
                  <span
                    class="inline-flex min-w-8 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="selectedSource === option.key ? option.chipClass : 'bg-white/20 text-white'"
                  >
                    {{ sourceCount(option.key) }}
                  </span>
                </div>
              </Link>
            </div>
          </div>
        </div>

        <!-- General Layer -->
        <div class="mb-8 bg-white rounded-2xl border border-gray-200 shadow-sm">
          <div class="px-6 pt-6 pb-3 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Capa General</h2>
            <p class="mt-1 text-sm text-gray-600">Información base compartida antes de entrar a etapas e instrumentos</p>
          </div>

          <div class="px-6 pt-4">
            <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
              <button
                v-for="tab in generalTabs"
                :key="tab.key"
                @click="activeGeneralTab = tab.key"
                :class="[
                  'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                  activeGeneralTab === tab.key
                    ? 'bg-blue-600 text-white shadow-lg hover:bg-blue-700'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
                ]"
                :aria-current="activeGeneralTab === tab.key ? 'page' : undefined"
              >
                {{ tab.label }}
              </button>
            </nav>
          </div>

          <div class="p-6 sm:p-8">
            <div v-show="activeGeneralTab === 'empresa'" class="animate-fade-in">
              <EmpresaTab :company-data="dashboardData.company_data" :organization="dashboardData.organization" />
            </div>

            <div v-show="activeGeneralTab === 'evaluacion'" class="animate-fade-in">
              <EvaluationTab
                :evaluations="evaluations"
                :available-evaluation-types="availableEvaluationTypes"
              />
            </div>

            <div v-show="activeGeneralTab === 'comite'" class="animate-fade-in space-y-6">
              <CommitteeTab
                :company-data="dashboardData.company_data"
                :committee-members="committeeMembers"
                :can-manage-committee-members="isAdmin"
                :work-center-id="workCenter.id"
                :constitutive-act="constitutiveAct"
                :can-view-submitted-act="isAdmin"
                :can-upload-submitted-act="canUploadSubmittedAct"
                :can-upload-admin-act="isAdmin"
                @add-member="openCommitteeMemberModal"
                @delete-member="confirmDeleteMember"
              />
            </div>

            <div v-show="activeGeneralTab === 'sensibilizacion'" class="animate-fade-in">
              <SensibilizationTab
                :videos="sensitizationVideos"
                :can-manage-videos="isAdmin"
                :work-center-id="workCenter.id"
              />
            </div>

            <div v-show="activeGeneralTab === 'politica'" class="animate-fade-in">
              <PolicyTab />
            </div>
          </div>
        </div>

        <!-- Instrument Cards Grid -->
        <div class="mb-4 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Instrumentos NOM-035</h3>
            <p class="text-sm text-gray-600 mt-1">Selecciona un instrumento para ver su análisis especializado</p>
          </div>
          <p class="text-xs text-gray-500">Los instrumentos sin datos aparecen bloqueados</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <Link
            v-for="instrument in instruments"
            :key="instrument.key"
            :href="instrumentHref(instrument)"
            class="group block h-full bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 overflow-hidden"
            :class="{ 'opacity-60 pointer-events-none': instrument.count === 0 }"
          >
            <!-- Color Accent Bar -->
            <div
              class="h-1.5"
              :class="colorAccent(instrument.color)"
            />

            <div class="p-6 h-full flex flex-col">
              <!-- Icon + Title -->
              <div class="flex items-start gap-4 mb-4">
                <div
                  class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                  :class="iconBg(instrument.color)"
                >
                  <!-- chart-bar icon (Ref III) -->
                  <svg v-if="instrument.icon === 'chart-bar'" class="w-6 h-6" :class="iconColor(instrument.color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  <!-- document-text icon (Ref I) -->
                  <svg v-else-if="instrument.icon === 'document-text'" class="w-6 h-6" :class="iconColor(instrument.color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <!-- shield-check icon (Cisneros) -->
                  <svg v-else-if="instrument.icon === 'shield-check'" class="w-6 h-6" :class="iconColor(instrument.color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3.944a11.955 11.955 0 01-8.618 2.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.59 21 9c0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                  <!-- sun icon (Clima Laboral) -->
                  <svg v-else-if="instrument.icon === 'sun'" class="w-6 h-6" :class="iconColor(instrument.color)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                </div>

                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-700 transition-colors">
                    {{ instrument.label }}
                  </h3>
                  <p class="text-sm font-medium" :class="subtitleColor(instrument.color)">
                    {{ instrument.subtitle }}
                  </p>
                </div>

                <!-- Count Badge -->
                <div
                  class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-bold"
                  :class="badgeClasses(instrument.color)"
                >
                  {{ instrument.count }}
                </div>
              </div>


              <!-- Description -->
              <p class="text-sm text-gray-600 leading-relaxed mb-4">
                {{ instrument.description }}
              </p>

              <div class="mb-4 flex flex-wrap gap-2 text-xs">
                <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 font-semibold text-sky-700">
                  Online: {{ instrument.online_count ?? 0 }}
                </span>
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-700">
                  Presencial: {{ instrument.paper_count ?? 0 }}
                </span>
              </div>

              <!-- Footer -->
              <div class="mt-auto flex items-center justify-between pt-4 border-t border-gray-100">
                <span
                  v-if="instrument.count > 0"
                  class="inline-flex items-center text-sm font-medium"
                  :class="subtitleColor(instrument.color)"
                >
                  <span class="relative flex h-2 w-2 mr-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="pingColor(instrument.color)"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2" :class="dotColor(instrument.color)"></span>
                  </span>
                  {{ instrument.count }} {{ instrument.count === 1 ? 'evaluación' : 'evaluaciones' }} {{ selectedSourceLabel }}
                </span>
                <span v-else class="text-sm text-gray-400">
                  Sin evaluaciones
                </span>

                <span class="inline-flex items-center text-sm font-medium text-gray-500 group-hover:text-blue-600 transition-colors">
                  Ver dashboard
                  <svg class="ml-1.5 w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </span>
              </div>
            </div>
          </Link>
        </div>

        <!-- Empty State -->
        <div v-if="totalEvaluations === 0" class="mt-8 text-center py-12 bg-white rounded-2xl border border-gray-200">
          <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-4 text-lg font-semibold text-gray-900">Sin evaluaciones procesadas</h3>
          <p class="mt-2 text-sm text-gray-600 max-w-md mx-auto">
            Aún no se han procesado evaluaciones NOM-035 para este centro de trabajo.
            Una vez que se importen evaluaciones, podrás acceder a los dashboards por instrumento.
          </p>
        </div>

        <div v-if="showCommitteeMemberModal && isAdmin" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Agregar Miembro del Comité</h3>
              <button @click="closeCommitteeMemberModal" type="button" class="text-gray-400 hover:text-gray-500" aria-label="Cerrar modal">
                ×
              </button>
            </div>

            <form @submit.prevent="submitCommitteeMember" class="space-y-4">
              <div>
                <label for="member_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input id="member_name" v-model="committeeMemberForm.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                <p v-if="committeeMemberForm.errors.name" class="mt-1 text-xs text-red-500">{{ committeeMemberForm.errors.name }}</p>
              </div>
              <div>
                <label for="member_department" class="block text-sm font-medium text-gray-700">Área</label>
                <input id="member_department" v-model="committeeMemberForm.department_area" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                <p v-if="committeeMemberForm.errors.department_area" class="mt-1 text-xs text-red-500">{{ committeeMemberForm.errors.department_area }}</p>
              </div>
              <div>
                <label for="member_position" class="block text-sm font-medium text-gray-700">Puesto</label>
                <input id="member_position" v-model="committeeMemberForm.position" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                <p v-if="committeeMemberForm.errors.position" class="mt-1 text-xs text-red-500">{{ committeeMemberForm.errors.position }}</p>
              </div>
              <div>
                <label for="member_factor" class="block text-sm font-medium text-gray-700">Factor</label>
                <input id="member_factor" v-model="committeeMemberForm.factor" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                <p v-if="committeeMemberForm.errors.factor" class="mt-1 text-xs text-red-500">{{ committeeMemberForm.errors.factor }}</p>
              </div>

              <div class="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  @click="closeCommitteeMemberModal"
                  class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="committeeMemberForm.processing"
                  class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                >
                  {{ committeeMemberForm.processing ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showDeleteConfirmModal && isAdmin" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900">Eliminar Miembro</h3>
            <p class="mt-2 text-sm text-gray-500">
              ¿Estás seguro de que deseas eliminar a <strong>{{ memberToDelete?.name }}</strong>? Esta acción no se puede deshacer.
            </p>
            <div class="mt-5 flex justify-end gap-3">
              <button
                type="button"
                @click="showDeleteConfirmModal = false"
                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button
                type="button"
                @click="deleteMember"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
import EmpresaTab from '@/Components/Organization/Nom035/EmpresaTab.vue';
import CommitteeTab from '@/Components/Organization/Nom035/CommitteeTab.vue';
import SensibilizationTab from '@/Components/Organization/Nom035/SensibilizationTab.vue';
import PolicyTab from '@/Components/Organization/Nom035/PolicyTab.vue';
import EvaluationTab from '@/Components/Organization/Nom035/EvaluationTab.vue';

interface WorkCenterInfo {
  id: string;
  name: string;
  code: string;
}

interface OrganizationInfo {
  id: string;
  name: string;
  logo: string | null;
}

interface Instrument {
  key: string;
  label: string;
  subtitle: string;
  description: string;
  count: number;
  online_count: number;
  paper_count: number;
  route: string;
  color: string;
  icon: string;
}

interface SourceSummary {
  online: number;
  paper: number;
}

interface CompanyData {
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
  workforce?: {
    total_trabajadores: number | null;
    total_hombres: number | null;
    total_mujeres: number | null;
  };
  sample?: {
    muestra_aplicada: number | null;
    muestra_hombres: number | null;
    muestra_mujeres: number | null;
    justificacion_muestra: string | null;
  };
  committee?: {
    comite_integrantes: number | null;
    comite_mujeres: number | null;
    comite_hombres: number | null;
  };
  evaluation_date?: string | null;
}

interface DashboardData {
  organization: {
    id: string;
    name: string;
    logo: string | null;
  };
  work_center: WorkCenterInfo;
  company_data: CompanyData;
}

interface Evaluation {
  id: string;
  evaluation_type?: string;
  personal_folio?: string;
  demographicData?: Record<string, unknown>;
}

interface EvaluationType {
  key: string;
  label: string;
  description: string;
  badge: string;
  color: string;
  icon: string;
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

interface SensitizationVideo {
  id: number;
  title: string;
  description: string | null;
  audience: string;
  video_url: string;
  original_filename: string;
  file_size_human: string;
  created_at: string | null;
}

const props = withDefaults(defineProps<{
  workCenter: WorkCenterInfo;
  organization: OrganizationInfo;
  dashboardData: DashboardData;
  instruments: Instrument[];
  totalEvaluations: number;
  selectedSource?: 'online' | 'paper' | 'all';
  sourceSummary?: SourceSummary;
  evaluations?: Evaluation[];
  availableEvaluationTypes?: EvaluationType[];
  committeeMembers?: CommitteeMember[];
  constitutiveAct?: ConstitutiveActData;
  sensitizationVideos?: SensitizationVideo[];
}>(), {
  evaluations: () => [],
  availableEvaluationTypes: () => [],
  committeeMembers: () => [],
  constitutiveAct: () => ({
    submitted_path: null,
    submitted_at: null,
    admin_path: null,
    admin_at: null,
  }),
  selectedSource: 'online',
  sourceSummary: () => ({
    online: 0,
    paper: 0,
  }),
  sensitizationVideos: () => [],
});

const committeeMembers = ref<CommitteeMember[]>(props.committeeMembers);
const constitutiveAct = computed<ConstitutiveActData>(() => props.constitutiveAct);
const sensitizationVideos = computed<SensitizationVideo[]>(() => props.sensitizationVideos ?? []);
const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);
const page = usePage();

const isAdmin = computed(() => {
  const roles = (page.props.auth as { user?: { roles?: Array<{ name: string }> } })?.user?.roles ?? [];
  return roles.some((role) => role.name === 'admin' || role.name === 'super-admin');
});

const canUploadSubmittedAct = computed(() => {
  const roles = (page.props.auth as { user?: { roles?: Array<{ name: string }> } })?.user?.roles ?? [];
  const hasWorkCenterUserRole = roles.some((role) => role.name === 'work_center_user');
  return hasWorkCenterUserRole && !isAdmin.value;
});

const generalTabs = [
  { key: 'empresa', label: 'Empresa' },
  { key: 'evaluacion', label: 'Evaluación' },
  { key: 'comite', label: 'Comité' },
  { key: 'sensibilizacion', label: 'Sensibilización' },
  { key: 'politica', label: 'Política' },
];

const activeGeneralTab = ref('empresa');

const sourceOptions: Array<{ key: 'online' | 'paper' | 'all'; label: string; caption: string; chipClass: string }> = [
  {
    key: 'online',
    label: 'Online',
    caption: 'Captura digital',
    chipClass: 'bg-sky-100 text-sky-800',
  },
  {
    key: 'paper',
    label: 'Presencial',
    caption: 'Captura OMR en papel',
    chipClass: 'bg-amber-100 text-amber-800',
  },
  {
    key: 'all',
    label: 'Ambos',
    caption: 'Online y presencial',
    chipClass: 'bg-emerald-100 text-emerald-800',
  },
];

const sourceCount = (source: 'online' | 'paper' | 'all'): number => {
  if (source === 'all') {
    return props.sourceSummary.online + props.sourceSummary.paper;
  }

  return source === 'online' ? props.sourceSummary.online : props.sourceSummary.paper;
};

const selectedSourceLabel = computed(() => {
  if (props.selectedSource === 'paper') {
    return 'Presencial';
  }

  if (props.selectedSource === 'all') {
    return 'Online y Presencial';
  }

  return 'Online';
});

const sourceDescription = computed(() => {
  if (props.selectedSource === 'all') {
    return 'Mostrando evaluaciones capturadas desde formularios en línea y formularios físicos (OMR).';
  }

  return props.selectedSource === 'paper'
    ? 'Mostrando evaluaciones capturadas desde formularios físicos (OMR).'
    : 'Mostrando evaluaciones capturadas desde formularios en línea.';
});

const sourceHref = (source: 'online' | 'paper' | 'all'): string => {
  return route('work-centers.dashboard.nom-035-index', {
    workCenter: props.workCenter.id,
    source,
  });
};

const instrumentHref = (instrument: Instrument): string => {
  return route(instrument.route, {
    workCenter: props.workCenter.id,
    source: props.selectedSource,
  });
};

const showCommitteeMemberModal = ref(false);
const showDeleteConfirmModal = ref(false);
const memberToDelete = ref<CommitteeMember | null>(null);

const committeeMemberForm = useForm({
  name: '',
  department_area: '',
  position: '',
  factor: '',
});

const openCommitteeMemberModal = (): void => {
  if (!isAdmin.value) {
    return;
  }

  committeeMemberForm.reset();
  committeeMemberForm.clearErrors();
  showCommitteeMemberModal.value = true;
};

const closeCommitteeMemberModal = (): void => {
  showCommitteeMemberModal.value = false;
  committeeMemberForm.reset();
};

const submitCommitteeMember = (): void => {
  if (!isAdmin.value) {
    return;
  }

  committeeMemberForm.post(route('work-centers.committee-members.store', props.workCenter.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeCommitteeMemberModal();
      router.reload({
        only: ['committeeMembers'],
        onSuccess: (page) => {
          committeeMembers.value = (page.props.committeeMembers as CommitteeMember[]) ?? [];
        },
      });
    },
  });
};

const confirmDeleteMember = (member: CommitteeMember): void => {
  if (!isAdmin.value) {
    return;
  }

  memberToDelete.value = member;
  showDeleteConfirmModal.value = true;
};

const deleteMember = (): void => {
  if (!isAdmin.value) {
    return;
  }

  if (!memberToDelete.value) {
    return;
  }

  router.delete(route('work-centers.committee-members.destroy', [props.workCenter.id, memberToDelete.value.id]), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteConfirmModal.value = false;
      memberToDelete.value = null;
      router.reload({
        only: ['committeeMembers'],
        onSuccess: (page) => {
          committeeMembers.value = (page.props.committeeMembers as CommitteeMember[]) ?? [];
        },
      });
    },
  });
};

const colorAccent = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-600',
    red: 'bg-red-600',
    amber: 'bg-amber-500',
    orange: 'bg-orange-500',
    green: 'bg-green-600',
    teal: 'bg-teal-600',
  };
  return map[color] ?? 'bg-gray-400';
};

const iconBg = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-100',
    red: 'bg-red-100',
    amber: 'bg-amber-100',
    orange: 'bg-orange-100',
    green: 'bg-green-100',
    teal: 'bg-teal-100',
  };
  return map[color] ?? 'bg-gray-100';
};

const iconColor = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'text-blue-600',
    red: 'text-red-600',
    amber: 'text-amber-600',
    orange: 'text-orange-600',
    green: 'text-green-600',
    teal: 'text-teal-600',
  };
  return map[color] ?? 'text-gray-600';
};

const subtitleColor = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'text-blue-600',
    red: 'text-red-600',
    amber: 'text-amber-600',
    orange: 'text-orange-600',
    green: 'text-green-600',
    teal: 'text-teal-600',
  };
  return map[color] ?? 'text-gray-600';
};

const badgeClasses = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-800',
    red: 'bg-red-100 text-red-800',
    amber: 'bg-amber-100 text-amber-800',
    orange: 'bg-orange-100 text-orange-800',
    green: 'bg-green-100 text-green-800',
    teal: 'bg-teal-100 text-teal-800',
  };
  return map[color] ?? 'bg-gray-100 text-gray-800';
};

const pingColor = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-400',
    red: 'bg-red-400',
    amber: 'bg-amber-400',
    orange: 'bg-orange-400',
    green: 'bg-green-400',
    teal: 'bg-teal-400',
  };
  return map[color] ?? 'bg-gray-400';
};

const dotColor = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-500',
    red: 'bg-red-500',
    amber: 'bg-amber-500',
    orange: 'bg-orange-500',
    green: 'bg-green-500',
    teal: 'bg-teal-500',
  };
  return map[color] ?? 'bg-gray-500';
};
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
  animation: fadeIn 0.25s ease-in;
}
</style>
