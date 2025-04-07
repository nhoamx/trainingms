<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar, getElementAtEvent } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    Colors
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    Colors
);

// Define emitted events
const emit = defineEmits(['category-click']);

const props = defineProps({
    chartData: {
        type: Array,
        required: true,
        default: () => []
    }
});

// Define readable names for answers
const answerLabels = {
    'A': 'Siempre',
    'B': 'Casi siempre',
    'C': 'Algunas veces',
    'D': 'Casi nunca',
    'E': 'Nunca',
    'INVALID': 'Inválido / Sin Respuesta'
};

// Store category IDs corresponding to labels index
const categoryIdsByIndex = ref([]);

const processedChartData = computed(() => {
    const categories = props.chartData.map(item => item.name);
    categoryIdsByIndex.value = props.chartData.map(item => item.id);

    if (categories.length === 0) {
        return { labels: [], datasets: [] };
    }

    const allAnswerKeys = new Set();
    props.chartData.forEach(item => {
        Object.keys(item.answers).forEach(ans => allAnswerKeys.add(ans));
    });
    const sortedAnswerKeys = Array.from(allAnswerKeys).sort();

    const datasets = sortedAnswerKeys.map(answerKey => ({
        label: answerLabels[answerKey] || `Respuesta ${answerKey}`,
        data: props.chartData.map(item => item.answers[answerKey] || 0)
    }));

    return {
        labels: categories,
        datasets: datasets
    };
});

const chartRef = ref(null);

// Click handler for the chart
const handleChartClick = (event) => {
    const chart = chartRef.value?.chart;
    if (!chart) return;

    const elements = getElementAtEvent(chart, event);

    if (elements.length > 0) {
        const { index } = elements[0];
        const categoryId = categoryIdsByIndex.value[index];
        console.log('Clicked Category Index:', index, 'Category ID:', categoryId);
        if (categoryId) {
            emit('category-click', categoryId);
        }
    }
};

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    onClick: handleChartClick,
    plugins: {
        legend: { position: 'top' },
        title: {
            display: true,
            text: 'Distribución de Respuestas por Categoría (Haz clic en una categoría para ver Dominios)'
        },
        tooltip: { enabled: true, mode: 'index', intersect: false }
    },
    scales: {
        x: { stacked: true, title: { display: true, text: 'Categoría' } },
        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Número de Respuestas' } }
    }
});

onMounted(() => {
    console.log("CategoryBarChart mounted. Received data (array format expected):", props.chartData);
    console.log("Processed chart data:", processedChartData.value);
});

watch(() => props.chartData, (newData) => {
    console.log("CategoryBarChart data updated:", newData);
    console.log("Processed chart data updated:", processedChartData.value);
}, { deep: true });

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
            No hay datos suficientes para mostrar la gráfica.
        </div>
    </div>
</template>

<style scoped>
/* Add any necessary scoped styles */
</style>
