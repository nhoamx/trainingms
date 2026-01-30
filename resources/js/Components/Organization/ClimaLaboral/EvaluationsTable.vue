<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div v-if="evaluations.length > 0" class="bg-slate-50 rounded-lg p-4 border border-slate-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Contract Type Filter -->
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">
            {{ t('Contract Type') }}
          </label>
          <select
            v-model="contractTypeFilter"
            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="type in uniqueContractTypes" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Area Filter -->
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">
            {{ t('Area') }}
          </label>
          <select
            v-model="areaFilter"
            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="area in uniqueAreas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Position Filter -->
        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">
            {{ t('Position') }}
          </label>
          <select
            v-model="positionFilter"
            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="position in uniquePositions" :key="position" :value="position">
              {{ position }}
            </option>
          </select>
        </div>
      </div>

      <!-- Clear Filters Button -->
      <div v-if="hasActiveFilters" class="mt-3 flex justify-end">
        <button
          @click="clearFilters"
          class="text-sm text-blue-600 hover:text-blue-700 font-medium"
        >
          {{ t('Clear Filters') }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="evaluations.length === 0" class="text-center py-12">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-slate-500 font-medium">{{ emptyMessage }}</p>
    </div>

    <!-- No Results After Filter -->
    <div v-else-if="filteredEvaluations.length === 0" class="text-center py-12">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <p class="text-slate-500 font-medium">{{ t('No results found with current filters') }}</p>
    </div>

    <!-- Table -->
    <div v-else class="space-y-4">
      <div class="overflow-x-auto bg-white rounded-lg border border-slate-200">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                {{ t('Folio') }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                <button
                  @click="toggleSortOrder"
                  class="inline-flex items-center gap-1 hover:text-blue-600 transition-colors"
                >
                  {{ t('Score') }}
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    :class="sortOrder === 'desc' ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                </button>
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                {{ t('Contract Type') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                {{ t('Area') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                {{ t('Position') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr 
              v-for="evaluation in paginatedEvaluations" 
              :key="evaluation.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-sm font-semibold text-slate-900">
                    {{ evaluation.personal_folio || evaluation.folio }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                  {{ evaluation.total_score }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-slate-700">
                {{ evaluation.demographicData?.contract_type || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-slate-700">
                {{ evaluation.demographicData?.department || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-slate-700">
                {{ evaluation.demographicData?.position || '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-lg p-4 border border-slate-200">
        <!-- Items per page -->
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-700">{{ t('Show') }}:</label>
          <select
            v-model.number="itemsPerPage"
            class="px-3 py-1 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span class="text-sm text-slate-600">
            {{ t('Showing') }} {{ startItem }} - {{ endItem }} {{ t('of') }} {{ filteredEvaluations.length }}
          </span>
        </div>

        <!-- Page Navigation -->
        <div class="flex items-center gap-2">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1 text-sm font-medium rounded-lg transition-colors"
            :class="currentPage === 1 
              ? 'bg-slate-100 text-slate-400 cursor-not-allowed' 
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            {{ t('Previous') }}
          </button>

          <div class="flex items-center gap-1">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              class="px-3 py-1 text-sm font-medium rounded-lg transition-colors"
              :class="page === currentPage
                ? 'bg-blue-600 text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 text-sm font-medium rounded-lg transition-colors"
            :class="currentPage === totalPages 
              ? 'bg-slate-100 text-slate-400 cursor-not-allowed' 
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            {{ t('Next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface Evaluation {
  id: string;
  folio: string;
  personal_folio: string;
  total_score: number;
  interpretation: string;
  demographicData?: DemographicData;
}

interface Props {
  evaluations: Evaluation[];
  emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  emptyMessage: 'No evaluations found',
});

// Filters
const contractTypeFilter = ref<string>('');
const areaFilter = ref<string>('');
const positionFilter = ref<string>('');

// Pagination
const currentPage = ref<number>(1);
const itemsPerPage = ref<number>(10);

// Sort
const sortOrder = ref<'asc' | 'desc'>('desc');

// Get unique values for filters
const uniqueContractTypes = computed(() => {
  const types = props.evaluations
    .map(e => e.demographicData?.contract_type)
    .filter(Boolean) as string[];
  return [...new Set(types)].sort();
});

const uniqueAreas = computed(() => {
  const areas = props.evaluations
    .map(e => e.demographicData?.department)
    .filter(Boolean) as string[];
  return [...new Set(areas)].sort();
});

const uniquePositions = computed(() => {
  const positions = props.evaluations
    .map(e => e.demographicData?.position)
    .filter(Boolean) as string[];
  return [...new Set(positions)].sort();
});

// Check if filters are active
const hasActiveFilters = computed(() => {
  return !!(contractTypeFilter.value || areaFilter.value || positionFilter.value);
});

// Clear all filters
const clearFilters = () => {
  contractTypeFilter.value = '';
  areaFilter.value = '';
  positionFilter.value = '';
  currentPage.value = 1;
};

// Apply filters
const filteredEvaluations = computed(() => {
  let filtered = [...props.evaluations];

  if (contractTypeFilter.value) {
    filtered = filtered.filter(e => 
      e.demographicData?.contract_type === contractTypeFilter.value
    );
  }

  if (areaFilter.value) {
    filtered = filtered.filter(e => 
      e.demographicData?.department === areaFilter.value
    );
  }

  if (positionFilter.value) {
    filtered = filtered.filter(e => 
      e.demographicData?.position === positionFilter.value
    );
  }

  // Apply sort
  filtered.sort((a, b) => {
    if (sortOrder.value === 'desc') {
      return b.total_score - a.total_score;
    } else {
      return a.total_score - b.total_score;
    }
  });

  return filtered;
});

// Toggle sort order
const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc';
};

// Pagination calculations
const totalPages = computed(() => {
  return Math.ceil(filteredEvaluations.value.length / itemsPerPage.value);
});

const startItem = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const endItem = computed(() => {
  const end = currentPage.value * itemsPerPage.value;
  return Math.min(end, filteredEvaluations.value.length);
});

const paginatedEvaluations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredEvaluations.value.slice(start, end);
});

const visiblePages = computed(() => {
  const pages: number[] = [];
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    // Show all pages if 7 or less
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Always show first page
    pages.push(1);

    if (current > 3) {
      pages.push(-1); // Ellipsis
    }

    // Show pages around current
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    if (current < total - 2) {
      pages.push(-1); // Ellipsis
    }

    // Always show last page
    pages.push(total);
  }

  return pages;
});

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// Reset to page 1 when filters change
const resetPagination = () => {
  currentPage.value = 1;
};

// Watch filters to reset pagination
import { watch } from 'vue';
watch([contractTypeFilter, areaFilter, positionFilter, itemsPerPage], resetPagination);
</script>
