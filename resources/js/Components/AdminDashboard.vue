<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-1">
          <h2 class="text-2xl font-bold text-gray-900">Panel de Organizaciones</h2>
          <p class="text-sm text-gray-600">Vista ejecutiva para gestionar centros de trabajo e instrumentos por organización.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('organizations.create')"
            class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          >
            Nueva organización
          </Link>
          <Link
            v-if="singleOrganizationId"
            :href="route('organizations.work-centers.create', { organization: singleOrganizationId })"
            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
          >
            Nuevo centro de trabajo
          </Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
      <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
        <p class="text-xs uppercase tracking-wide text-blue-700">Organizaciones</p>
        <p class="mt-1 text-2xl font-bold text-blue-900">{{ filteredOrganizations.length }}</p>
      </div>
      <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs uppercase tracking-wide text-emerald-700">Centros de trabajo</p>
        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ totalWorkCenters }}</p>
      </div>
      <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
        <p class="text-xs uppercase tracking-wide text-violet-700">Última evaluación global</p>
        <p class="mt-1 text-sm font-semibold text-violet-900">{{ latestGlobalEvaluation }}</p>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="space-y-3">
        <div>
          <label for="search-input" class="mb-2 block text-sm font-medium text-gray-700">Buscar organización</label>
          <div class="relative rounded-xl border border-slate-200 bg-slate-50/80 p-1 transition-colors focus-within:border-blue-400 focus-within:bg-white">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 104.12 9.14l2.62 2.61a.75.75 0 101.06-1.06l-2.61-2.62A5.5 5.5 0 009 3.5zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
              </svg>
            </span>
            <input
              id="search-input"
              v-model="searchQuery"
              type="text"
              placeholder="Nombre o instrumento..."
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
          <label class="mb-2 block text-sm font-medium text-gray-700">Instrumento</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="instrument in instrumentFilterChips"
              :key="instrument"
              type="button"
              :aria-pressed="selectedInstrument === instrument"
              :class="getFilterChipClass(selectedInstrument === instrument)"
              @click="selectedInstrument = instrument"
            >
              {{ instrument === 'all' ? 'Todos' : instrument }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="filteredOrganizations.length === 0" class="rounded-lg border border-gray-200 bg-white p-8 text-center">
      <p class="text-base font-medium text-gray-700">No se encontraron organizaciones.</p>
      <p class="mt-1 text-sm text-gray-500">Ajusta tu búsqueda o crea una nueva organización.</p>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-700">Organización</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700">Centros</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700">Instrumentos</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-700">Última evaluación</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-700">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="organization in filteredOrganizations" :key="organization.id" class="hover:bg-slate-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-100">
                    <img
                      v-if="organization.logo"
                      :src="`/storage/${organization.logo}`"
                      :alt="`Logo de ${organization.name}`"
                      class="h-7 w-7 object-contain"
                    >
                    <span v-else class="text-xs font-semibold text-slate-500">ORG</span>
                  </div>
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ organization.name }}</p>
                    <p class="text-xs text-slate-500">{{ organization.evaluations_count }} evaluaciones</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                  {{ organization.work_centers_count ?? 0 }} centros
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="label in getInstrumentLabels(organization)"
                    :key="`${organization.id}_${label}`"
                    :class="getInstrumentBadgeClass(label)"
                  >
                    {{ label }}
                  </span>
                  <span
                    v-if="getInstrumentLabels(organization).length === 0"
                    class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500"
                  >
                    Sin instrumentos
                  </span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="getLastEvaluationBadgeClass(organization.last_evaluation_at)" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold">
                  {{ formatLastEvaluation(organization.last_evaluation_at) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                  <Link
                    :href="route('organizations.work-centers.index', { organization: organization.id })"
                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                  >
                    Ver centros
                  </Link>
                  <Link
                    :href="route('organizations.work-centers.create', { organization: organization.id })"
                    class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200"
                  >
                    Agregar centro
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  organizations: {
    type: Array,
    default: () => [],
  },
});

