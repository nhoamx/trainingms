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
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  ChartDataLabels
);

const props = defineProps({
  questionReports: {
    type: Object,
    required: true
  }
});

// Referencias para los gráficos
const estadoCivilChart = ref(null);
const estadoCivilTableChart = ref(null);
const edadDistributionChart = ref(null);
const nivelAcademicoChart = ref(null);
const nivelAcademicoTableChart = ref(null);
const answersDistributionChart = ref(null);
const categoryScoresChart = ref(null);

// Datos procesados para gráfico de estado civil
const estadoCivilData = computed(() => {
  if (!props.questionReports?.estadoCivil?.length) return null;

  const labels = props.questionReports.estadoCivil.map(item => item.estado_civil);
  const counts = props.questionReports.estadoCivil.map(item => item.total);

  return {
    labels: labels,
    datasets: [{
      label: 'Distribución por Estado Civil',
      data: counts,
      backgroundColor: [
        '#4F46E5', // Indigo
        '#10B981', // Emerald
        '#EF4444', // Red
        '#F59E0B', // Amber
        '#8B5CF6', // Purple
        '#6B7280'  // Gray
      ]
    }]
  };
});

// Datos para la tabla de estado civil
const estadoCivilTableData = computed(() => {
  if (!props.questionReports?.estadoCivil?.length) return null;

  const tableData = {
    labels: ['Estado Civil', 'Total', 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto', 'Nu+Ba', 'Me+Al+Ma', 'CF*'],
    datasets: props.questionReports.estadoCivil.map(item => {
      return {
        estado_civil: item.estado_civil,
        total: item.total,
        nulo: item.niveles.Nulo,
        bajo: item.niveles.Bajo,
        medio: item.niveles.Medio,
        alto: item.niveles.Alto,
        muy_alto: item.niveles['Muy Alto'],
        nu_ba: item.nu_ba,
        me_al_ma: item.me_al_ma,
        cf: item.cf
      };
    })
  };

  return tableData;
});

// Datos para distribución por edad
const edadDistributionData = computed(() => {
  if (!props.questionReports?.edadDistribution) return null;

  const ageRanges = props.questionReports.edadDistribution;
  const labels = Object.keys(ageRanges);
  const counts = labels.map(range => ageRanges[range].count || 0);

  // Datos para la gráfica de barras
  return {
    labels: labels,
    datasets: [{
      label: 'Distribución por Edad',
      data: counts,
      backgroundColor: '#60A5FA' // Azul
    }]
  };
});

// Datos para nivel académico
const nivelAcademicoData = computed(() => {
  if (!props.questionReports?.nivelAcademico?.length) return null;

  const labels = props.questionReports.nivelAcademico.map(item => item.nivel);
  const counts = props.questionReports.nivelAcademico.map(item => item.total);

  return {
    labels: labels,
    datasets: [{
      label: 'Nivel Académico',
      data: counts,
      backgroundColor: [
        '#3B82F6', // Blue
        '#EC4899', // Pink
        '#8B5CF6', // Purple
        '#F59E0B', // Amber
        '#10B981', // Emerald
        '#6366F1', // Indigo
        '#EF4444', // Red
        '#14B8A6', // Teal
        '#F97316', // Orange
        '#8B5CF6', // Purple
        '#22C55E', // Green
        '#A855F7', // Purple
        '#F43F5E', // Rose
        '#0EA5E9'  // Sky
      ]
    }]
  };
});

// Datos para tabla de nivel académico
const nivelAcademicoTableData = computed(() => {
  if (!props.questionReports?.nivelAcademico?.length) return null;

  const tableData = {
    labels: ['Nivel Académico', 'Total', 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto', 'Nu+Ba', 'Me+Al+Ma', 'CF*'],
    datasets: props.questionReports.nivelAcademico.map(item => {
      return {
        nivel: item.nivel,
        total: item.total,
        nulo: item.niveles.Nulo,
        bajo: item.niveles.Bajo,
        medio: item.niveles.Medio,
        alto: item.niveles.Alto,
        muy_alto: item.niveles['Muy Alto'],
        nu_ba: item.nu_ba,
        me_al_ma: item.me_al_ma,
        cf: item.cf
      };
    })
  };

  return tableData;
});

// Datos para distribución de respuestas
const answersDistributionData = computed(() => {
  if (!props.questionReports?.answersDistribution) return null;

  const distribution = props.questionReports.answersDistribution;
  const labels = Object.keys(distribution);
  const counts = labels.map(key => distribution[key]);

  return {
    labels: labels,
    datasets: [{
      label: 'Distribución de Respuestas',
      data: counts,
      backgroundColor: [
        '#3B82F6', // A - Blue
        '#22C55E', // B - Green
        '#F59E0B', // C - Amber
        '#EF4444', // D - Red
        '#8B5CF6'  // E - Purple
      ]
    }]
  };
});

// Datos para puntajes por categoría
const categoryScoresData = computed(() => {
  if (!props.questionReports?.categoryScores?.length) return null;

  const labels = props.questionReports.categoryScores.map(item => item.name);
  const scores = props.questionReports.categoryScores.map(item => item.avg_score);

  return {
    labels: labels,
    datasets: [{
      label: 'Puntajes por Categoría',
      data: scores,
      backgroundColor: [
        '#3B82F6', // Blue
        '#10B981', // Emerald
        '#F59E0B', // Amber
        '#EF4444', // Red
        '#8B5CF6'  // Purple
      ]
    }]
  };
});

// Definir rangos de categorías según la Guía de Referencia III
const categoryRanges = {
  'Ambiente de trabajo': {
    nulo: { max: 5 },
    bajo: { min: 5, max: 9 },
    medio: { min: 9, max: 11 },
    alto: { min: 11, max: 14 },
    muy_alto: { min: 14 }
  },
  'Factores propios de la actividad': {
    nulo: { max: 15 },
    bajo: { min: 15, max: 30 },
    medio: { min: 30, max: 45 },
    alto: { min: 45, max: 60 },
    muy_alto: { min: 60 }
  },
  'Organización del tiempo de trabajo': {
    nulo: { max: 5 },
    bajo: { min: 5, max: 7 },
    medio: { min: 7, max: 10 },
    alto: { min: 10, max: 13 },
    muy_alto: { min: 13 }
  },
  'Liderazgo y relaciones en el trabajo': {
    nulo: { max: 14 },
    bajo: { min: 14, max: 29 },
    medio: { min: 29, max: 42 },
    alto: { min: 42, max: 58 },
    muy_alto: { min: 58 }
  },
  'Entorno organizacional': {
    nulo: { max: 10 },
    bajo: { min: 10, max: 14 },
    medio: { min: 14, max: 18 },
    alto: { min: 18, max: 23 },
    muy_alto: { min: 23 }
  }
};

// Función para obtener el nivel de riesgo de una categoría
const getCategoryRiskLevel = (categoryName, score) => {
  const ranges = categoryRanges[categoryName];
  if (!ranges) return '';

  if (score < ranges.nulo.max) return 'Nulo';
  if (score < ranges.bajo.max) return 'Bajo';
  if (score < ranges.medio.max) return 'Medio';
  if (score < ranges.alto.max) return 'Alto';
  return 'Muy Alto';
};

// Función para obtener los rangos de texto de una categoría
const getCategoryRangeText = (categoryName, level) => {
  const ranges = categoryRanges[categoryName];
  if (!ranges) return '';

  const range = ranges[level];
  if (!range) return '';

  if (level === 'nulo') {
    return `C_cat < ${range.max}`;
  } else if (level === 'muy_alto') {
    return `C_cat ≥ ${range.min}`;
  } else {
    return `${range.min} ≤ C_cat < ${range.max}`;
  }
};

// Función para obtener el color CSS basado en el nivel
const getCategoryColorClass = (categoryName, score, level) => {
  const ranges = categoryRanges[categoryName];
  if (!ranges) return '';

  const range = ranges[level];
  if (!range) return '';

  let isInRange = false;
  if (level === 'nulo') {
    isInRange = score < range.max;
  } else if (level === 'muy_alto') {
    isInRange = score >= range.min;
  } else {
    isInRange = score >= range.min && score < range.max;
  }

  if (isInRange) {
    switch (level) {
      case 'nulo': return 'bg-green-200';
      case 'bajo': return 'bg-green-300';
      case 'medio': return 'bg-yellow-200';
      case 'alto': return 'bg-orange-200';
      case 'muy_alto': return 'bg-red-200';
    }
  }

  return '';
};

// Opciones de los gráficos
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top'
    },
    datalabels: {
      color: '#FFFFFF',
      font: {
        weight: 'bold'
      },
      formatter: (value, context) => {
        return value;
      }
    }
  }
};

