<script setup>
import { ref, computed, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

// Registrar todos los componentes de Chart.js que necesitamos
Chart.register(...registerables);

const props = defineProps({
  domain: {
    type: Object,
    required: true
  }
});

const canvasRef = ref(null);
const chart = ref(null);


// Paleta UI/UX recomendada para máximo contraste y claridad
// Orden: Muy Alto (A), Alto (B), Medio (C), Bajo (D), Nulo (E)
const typeColors = {
  'A': '#EF4444', // Muy alto - Rojo fuerte
  'B': '#F97316', // Alto - Naranja intenso
  'C': '#F59E0B', // Medio - Amarillo dorado
  'D': '#10B981', // Bajo - Verde
  'E': '#3B82F6', // Nulo - Azul
};

const typeLabels = {
  'A': 'Muy Alto',
  'B': 'Alto',
  'C': 'Medio',
  'D': 'Bajo',
  'E': 'Nulo',
};

const chartData = computed(() => {
  // Orden E, D, C, B, A (Nulo a Muy alto)
  const answerTypes = ['E', 'D', 'C', 'B', 'A'];
  return {
    labels: answerTypes.map(type => typeLabels[type]),
    datasets: [{
      label: 'Respuestas',
      data: answerTypes.map(type => props.domain.responses[type] || 0),
      backgroundColor: answerTypes.map(type => typeColors[type]),
      borderColor: answerTypes.map(type => typeColors[type]),
      borderWidth: 1
    }]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const value = context.raw;
          const total = props.domain.total;
          const percentage = ((value / total) * 100).toFixed(1);
          return `${value} respuestas (${percentage}%)`;
        }
      }
    },
    datalabels: {
      // Color del texto según el color de fondo para mejor legibilidad
      color: function(context) {
        const index = context.dataIndex;
        const type = ['E', 'D', 'C', 'B', 'A'][index];
        // Para fondos claros (amarillo C y turquesa E) usamos texto negro
        return type === 'C' || type === 'E' ? '#000000' : '#FFFFFF';
      },
      font: {
        weight: 'bold'
      },
      formatter: function(value, context) {
        const total = props.domain.total;
        return value > 0 ? `${((value / total) * 100).toFixed(0)}%` : '';
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Número de respuestas'
      }
    }
  }
};

onMounted(() => {
  if (canvasRef.value) {
    // Destruir el gráfico anterior si existe
    if (chart.value) {
      chart.value.destroy();
    }
    
    // Crear el nuevo gráfico
    const ctx = canvasRef.value.getContext('2d');
    chart.value = new Chart(ctx, {
      type: 'bar',
      data: chartData.value,
      options: chartOptions
    });
  }
});
</script>

<template>
  <div class="h-full">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
