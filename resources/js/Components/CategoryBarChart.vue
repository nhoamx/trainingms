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


// Colores y etiquetas para niveles de riesgo NOM-035
const riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
const riskColors = {
  'Nulo': '#3B82F6',      // Azul
  'Bajo': '#10B981',      // Verde
  'Medio': '#F8FF03',     // Amarillo
  'Alto': '#F97316',      // Naranja
  'Muy Alto': '#EF4444'   // Rojo
};
const riskLabels = {
  'Nulo': 'Nulo',
  'Bajo': 'Bajo',
  'Medio': 'Medio',
  'Alto': 'Alto',
  'Muy Alto': 'Muy Alto'
};

const chartData = computed(() => {
  const data = {
    labels: riskLevels.map(risk => riskLabels[risk]),
    datasets: [{
      label: 'Personas',
      data: riskLevels.map(risk => (props.category.risk_levels && props.category.risk_levels[risk]) ? props.category.risk_levels[risk] : 0),
      backgroundColor: riskLevels.map(risk => riskColors[risk]),
      borderColor: riskLevels.map(risk => riskColors[risk]),
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
          // Sumar el total de personas en todos los niveles de riesgo
          const total = Object.values(props.category.risk_levels || {}).reduce((acc, v) => acc + v, 0);
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
          return `${value} personas (${percentage}%)`;
        }
      }
    },
    datalabels: {
      // Color del texto según el color de fondo para mejor legibilidad
      color: function(context) {
        // Para amarillo y azul, usar texto negro, para los demás blanco
        const risk = riskLevels[context.dataIndex];
        return (risk === 'Medio' || risk === 'Nulo') ? '#000000' : '#FFFFFF';
      },
      font: {
        weight: 'bold'
      },
      formatter: function(value, context) {
        const total = Object.values(props.category.risk_levels || {}).reduce((acc, v) => acc + v, 0);
        return value > 0 && total > 0 ? `${((value / total) * 100).toFixed(0)}%` : '';
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