// Opciones para gráficos de barras
const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true
    }
  },
  plugins: {
    legend: {
      position: 'top'
    },
    datalabels: {
      color: '#FFFFFF',
      anchor: 'center',
      align: 'center',
      font: {
        weight: 'bold'
      }
    }
  }
};

// Observar cambios en los datos
watch(() => props.questionReports, () => {
  updateCharts();
}, { deep: true });

// Actualizar gráficos cuando los datos cambien
const updateCharts = () => {
  if (estadoCivilChart.value) {
    estadoCivilChart.value.update();
  }
  if (edadDistributionChart.value) {
    edadDistributionChart.value.update();
  }
  if (nivelAcademicoChart.value) {
    nivelAcademicoChart.value.update();
  }
  if (answersDistributionChart.value) {
    answersDistributionChart.value.update();
  }
  if (categoryScoresChart.value) {
    categoryScoresChart.value.update();
  }
};

// Variables para controlar pestañas en el reporte detallado
const selectedDemographicType = ref('estadoCivil');
const selectedDemographicValue = ref(null);

// Computar valores disponibles para el tipo demográfico seleccionado
const demographicValues = computed(() => {
  if (!props.questionReports?.questionDetail) return [];

  const detailData = props.questionReports.questionDetail[selectedDemographicType.value];
  if (!detailData) return [];

  return Object.entries(detailData).map(([key, value]) => ({
    key: key,
    label: value.label,
    total: value.total
  }));
});

