<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors);

// No emits needed for now

const props = defineProps({
    chartData: { type: Array, required: true, default: () => [] },
    domainName: { type: String, default: '' }
});

const answerLabels = {
    'A': 'Siempre', 'B': 'Casi siempre', 'C': 'Algunas veces',
    'D': 'Casi nunca', 'E': 'Nunca', 'INVALID': 'Inválido / Sin Respuesta'
};

const processedChartData = computed(() => {
    const dimensions = props.chartData.map(item => item.name);
    if (dimensions.length === 0) return { labels: [], datasets: [] };

    const allAnswerKeys = new Set();
    props.chartData.forEach(item => { Object.keys(item.answers).forEach(ans => allAnswerKeys.add(ans)); });
    const sortedAnswerKeys = Array.from(allAnswerKeys).sort();

    const datasets = sortedAnswerKeys.map(answerKey => ({
        label: answerLabels[answerKey] || `Respuesta ${answerKey}`,
        data: props.chartData.map(item => item.answers[answerKey] || 0)
    }));

    return { labels: dimensions, datasets: datasets };
});

const chartRef = ref(null);

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        title: {
            display: true,
            text: `Distribución por Dimensión (Dominio: ${props.domainName || '-'})`
        },
        tooltip: { enabled: true, mode: 'index', intersect: false }
    },
    scales: {
        x: { stacked: true, title: { display: true, text: 'Dimensión' } },
        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Número de Respuestas' } }
    }
}));

onMounted(() => { console.log("DimensionBarChart mounted:", props.chartData); });
watch(() => props.chartData, (newData) => { console.log("DimensionBarChart updated:", newData); }, { deep: true });

</script>

<template>
    <div style="height: 400px; position: relative;">
        <Bar
            ref="chartRef"
            v-if="processedChartData.labels.length > 0"
            :data="processedChartData"
            :options="chartOptions"
        />
        <div v-else class="text-center text-gray-500 p-4">
            No hay datos de dimensión para mostrar para este dominio.
        </div>
    </div>
</template>

<style scoped>
/* Add any necessary scoped styles */
</style>
