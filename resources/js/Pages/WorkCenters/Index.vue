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
const isDownloadingMetrics = ref(false);

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

  return 'bg-blue-100 text-blue-800';
};

const getClinicalBadgeClass = (count) => {
  const safeCount = Number(count ?? 0);

  if (safeCount === 0) {
    return 'bg-slate-100 text-slate-600';
  }

  return 'bg-red-100 text-red-800';
};

const getFilterChipClass = (isActive) => {
  if (isActive) {
    return 'inline-flex items-center rounded-full border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 transition-colors';
  }

  return 'inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50';
};

const getDownloadFilename = (contentDisposition) => {
  if (!contentDisposition) {
    return 'reporte_centros.xlsx';
  }

  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8Match && utf8Match[1]) {
    return decodeURIComponent(utf8Match[1]);
  }

  const asciiMatch = contentDisposition.match(/filename="?([^";]+)"?/i);
  if (asciiMatch && asciiMatch[1]) {
    return asciiMatch[1];
  }

  return 'reporte_centros.xlsx';
};

const downloadMetricsExcel = async () => {
  if (isDownloadingMetrics.value) {
    return;
  }

  isDownloadingMetrics.value = true;

  try {
    const response = await fetch(route('organizations.work-centers.export-metrics', { organization: props.organization.id }), {
      method: 'GET',
      headers: {
        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      throw new Error('No se pudo generar el archivo.');
    }

    const blob = await response.blob();
    const filename = getDownloadFilename(response.headers.get('content-disposition'));
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error);
  } finally {
    isDownloadingMetrics.value = false;
  }
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
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-70"
              :disabled="isDownloadingMetrics"
              @click="downloadMetricsExcel"
            >
              <svg v-if="isDownloadingMetrics" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
              </svg>
              <svg v-else class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 2a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V2.75A.75.75 0 0110 2z" />
                <path d="M3.5 13.5a.75.75 0 01.75.75v1a1 1 0 001 1h8.5a1 1 0 001-1v-1a.75.75 0 011.5 0v1a2.5 2.5 0 01-2.5 2.5h-8.5a2.5 2.5 0 01-2.5-2.5v-1a.75.75 0 01.75-.75z" />
              </svg>
              {{ isDownloadingMetrics ? 'Generando Excel...' : 'Descargar Excel' }}
            </button>
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
          <table class="min-w-[980px] w-full table-fixed divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="w-[40%] px-5 py-3 text-left font-semibold text-slate-700">Centro de trabajo</th>
                <th class="w-[15%] px-4 py-3 text-center font-semibold text-slate-700">Presentaron</th>
                <th class="w-[15%] px-4 py-3 text-center font-semibold text-slate-700">Hombres</th>
                <th class="w-[15%] px-4 py-3 text-center font-semibold text-slate-700">Mujeres</th>
                <th class="w-[15%] px-4 py-3 text-center font-semibold text-slate-700">Atención clínica</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="workCenter in visibleWorkCenters" :key="workCenter.id" class="hover:bg-slate-50">
                <td class="px-5 py-4 align-middle">
                  <p class="font-medium text-slate-900">{{ workCenter.name }}</p>
                  <p class="mt-1 text-xs text-slate-500">Código: {{ workCenter.code }}</p>
                  <div class="mt-1 flex flex-wrap gap-1.5">
                    <span v-if="workCenter.is_primary" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Principal</span>
                    <span
                      v-if="Number(workCenter.requires_clinical_attention_count ?? 0) > 0"
                      class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700"
                    >
                      Requieren atención clínica: {{ workCenter.requires_clinical_attention_count ?? 0 }}
                    </span>
                  </div>
                  <div class="mt-3">
                    <Link
                      :href="route('work-centers.dashboard.nom-035-index', { workCenter: workCenter.id })"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    >
                      <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M11.03 3.47a.75.75 0 011.06 0l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 11-1.06-1.06l3.97-3.97H3.5a.75.75 0 010-1.5h11.5l-3.97-3.97a.75.75 0 010-1.06z" />
                      </svg>
                      Ver detalles
                    </Link>
                  </div>
                </td>
                <td class="px-4 py-4 text-center align-middle">
                  <span :class="getEvaluatedPeopleBadgeClass(workCenter.evaluated_people_count)" class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.evaluated_people_count ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center align-middle">
                  <span :class="getEvaluatedPeopleBadgeClass(workCenter.men_count)" class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.men_count ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center align-middle">
                  <span :class="getEvaluatedPeopleBadgeClass(workCenter.women_count)" class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.women_count ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center align-middle">
                  <span :class="getClinicalBadgeClass(workCenter.requires_clinical_attention_count)" class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ workCenter.requires_clinical_attention_count ?? 0 }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Dashboard>
</template>