// Mantener actualizada la selección cuando cambia el tipo demográfico
watch(selectedDemographicType, () => {
  if (demographicValues.value.length > 0) {
    selectedDemographicValue.value = demographicValues.value[0].key;
  } else {
    selectedDemographicValue.value = null;
  }
});

// Inicializar el valor seleccionado cuando los datos estén disponibles
watch(() => props.questionReports?.questionDetail, () => {
  if (demographicValues.value.length > 0 && !selectedDemographicValue.value) {
    selectedDemographicValue.value = demographicValues.value[0].key;
  }
}, { immediate: true });

// Obtener el detalle de preguntas para el criterio demográfico seleccionado
const selectedDemographicDetail = computed(() => {
  if (!props.questionReports?.questionDetail || !selectedDemographicType.value || !selectedDemographicValue.value) {
    return null;
  }

  const detailData = props.questionReports.questionDetail[selectedDemographicType.value];
  if (!detailData || !detailData[selectedDemographicValue.value]) return null;

  return detailData[selectedDemographicValue.value];
});

// Obtener todas las preguntas disponibles para la visualización detallada
const availableQuestions = computed(() => {
  if (!selectedDemographicDetail.value?.questions) return [];

  return Object.keys(selectedDemographicDetail.value.questions)
    .sort((a, b) => parseInt(a) - parseInt(b)); // Ordenar numéricamente
});

// Datos para gráfico de distribución de niveles de Estado Civil
const estadoCivilDistributionData = computed(() => {
  if (!props.questionReports?.estadoCivil?.length) return null;

  const labels = props.questionReports.estadoCivil.map(item => item.estado_civil);

  return {
    labels: labels,
    datasets: [
      {
        label: 'Nulo',
        data: props.questionReports.estadoCivil.map(item => item.niveles.Nulo),
        backgroundColor: '#10B981', // Verde
        stack: 'Stack 0'
      },
      {
        label: 'Bajo',
        data: props.questionReports.estadoCivil.map(item => item.niveles.Bajo),
        backgroundColor: '#34D399', // Verde claro
        stack: 'Stack 0'
      },
      {
        label: 'Medio',
        data: props.questionReports.estadoCivil.map(item => item.niveles.Medio),
        backgroundColor: '#F59E0B', // Amarillo
        stack: 'Stack 0'
      },
      {
        label: 'Alto',
        data: props.questionReports.estadoCivil.map(item => item.niveles.Alto),
        backgroundColor: '#F97316', // Naranja
        stack: 'Stack 0'
      },
      {
        label: 'Muy Alto',
        data: props.questionReports.estadoCivil.map(item => item.niveles['Muy Alto']),
        backgroundColor: '#EF4444', // Rojo
        stack: 'Stack 0'
      }
    ]
  };
});

