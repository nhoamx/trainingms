<template>
  <div>
    <!-- Título -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Datos Demográficos</h2>
      <p class="text-sm text-gray-600 mt-1">Total de evaluaciones: {{ demographicDetails.total_evaluations }}</p>
    </div>

    <!-- Grid de Gráficas -->
    <div v-if="demographicDetails.total_evaluations > 0" class="space-y-6">
      <!-- Row 1: Género -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Género</h3>
        <canvas ref="genderChartCanvas" style="height: 300px"></canvas>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
          <div v-for="(count, label) in genderCounts" :key="label" class="flex justify-between">
            <span class="text-gray-700">{{ label }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <!-- Row 2: Tipo de Contrato - Género + Tipo de Contrato -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tipo de Contrato -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Tipo de Contrato</h3>
          <canvas ref="contractTypeChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in contractTypeCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Género + Tipo de Contrato -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género + Tipo de Contrato</h3>
          <canvas ref="genderContractChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in genderContractCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: Turno - Género + Turno -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Turno -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Turno</h3>
          <canvas ref="shiftChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in shiftCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Género + Turno -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género + Turno</h3>
          <canvas ref="genderShiftChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in genderShiftCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 4: Puesto -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Puesto</h3>
        <canvas ref="positionChartCanvas" style="height: 300px"></canvas>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div v-for="(count, label) in positionCounts" :key="label" class="flex justify-between">
            <span class="text-gray-700">{{ label }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <!-- Row 5: Género (Masculino) + Puesto - Género (Femenino) + Puesto -->
      <div class="grid grid-cols-1 gap-6">
        <!-- Género (Masculino) + Puesto -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género (Masculino) + Puesto</h3>
          <canvas ref="malePositionChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in malePositionCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Género (Femenino) + Puesto -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género (Femenino) + Puesto</h3>
          <canvas ref="femalePositionChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in femalePositionCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 6: Área -->
      <div class="bg-white rounded-lg p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Área</h3>
        <canvas ref="areaChartCanvas" style="height: 300px"></canvas>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
          <div v-for="(count, label) in areaCounts" :key="label" class="flex justify-between">
            <span class="text-gray-700">{{ label }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
      </div>

      <!-- Row 7: Género (Masculino) + Área - Género (Femenino) + Área -->
      <div class="grid grid-cols-1 gap-6">
        <!-- Género (Masculino) + Área -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género (Masculino) + Área</h3>
          <canvas ref="maleAreaChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in maleAreaCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
          </div>
        </div>

        <!-- Género (Femenino) + Área -->
        <div class="bg-white rounded-lg p-6 border border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-6">Género (Femenino) + Área</h3>
          <canvas ref="femaleAreaChartCanvas" style="height: 300px"></canvas>
          <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-2 gap-4 text-sm">
            <div v-for="(count, label) in femaleAreaCounts" :key="label" class="flex justify-between">
              <span class="text-gray-700">{{ label }}</span>
              <span class="font-semibold text-gray-900">{{ count }}</span>
            </div>
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
const genderContractChartCanvas = ref<HTMLCanvasElement>();
const genderShiftChartCanvas = ref<HTMLCanvasElement>();
const malePositionChartCanvas = ref<HTMLCanvasElement>();
const femalePositionChartCanvas = ref<HTMLCanvasElement>();
const maleAreaChartCanvas = ref<HTMLCanvasElement>();
const femaleAreaChartCanvas = ref<HTMLCanvasElement>();

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

const getCountByCombination = (field1: keyof DemographicData, value1: string, field2: keyof DemographicData, options2: string[]): Record<string, number> => {
  const counts: Record<string, number> = {};
  
  options2.forEach(option => {
    counts[option] = 0;
  });

  props.evaluations.forEach((evaluation: Evaluation) => {
    const demo = evaluation.demographicData;
    if (!demo) return;

    const demo1 = demo[field1] || '';
    const demo2 = demo[field2] || '';

    if (demo1 === value1 && demo2 && counts.hasOwnProperty(demo2)) {
      counts[demo2]++;
    }
  });

  return counts;
};

const getCombinationCounts = (field1: 'gender' | 'contract_type' | 'position' | 'department' | 'work_schedule', options1: string[], field2: 'gender' | 'contract_type' | 'position' | 'department' | 'work_schedule', options2: string[]): Record<string, number> => {
  const counts: Record<string, number> = {};
  
  props.evaluations.forEach((evaluation: Evaluation) => {
    const demo = evaluation.demographicData;
    if (!demo) return;

    let value1 = '';
    let value2 = '';

    if (field1 === 'gender') value1 = demo.gender || '';
    else if (field1 === 'contract_type') value1 = demo.contract_type || '';
    else if (field1 === 'position') value1 = demo.position || '';
    else if (field1 === 'department') value1 = demo.department || '';
    else if (field1 === 'work_schedule') value1 = demo.work_schedule || '';

    if (field2 === 'gender') value2 = demo.gender || '';
    else if (field2 === 'contract_type') value2 = demo.contract_type || '';
    else if (field2 === 'position') value2 = demo.position || '';
    else if (field2 === 'department') value2 = demo.department || '';
    else if (field2 === 'work_schedule') value2 = demo.work_schedule || '';

    if (value1 && value2) {
      const key = `${value1} - ${value2}`;
      counts[key] = (counts[key] || 0) + 1;
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

// Combination counts
const genderContractCounts = computed(() => getCombinationCounts('gender', props.demographicDetails.genders, 'contract_type', props.demographicDetails.contract_types));
const genderShiftCounts = computed(() => getCombinationCounts('gender', props.demographicDetails.genders, 'work_schedule', props.demographicDetails.shifts));
const malePositionCounts = computed(() => getCountByCombination('gender', 'Masculino', 'position', props.demographicDetails.positions));
const femalePositionCounts = computed(() => getCountByCombination('gender', 'Femenino', 'position', props.demographicDetails.positions));
const maleAreaCounts = computed(() => getCountByCombination('gender', 'Masculino', 'department', props.demographicDetails.areas));
const femaleAreaCounts = computed(() => getCountByCombination('gender', 'Femenino', 'department', props.demographicDetails.areas));

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

const createCombinationChart = (canvasRef: any, counts: Record<string, number>, chartKey: string): void => {
  if (!canvasRef.value) return;

  const ctx = canvasRef.value.getContext('2d');
  if (!ctx) return;

  const existingChart = chartInstances.value[chartKey];
  if (existingChart) {
    existingChart.destroy();
  }

  const labels = Object.keys(counts).filter(label => counts[label] > 0);
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
      
      createCombinationChart(genderContractChartCanvas, genderContractCounts.value, 'genderContract');
      createCombinationChart(genderShiftChartCanvas, genderShiftCounts.value, 'genderShift');
      createCombinationChart(malePositionChartCanvas, malePositionCounts.value, 'malePosition');
      createCombinationChart(femalePositionChartCanvas, femalePositionCounts.value, 'femalePosition');
      createCombinationChart(maleAreaChartCanvas, maleAreaCounts.value, 'maleArea');
      createCombinationChart(femaleAreaChartCanvas, femaleAreaCounts.value, 'femaleArea');
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
