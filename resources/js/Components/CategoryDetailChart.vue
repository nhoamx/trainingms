<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels'; // Import datalabels plugin

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Colors, ChartDataLabels); // Register plugin

// Bandera para desactivar la visualización de gráficas actuales
// NOTA: Esta gráfica está desactivada, usar el nuevo reporte de categorías accesible desde el menú de navegación
const disableChart = true;

const props = defineProps({
    // Expects { 'A': count, 'B': count, 'C': count, 'D': count, 'E': count, 'INVALID': count }
    answerDistribution: { type: Object, required: true, default: () => ({}) },
    categoryName: { type: String, default: '' }
});

// Define mapping from answer keys to NEW labels and colors (matching table levels)
// Order defines the display order on the chart
const answerConfig = [
    { key: 'E', label: 'Nulo', color: '#99f6e4' },        // Respuesta E -> Nulo (Teal)
    { key: 'D', label: 'Bajo', color: '#bbf7d0' },       // Respuesta D -> Bajo (Green)
    { key: 'C', label: 'Medio', color: '#fef08a' },      // Respuesta C -> Medio (Yellow)
    { key: 'B', label: 'Alto', color: '#fed7aa' },       // Respuesta B -> Alto (Orange)
    { key: 'A', label: 'Muy Alto', color: '#fca5a5' },    // Respuesta A -> Muy Alto (Red)
    { key: 'INVALID', label: 'Inválido', color: '#9ca3af' } // Respuesta INVALID -> Inválido (Gray)
];

const processedChartData = computed(() => {
    const labels = answerConfig.map(a => a.label); // Use new labels
    // Data still comes from counts of original answers (A, B, C, D, E, INVALID)
    const data = answerConfig.map(a => props.answerDistribution[a.key] || 0);
    const backgroundColors = answerConfig.map(a => a.color); // Use new colors

    const total = data.reduce((sum, value) => sum + value, 0);

    return {
        labels: labels,
        datasets: [{
            label: 'Número de Respuestas', // Tooltip label prefix
            data: data,
            backgroundColor: backgroundColors,
            borderColor: backgroundColors,
            borderWidth: 1,
            total: total // For percentage calculation
        }]
    };
});

const chartRef = ref(null);

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'x',
    plugins: {
        legend: { display: false },
        title: {
            display: true,
            text: `Distribución de Tipos de Respuesta - ${props.categoryName || '-'}` // Adjusted title slightly
        },
        tooltip: {
            enabled: true,
            callbacks: {
                 label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) { label += ': '; }
                    if (context.parsed.y !== null) {
                        label += context.parsed.y; // Show the count
                        const total = context.dataset.total;
                        if (total > 0) {
                             const percentage = ((context.parsed.y / total) * 100).toFixed(2) + '%';
                             label += ` (${percentage})`; // Add percentage
                        }
                    }
                    return label;
                }
            }
        },
        datalabels: { // Show percentage on top of bars
             anchor: 'end',
             align: 'top',
             formatter: (value, context) => {
                 const total = context.chart.data.datasets[0].total;
                 if (total === 0 || value === 0) return ''; // Avoid 0% or division by zero, hide label
                 const percentage = ((value / total) * 100).toFixed(1) + '%'; // Use 1 decimal place for cleaner look
                 return percentage;
             },
             color: '#374151', // Slightly darker gray
             font: {
                 weight: 'bold',
                 size: 11
             }
         }
    },
    scales: {
        x: {
             stacked: false,
             grid: { display: false },
             ticks: { // Ensure labels fit
                font: {
                    size: 11
                }
            }
         },
        y: {
             stacked: false,
             beginAtZero: true,
             title: {
                 display: false,
             }
         }
    }
}));

</script>

<template>
    <div style="height: 350px; position: relative;">
        <div v-if="disableChart" class="text-center p-4 h-full flex flex-col items-center justify-center bg-blue-50 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <p class="text-blue-700 font-medium mb-1">¡Tenemos un nuevo reporte de categorías mejorado!</p>
            <p class="text-gray-600">Por favor, utiliza el nuevo reporte accesible desde el menú de navegación.</p>
            <a :href="route('reports.category.show')" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Ir al nuevo reporte
            </a>
        </div>
        <Bar
            v-else-if="processedChartData.labels.length > 0 && processedChartData.datasets[0].data.some(d => d > 0)"
            ref="chartRef"
            :data="processedChartData"
            :options="chartOptions"
        />
        <div v-else class="text-center text-gray-500 p-4 h-full flex items-center justify-center">
            No hay datos de distribución para mostrar para esta categoría.
        </div>
    </div>
</template>

<style scoped>
/* Add any necessary scoped styles */
</style>
