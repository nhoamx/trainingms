<script setup>
import { ref, computed, onMounted } from 'vue';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  dimensionScores: {
    type: Array,
    required: true,
    default: () => []
  },
  title: {
    type: String,
    default: 'Puntuación Total por Dimensión'
  }
});

// Creamos una copia ordenada de mayor a menor puntuación
const sortedScores = computed(() => {
  return [...props.dimensionScores].sort((a, b) => b.total_score - a.total_score);
});

// Preparamos los datos para el gráfico
const chartData = computed(() => ({
  labels: sortedScores.value.map(item => item.name),
  datasets: [
    {
      label: 'Puntuación Total',
      data: sortedScores.value.map(item => item.total_score),
      backgroundColor: sortedScores.value.map(item => {
        // Calculamos el color basado en la puntuación relativa al máximo
        const maxScore = Math.max(...sortedScores.value.map(s => s.total_score));
        const normalizedScore = maxScore > 0 ? item.total_score / maxScore : 0;
        
        // Esquema de colores: rojo (alto riesgo) a verde (bajo riesgo)
        if (normalizedScore > 0.8) return '#F44336'; // Rojo - Muy Alto
        if (normalizedScore > 0.6) return '#FFB300'; // Naranja - Alto
        if (normalizedScore > 0.4) return '#FFEB3B'; // Amarillo - Medio
        if (normalizedScore > 0.2) return '#8BC34A'; // Verde - Bajo
        return '#4DD0C6'; // Turquesa - Nulo
      }),
      borderWidth: 1
    }
  ]
}));

// Opciones del gráfico
const chartOptions = {
  indexAxis: 'y',  // Para hacer barras horizontales
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const item = sortedScores.value[context.dataIndex];
          return `Puntuación: ${item.total_score} (${item.question_count} preguntas, promedio: ${item.avg_score})`;
        }
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
        text: 'Dimensión'
      }
    }
  }
};

const chart = ref(null);

onMounted(() => {
  // Hacer cualquier inicialización adicional si es necesario
});
</script>

<template>
  <div>
    <h3 class="text-lg font-semibold mb-6">{{ title }}</h3>
    <div class="h-[400px] w-full">
      <Bar 
        :data="chartData" 
        :options="chartOptions"
        ref="chart"
      />
    </div>
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
