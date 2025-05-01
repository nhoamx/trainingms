<script setup>
import { ref, computed, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    domainScores: {
        type: Array,
        required: true,
        default: () => []
    },
    title: {
        type: String,
        default: 'Puntuación Total por Dominio'
    }
});

const chartData = computed(() => {
    const labels = props.domainScores.map(item => item.name);
    const data = props.domainScores.map(item => item.total_score);
    
    // Asignamos colores según la puntuación con el esquema de colores
    const colors = data.map(score => {
        // Determinamos el color basado en el score (asumiendo que mayor score = mayor riesgo)
        const maxPossible = Math.max(...data);
        const normalized = maxPossible > 0 ? score / maxPossible : 0;
        
        // Aplicamos los colores
        if (normalized < 0.20) {
            return '#4DD0C6'; // Nulo (turquesa claro)
        } else if (normalized < 0.40) {
            return '#8BC34A'; // Bajo (verde)
        } else if (normalized < 0.60) {
            return '#FFEB3B'; // Medio (amarillo)
        } else if (normalized < 0.80) {
            return '#FFB300'; // Alto (naranja)
        } else {
            return '#F44336'; // Muy Alto (rojo)
        }
    });
    
    return {
        labels: labels,
        datasets: [
            {
                label: 'Puntuación Total',
                data: data,
                backgroundColor: colors,
                borderColor: colors.map(color => color),
                borderWidth: 1
            }
        ]
    };
});

const chartOptions = computed(() => ({
    indexAxis: 'y', // Esto hace que las barras sean horizontales
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const domainIndex = context.dataIndex;
                    const domain = props.domainScores[domainIndex];
                    
                    return [
                        `Puntuación: ${context.raw}`,
                        `Preguntas: ${domain.question_count}`,
                        `Promedio: ${domain.avg_score}`
                    ];
                }
            }
        },
        title: {
            display: true,
            text: props.title,
            font: {
                size: 16
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true,
            title: {
                display: true,
                text: 'Puntuación Total'
            }
        },
        y: {
            title: {
                display: true,
                text: 'Dominio'
            }
        }
    }
}));
</script>

<template>
    <div class="bg-white p-6 rounded-lg shadow">
        <div style="height: 400px; position: relative;">
            <Bar
                v-if="domainScores && domainScores.length > 0"
                :data="chartData"
                :options="chartOptions"
            />
            <div v-else class="flex items-center justify-center h-full text-gray-500">
                No hay datos disponibles para mostrar
            </div>
        </div>
    </div>
</template>
