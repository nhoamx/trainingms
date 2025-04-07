<script setup>
// Script identical to DomainDetailChart, just different prop name and title
import { ref, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors, ChartDataLabels);

const props = defineProps({
    answerDistribution: { type: Object, required: true, default: () => ({}) },
    dimensionName: { type: String, default: '' } // Changed prop name
});

const answerConfig = [ /* ... same config ... */
    { key: 'E', label: 'Nulo', color: '#99f6e4' },
    { key: 'D', label: 'Bajo', color: '#bbf7d0' },
    { key: 'C', label: 'Medio', color: '#fef08a' },
    { key: 'B', label: 'Alto', color: '#fed7aa' },
    { key: 'A', label: 'Muy Alto', color: '#fca5a5' },
    { key: 'INVALID', label: 'Inválido', color: '#9ca3af' }
];

const processedChartData = computed(() => { /* ... same logic ... */
    const labels = answerConfig.map(a => a.label);
    const data = answerConfig.map(a => props.answerDistribution[a.key] || 0);
    const backgroundColors = answerConfig.map(a => a.color);
    const total = data.reduce((sum, value) => sum + value, 0);
    return {
        labels: labels,
        datasets: [{
            label: 'Número de Respuestas',
            data: data,
            backgroundColor: backgroundColors, borderColor: backgroundColors, borderWidth: 1, total: total
        }]
    };
});
const chartRef = ref(null);
const chartOptions = computed(() => ({ /* ... same options ... */
    responsive: true, maintainAspectRatio: false, indexAxis: 'x',
    plugins: {
        legend: { display: false },
        title: { display: true, text: `Distribución de Respuestas - ${props.dimensionName || '-'}` }, // Use dimensionName
        tooltip: { enabled: true, callbacks: { /* ... */ } },
        datalabels: { anchor: 'end', align: 'top', formatter: (v, ctx) => {/* ... percentage ... */ const t = ctx.chart.data.datasets[0].total; if(t===0 || v===0) return ''; return ((v/t)*100).toFixed(1)+'%';}, color: '#374151', font: { weight: 'bold', size: 11 } }
    },
    scales: {
        x: { stacked: false, grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { stacked: false, beginAtZero: true, title: { display: false } }
    }
}));
</script>
<template>
    <div style="height: 350px; position: relative;">
        <Bar ref="chartRef"
            v-if="processedChartData.labels.length > 0 && processedChartData.datasets[0].data.some(d => d > 0)"
            :data="processedChartData" :options="chartOptions" />
        <div v-else class="text-center text-gray-500 p-4 h-full flex items-center justify-center">
            No hay datos de distribución para mostrar para esta dimensión.
        </div>
    </div>
</template>
<style scoped></style>
