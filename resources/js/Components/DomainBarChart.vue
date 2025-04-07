<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar, getElementAtEvent } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale, // Using CategoryScale for domain names on X-axis
    LinearScale,
    Colors
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors);

// Define emitted events
const emit = defineEmits(['domain-click']);

const props = defineProps({
    chartData: {
        type: Array, // Expect an array of objects {id, name, answers}
        required: true,
        default: () => []
    },
    categoryName: { // Add prop to display the selected category name in the title
        type: String,
        default: ''
    }
});

// Reusable answer labels (could be moved to a shared utility)
const answerLabels = {
    'A': 'Siempre',
    'B': 'Casi siempre',
    'C': 'Algunas veces',
    'D': 'Casi nunca',
    'E': 'Nunca',
    'INVALID': 'Inválido / Sin Respuesta'
};

// Store domain IDs corresponding to labels index
const domainIdsByIndex = ref([]);

const processedChartData = computed(() => {
    const domains = props.chartData.map(item => item.name);
    domainIdsByIndex.value = props.chartData.map(item => item.id); // Store IDs

    if (domains.length === 0) {
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
        labels: domains, // Use domain names as labels
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
        const domainId = domainIdsByIndex.value[index]; // Get the ID
        console.log('Clicked Domain Index:', index, 'Domain ID:', domainId);
        if (domainId) {
            emit('domain-click', domainId);
        }
    }
};

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    onClick: handleChartClick, // Add the click handler
    plugins: {
        legend: { position: 'top' },
        title: {
            display: true,
            text: `Distribución por Dominio (Categoría: ${props.categoryName || '-'}) - Haz clic para ver Dimensiones`
        },
        tooltip: { enabled: true, mode: 'index', intersect: false }
    },
    scales: {
        x: { stacked: true, title: { display: true, text: 'Dominio' } },
        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Número de Respuestas' } }
    }
}));

onMounted(() => {
    console.log("DomainBarChart mounted. Received data:", props.chartData);
});

watch(() => props.chartData, (newData) => {
    console.log("DomainBarChart data updated:", newData);
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
            No hay datos de dominio para mostrar para esta categoría.
        </div>
    </div>
</template>

<style scoped>
/* Add any necessary scoped styles */
</style>
