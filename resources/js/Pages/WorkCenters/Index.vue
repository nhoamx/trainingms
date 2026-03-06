<script setup>
import Dashboard from '../../Layouts/Dashboard.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Centros de Trabajo',
  },
  organization: {
    type: Object,
    required: true,
  },
  workCenters: {
    type: Array,
    default: () => [],
  },
});

const searchQuery = ref('');
const sortBy = ref('default');
const clinicalFilter = ref('all');

const sortChips = [
  { key: 'default', label: 'Predeterminado' },
  { key: 'evaluated_people_desc', label: 'Más personas evaluadas' },
  { key: 'evaluated_people_asc', label: 'Menos personas evaluadas' },
];

const clinicalFilterChips = [
  { key: 'all', label: 'Todos' },
  { key: 'requires_clinical', label: 'Con atención clínica' },
];

const filteredWorkCenters = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return props.workCenters.filter((workCenter) => {
    const name = String(workCenter.name ?? '').toLowerCase();
    const code = String(workCenter.code ?? '').toLowerCase();
    const type = String(workCenter.work_center_type ?? '').toLowerCase();
    const matchesText = !query || name.includes(query) || code.includes(query) || type.includes(query);

    if (!matchesText) {
      return false;
    }

    if (clinicalFilter.value === 'requires_clinical') {
      return Number(workCenter.requires_clinical_attention_count ?? 0) > 0;
    }

    return true;
  });
});

const visibleWorkCenters = computed(() => {
  const workCenters = [...filteredWorkCenters.value];

  if (sortBy.value === 'evaluated_people_desc') {
    return workCenters.sort((left, right) => {
      return Number(right.evaluated_people_count ?? 0) - Number(left.evaluated_people_count ?? 0)
        || String(left.name ?? '').localeCompare(String(right.name ?? ''), 'es');
    });
  }

  if (sortBy.value === 'evaluated_people_asc') {
    return workCenters.sort((left, right) => {
      return Number(left.evaluated_people_count ?? 0) - Number(right.evaluated_people_count ?? 0)
        || String(left.name ?? '').localeCompare(String(right.name ?? ''), 'es');
    });
  }

  return workCenters;
});

const totalEvaluatedPeople = computed(() => {
  return visibleWorkCenters.value.reduce((total, workCenter) => total + Number(workCenter.evaluated_people_count ?? 0), 0);
});

const totalClinicalAttention = computed(() => {
  return visibleWorkCenters.value.reduce((total, workCenter) => total + Number(workCenter.requires_clinical_attention_count ?? 0), 0);
});

const getEvaluatedPeopleBadgeClass = (count) => {
  const safeCount = Number(count ?? 0);

  if (safeCount === 0) {
    return 'bg-slate-100 text-slate-600';
  }

  if (safeCount < 10) {
    return 'bg-amber-100 text-amber-800';
  }

  return 'bg-emerald-100 text-emerald-800';
};

const getClinicalBadgeClass = (count) => {
  const safeCount = Number(count ?? 0);

  if (safeCount === 0) {
    return 'bg-slate-100 text-slate-600';
  }

  return 'bg-rose-100 text-rose-800';
};

const getFilterChipClass = (isActive) => {
  if (isActive) {
    return 'inline-flex items-center rounded-full border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 transition-colors';
  }

  return 'inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50';
};
</script>

<template>
  <Dashboard :title="title">
    <div class="space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Centros de trabajo</h1>
            <p class="mt-1 text-sm text-gray-600">Organización: {{ organization.name }}</p>
          </div>
          <div class="flex items-center gap-2">
            <Link
              :href="route('dashboard')"
              class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
            >
              Regresar
            </Link>
            <Link
              :href="route('organizations.work-centers.create', { organization: organization.id })"
              class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
            >
              Nuevo centro
            </Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
          <p class="text-xs uppercase tracking-wide text-blue-700">Total centros de trabajo</p>
          <p class="mt-1 text-2xl font-bold text-blue-900">{{ visibleWorkCenters.length }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <p class="text-xs uppercase tracking-wide text-emerald-700">Total personas evaluadas</p>
          <p class="mt-1 text-2xl font-bold text-emerald-900">{{ totalEvaluatedPeople }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
          <p class="text-xs uppercase tracking-wide text-rose-700">Personas que requieren atención clínica</p>
          <p class="mt-1 text-2xl font-bold text-rose-900">{{ totalClinicalAttention }}</p>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="space-y-3">
          <div>
            <label for="work-center-search" class="mb-2 block text-sm font-medium text-gray-700">Buscar centro de trabajo</label>
            <div class="relative rounded-xl border border-slate-200 bg-slate-50/80 p-1 transition-colors focus-within:border-blue-400 focus-within:bg-white">
              <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 104.12 9.14l2.62 2.61a.75.75 0 101.06-1.06l-2.61-2.62A5.5 5.5 0 009 3.5zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                </svg>
              </span>
              <input
                id="work-center-search"
                v-model="searchQuery"
                type="text"
                placeholder="Nombre, código o tipo..."
                class="w-full rounded-lg border-transparent bg-transparent py-2 pl-9 pr-16 text-sm focus:border-transparent focus:ring-0"
              >
              <button
                v-if="searchQuery"
                type="button"
                class="absolute inset-y-0 right-2 inline-flex items-center rounded-md px-2 text-xs font-semibold text-gray-500 hover:bg-slate-100 hover:text-gray-700"
                @click="searchQuery = ''"
              >
                Limpiar
              </button>
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Ordenar por</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="chip in sortChips"
                :key="chip.key"
                type="button"
                :aria-pressed="sortBy === chip.key"
                :class="getFilterChipClass(sortBy === chip.key)"
                @click="sortBy = chip.key"
              >
                {{ chip.label }}
              </button>
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Filtro de atención clínica</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="chip in clinicalFilterChips"
                :key="chip.key"
                type="button"
                :aria-pressed="clinicalFilter === chip.key"
                :class="getFilterChipClass(clinicalFilter === chip.key)"
                @click="clinicalFilter = chip.key"
              >
                {{ chip.label }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div v-if="visibleWorkCenters.length === 0" class="p-10 text-center">
          <p class="text-base font-medium text-gray-700">No hay centros de trabajo registrados.</p>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'No se encontraron centros con ese nombre.' : 'Agrega el primer centro para comenzar.' }}
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Centro de trabajo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Personas evaluadas</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Atención clínica</th>
                <th class="px-4 py-3 text-right font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="workCenter in visibleWorkCenters" :key="workCenter.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-900">{{ workCenter.code }}</td>
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-900">{{ workCenter.name }}</p>
                  <div class="mt-1 flex flex-wrap gap-1.5">
                    <span v-if="workCenter.is_primary" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Principal</span>
                    <span
                      v-if="Number(workCenter.requires_clinical_attention_count ?? 0) > 0"
                      class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700"
                    >
                      Requieren atención clínica: {{ workCenter.requires_clinical_attention_count ?? 0 }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-700">
                  <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                    {{ workCenter.work_center_type }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span :class="getEvaluatedPeopleBadgeClass(workCenter.evaluated_people_count)" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.evaluated_people_count ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span :class="getClinicalBadgeClass(workCenter.requires_clinical_attention_count)" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.requires_clinical_attention_count ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <Link
                      :href="route('work-centers.dashboard.nom-035-index', { workCenter: workCenter.id })"
                      class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                    >
                      Ver dashboard
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Dashboard>
</template>
