<script setup>
import { computed } from 'vue';

const props = defineProps({
    categoryScores: {
        type: Array,
        required: true,
        default: () => []
    }
});

// Calcular el total general de puntos para porcentajes
const totalOverall = computed(() => {
    return props.categoryScores.reduce((sum, category) => sum + category.total_score, 0);
});

// Calcular el porcentaje de contribución de cada categoría al total
const categoriesWithPercentage = computed(() => {
    if (totalOverall.value === 0) return props.categoryScores;
    
    return props.categoryScores.map(category => ({
        ...category,
        percentage: ((category.total_score / totalOverall.value) * 100).toFixed(2)
    }));
});
</script>

<template>
    <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
        <h3 class="text-lg font-semibold mb-4">Desglose de Puntuación por Categoría</h3>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categoría
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Puntuación Total
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        # Preguntas
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Promedio
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        % del Total
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(category, index) in categoriesWithPercentage" :key="index" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ category.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ category.total_score }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                        {{ category.question_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                        <span 
                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                            :style="{
                                backgroundColor: category.avg_score < 1 ? '#4DD0C6' : 
                                                 category.avg_score < 2 ? '#8BC34A' :
                                                 category.avg_score < 3 ? '#FFEB3B' :
                                                 category.avg_score < 4 ? '#FFB300' : '#F44336',
                                color: category.avg_score >= 1 && category.avg_score < 3 ? '#000' : '#fff'
                            }"
                        >
                            {{ category.avg_score }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                        {{ category.percentage }}%
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="h-2.5 rounded-full"
                                :style="{
                                    width: `${category.percentage}%`,
                                    backgroundColor: category.avg_score < 1 ? '#4DD0C6' : 
                                                     category.avg_score < 2 ? '#8BC34A' :
                                                     category.avg_score < 3 ? '#FFEB3B' :
                                                     category.avg_score < 4 ? '#FFB300' : '#F44336'
                                }"
                            ></div>
                        </div>
                    </td>
                </tr>
                
                <!-- Fila de totales -->
                <tr class="bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                        TOTAL
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ totalOverall }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ props.categoryScores.reduce((sum, cat) => sum + cat.question_count, 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ 
                            totalOverall && props.categoryScores.length ? 
                            (totalOverall / props.categoryScores.reduce((sum, cat) => sum + cat.question_count, 0)).toFixed(2) : 
                            '0.00' 
                        }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        100%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
