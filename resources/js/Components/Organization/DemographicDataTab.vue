<template>
  <div>
    <!-- Título -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Datos Demográficos</h2>
      <p class="text-sm text-gray-600 mt-1">Total de evaluaciones: {{ demographicDetails.total_evaluations }}</p>
    </div>

    <!-- Grid de Gráficas -->
    <div v-if="demographicDetails.total_evaluations > 0" class="space-y-6">
      <!-- Row 1: Género, Turno, Tipo de Contrato (2-1 layout) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Género -->
        <div class="bg-white rounded-lg p-6 border border-gray-200 lg:col-span-1">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género</h3>
          <canvas ref="genderChartCanvas" style="height: 250px"></canvas>
          <div class="mt-4 space-y-2 text-sm">
            <div v-for="(count, label) in genderCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Turno -->
        <div class="bg-white rounded-lg p-6 border border-gray-200 lg:col-span-1">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Turno</h3>
          <canvas ref="shiftChartCanvas" style="height: 250px"></canvas>
          <div class="mt-4 space-y-2 text-sm">
            <div v-for="(count, label) in shiftCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Tipo de Contrato -->
        <div class="bg-white rounded-lg p-6 border border-gray-200 lg:col-span-1">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Tipo de Contrato</h3>
          <canvas ref="contractTypeChartCanvas" style="height: 250px"></canvas>
          <div class="mt-4 space-y-2 text-sm">
            <div v-for="(count, label) in contractTypeCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Puesto (full width) -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Puesto</h3>
        <canvas ref="positionChartCanvas" style="height: 300px"></canvas>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
          <div v-for="(count, label) in positionCounts" :key="label" class="flex justify-between">
            <span class="text-gray-700">{{ label }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <!-- Row 3: Área (full width) -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Área</h3>
        <canvas ref="areaChartCanvas" style="height: 300px"></canvas>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
          <div v-for="(count, label) in areaCounts" :key="label" class="flex justify-between">
            <span class="text-gray-700">{{ label }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-else class="bg-gray-50 rounded-lg p-12 text-center">
      <div class="text-4xl mb-4">📭</div>
      <p class="text-gray-600 text-lg">No hay datos disponibles</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface Evaluation {
  id: string;
  demographicData?: DemographicData;
}

interface Props {
  demographicDetails: DemographicDetails;
  evaluations: Evaluation[];
}

const props = withDefaults(defineProps<Props>(), {
  demographicDetails: () => ({
    genders: [],
    contract_types: [],
    positions: [],
    areas: [],
    shifts: [],
    total_evaluations: 0,
  }),
  evaluations: () => [],
});

const genderChartCanvas = ref<HTMLCanvasElement>();
const contractTypeChartCanvas = ref<HTMLCanvasElement>();
const positionChartCanvas = ref<HTMLCanvasElement>();
const areaChartCanvas = ref<HTMLCanvasElement>();
const shiftChartCanvas = ref<HTMLCanvasElement>();

const chartInstances = ref<Record<string, Chart>>({});

const getChartColor = (index: number): string => {
  const colors = [
    'rgba(59, 130, 246, 0.8)',    // Blue
    'rgba(34, 197, 94, 0.8)',     // Green
    'rgba(239, 68, 68, 0.8)',     // Red
    'rgba(251, 146, 60, 0.8)',    // Orange
    'rgba(168, 85, 247, 0.8)',    // Purple
    'rgba(14, 165, 233, 0.8)',    // Cyan
    'rgba(236, 72, 153, 0.8)',    // Pink
    'rgba(100, 116, 139, 0.8)',   // Slate
  ];
  return colors[index % colors.length];
};

const getCountByField = (field: 'gender' | 'contract_type' | 'position' | 'department' | 'work_schedule', options: string[]): Record<string, number> => {
  const counts: Record<string, number> = {};
  
  options.forEach(option => {
    counts[option] = 0;
  });

  props.evaluations.forEach((evaluation: Evaluation) => {
    const demo = evaluation.demographicData;
    if (!demo) return;

    let value = '';
    if (field === 'gender') value = demo.gender || '';
    else if (field === 'contract_type') value = demo.contract_type || '';
    else if (field === 'position') value = demo.position || '';
    else if (field === 'department') value = demo.department || '';
    else if (field === 'work_schedule') value = demo.work_schedule || '';

    if (value && counts.hasOwnProperty(value)) {
      counts[value]++;
    }
  });

  return counts;
};

// Computed properties for counts display
const genderCounts = computed(() => getCountByField('gender', props.demographicDetails.genders));
const contractTypeCounts = computed(() => getCountByField('contract_type', props.demographicDetails.contract_types));
const positionCounts = computed(() => getCountByField('position', props.demographicDetails.positions));
const areaCounts = computed(() => getCountByField('department', props.demographicDetails.areas));
const shiftCounts = computed(() => getCountByField('work_schedule', props.demographicDetails.shifts));

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  if (maxValue <= 1000) return 100;
  if (maxValue <= 5000) return 500;
  return Math.ceil(maxValue / 5 / 100) * 100;
};

const createChart = (canvasRef: any, field: 'gender' | 'contract_type' | 'position' | 'department' | 'work_schedule', options: string[], chartKey: string): void => {
  if (!canvasRef.value) return;

  const ctx = canvasRef.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    existingChart.destroy();
  }

  const counts = getCountByField(field, options);
  const labels = options.filter(opt => counts[opt] > 0);
  const data = labels.map(label => counts[label]);
  const backgroundColors = labels.map((_, index) => getChartColor(index));
  
  const maxValue = data.length > 0 ? Math.max(...data) : 0;
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: backgroundColors,
          borderColor: backgroundColors.map(c => c.replace('0.8', '1')),
          borderWidth: 2,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      indexAxis: 'x',
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize,
          },
        },
      },
    },
  });

  chartInstances.value[chartKey] = chart;
};

const renderCharts = (): void => {
  nextTick(() => {
    if (props.demographicDetails.total_evaluations > 0) {
      createChart(genderChartCanvas, 'gender', props.demographicDetails.genders, 'gender');
      createChart(contractTypeChartCanvas, 'contract_type', props.demographicDetails.contract_types, 'contractType');
      createChart(positionChartCanvas, 'position', props.demographicDetails.positions, 'position');
      createChart(areaChartCanvas, 'department', props.demographicDetails.areas, 'area');
      createChart(shiftChartCanvas, 'work_schedule', props.demographicDetails.shifts, 'shift');
    }
  });
};

watch([() => props.evaluations, () => props.demographicDetails], () => {
  renderCharts();
}, { deep: true });

onMounted(() => {
  renderCharts();
});
</script>
