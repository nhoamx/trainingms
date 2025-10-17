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
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(answer, question) in guideIIIResults.answers" :key="question">
                                                <td class="px-6 py-4 text-sm font-semibold text-indigo-600">{{ getQuestionNumber(question) }}</td>
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
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="(answer, question) in section.questions" :key="question">
                                                    <td class="px-6 py-4 text-sm font-semibold text-indigo-600">{{ getQuestionNumber(question) }}</td>
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
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(answer, idx) in Object.values(guideIIIResults.citsats_s1)" :key="idx">
                                                <td class="px-6 py-4 text-sm font-semibold text-indigo-600">{{ idx + 73 }}</td>
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

                    <!-- Marked Image Tab (Admin Only) -->
                    <div v-else-if="currentTab === 'markedImage' && isAdmin">
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-blue-600 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-900">Imagen Procesada con Marcadores</h3>
                                        <p class="mt-1 text-sm text-blue-700">
                                            Esta imagen muestra los marcadores de alineación (verde) y las burbujas detectadas (azul) durante el procesamiento OCR.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    Formulario Procesado - Folio: {{ evaluation.folio }}
                                </h3>
                                
                                <div class="flex justify-center">
                                    <div class="max-w-full overflow-auto">
                                        <img 
                                            :src="`/storage/folios/${evaluation.folio}.png`" 
                                            :alt="`Imagen procesada folio ${evaluation.folio}`"
                                            class="max-w-full h-auto rounded border border-gray-300 shadow-sm"
                                            @error="handleImageError"
                                        />
                                    </div>
                                </div>

                                <div class="mt-6 text-sm text-gray-600">
                                    <p class="font-medium mb-2">Leyenda:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><span class="text-green-600 font-semibold">Verde</span>: Marcadores de alineación (4 esquinas)</li>
                                        <li><span class="text-blue-600 font-semibold">Azul</span>: Burbujas detectadas por el sistema OCR</li>
                                    </ul>
                                </div>
                            </div>
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
    isAdmin?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    totalScore: 0,
    results: () => [],
    guideIResults: null,
    guideVResults: null,
    guideIIIResults: null,
    cisnerosResults: null,
    isAdmin: false
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
    // Admin-only tab for marked images
    if (props.isAdmin && props.evaluation?.folio) {
        tabs.push({ key: 'markedImage', label: 'Imagen Procesada' });
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

// Handle image loading errors
const handleImageError = (event: Event) => {
    const target = event.target as HTMLImageElement;
    target.style.display = 'none';
    const parent = target.parentElement;
    if (parent) {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'text-center py-12 text-gray-500';
        errorMessage.innerHTML = `
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2">Imagen no disponible para este folio</p>
            <p class="text-sm text-gray-400 mt-1">La imagen procesada aún no está disponible o no se generó correctamente</p>
        `;
        parent.appendChild(errorMessage);
    }
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

// Mapeo de texto de pregunta a número (Referencia III completa)
const questionNumberMap: Record<string, number> = {
    'El espacio donde trabajo me permite realizar mis actividades de manera segura e higiénica': 1,
    'Mi trabajo me exige hacer mucho esfuerzo físico': 2,
    'Me preocupa sufrir un accidente en mi trabajo': 3,
    'Considero que en mi trabajo se aplican las normas de seguridad y salud en el trabajo': 4,
    'Considero que las actividades que realizo son peligrosas': 5,
    'Por la cantidad de trabajo que tengo debo quedarme tiempo adicional a mi turno': 6,
    'Por la cantidad de trabajo que tengo debo trabajar sin parar': 7,
    'Considero que es necesario mantener un ritmo de trabajo acelerado': 8,
    'Mi trabajo exige que esté muy concentrado': 9,
    'Mi trabajo requiere que memorice mucha información': 10,
    'En mi trabajo tengo que tomar decisiones difíciles muy rápido': 11,
    'Mi trabajo exige que atienda varios asuntos al mismo tiempo': 12,
    'En mi trabajo soy responsable de cosas de mucho valor': 13,
    'Respondo ante mi jefe por los resultados de toda mi área de trabajo': 14,
    'En el trabajo me dan órdenes contradictorias': 15,
    'Considero que en mi trabajo me piden hacer cosas innecesarias': 16,
    'Trabajo horas extras más de tres veces a la semana': 17,
    'Mi trabajo me exige laborar en días de descanso, festivos o fines de semana': 18,
    'Considero que el tiempo en el trabajo es mucho y perjudica mis actividades familiares o personales': 19,
    'Debo atender asuntos de trabajo cuando estoy en casa': 20,
    'Pienso en las actividades familiares o personales cuando estoy en mi trabajo': 21,
    'Pienso que mis responsabilidades familiares afectan mi trabajo': 22,
    'Mi trabajo permite que desarrolle nuevas habilidades': 23,
    'En mi trabajo puedo aspirar a un mejor puesto': 24,
    'Durante mi jornada de trabajo puedo tomar pausas cuando las necesito': 25,
    'Puedo decidir cuánto trabajo realizo durante la jornada laboral': 26,
    'Puedo decidir la velocidad a la que realizo mis actividades en mi trabajo': 27,
    'Puedo cambiar el orden de las actividades que realizo en mi trabajo': 28,
    'Los cambios que se presentan en mi trabajo dificultan mi labor': 29,
    'Cuando se presentan cambios en mi trabajo se tienen en cuenta mis ideas o aportaciones': 30,
    'Me informan con claridad cuáles son mis funciones': 31,
    'Me explican claramente los resultados que debo obtener en mi trabajo': 32,
    'Me explican claramente los objetivos de mi trabajo': 33,
    'Me informan con quién puedo resolver problemas o asuntos de trabajo': 34,
    'Me permiten asistir a capacitaciones relacionadas con mi trabajo': 35,
    'Recibo capacitación útil para hacer mi trabajo': 36,
    'Mi jefe ayuda a organizar mejor el trabajo': 37,
    'Mi jefe tiene en cuenta mis puntos de vista y opiniones': 38,
    'Mi jefe me comunica a tiempo la información relacionada con el trabajo': 39,
    'La orientación que me da mi jefe me ayuda a realizar mejor mi trabajo': 40,
    'Mi jefe ayuda a solucionar los problemas que se presentan en el trabajo': 41,
    'Puedo confiar en mis compañeros de trabajo': 42,
    'Entre compañeros solucionamos los problemas de trabajo de forma respetuosa': 43,
    'En mi trabajo me hacen sentir parte del grupo': 44,
    'Cuando tenemos que realizar trabajo de equipo los compañeros colaboran': 45,
    'Mis compañeros de trabajo me ayudan cuando tengo dificultades': 46,
    'Me informan sobre lo que hago bien en mi trabajo': 47,
    'La forma como evalúan mi trabajo en mi centro de trabajo me ayuda a mejorar mi desempeño': 48,
    'En mi centro de trabajo me pagan a tiempo mi salario': 49,
    'El pago que recibo es el que merezco por el trabajo que realizo': 50,
    'Si obtengo los resultados esperados en mi trabajo me recompensan o reconocen': 51,
    'Las personas que hacen bien el trabajo pueden crecer laboralmente': 52,
    'Considero que mi trabajo es estable': 53,
    'En mi trabajo existe continua rotación de personal': 54,
    'Siento orgullo de laborar en este centro de trabajo': 55,
    'Me siento comprometido con mi trabajo': 56,
    'En mi trabajo puedo expresarme libremente sin interrupciones': 57,
    'Recibo críticas constantes a mi persona y/o trabajo': 58,
    'Recibo burlas, calumnias, difamaciones, humillaciones o ridiculizaciones': 59,
    'Se ignora mi presencia o se me excluye de las reuniones de trabajo y en la toma de decisiones': 60,
    'Se manipulan las situaciones de trabajo para hacerme parecer un mal trabajador': 61,
    'Se ignoran mis éxitos laborales y se atribuyen a otros trabajadores': 62,
    'Me bloquean o impiden las oportunidades que tengo para obtener ascenso o mejora en mi trabajo': 63,
    'He presenciado actos de violencia en mi centro de trabajo': 64,
    'Atiendo clientes o usuarios muy enojados': 65,
    'Mi trabajo me exige atender personas muy necesitadas de ayuda o enfermas': 66,
    'Para hacer mi trabajo debo demostrar sentimientos distintos a los míos': 67,
    'Mi trabajo me exige atender situaciones de violencia': 68,
    'Comunican tarde los asuntos de trabajo': 69,
    'Dificultan el logro de los resultados del trabajo': 70,
    'Cooperan poco cuando se necesita': 71,
    'Ignoran las sugerencias para mejorar su trabajo': 72,
};

// Función para obtener el número de pregunta a partir del texto
const getQuestionNumber = (questionText: string): number => {
    return questionNumberMap[questionText] || 0;
};

</script>
