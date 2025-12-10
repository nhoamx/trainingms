<template>
  <div class="space-y-6">
    <!-- Filtros Demográficos -->
    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros Demográficos</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Genero -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
          <select
            v-model="filters.gender"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="gender in demographicDetails.genders" :key="gender" :value="gender">
              {{ gender }}
            </option>
          </select>
        </div>

        <!-- Tipo de Contrato -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato</label>
          <select
            v-model="filters.contract_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="type in demographicDetails.contract_types" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Puesto -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Puesto</label>
          <select
            v-model="filters.position"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="position in demographicDetails.positions" :key="position" :value="position">
              {{ position }}
            </option>
          </select>
        </div>

        <!-- Área -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
          <select
            v-model="filters.area"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="area in demographicDetails.areas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Turno -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
          <select
            v-model="filters.shift"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Todos</option>
            <option v-for="shift in demographicDetails.shifts" :key="shift" :value="shift">
              {{ shift }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla de Factores de Riesgo -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Top 3 Factores de Riesgo</h3>
        <p class="text-sm text-gray-600 mt-1">Basado en el total de respuestas de desacuerdo</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Factor de Riesgo</th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full">
                  Totalmente de Acuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                  De Acuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                  Desacuerdo
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 rounded-full">
                  Totalmente Desacuerdo
                </span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(factor, index) in topThreeFactors"
              :key="factor.name"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-semibold text-sm"
                    :class="getSeverityBadgeClass(index)"
                  >
                    {{ index + 1 }}
                  </span>
                  <span class="font-medium text-gray-900">{{ factor.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-green-50 rounded-lg font-semibold text-green-700">
                  {{ factor.counts['Totalmente de Acuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-50 rounded-lg font-semibold text-blue-700">
                  {{ factor.counts['De Acuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-yellow-50 rounded-lg font-semibold text-yellow-700">
                  {{ factor.counts['Desacuerdo'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-red-50 rounded-lg font-semibold text-red-700">
                  {{ factor.counts['Totalmente Desacuerdo'] || 0 }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="topThreeFactors.length === 0" class="p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-lg font-semibold text-gray-900 mb-2">No hay datos disponibles</p>
        <p class="text-gray-600">Intenta cambiar los filtros para ver los factores de riesgo</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface DemographicData {
  gender?: string;
  tipo_contrato?: string;
  puesto?: string;
  area?: string;
  turno?: string;
}

interface DimensionScore {
  name: string;
  score: number;
  interpretation: string;
}

interface Evaluation {
  id: string;
  demographic_data?: DemographicData;
  dimensions?: DimensionScore[];
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface RiskFactor {
  name: string;
  disagreementSum: number;
  counts: Record<string, number>;
}

interface Props {
  evaluations: Evaluation[];
  demographicDetails: DemographicDetails;
}

const props = defineProps<Props>();

// Filters
const filters = ref({
  gender: '',
  contract_type: '',
  position: '',
  area: '',
  shift: '',
});

// Filtered evaluations
const filteredEvaluations = computed(() => {
  return props.evaluations.filter((evaluation) => {
    const demo = evaluation.demographic_data || {};

    if (filters.value.gender && demo.gender !== filters.value.gender) {
      return false;
    }
    if (filters.value.contract_type && demo.tipo_contrato !== filters.value.contract_type) {
      return false;
    }
    if (filters.value.position && demo.puesto !== filters.value.position) {
      return false;
    }
    if (filters.value.area && demo.area !== filters.value.area) {
      return false;
    }
    if (filters.value.shift && demo.turno !== filters.value.shift) {
      return false;
    }

    return true;
  });
});

// Calculate top 3 risk factors
const topThreeFactors = computed(() => {
  const factorMap: Record<string, Record<string, number>> = {};

  // Aggregate dimension scores
  filteredEvaluations.value.forEach((evaluation) => {
    if (!evaluation.dimensions || !Array.isArray(evaluation.dimensions)) {
      return;
    }

    evaluation.dimensions.forEach((dimension) => {
      if (!factorMap[dimension.name]) {
        factorMap[dimension.name] = {
          'Totalmente de Acuerdo': 0,
          'De Acuerdo': 0,
          'Desacuerdo': 0,
          'Totalmente Desacuerdo': 0,
        };
      }

      // Map interpretation to agreement level
      const interpretation = dimension.interpretation || '';
      if (factorMap[dimension.name][interpretation] !== undefined) {
        factorMap[dimension.name][interpretation]++;
      }
    });
  });

  // Convert to array and calculate disagreement sum
  const factors: RiskFactor[] = Object.entries(factorMap).map(([name, counts]) => ({
    name,
    counts: counts as Record<string, number>,
    disagreementSum: (counts['Desacuerdo'] || 0) + (counts['Totalmente Desacuerdo'] || 0),
  }));

  // Sort by disagreement sum (descending) and return top 3
  return factors
    .sort((a, b) => b.disagreementSum - a.disagreementSum)
    .slice(0, 3)
    .map((factor) => ({
      name: factor.name,
      counts: factor.counts,
      disagreementSum: factor.disagreementSum,
    }));
});

// Get severity badge color based on rank
const getSeverityBadgeClass = (index: number): string => {
  const severities = [
    'bg-red-600',      // 1st place - worst
    'bg-orange-600',   // 2nd place
    'bg-yellow-600',   // 3rd place
  ];
  return severities[index] || 'bg-gray-600';
};
</script>
