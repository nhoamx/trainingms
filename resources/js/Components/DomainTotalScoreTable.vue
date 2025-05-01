<script setup>
import { computed } from 'vue';

const props = defineProps({
    domainScores: {
        type: Array,
        required: true,
        default: () => []
    }
});

// Calcular el total general de puntos para porcentajes
const totalOverall = computed(() => {
    return props.domainScores.reduce((sum, domain) => sum + domain.total_score, 0);
});

// Calcular el porcentaje de contribución de cada dominio al total
const domainsWithPercentage = computed(() => {
    if (totalOverall.value === 0) return props.domainScores;
    
    return props.domainScores.map(domain => ({
        ...domain,
        percentage: ((domain.total_score / totalOverall.value) * 100).toFixed(2)
    }));
});
</script>

<template>
    <div class="bg-white p-6 rounded-lg shadow overflow-x-auto">
        <h3 class="text-lg font-semibold mb-4">Desglose de Puntuación por Dominio</h3>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Dominio
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
                <tr v-for="(domain, index) in domainsWithPercentage" :key="index" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ domain.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ domain.total_score }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                        {{ domain.question_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                        <span 
                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                            :style="{
                                backgroundColor: domain.avg_score < 1 ? '#4DD0C6' : 
                                                 domain.avg_score < 2 ? '#8BC34A' :
                                                 domain.avg_score < 3 ? '#FFEB3B' :
                                                 domain.avg_score < 4 ? '#FFB300' : '#F44336',
                                color: domain.avg_score >= 1 && domain.avg_score < 3 ? '#000' : '#fff'
                            }"
                        >
                            {{ domain.avg_score }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                        {{ domain.percentage }}%
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                            <div class="h-2.5 rounded-full"
                                :style="{
                                    width: `${domain.percentage}%`,
                                    backgroundColor: domain.avg_score < 1 ? '#4DD0C6' : 
                                                     domain.avg_score < 2 ? '#8BC34A' :
                                                     domain.avg_score < 3 ? '#FFEB3B' :
                                                     domain.avg_score < 4 ? '#FFB300' : '#F44336'
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
                        {{ props.domainScores.reduce((sum, dom) => sum + dom.question_count, 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">
                        {{ 
                            totalOverall && props.domainScores.length ? 
                            (totalOverall / props.domainScores.reduce((sum, dom) => sum + dom.question_count, 0)).toFixed(2) : 
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
