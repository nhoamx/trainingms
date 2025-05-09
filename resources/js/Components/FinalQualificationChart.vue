<script setup>
import { ref, computed } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Pie } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
  riskLevels: {
    type: Object,
    required: true,
    default: () => ({})
  }
});

// Colores para cada nivel de riesgo según la NOM-035
const riskColors = {
  'Nulo': '#3B82F6',      // Azul
  'Bajo': '#10B981',      // Verde
  'Medio': '#F59E0B',     // Amarillo dorado
  'Alto': '#F97316',      // Naranja
  'Muy Alto': '#EF4444'   // Rojo
};

// Orden de los niveles de riesgo
const riskOrder = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];

// Calcular el total de evaluaciones
const totalEvaluations = computed(() => {
  return riskOrder.reduce((sum, level) => sum + (props.riskLevels[level] || 0), 0);
});

// Preparar datos para la gráfica
const chartData = computed(() => {
  return {
    labels: riskOrder,
    datasets: [{
      data: riskOrder.map(level => props.riskLevels[level] || 0),
      backgroundColor: riskOrder.map(level => riskColors[level]),
      borderColor: riskOrder.map(level => riskColors[level]),
      borderWidth: 1
    }]
  };
});

// Opciones de la gráfica
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        font: {
          size: 12
        }
      }
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const value = context.raw;
          const percentage = totalEvaluations.value > 0 
            ? ((value / totalEvaluations.value) * 100).toFixed(1) 
            : 0;
          return `${context.label}: ${value} personas (${percentage}%)`;
        }
      }
    }
  }
};

</script>

<template>
  <div>
    <!-- Leyenda de totales en la parte superior -->
    <div class="mb-4 text-sm text-gray-600">
      Total de evaluaciones: {{ totalEvaluations }}
    </div>
    
    <!-- Contenedor de la gráfica con altura fija -->
    <div class="h-[400px]">
      <Pie
        v-if="totalEvaluations > 0"
        :data="chartData"
        :options="chartOptions"
      />
      <div 
        v-else 
        class="h-full flex items-center justify-center text-gray-500"
      >
        No hay datos disponibles para mostrar
      </div>
    </div>
    
    <!-- Leyenda de niveles de riesgo con colores -->
    <div class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-2">
      <div 
        v-for="level in riskOrder" 
        :key="level"
        class="flex items-center space-x-2 p-2 rounded"
      >
        <div 
          class="w-4 h-4 rounded-full" 
          :style="{ backgroundColor: riskColors[level] }"
        ></div>
        <span class="text-sm text-gray-700">{{ level }}</span>
      </div>
    </div>
  </div>
</template>