// Datos para gráfico de distribución de niveles de Edad
const edadLevelDistributionData = computed(() => {
  if (!props.questionReports?.edadDistribution) return null;

  const ageRanges = props.questionReports.edadDistribution;
  const labels = Object.keys(ageRanges);

  // Filtrar rangos con datos
  const validLabels = labels.filter(range => ageRanges[range].count > 0);

  return {
    labels: validLabels,
    datasets: [
      {
        label: 'Nulo',
        data: validLabels.map(range => ageRanges[range].analysis?.niveles?.Nulo || 0),
        backgroundColor: '#10B981', // Verde
        stack: 'Stack 0'
      },
      {
        label: 'Bajo',
        data: validLabels.map(range => ageRanges[range].analysis?.niveles?.Bajo || 0),
        backgroundColor: '#34D399', // Verde claro
        stack: 'Stack 0'
      },
      {
        label: 'Medio',
        data: validLabels.map(range => ageRanges[range].analysis?.niveles?.Medio || 0),
        backgroundColor: '#F59E0B', // Amarillo
        stack: 'Stack 0'
      },
      {
        label: 'Alto',
        data: validLabels.map(range => ageRanges[range].analysis?.niveles?.Alto || 0),
        backgroundColor: '#F97316', // Naranja
        stack: 'Stack 0'
      },
      {
        label: 'Muy Alto',
        data: validLabels.map(range => ageRanges[range].analysis?.niveles?.['Muy Alto'] || 0),
        backgroundColor: '#EF4444', // Rojo
        stack: 'Stack 0'
      }
    ]
  };
});

// Datos para gráfico de distribución de niveles de Nivel Académico
const nivelAcademicoDistributionData = computed(() => {
  if (!props.questionReports?.nivelAcademico?.length) return null;

  const labels = props.questionReports.nivelAcademico.map(item => item.nivel);

  return {
    labels: labels,
    datasets: [
      {
        label: 'Nulo',
        data: props.questionReports.nivelAcademico.map(item => item.niveles.Nulo),
        backgroundColor: '#10B981', // Verde
        stack: 'Stack 0'
      },
      {
        label: 'Bajo',
        data: props.questionReports.nivelAcademico.map(item => item.niveles.Bajo),
        backgroundColor: '#34D399', // Verde claro
        stack: 'Stack 0'
      },
      {
        label: 'Medio',
        data: props.questionReports.nivelAcademico.map(item => item.niveles.Medio),
        backgroundColor: '#F59E0B', // Amarillo
        stack: 'Stack 0'
      },
      {
        label: 'Alto',
        data: props.questionReports.nivelAcademico.map(item => item.niveles.Alto),
        backgroundColor: '#F97316', // Naranja
        stack: 'Stack 0'
      },
      {
        label: 'Muy Alto',
        data: props.questionReports.nivelAcademico.map(item => item.niveles['Muy Alto']),
        backgroundColor: '#EF4444', // Rojo
        stack: 'Stack 0'
      }
    ]
  };
});

// Datos para gráfico de CF por grupo demográfico
const cfComparisonData = computed(() => {
  if (!props.questionReports) return null;

  const datasets = [];
  const labels = [];

  // Estado Civil
  if (props.questionReports.estadoCivil?.length) {
    labels.push('Estado Civil');
    datasets.push({
      label: 'Estado Civil',
      data: [props.questionReports.estadoCivil.reduce((sum, item) => sum + item.cf, 0) / props.questionReports.estadoCivil.length],
      backgroundColor: '#4F46E5', // Indigo
    });
  }

  // Edad
  if (props.questionReports.edadDistribution) {
    const validRanges = Object.values(props.questionReports.edadDistribution).filter(range => range.count > 0);

    if (validRanges.length > 0) {
      labels.push('Edad');
      const avgCF = validRanges.reduce((sum, range) => sum + (range.analysis?.cf || 0), 0) / validRanges.length;
      datasets.push({
        label: 'Edad',
        data: [avgCF],
        backgroundColor: '#60A5FA', // Azul
      });
    }
  }

  // Nivel Académico
  if (props.questionReports.nivelAcademico?.length) {
    labels.push('Nivel Académico');
    datasets.push({
      label: 'Nivel Académico',
      data: [props.questionReports.nivelAcademico.reduce((sum, item) => sum + item.cf, 0) / props.questionReports.nivelAcademico.length],
      backgroundColor: '#8B5CF6', // Morado
    });
  }

  return {
    labels: labels,
    datasets: datasets
  };
});

