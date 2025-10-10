<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
            <!-- Header with navigation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('organization.results.list', { organization: organization.id })"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Volver a Lista
                        </Link>
                    </div>
                </div>

                <div class="mt-4 text-gray-600">
                    <p class="text-lg font-semibold">{{ organization.name }}</p>
                    <p>Folio Personal: {{ personalFolio }}</p>
                    <p>Fecha: {{ evaluation.created_at }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex">
                        <button
                            v-for="tab in availableTabs"
                            :key="tab.key"
                            @click="currentTab = tab.key"
                            :class="[
                                currentTab === tab.key
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'py-4 px-6 text-center border-b-2 font-medium text-sm flex-1'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Summary Tab -->
                    <div v-if="currentTab === 'summary' && guideIIIResults" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Final Score -->
                            <div class="bg-white p-6 rounded-lg shadow flex flex-col justify-center items-center">
                                <h3 class="text-2xl text-center font-semibold text-gray-900 mb-4">Calificación Final</h3>
                                <div class="text-5xl font-bold text-blue-600">
                                    {{ totalScore }}
                                </div>
                                <div class="mt-4 text-sm text-gray-600">
                                    Nivel de Riesgo: <span :class="getRiskLevelClass(totalScore)" class="font-semibold">{{ getRiskLevel(totalScore) }}</span>
                                </div>
                            </div>

                            <!-- Categories Summary -->
                            <div class="bg-white p-6 rounded-lg shadow">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Categorías</h3>
                                <div class="space-y-2">
                                    <div v-for="category in categoryScores" :key="category.name" class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm">{{ category.name }}:</span>
                                        <span class="font-semibold">{{ category.score }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Domains Summary -->
                            <div class="bg-white p-6 rounded-lg shadow">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Dominios</h3>
                                <div class="space-y-2">
                                    <div v-for="domain in domainScores" :key="domain.name" class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm">{{ domain.name }}:</span>
                                        <span class="font-semibold">{{ domain.score }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Results Table -->
                        <div class="overflow-x-auto mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalle por Categoría, Dominio y Dimensión</h3>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                        <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase">Dominio</th>
                                        <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase">Dimensión</th>
                                        <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase">Ítem</th>
                                        <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase">Puntaje</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template v-for="(cat, catIdx) in groupedResults" :key="catIdx">
                                        <template v-for="(dom, domIdx) in cat.dominios" :key="domIdx">
                                            <template v-for="(dim, dimIdx) in dom.dimensiones" :key="dimIdx">
                                                <template v-for="(item, itemIdx) in dim.items" :key="itemIdx">
                                                    <tr>
                                                        <td v-if="domIdx === 0 && dimIdx === 0 && itemIdx === 0" :rowspan="cat.rowspan" class="px-6 py-4 border border-gray-200 text-center align-middle font-medium bg-gray-50">
                                                            {{ cat.nombre }}
                                                            <div class="text-xs text-gray-500 font-normal">Puntaje: {{ cat.puntaje }}</div>
                                                        </td>
                                                        <td v-if="dimIdx === 0 && itemIdx === 0" :rowspan="dom.rowspan" class="px-6 py-4 border border-gray-200 text-center align-middle font-medium">
                                                            {{ dom.nombre }}
                                                            <div class="text-xs text-gray-500 font-normal">Puntaje: {{ dom.puntaje }}</div>
                                                        </td>
                                                        <td v-if="itemIdx === 0" :rowspan="dim.rowspan" class="px-6 py-4 border border-gray-200 text-center align-middle text-sm">
                                                            {{ dim.nombre }}
                                                        </td>
                                                        <td class="px-6 py-4 border border-gray-200 text-center text-sm">{{ item.nombre }}</td>
                                                        <td class="px-6 py-4 border border-gray-200 text-center font-semibold">{{ item.puntaje }}</td>
                                                    </tr>
                                                </template>
                                            </template>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Guide I Tab -->
                    <div v-else-if="currentTab === 'guideI'">
                        <div v-if="guideIResults" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Guía de Referencia I - Acontecimientos Traumáticos Severos</h3>
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="(answer, question) in guideIResults.answers" :key="question" class="bg-white p-4 rounded shadow-sm">
                                        <p class="font-medium text-gray-700">{{ question }}</p>
                                        <p class="text-gray-900 mt-2">{{ answer }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            No hay resultados disponibles para la Guía de Referencia I
                        </div>
                    </div>

                    <!-- Guide III Tab -->
                    <div v-else-if="currentTab === 'guideIII'">
                        <div v-if="guideIIIResults" class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Guía de Referencia III - Respuestas Principales</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(answer, question) in guideIIIResults.answers" :key="question">
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ question }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Conditional Questions -->
                            <div v-if="guideIIIResults.conditional && guideIIIResults.conditional.length > 0">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Preguntas Condicionales</h3>
                                <div v-for="(section, idx) in guideIIIResults.conditional" :key="idx" class="mb-6">
                                    <div class="font-semibold text-blue-700 mb-1">{{ section.section }}: <span class="font-normal text-gray-700">{{ section.condition }}</span></div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="(answer, question) in section.questions" :key="question">
                                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ question }}</td>
                                                    <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- CITSATS-S1 -->
                            <div v-if="guideIIIResults.citsats_s1 && Object.keys(guideIIIResults.citsats_s1).length > 0">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Acontecimientos Traumáticos (CITSATS-S1)</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(answer, idx) in Object.values(guideIIIResults.citsats_s1)" :key="idx">
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ idx + 1 }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-700">
                                                    <span v-if="citsatsQuestions[idx + 73]">{{ citsatsQuestions[idx + 73] }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            No hay resultados disponibles para la Guía de Referencia III
                        </div>
                    </div>

                    <!-- Guide V Tab -->
                    <div v-else-if="currentTab === 'guideV'">
                        <div v-if="guideVResults" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Guía de Referencia V - Datos Demográficos</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campo</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(value, field) in guideVResults.demographic_data" :key="field">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ field }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ value }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            No hay resultados disponibles para la Guía de Referencia V
                        </div>
                    </div>

                    <!-- Cisneros Tab -->
                    <div v-else-if="currentTab === 'cisneros'">
                        <div v-if="cisnerosResults" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Escala CISNEROS - Violencia Laboral</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(answer, question) in cisnerosResults.answers" :key="question">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ question }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            <p class="text-lg font-medium">Escala CISNEROS</p>
                            <p class="mt-2">En Desarrollo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dashboard from "../../Layouts/Dashboard.vue";
import type {
    Organization,
    Evaluation,
    DetailedResultRow,
    GuideIResults,
    GuideIIIResults,
    GuideVResults,
    CisnerosResults,
    Tab,
    GroupedCategory,
    CategorySummary,
    DomainSummary
} from '../../types/results';

interface Props {
    organization: Organization;
    personalFolio: string;
    evaluation: Evaluation;
    totalScore: number;
    results: DetailedResultRow[];
    guideIResults: GuideIResults | null;
    guideVResults: GuideVResults | null;
    guideIIIResults: GuideIIIResults | null;
    cisnerosResults: CisnerosResults | null;
}

const props = withDefaults(defineProps<Props>(), {
    totalScore: 0,
    results: () => [],
    guideIResults: null,
    guideVResults: null,
    guideIIIResults: null,
    cisnerosResults: null
});

// Agrupa los resultados para renderizar la tabla jerárquica con rowspan
const groupedResults = computed<GroupedCategory[]>(() => {
    if (!props.results.length) return [];
    const cats: GroupedCategory[] = [];
    const catMap: Record<string, GroupedCategory> = {};
    
    props.results.forEach(row => {
        let cat = catMap[row.categoria.nombre];
        if (!cat) {
            cat = {
                nombre: row.categoria.nombre,
                puntaje: row.categoria.puntaje,
                dominios: [],
                rowspan: 0
            };
            catMap[row.categoria.nombre] = cat;
            cats.push(cat);
        }
        
        let dom = cat.dominios.find(d => d.nombre === row.dominio.nombre);
        if (!dom) {
            dom = {
                nombre: row.dominio.nombre,
                puntaje: row.dominio.puntaje,
                dimensiones: [],
                rowspan: 0
            };
            cat.dominios.push(dom);
        }
        
        let dim = dom.dimensiones.find(d => d.nombre === row.dimension);
        if (!dim) {
            dim = {
                nombre: row.dimension,
                items: [],
                rowspan: 0
            };
            dom.dimensiones.push(dim);
        }
        
        dim.items.push({ nombre: row.item, puntaje: row.puntaje });
    });
    
    // Calcular rowspans correctamente
    cats.forEach(cat => {
        cat.rowspan = 0;
        cat.dominios.forEach(dom => {
            dom.rowspan = 0;
            dom.dimensiones.forEach(dim => {
                dim.rowspan = dim.items.length;
                dom.rowspan += dim.rowspan;
            });
            cat.rowspan += dom.rowspan;
        });
    });
    
    return cats;
});

const currentTab = ref<string>('summary');

const availableTabs = computed<Tab[]>(() => {
    const tabs: Tab[] = [];
    if (props.results && props.results.length) {
        tabs.push({ key: 'summary', label: 'Resumen' });
    }
    if (props.evaluation?.has_guide_i) {
        tabs.push({ key: 'guideI', label: 'Guía I' });
    }
    if (props.evaluation?.has_guide_iii) {
        tabs.push({ key: 'guideIII', label: 'Guía III' });
    }
    if (props.evaluation?.has_guide_v) {
        tabs.push({ key: 'guideV', label: 'Guía V' });
    }
    if (props.evaluation?.has_cisneros) {
        tabs.push({ key: 'cisneros', label: 'CISNEROS' });
    }
    return tabs;
});

const categoryScores = computed<CategorySummary[]>(() => {
    const categories: Record<string, CategorySummary> = {};
    props.results.forEach(row => {
        if (!categories[row.categoria.nombre]) {
            categories[row.categoria.nombre] = {
                name: row.categoria.nombre,
                score: row.categoria.puntaje
            };
        }
    });
    return Object.values(categories);
});

const domainScores = computed<DomainSummary[]>(() => {
    const domains: Record<string, DomainSummary> = {};
    props.results.forEach(row => {
        const key = `${row.categoria.nombre}|${row.dominio.nombre}`;
        if (!domains[key]) {
            domains[key] = {
                name: row.dominio.nombre,
                score: row.dominio.puntaje
            };
        }
    });
    return Object.values(domains);
});

const getRiskLevel = (score: number): string => {
    if (score < 50) return 'Nulo';
    if (score < 75) return 'Bajo';
    if (score < 99) return 'Medio';
    if (score < 140) return 'Alto';
    return 'Muy Alto';
};

const getRiskLevelClass = (score: number): string => {
    const level = getRiskLevel(score);
    const classes: Record<string, string> = {
        'Nulo': 'text-green-600',
        'Bajo': 'text-yellow-600',
        'Medio': 'text-orange-600',
        'Alto': 'text-red-600',
        'Muy Alto': 'text-red-800'
    };
    return classes[level] || 'text-gray-600';
};

// Preguntas CITSATS (73-78) - Solo para referencia visual
const citsatsQuestions: Record<number, string> = {
    73: 'Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave',
    74: 'Asaltos',
    75: 'Actos violentos que derivaron en lesiones graves',
    76: 'Secuestro',
    77: 'Amenazas',
    78: 'Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas',
};

</script>
