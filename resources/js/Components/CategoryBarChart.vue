<script setup>
import { ref, computed, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

// Registrar todos los componentes de Chart.js que necesitamos
Chart.register(...registerables);

const props = defineProps({
  category: {
    type: Object,
    required: true
  }
});

const canvasRef = ref(null);
const chart = ref(null);

// Arreglo de colores para los tipos de respuestas - nuevos colores solicitados
const typeColors = {
  'A': '#F44336', // Rojo para "Siempre" (Muy Alto)
  'B': '#FFB300', // Naranja para "Casi Siempre" (Alto)
  'C': '#FFEB3B', // Amarillo para "Algunas Veces" (Medio)
  'D': '#8BC34A', // Verde para "Casi Nunca" (Bajo)
  'E': '#4DD0C6', // Turquesa para "Nunca" (Nulo)
};

// Etiquetas para los tipos de respuestas
const typeLabels = {
  'A': 'Siempre',
  'B': 'Casi siempre',
  'C': 'Algunas veces',
  'D': 'Casi nunca',
  'E': 'Nunca',
};

const chartData = computed(() => {
  // Ordenamos los tipos de respuesta de E a A (de Nulo a Muy Alto)
  const answerTypes = ['E', 'D', 'C', 'B', 'A'];
  
  const data = {
    labels: answerTypes.map(type => typeLabels[type]),
    datasets: [{
      label: 'Respuestas',
      data: answerTypes.map(type => props.category.responses[type] || 0),
      backgroundColor: answerTypes.map(type => typeColors[type]),
      borderColor: answerTypes.map(type => typeColors[type]),
      borderWidth: 1
    }]
  };
  
  return data;
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
          const total = props.category.total;
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
        // Para fondos claros (amarillo) usamos texto negro, para el resto texto blanco
        return type === 'C' || type === 'E' ? '#000000' : '#FFFFFF';
      },
      font: {
        weight: 'bold'
      },
      formatter: function(value, context) {
        const total = props.category.total;
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