// Opciones para gráficos de barras apiladas
const stackedBarChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    x: {
      stacked: true,
    },
    y: {
      stacked: true,
      beginAtZero: true
    }
  },
  plugins: {
    legend: {
      position: 'top'
    },
    datalabels: {
      color: '#FFFFFF',
      font: {
        weight: 'bold'
      },
      display: function(context) {
        return context.dataset.data[context.dataIndex] > 0;
      }
    }
  }
};
</script>

<template>
  <div class="grid grid-cols-1 gap-6 py-4">
    <!-- Distribución por Estado Civil -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución por Estado Civil</h3>
      <div class="h-96">
        <Pie
          v-if="estadoCivilData"
          :data="estadoCivilData"
          :options="chartOptions"
          ref="estadoCivilChart"
        />
      </div>

      <!-- Análisis por Estado Civil (Distribución Niveles) -->
      <div class="mt-6 h-96">
        <h4 class="text-md font-semibold mb-2">Distribución de Niveles por Estado Civil</h4>
        <Bar
          v-if="estadoCivilDistributionData"
          :data="estadoCivilDistributionData"
          :options="stackedBarChartOptions"
        />
      </div>

      <!-- Tabla de Estado Civil -->
      <div class="mt-6 overflow-x-auto">
        <h4 class="text-md font-semibold mb-2">Análisis por Estado Civil</h4>
        <table class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">Estado Civil</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Total</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nulo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Bajo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Medio</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Muy Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nu+Ba</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Me+Al+MA</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">CF*</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, index) in estadoCivilTableData?.datasets" :key="index">
              <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 border">{{ item.estado_civil }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.total }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.nulo }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.bajo }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.medio }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.alto }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.muy_alto }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ item.nu_ba }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ item.me_al_ma }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold"
                  :class="{
                    'bg-green-700 text-white': item.cf >= 80,
                    'bg-green-500 text-white': item.cf >= 70 && item.cf < 80,
                    'bg-yellow-400': item.cf >= 60 && item.cf < 70,
                    'bg-red-600 text-white': item.cf < 60
                  }">{{ item.cf }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Distribución por Edad -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución por Edad</h3>
      <div class="h-96">
        <Bar
          v-if="edadDistributionData"
          :data="edadDistributionData"
          :options="barChartOptions"
          ref="edadDistributionChart"
        />
      </div>

      <!-- Análisis por Edad (Distribución Niveles) -->
      <div class="mt-6 h-96">
        <h4 class="text-md font-semibold mb-2">Distribución de Niveles por Rango de Edad</h4>
        <Bar
          v-if="edadLevelDistributionData"
          :data="edadLevelDistributionData"
          :options="stackedBarChartOptions"
        />
      </div>

      <!-- Tabla de Edad -->
      <div class="mt-6 overflow-x-auto">
        <h4 class="text-md font-semibold mb-2">Análisis por Rangos de Edad</h4>
        <table class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">Rango de Edad</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Total</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nulo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Bajo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Medio</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Muy Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nu+Ba</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Me+Al+MA</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">CF*</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(data, range) in questionReports?.edadDistribution" :key="range" v-if="data && data.count && data.count > 0">
              <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 border">{{ range }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.count }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.analysis?.niveles?.Nulo || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.analysis?.niveles?.Bajo || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.analysis?.niveles?.Medio || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.analysis?.niveles?.Alto || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ data.analysis?.niveles?.['Muy Alto'] || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ data.analysis?.nu_ba || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ data.analysis?.me_al_ma || 0 }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold"
                  :class="{
                    'bg-green-700 text-white': data.analysis?.cf >= 80,
                    'bg-green-500 text-white': data.analysis?.cf >= 70 && data.analysis?.cf < 80,
                    'bg-yellow-400': data.analysis?.cf >= 60 && data.analysis?.cf < 70,
                    'bg-red-600 text-white': data.analysis?.cf < 60
                  }">{{ data.analysis?.cf || 0 }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Nivel Académico -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Nivel Académico</h3>
      <div class="h-96">
        <Pie
          v-if="nivelAcademicoData"
          :data="nivelAcademicoData"
          :options="chartOptions"
          ref="nivelAcademicoChart"
        />
      </div>

      <!-- Análisis por Nivel Académico (Distribución Niveles) -->
      <div class="mt-6 h-96">
        <h4 class="text-md font-semibold mb-2">Distribución de Niveles por Nivel Académico</h4>
        <Bar
          v-if="nivelAcademicoDistributionData"
          :data="nivelAcademicoDistributionData"
          :options="stackedBarChartOptions"
        />
      </div>

      <!-- Tabla de Nivel Académico -->
      <div class="mt-6 overflow-x-auto">
        <h4 class="text-md font-semibold mb-2">Análisis por Nivel Académico</h4>
        <table class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">Nivel Académico</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Total</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nulo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Bajo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Medio</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Muy Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nu+Ba</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Me+Al+MA</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">CF*</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, index) in nivelAcademicoTableData?.datasets" :key="index">
              <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 border">{{ item.nivel }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.total }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.nulo }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.bajo }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.medio }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.alto }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border">{{ item.muy_alto }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ item.nu_ba }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ item.me_al_ma }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold"
                  :class="{
                    'bg-green-700 text-white': item.cf >= 80,
                    'bg-green-500 text-white': item.cf >= 70 && item.cf < 80,
                    'bg-yellow-400': item.cf >= 60 && item.cf < 70,
                    'bg-red-600 text-white': item.cf < 60
                  }">{{ item.cf }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Distribución de Respuestas -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Distribución de Respuestas</h3>
      <div class="h-96">
        <Pie
          v-if="answersDistributionData"
          :data="answersDistributionData"
          :options="chartOptions"
          ref="answersDistributionChart"
        />
      </div>
      <div class="mt-4 text-sm text-gray-500">
        <p>A - Nunca, B - Casi nunca, C - Algunas veces, D - Casi siempre, E - Siempre</p>
        <p class="mt-2 italic">* CF = Calificación Final</p>
      </div>
    </div>

    <!-- Comparación de CF por Grupo Demográfico -->
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Comparación de Calificación Final por Grupo Demográfico</h3>
      <div class="h-96">
        <Bar
          v-if="cfComparisonData"
          :data="cfComparisonData"
          :options="barChartOptions"
        />
      </div>
      <div class="mt-4 text-sm text-gray-500">
        <p class="italic">* CF Promedio de cada grupo demográfico</p>
      </div>
    </div>

    <!-- Puntajes por Categoría (Guía de Referencia III) -->
    <div v-if="questionReports?.categoryScores?.length" class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Puntajes por Categoría (Guía de Referencia III)</h3>
      <div class="h-96">
        <Bar
          v-if="categoryScoresData"
          :data="categoryScoresData"
          :options="barChartOptions"
          ref="categoryScoresChart"
        />
      </div>

      <!-- Tabla de puntajes por categoría -->
      <div class="mt-6 overflow-x-auto">
        <h4 class="text-md font-semibold mb-2">Análisis por Categoría</h4>
        <table class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">Categoría</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Puntaje</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nivel</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nulo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Bajo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Medio</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Muy Alto</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(category, index) in questionReports.categoryScores" :key="index">
              <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 border">{{ category.name }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold">{{ category.avg_score.toFixed(2) }}</td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border font-bold"
                  :class="{
                    'bg-green-200': category.level === 'Nulo',
                    'bg-green-300': category.level === 'Bajo',
                    'bg-yellow-200': category.level === 'Medio',
                    'bg-orange-200': category.level === 'Alto',
                    'bg-red-200': category.level === 'Muy Alto'
                  }">
                {{ category.level }}
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border text-xs"
                  :class="getCategoryColorClass(category.name, category.avg_score, 'nulo')">
                {{ getCategoryRangeText(category.name, 'nulo') }}
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border text-xs"
                  :class="getCategoryColorClass(category.name, category.avg_score, 'bajo')">
                {{ getCategoryRangeText(category.name, 'bajo') }}
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border text-xs"
                  :class="getCategoryColorClass(category.name, category.avg_score, 'medio')">
                {{ getCategoryRangeText(category.name, 'medio') }}
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border text-xs"
                  :class="getCategoryColorClass(category.name, category.avg_score, 'alto')">
                {{ getCategoryRangeText(category.name, 'alto') }}
              </td>
              <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-900 border text-xs"
                  :class="getCategoryColorClass(category.name, category.avg_score, 'muy_alto')">
                {{ getCategoryRangeText(category.name, 'muy_alto') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Análisis Detallado por Pregunta -->
    <div v-if="questionReports?.questionDetail" class="bg-white p-4 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Análisis Detallado por Pregunta (Guía de Referencia III)</h3>

      <!-- Selector de tipo demográfico -->
      <div class="flex space-x-4 mb-4">
        <div class="inline-flex shadow-sm rounded-md" role="group">
          <button
            @click="selectedDemographicType = 'estadoCivil'"
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium',
              selectedDemographicType === 'estadoCivil'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            ]"
          >
            Estado Civil
          </button>
          <button
            @click="selectedDemographicType = 'edad'"
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium border-l border-gray-200',
              selectedDemographicType === 'edad'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            ]"
          >
            Edad
          </button>
          <button
            @click="selectedDemographicType = 'nivelAcademico'"
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium border-l border-gray-200',
              selectedDemographicType === 'nivelAcademico'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50'
            ]"
          >
            Nivel Académico
          </button>
        </div>
      </div>

      <!-- Selector de valor demográfico -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Seleccione {{ selectedDemographicType === 'estadoCivil' ? 'Estado Civil' : selectedDemographicType === 'edad' ? 'Rango de Edad' : 'Nivel Académico' }}:
        </label>
        <select
          v-model="selectedDemographicValue"
          class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        >
          <option
            v-for="value in demographicValues"
            :key="value.key"
            :value="value.key"
          >
            {{ value.label }} ({{ value.total }} personas)
          </option>
        </select>
      </div>

      <!-- Tabla de Análisis Detallado por Pregunta -->
      <div class="overflow-x-auto">
        <h4 class="text-md font-semibold mb-2">
          Detalle de preguntas para
          <span class="text-blue-600">
            {{ selectedDemographicDetail?.label }}
          </span>
          ({{ selectedDemographicDetail?.total }} personas)
        </h4>

        <table v-if="selectedDemographicDetail" class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border">Pregunta</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Total</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nulo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Bajo</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Medio</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Muy Alto</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Nu+Ba</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">Me+Al+MA</th>
              <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border">CF*</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="question in availableQuestions" :key="question">
              <td class="px-3 py-2 text-sm text-gray-900 border font-medium">{{ question }}</td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].total }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].niveles.Nulo }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].niveles.Bajo }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].niveles.Medio }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].niveles.Alto }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border">
                {{ selectedDemographicDetail.questions[question].niveles['Muy Alto'] }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border font-bold">
                {{ selectedDemographicDetail.questions[question].nu_ba }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border font-bold">
                {{ selectedDemographicDetail.questions[question].me_al_ma }}
              </td>
              <td class="px-3 py-2 text-sm text-center text-gray-900 border font-bold"
                  :class="{
                    'bg-green-700 text-white': selectedDemographicDetail.questions[question].cf >= 80,
                    'bg-green-500 text-white': selectedDemographicDetail.questions[question].cf >= 70 && selectedDemographicDetail.questions[question].cf < 80,
                    'bg-yellow-400': selectedDemographicDetail.questions[question].cf >= 60 && selectedDemographicDetail.questions[question].cf < 70,
                    'bg-red-600 text-white': selectedDemographicDetail.questions[question].cf < 60
                  }">
                {{ selectedDemographicDetail.questions[question].cf }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="text-gray-500 text-center py-4">
          No hay datos disponibles para este criterio demográfico
        </div>
      </div>

      <div class="mt-4 text-sm text-gray-500">
        <p class="italic">* CF = Calificación Final</p>
      </div>
    </div>
  </div>
</template>
