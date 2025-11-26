<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    organization: { type: Object, default: () => null },
    evaluationStats: { type: Array, default: () => [] },
    instrumentRoutes: { type: Object, default: () => ({}) },
    recentEvaluations: { type: Array, default: () => [] },
    onboardingTips: { type: Array, default: () => [] },
});

const hasData = computed(() => props.evaluationStats.length > 0 || props.recentEvaluations.length > 0);

// Derive estado categories similar to Results List behaviour
function deriveEstado(group) {
    const faltanGuias = (!group.has_referencia_iii || !group.has_referencia_v) && !group.has_likert;
    const faltanDatos = group.missing_data && group.missing_data.length > 0;
    if (!faltanGuias && !faltanDatos) return 'completo';
    if (faltanGuias && faltanDatos) return 'faltan_guias_y_datos';
    if (faltanGuias) return 'faltan_guias';
    return 'faltan_datos';
}

// Filters
const generoFilter = ref('');
const tipoFilter = ref('');
const searchFolio = ref('');

const uniqueGeneros = computed(() => {
    const set = new Set(props.recentEvaluations.map(e => (e.gender || '').trim()).filter(Boolean));
    return Array.from(set);
});
const uniqueTipos = computed(() => {
    const set = new Set(props.recentEvaluations.map(e => e.evaluation_type).filter(Boolean));
    return Array.from(set);
});
// Estado UI removido según requerimiento

const typeLabels = {
    referencia_i: 'Referencia I',
    referencia_iii: 'Referencia III',
    referencia_v: 'Referencia V',
    cisneros: 'Cisneros',
    likert: 'Clima laboral',
};

const filteredEvaluations = computed(() => {
    return props.recentEvaluations.filter(ev => {
        if (generoFilter.value && (ev.gender || '').trim() !== generoFilter.value) return false;
        if (tipoFilter.value && ev.evaluation_type !== tipoFilter.value) return false;
        if (searchFolio.value && !String(ev.personal_folio).toLowerCase().includes(searchFolio.value.toLowerCase())) return false;
        return true;
    });
});

function resetFilters() {
    generoFilter.value = '';
    tipoFilter.value = '';
    searchFolio.value = '';
}

function rowHasIssues(ev) {
    return !!ev.demographic_missing || !!ev.missing_questions;
}

function rowHighlightClass(ev) {
    return rowHasIssues(ev) ? 'bg-amber-50 hover:bg-amber-100' : 'hover:bg-gray-50';
}

function getDetailRoute(ev) {
    if (ev.evaluation_type === 'likert') {
        return route('organization.results.likert', { organization: props.organization.id, personalFolio: ev.personal_folio });
    }
    return route('organization.results.detail', { organization: props.organization.id, personalFolio: ev.personal_folio });
}
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-bold text-gray-800">Dashboard de Organización</h1>
            <p class="text-sm text-gray-600" v-if="organization">{{ organization.name }}</p>
        </div>

        <!-- Instruments Overview -->
        <section v-if="evaluationStats.length">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">Instrumentos Aplicados</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="stat in evaluationStats" :key="stat.key" :class="['p-4 rounded-md border shadow-sm transition', stat.highlight ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-gray-200']">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-gray-800">{{ stat.label }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ stat.count }}</span>
                    </div>
                    <p class="text-xs text-gray-600 min-h-[2.5rem]">{{ stat.description }}</p>
                    <div class="mt-3">
                        <Link v-if="instrumentRoutes[stat.key]" :href="instrumentRoutes[stat.key]" class="text-xs inline-flex items-center gap-1 font-medium text-indigo-600 hover:underline">
                            Ver reporte
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h5.69L9.22 8.06a.75.75 0 111.06-1.06l3.25 3.25a.75.75 0 010 1.06l-3.25 3.25a.75.75 0 11-1.06-1.06l2.22-2.19H5.75A.75.75 0 015 10z" clip-rule="evenodd"/></svg>
                        </Link>
                        <span v-else class="text-xs text-gray-400">Ruta no disponible</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Evaluations -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Evaluaciones Recientes</h2>
                <Link v-if="instrumentRoutes.results_list" :href="instrumentRoutes.results_list" class="text-sm font-medium text-indigo-600 hover:underline">Ver todas</Link>
            </div>
            <div class="mb-4 flex flex-wrap gap-3 items-end">
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-gray-600 mb-1">Folio</label>
                    <input v-model="searchFolio" type="text" class="border border-gray-300 rounded px-2 py-1 text-sm w-40" placeholder="Buscar..." />
                </div>
                <div class="flex flex-col" v-if="uniqueGeneros.length">
                    <label class="text-xs font-semibold text-gray-600 mb-1">Género</label>
                    <select v-model="generoFilter" class="border border-gray-300 rounded px-2 py-1 text-sm w-40">
                        <option value="">Todos</option>
                        <option v-for="g in uniqueGeneros" :key="g" :value="g">{{ g }}</option>
                    </select>
                </div>
                <div class="flex flex-col" v-if="uniqueTipos.length">
                    <label class="text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                    <select v-model="tipoFilter" class="border border-gray-300 rounded px-2 py-1 text-sm w-44">
                        <option value="">Todos</option>
                        <option v-for="t in uniqueTipos" :key="t" :value="t">{{ typeLabels[t] || t }}</option>
                    </select>
                </div>
                <button @click="resetFilters" class="text-xs px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-50">Limpiar</button>
            </div>
            <div v-if="filteredEvaluations.length" class="overflow-x-auto border border-gray-200 rounded-md">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Folio Personal</th>
                            <th class="px-3 py-2 text-left font-medium">Nombre</th>
                            <th class="px-3 py-2 text-left font-medium">Tipo</th>
                            <th class="px-3 py-2 text-left font-medium">Género</th>
                            <th class="px-3 py-2 text-left font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="ev in filteredEvaluations" :key="ev.id" :class="rowHighlightClass(ev)">
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span v-if="rowHasIssues(ev)" class="text-amber-500" title="Esta evaluación tiene datos incompletos">⚠️</span>
                                    <span class="font-mono text-xs">{{ ev.personal_folio || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ ev.evaluee_name ? ev.evaluee_name : '—' }}</td>
                            <td class="px-3 py-2 text-xs">
                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700">{{ typeLabels[ev.evaluation_type] || ev.evaluation_type }}</span>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ ev.gender || '—' }}</td>
                            <td class="px-3 py-2 text-xs">
                                <Link :href="getDetailRoute(ev)" class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Ver Detalles
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-6 border border-dashed rounded-md text-center text-sm text-gray-600">
                No hay evaluaciones para los filtros seleccionados.
            </div>
        </section>

        <!-- Onboarding / Tips -->
        <section>
            <h2 class="text-lg font-semibold mb-3 text-gray-700">Guía Rápida</h2>
            <ul class="space-y-2">
                <li v-for="(tip,i) in onboardingTips" :key="i" class="flex items-start gap-2">
                    <span class="mt-0.5 w-2 h-2 rounded-full bg-indigo-400"></span>
                    <p class="text-sm text-gray-600">{{ tip }}</p>
                </li>
            </ul>
        </section>

        <!-- Empty State -->
        <div v-if="!hasData" class="p-6 text-center border border-dashed rounded-md text-sm text-gray-600">
            Aún no hay datos suficientes. Comienza aplicando las evaluaciones y regresa para ver los reportes.
        </div>
    </div>
</template>

<style scoped>
</style>