const searchQuery = ref('');
const selectedInstrument = ref('all');

const getInstrumentLabels = (organization) => {
  if (Array.isArray(organization.instrument_labels) && organization.instrument_labels.length > 0) {
    return organization.instrument_labels;
  }

  const labels = [];
  if (organization.has_nom_035) {
    labels.push('NOM-035');
  }
  if (organization.has_clima_laboral) {
    labels.push('Clima Laboral');
  }
  if (organization.has_online_evaluations) {
    labels.push('En Línea');
  }
  if (organization.has_nom_002) {
    labels.push('NOM-002');
  }

  return labels;
};

const getInstrumentBadgeClass = (label) => {
  if (label === 'NOM-035') {
    return 'inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800';
  }

  if (label === 'Clima Laboral') {
    return 'inline-flex items-center rounded bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800';
  }

  if (label === 'En Línea') {
    return 'inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800';
  }

  if (label === 'NOM-002') {
    return 'inline-flex items-center rounded bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800';
  }

  return 'inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700';
};

const formatLastEvaluation = (dateValue) => {
  if (!dateValue) {
    return 'Sin evaluaciones';
  }

  const parsedDate = new Date(dateValue);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'Sin evaluaciones';
  }

  return parsedDate.toLocaleDateString('es-MX', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  });
};

const getLastEvaluationBadgeClass = (dateValue) => {
  if (!dateValue) {
    return 'bg-slate-100 text-slate-600';
  }

  const parsedDate = new Date(dateValue);
  if (Number.isNaN(parsedDate.getTime())) {
    return 'bg-slate-100 text-slate-600';
  }

  const daysDiff = (Date.now() - parsedDate.getTime()) / (1000 * 60 * 60 * 24);

  if (daysDiff <= 30) {
    return 'bg-emerald-100 text-emerald-800';
  }

  if (daysDiff <= 90) {
    return 'bg-amber-100 text-amber-800';
  }

  return 'bg-rose-100 text-rose-800';
};

const availableInstrumentFilters = computed(() => {
  return [...new Set(props.organizations.flatMap((organization) => getInstrumentLabels(organization)))].sort((left, right) => {
    return left.localeCompare(right, 'es');
  });
});

const instrumentFilterChips = computed(() => {
  return ['all', ...availableInstrumentFilters.value];
});

const getFilterChipClass = (isActive) => {
  if (isActive) {
    return 'inline-flex items-center rounded-full border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 transition-colors';
  }

  return 'inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50';
};

const filteredOrganizations = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  return props.organizations.filter((organization) => {
    const organizationName = String(organization.name ?? '').toLowerCase();
    const instrumentLabels = getInstrumentLabels(organization);
    const matchesSearch = query.length === 0
      || organizationName.includes(query)
      || instrumentLabels.some((label) => label.toLowerCase().includes(query));

    const matchesInstrument = selectedInstrument.value === 'all' || instrumentLabels.includes(selectedInstrument.value);

    return matchesSearch && matchesInstrument;
  });
});

const singleOrganizationId = computed(() => {
  if (filteredOrganizations.value.length !== 1) {
    return null;
  }

  return filteredOrganizations.value[0].id;
});

const totalWorkCenters = computed(() => {
  return filteredOrganizations.value.reduce((total, organization) => {
    return total + Number(organization.work_centers_count ?? 0);
  }, 0);
});

const latestGlobalEvaluation = computed(() => {
  const latestTimestamp = filteredOrganizations.value
    .map((organization) => organization.last_evaluation_at)
    .filter(Boolean)
    .map((dateValue) => new Date(dateValue).getTime())
    .filter((timestamp) => !Number.isNaN(timestamp))
    .sort((left, right) => right - left)[0];

  if (!latestTimestamp) {
    return 'Sin evaluaciones';
  }

  return formatLastEvaluation(new Date(latestTimestamp).toISOString());
});
</script>
