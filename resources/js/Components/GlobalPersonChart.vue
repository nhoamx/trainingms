<script setup>
import { ref, computed } from 'vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import { Pie, Bar } from 'vue-chartjs';

ChartJS.register(ArcElement, CategoryScale, LinearScale, BarElement, Tooltip, Legend, Title);

const props = defineProps({
  personData: {
    type: Object,
    required: true,
    default: () => ({})
  }
});

// Determinar si tenemos datos válidos para mostrar
const hasValidData = computed(() => {
  return props.personData && 
         props.personData.person_counts && 
         Object.keys(props.personData.person_counts).length > 0;
});

// Preparar los datos para el gráfico de pastel
const pieChartData = computed(() => {
  if (!hasValidData.value) return { labels: [], datasets: [] };
  
  // Obtener los datos de personas
  const personData = props.personData.person_counts;
  
  // Preparar etiquetas y datos
  const labels = Object.keys(personData).map(key => {
    return personData[key].label;
  });
  
  const data = Object.keys(personData).map(key => {
    return personData[key].value;
  });
  
  const backgroundColor = Object.keys(personData).map(key => {
    return personData[key].color;
  });
  
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor,
        borderWidth: 1
      }
    ]
  };
});

// Preparar los datos para el gráfico de barras
const barChartData = computed(() => {
  if (!hasValidData.value) return { labels: [], datasets: [] };
  
  // Obtener los datos de personas
  const personData = props.personData.person_counts;
  
  // Preparar etiquetas y datos
  const labels = Object.keys(personData).map(key => {
    return key + ' - ' + personData[key].label;
  });
  
  const data = Object.keys(personData).map(key => {
    return personData[key].value;
  });
  
  const backgroundColor = Object.keys(personData).map(key => {
    return personData[key].color;
  });
  
  return {
    labels,
    datasets: [
      {
        label: 'Total de Personas',
        data,
        backgroundColor,
        borderWidth: 1
      }
    ]
  };
});

// Opciones para el gráfico de pastel
const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
    },
    title: {
      display: true,
      text: 'Distribución Global de Personas por Respuesta'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.raw || 0;
          const total = props.personData.total_persons || 0;
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
          return `${label}: ${value} personas (${percentage}%)`;
        }
      }
    }
  }
};

// Opciones para el gráfico de barras
const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: true,
      text: 'Conteo de Personas por Tipo de Respuesta'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.dataset.label || '';
          const value = context.raw || 0;
          const total = props.personData.total_persons || 0;
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
          return `${label}: ${value} personas (${percentage}%)`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Número de Personas',
        font: {
          weight: 'bold'
        }
      }
    },
    x: {
      title: {
        display: true,
        text: 'Opciones de Respuesta',
        font: {
          weight: 'bold'
        }
      }
    }
  }
};

// Total de personas
const totalPersons = computed(() => props.personData?.total_persons || 0);
</script>

<template>
  <div class="space-y-8">
    <div class="bg-white p-6 rounded-lg shadow">
      <div v-if="hasValidData" class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Total de Personas: {{ totalPersons }}</h3>
        <p class="text-sm text-gray-600">
          Número total de personas que han respondido al menos una pregunta del cuestionario.
        </p>
      </div>
      
      <!-- Resumen numérico -->
      <div v-if="hasValidData" class="grid grid-cols-5 gap-4 mb-6">
        <div 
          v-for="(value, key) in props.personData.person_counts" 
          :key="key"
          class="p-3 rounded text-white flex flex-col items-center"
          :style="{ backgroundColor: value.color }"
        >
          <div class="text-xs mb-1">{{ key }} - {{ value.label }}</div>
          <div class="text-2xl font-bold">{{ value.value }}</div>
          <div class="text-xs">{{ (value.percentage || 0).toFixed(1) }}%</div>
        </div>
      </div>
      
      <div v-if="hasValidData" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Gráfico de Pastel -->
        <div class="h-72">
          <h4 class="text-center text-sm font-medium text-gray-700">Distribución Porcentual</h4>
          <Pie 
            :data="pieChartData" 
            :options="pieChartOptions" 
          />
        </div>
        
        <!-- Gráfico de Barras -->
        <div class="h-72">
          <h4 class="text-center text-sm font-medium text-gray-700">Conteo por Respuesta</h4>
          <Bar 
            :data="barChartData" 
            :options="barChartOptions" 
          />
        </div>
      </div>
      
      <div v-else class="text-center text-gray-500 py-8">
        No hay datos disponibles para mostrar el gráfico.
      </div>
    </div>
  </div>
</template>
