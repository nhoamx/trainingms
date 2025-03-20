<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js';
import { Bar, Pie } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

const props = defineProps({
  demographicData: {
    type: Array,
    required: true
  }
});


// Referencias para los gráficos
const ageDistributionChart = ref(null);
const genderDistributionChart = ref(null);
const educationLevelChart = ref(null);
const departmentDistributionChart = ref(null);
const jobTypeChart = ref(null);
const contractTypeChart = ref(null);

// Datos procesados
const processedData = computed(() => {
  if (!props.demographicData.length) return null;

  const result = {
    genderData: {
      labels: ['Masculino', 'Femenino'],
      datasets: [{
        data: [0, 0],
        backgroundColor: ['#3B82F6', '#EC4899']
      }]
    },
    ageRanges: {
      labels: ['18-25', '26-35', '36-45', '46-55', '56+'],
      datasets: [{
        label: 'Distribución por Edad',
        data: [0, 0, 0, 0, 0],
        backgroundColor: '#60A5FA'
      }]
    },
    educationLevel: {
      labels: [],
      datasets: [{
        label: 'Nivel Educativo',
        data: [],
        backgroundColor: '#34D399'
      }]
    },
    departmentDistribution: {
      labels: [],
      datasets: [{
        label: 'Distribución por Departamento',
        data: [],
        backgroundColor: '#A78BFA'
      }]
    },
    jobTypeDistribution: {
      labels: [],
      datasets: [{
        label: 'Tipo de Puesto',
        data: [],
        backgroundColor: '#F472B6'
      }]
    },
    contractTypeDistribution: {
      labels: [],
      datasets: [{
        label: 'Tipo de Contratación',
        data: [],
        backgroundColor: '#FBBF24'
      }]
    }
  };

  console.log(props.demographicData);
  // Procesar datos
  const educationCount = new Map();
  const departmentCount = new Map();
  const jobTypeCount = new Map();
  const contractTypeCount = new Map();

  props.demographicData.forEach(evaluation => {
    const data = evaluation.demographic_data;
    if (!data) return;

    // Procesar género
    if (data.sexo === 'masculino') result.genderData.datasets[0].data[0]++;
    else if (data.sexo === 'femenino') result.genderData.datasets[0].data[1]++;

    // Procesar edad
    const age = parseInt(data.edad_d1 + (data.edad_d2 || '0'));
    if (!isNaN(age)) {
      if (age <= 25) result.ageRanges.datasets[0].data[0]++;
      else if (age <= 35) result.ageRanges.datasets[0].data[1]++;
      else if (age <= 45) result.ageRanges.datasets[0].data[2]++;
      else if (age <= 55) result.ageRanges.datasets[0].data[3]++;
      else result.ageRanges.datasets[0].data[4]++;
    }

    // Procesar nivel educativo
    if (data.ultimo_nivel_estudio) {
      educationCount.set(
        data.ultimo_nivel_estudio,
        (educationCount.get(data.ultimo_nivel_estudio) || 0) + 1
      );
    }

    // Procesar departamento
    if (data.departamento_seccion_area) {
      departmentCount.set(
        data.departamento_seccion_area,
        (departmentCount.get(data.departamento_seccion_area) || 0) + 1
      );
    }

    // Procesar tipo de puesto
    if (data.tipo_puesto) {
      jobTypeCount.set(
        data.tipo_puesto,
        (jobTypeCount.get(data.tipo_puesto) || 0) + 1
      );
    }

    // Procesar tipo de contratación
    if (data.tipo_contratacion) {
      contractTypeCount.set(
        data.tipo_contratacion,
        (contractTypeCount.get(data.tipo_contratacion) || 0) + 1
      );
    }
  });

  // Convertir Maps a arrays para los gráficos
  result.educationLevel.labels = Array.from(educationCount.keys());
  result.educationLevel.datasets[0].data = Array.from(educationCount.values());

  result.departmentDistribution.labels = Array.from(departmentCount.keys());
  result.departmentDistribution.datasets[0].data = Array.from(departmentCount.values());

  // Convertir Maps a arrays para los nuevos gráficos
  result.jobTypeDistribution.labels = Array.from(jobTypeCount.keys()).map(key => {
    const labels = {
      'operativo': 'Operativo',
      'prof_tecnoci': 'Profesional/Técnico',
      'supervisor': 'Supervisor',
      'gerente': 'Gerente'
    };
    return labels[key] || key;
  });
  result.jobTypeDistribution.datasets[0].data = Array.from(jobTypeCount.values());

  result.contractTypeDistribution.labels = Array.from(contractTypeCount.keys()).map(key => {
    const labels = {
      'indeterminado': 'Indeterminado',
      'determinado': 'Determinado',
      'honorarios': 'Honorarios',
      'subcontratacion': 'Subcontratación'
    };
    return labels[key] || key;
  });
  result.contractTypeDistribution.datasets[0].data = Array.from(contractTypeCount.values());

  return result;
});

// Opciones de los gráficos
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top'
    }
  }
};

// Observar cambios en los datos
watch(() => props.demographicData, () => {
  updateCharts();
}, { deep: true });

// Actualizar gráficos cuando los datos cambien
const updateCharts = () => {
  if (!processedData.value) return;

  if (ageDistributionChart.value) {
    ageDistributionChart.value.update();
  }
  if (genderDistributionChart.value) {
    genderDistributionChart.value.update();
  }
  if (educationLevelChart.value) {
    educationLevelChart.value.update();
  }
  if (departmentDistributionChart.value) {
    departmentDistributionChart.value.update();
  }
  if (jobTypeChart.value) {
    jobTypeChart.value.update();
  }
  if (contractTypeChart.value) {
    contractTypeChart.value.update();
  }
};
</script>

<template>
  <div class="grid grid-cols-1 gap-6 py-4">
    <!-- Distribución por Género -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución por Género</h3>
      <div class="h-96">
        <Pie
          v-if="processedData"
          :data="processedData.genderData"
          :options="chartOptions"
          ref="genderDistributionChart"
        />
      </div>
    </div>

    <!-- Distribución por Edad -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución por Edad</h3>
      <div class="h-96">
        <Bar
          v-if="processedData"
          :data="processedData.ageRanges"
          :options="chartOptions"
          ref="ageDistributionChart"
        />
      </div>
    </div>

    <!-- Nivel Educativo -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Nivel Educativo</h3>
      <div class="h-96">
        <Bar
          v-if="processedData"
          :data="processedData.educationLevel"
          :options="chartOptions"
          ref="educationLevelChart"
        />
      </div>
    </div>

    <!-- Distribución por Departamento -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución por Departamento</h3>
      <div class="h-96">
        <Bar
          v-if="processedData"
          :data="processedData.departmentDistribution"
          :options="chartOptions"
          ref="departmentDistributionChart"
        />
      </div>
    </div>

    <!-- Tipo de Puesto -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Tipo de Puesto</h3>
      <div class="h-96">
        <Bar
          v-if="processedData"
          :data="processedData.jobTypeDistribution"
          :options="chartOptions"
          ref="jobTypeChart"
        />
      </div>
    </div>

    <!-- Tipo de Contratación -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Tipo de Contratación</h3>
      <div class="h-96">
        <Bar
          v-if="processedData"
          :data="processedData.contractTypeDistribution"
          :options="chartOptions"
          ref="contractTypeChart"
        />
      </div>
    </div>
  </div>
</template>
