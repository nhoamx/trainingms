<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Botones de navegación -->
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
                            Volver
                        </Link>
                        <Link
                            :href="route('evaluations.show', { evaluation: evaluation.id })"
                            class="bg-blue-100 text-blue-700 px-4 py-2 rounded hover:bg-blue-200 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                            Ver Evaluación
                        </Link>
                    </div>
                </div>

                <div class="mt-4 text-gray-600">
                    <p>Folio: {{ evaluation.folio }}</p>
                    <p>Fecha: {{ evaluation.created_at }}</p>
                </div>
            </div>

            <!-- Sección de resumen -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Calificación Final -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col justify-center items-center h-full">
                    <h3 class="text-4xl text-center font-semibold text-gray-900 mb-4">Calificación Final</h3>
                    <div class="text-5xl font-bold text-blue-600">
                        {{ totalScore }}
                    </div>
                </div>

                <!-- Categorías -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Categorías</h3>
                    <div class="space-y-2">
                        <div v-for="category in categoryScores" :key="category.name"
                             class="flex justify-between items-center">
                            <span class="text-gray-700">{{ category.name }}:</span>
                            <span class="font-semibold">{{ category.score }}</span>
                        </div>
                    </div>
                </div>

                <!-- Dominios -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Dominios</h3>
                    <div class="space-y-2">
                        <div v-for="domain in domainScores" :key="domain.name"
                             class="flex justify-between items-center">
                            <span class="text-gray-700 pr-2 ">{{ domain.name }}:</span>
                            <span class="font-semibold">{{ domain.score }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla general de resultados -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Categoría
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Dominio
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Dimensión
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Puntaje
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template v-for="(category, categoryIndex) in groupedResults" :key="categoryIndex">
                                    <template v-for="(domain, domainIndex) in category.domains" :key="domainIndex">
                                        <template v-for="(dimension, dimensionIndex) in domain.dimensions" :key="dimensionIndex">
                                            <tr>
                                                <!-- Categoría -->
                                                <td v-if="domainIndex === 0 && dimensionIndex === 0"
                                                    :rowspan="category.rowspan"
                                                    class="px-6 py-4 align-middle border border-gray-200">
                                                    <div class="flex flex-col items-center justify-center h-full">
                                                        <div class="font-medium text-center">{{ category.name }}</div>
                                                        <div class="text-sm text-gray-500 text-center">
                                                            Puntaje: {{ category.score }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Dominio -->
                                                <td v-if="dimensionIndex === 0"
                                                    :rowspan="domain.rowspan"
                                                    class="px-6 py-4 align-middle border border-gray-200">
                                                    <div class="flex flex-col items-center justify-center h-full">
                                                        <div class="font-medium text-center">{{ domain.name }}</div>
                                                        <div class="text-sm text-gray-500 text-center">
                                                            Puntaje: {{ domain.score }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Dimensión -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    {{ dimension.name }}
                                                </td>

                                                <!-- Items -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    <div class="flex flex-wrap justify-center gap-2">
                                                        <span
                                                            v-for="(item, index) in dimension.items"
                                                            :key="index"
                                                            class="cursor-help relative group"
                                                        >
                                                            {{ item }}
                                                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-sm rounded-lg p-2 min-w-[200px] z-50 left-1/2 transform -translate-x-1/2">
                                                                <p class="font-bold mb-1">Pregunta:</p>
                                                                <p class="mb-2">{{ item }}</p>
                                                                <p class="font-bold mb-1">Respuesta:</p>
                                                                <p class="mb-2">{{ dimension.respuestas[index] }}</p>
                                                                <p class="font-bold mb-1">Puntaje:</p>
                                                                <p>{{ dimension.itemScores[index] }}</p>
                                                                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                                                                    <div class="border-8 border-transparent border-t-gray-800"></div>
                                                                </div>
                                                            </div>
                                                            {{ index < dimension.items.length - 1 ? ',' : '' }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- Puntaje -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    {{ dimension.score }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </template>
                            </tbody>
                    </table>
                </div>
            </div>

            <!-- Tablas de interpretación en cards separadas -->
            <!-- Tabla de interpretación final -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Interpretación de Resultados Finales</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Resultado del cuestionario
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            Calificación final
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-green-100': totalScore < 50}">
                                            C_final < 50
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-yellow-100': totalScore >= 50 && totalScore < 75}">
                                            50 ≤ C_final < 75
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-orange-100': totalScore >= 75 && totalScore < 99}">
                                            75 ≤ C_final < 99
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-red-100': totalScore >= 99 && totalScore < 140}">
                                            99 ≤ C_final < 140
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-red-200': totalScore >= 140}">
                                            C_final ≥ 140
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de interpretación por categorías -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Interpretación por Categorías</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Categoría
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="category in categoryScores" :key="category.name">
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            {{ category.name }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'nulo')">
                                            {{ getCategoryRangeText(category.name, 'nulo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'bajo')">
                                            {{ getCategoryRangeText(category.name, 'bajo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'medio')">
                                            {{ getCategoryRangeText(category.name, 'medio') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'alto')">
                                            {{ getCategoryRangeText(category.name, 'alto') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'muy_alto')">
                                            {{ getCategoryRangeText(category.name, 'muy_alto') }}
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de interpretación por dominios -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Interpretación por Dominios</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Dominio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="domain in domainScores" :key="domain.name">
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            {{ domain.name }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'nulo')">
                                            {{ getDomainRangeText(domain.name, 'nulo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'bajo')">
                                            {{ getDomainRangeText(domain.name, 'bajo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'medio')">
                                            {{ getDomainRangeText(domain.name, 'medio') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'alto')">
                                            {{ getDomainRangeText(domain.name, 'alto') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'muy_alto')">
                                            {{ getDomainRangeText(domain.name, 'muy_alto') }}
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue";
import { computed } from 'vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluation: {
        type: Object,
        required: true
    },
    results: {
        type: Array,
        required: true
    }
});

const groupedResults = computed(() => {
    const categoriesMap = new Map();

    props.results.forEach(result => {
        const categoryKey = result.categoria.nombre;
        const domainKey = result.dominio.nombre;

        if (!categoriesMap.has(categoryKey)) {
            categoriesMap.set(categoryKey, {
                name: result.categoria.nombre,
                score: result.categoria.puntaje,
                domains: new Map(),
                rowspan: 0
            });
        }

        const category = categoriesMap.get(categoryKey);
        if (!category.domains.has(domainKey)) {
            category.domains.set(domainKey, {
                name: result.dominio.nombre,
                score: result.dominio.puntaje,
                dimensions: new Map(),
                rowspan: 0
            });
        }

        const domain = category.domains.get(domainKey);
        const dimensionKey = result.dimension;

        if (!domain.dimensions.has(dimensionKey)) {
            domain.dimensions.set(dimensionKey, {
                name: result.dimension,
                items: [],
                respuestas: [],
                itemScores: [],
                score: 0
            });
        }

        const dimension = domain.dimensions.get(dimensionKey);
        dimension.items.push(result.item);
        dimension.respuestas.push(result.respuesta);
        dimension.itemScores.push(result.puntaje);
    });

    // Calcular scores y convertir Maps a arrays
    return Array.from(categoriesMap.values()).map(category => {
        const domains = Array.from(category.domains.values()).map(domain => {
            const dimensions = Array.from(domain.dimensions.values()).map(dimension => {
                // Calcular score de dimensión
                dimension.score = dimension.itemScores.reduce((sum, score) => sum + score, 0);
                return dimension;
            });

            // Calcular rowspan y score del dominio
            domain.rowspan = dimensions.length;
            domain.score = dimensions.reduce((sum, dim) => sum + dim.score, 0);

            return {
                ...domain,
                dimensions
            };
        });

        // Calcular rowspan y score de la categoría
        category.rowspan = domains.reduce((sum, domain) => sum + domain.rowspan, 0);
        category.score = domains.reduce((sum, domain) => sum + domain.score, 0);

        return {
            ...category,
            domains
        };
    });
});

const categoryScores = computed(() => {
    return groupedResults.value.map(category => ({
        name: category.name,
        score: category.score
    }));
});

const domainScores = computed(() => {
    return groupedResults.value.flatMap(category =>
        category.domains.map(domain => ({
            name: domain.name,
            score: domain.score
        }))
    );
});

const totalScore = computed(() => {
    return groupedResults.value.reduce((total, category) => total + category.score, 0);
});

const categoryRanges = {
    'Ambiente de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 9 },
        medio: { min: 9, max: 11 },
        alto: { min: 11, max: 14 },
        muy_alto: { min: 14 }
    },
    'Factores propios de la actividad': {
        nulo: { max: 15 },
        bajo: { min: 15, max: 30 },
        medio: { min: 30, max: 45 },
        alto: { min: 45, max: 60 },
        muy_alto: { min: 60 }
    },
    'Organización del tiempo de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 7 },
        medio: { min: 7, max: 10 },
        alto: { min: 10, max: 13 },
        muy_alto: { min: 13 }
    },
    'Liderazgo y relaciones en el trabajo': {
        nulo: { max: 14 },
        bajo: { min: 14, max: 29 },
        medio: { min: 29, max: 42 },
        alto: { min: 42, max: 58 },
        muy_alto: { min: 58 }
    },
    'Entorno organizacional': {
        nulo: { max: 10 },
        bajo: { min: 10, max: 14 },
        medio: { min: 14, max: 18 },
        alto: { min: 18, max: 23 },
        muy_alto: { min: 23 }
    }
};

const getCategoryRangeText = (categoryName, level) => {
    const ranges = categoryRanges[categoryName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    if (level === 'nulo') {
        return `C_cat < ${range.max}`;
    } else if (level === 'muy_alto') {
        return `C_cat ≥ ${range.min}`;
    } else {
        return `${range.min} ≤ C_cat < ${range.max}`;
    }
};

const getCategoryColorClass = (categoryName, score, level) => {
    const ranges = categoryRanges[categoryName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    const isInRange = (score) => {
        if (level === 'nulo') {
            return score < range.max;
        } else if (level === 'muy_alto') {
            return score >= range.min;
        } else {
            return score >= range.min && score < range.max;
        }
    };

    return {
        'bg-green-100': level === 'nulo' && isInRange(score),
        'bg-yellow-100': level === 'bajo' && isInRange(score),
        'bg-orange-100': level === 'medio' && isInRange(score),
        'bg-red-100': level === 'alto' && isInRange(score),
        'bg-red-200': level === 'muy_alto' && isInRange(score)
    };
};

const domainRanges = {
    'Condiciones en el ambiente de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 9 },
        medio: { min: 9, max: 11 },
        alto: { min: 11, max: 14 },
        muy_alto: { min: 14 }
    },
    'Carga de trabajo': {
        nulo: { max: 15 },
        bajo: { min: 15, max: 21 },
        medio: { min: 21, max: 27 },
        alto: { min: 27, max: 37 },
        muy_alto: { min: 37 }
    },
    'Falta de control sobre el trabajo': {
        nulo: { max: 11 },
        bajo: { min: 11, max: 16 },
        medio: { min: 16, max: 21 },
        alto: { min: 21, max: 25 },
        muy_alto: { min: 25 }
    },
    'Jornada de trabajo': {
        nulo: { max: 1 },
        bajo: { min: 1, max: 2 },
        medio: { min: 2, max: 4 },
        alto: { min: 4, max: 6 },
        muy_alto: { min: 6 }
    },
    'Interferencia en la relación trabajo-familia': {
        nulo: { max: 4 },
        bajo: { min: 4, max: 6 },
        medio: { min: 6, max: 8 },
        alto: { min: 8, max: 10 },
        muy_alto: { min: 10 }
    },
    'Liderazgo': {
        nulo: { max: 9 },
        bajo: { min: 9, max: 12 },
        medio: { min: 12, max: 16 },
        alto: { min: 16, max: 20 },
        muy_alto: { min: 20 }
    },
    'Relaciones en el trabajo': {
        nulo: { max: 10 },
        bajo: { min: 10, max: 13 },
        medio: { min: 13, max: 17 },
        alto: { min: 17, max: 21 },
        muy_alto: { min: 21 }
    },
    'Violencia': {
        nulo: { max: 7 },
        bajo: { min: 7, max: 10 },
        medio: { min: 10, max: 13 },
        alto: { min: 13, max: 16 },
        muy_alto: { min: 16 }
    },
    'Reconocimiento del desempeño': {
        nulo: { max: 6 },
        bajo: { min: 6, max: 10 },
        medio: { min: 10, max: 14 },
        alto: { min: 14, max: 18 },
        muy_alto: { min: 18 }
    },
    'Insuficiente sentido de pertenencia e inestabilidad': {
        nulo: { max: 4 },
        bajo: { min: 4, max: 6 },
        medio: { min: 6, max: 8 },
        alto: { min: 8, max: 10 },
        muy_alto: { min: 10 }
    }
};

const getDomainRangeText = (domainName, level) => {
    const ranges = domainRanges[domainName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    if (level === 'nulo') {
        return `C_dom < ${range.max}`;
    } else if (level === 'muy_alto') {
        return `C_dom ≥ ${range.min}`;
    } else {
        return `${range.min} ≤ C_dom < ${range.max}`;
    }
};

const getDomainColorClass = (domainName, score, level) => {
    const ranges = domainRanges[domainName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    const isInRange = (score) => {
        if (level === 'nulo') {
            return score < range.max;
        } else if (level === 'muy_alto') {
            return score >= range.min;
        } else {
            return score >= range.min && score < range.max;
        }
    };

    return {
        'bg-green-100': level === 'nulo' && isInRange(score),
        'bg-yellow-100': level === 'bajo' && isInRange(score),
        'bg-orange-100': level === 'medio' && isInRange(score),
        'bg-red-100': level === 'alto' && isInRange(score),
        'bg-red-200': level === 'muy_alto' && isInRange(score)
    };
};
</script>

<style scoped>
.border-b {
    border-bottom-width: 1px;
}

td[rowspan] {
    vertical-align: middle !important;
}

/* Asegurarse que el tooltip no se corte en los bordes de la tabla */
.overflow-x-auto {
    overflow: visible;
}
</style>
