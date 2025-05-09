<template>
  <div class="chart-container" style="position: relative; height: 300px;">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// Registrar todos los componentes de Chart.js que necesitamos
Chart.register(...registerables);
Chart.register(ChartDataLabels);

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['dimension', 'domain', 'category'].includes(value)
  }
});

const canvasRef = ref(null);
const chart = ref(null);

// Colores unificados para todos los tipos de gráficos
const riskColors = {
  'Nulo': '#3B82F6',      // Azul
  'Bajo': '#10B981',      // Verde
  'Medio': '#F59E0B',     // Amarillo
  'Alto': '#F97316',      // Naranja
  'Muy Alto': '#EF4444'   // Rojo
};

// Definir el orden de los niveles de riesgo (de menor a mayor)
const riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];

const chartData = computed(() => {
  // Ahora todos los tipos usan la misma estructura con risk_levels
  const sourceData = props.data.risk_levels || {};

  return {
    labels: riskLevels,
    datasets: [{
      label: props.type === 'category' ? 'Personas' : 'Respuestas',
      data: riskLevels.map(level => sourceData[level] || 0),
      backgroundColor: riskLevels.map(level => riskColors[level]),
      borderColor: riskLevels.map(level => riskColors[level]),
      borderWidth: 1
    }]
  };
});

const chartOptions = computed(() => ({
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
          const total = props.data.total || 0;
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
          return `${value} personas (${percentage}%)`;
        }
      }
    },
    datalabels: {
      color: function(context) {
        const risk = riskLevels[context.dataIndex];
        return (risk === 'Medio' || risk === 'Nulo') ? '#000000' : '#FFFFFF';
      },
      font: {
        weight: 'bold'
      },
      formatter: function(value, context) {
        const total = props.data.total || 0;
        return value > 0 && total > 0 ? `${((value / total) * 100).toFixed(0)}%` : '';
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Número de personas' 
      }
    }
  }
}));

const initChart = () => {
  // Asegurarse que el canvas existe antes de intentar crear el gráfico
  if (!canvasRef.value) return;

  // Destruir el gráfico anterior si existe para evitar duplicados
  if (chart.value) {
    chart.value.destroy();
  }

  // Crear el nuevo gráfico
  const ctx = canvasRef.value.getContext('2d');
  chart.value = new Chart(ctx, {
    type: 'bar',
    data: chartData.value,
    options: chartOptions.value
  });
};

// Inicializar el gráfico una vez que el componente esté montado
onMounted(() => {
  nextTick(() => {
    initChart();
  });
});

// Observar cambios en los datos para actualizar el gráfico
watch(() => props.data, () => {
  nextTick(() => {
    initChart();
  });
}, { deep: true });

// Observar cambios en el tipo para actualizar el gráfico
watch(() => props.type, () => {
  nextTick(() => {
    initChart();
  });
});
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
