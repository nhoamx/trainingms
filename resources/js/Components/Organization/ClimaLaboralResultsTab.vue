<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <ChartBarIcon class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <h2 class="text-3xl font-bold text-slate-900">{{ t('Work Climate Results') }}</h2>
            <p class="text-slate-600 mt-2">{{ t('Analysis by satisfaction level') }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="exportToExcel"
            :disabled="isExporting || evaluations.length === 0"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <svg v-if="!isExporting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isExporting ? t('Exporting...') : t('Export to Excel') }}</span>
          </button>

          <template v-if="workCenterId">
            <input
              ref="importFileInput"
              type="file"
              accept=".xlsx,.xls"
              class="hidden"
              @change="onImportFileChange"
            />
            <button
              @click="importFileInput?.click()"
              :disabled="isImporting"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              :title="t('Import and update records from Excel')"
            >
              <svg v-if="!isImporting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isImporting ? t('Uploading file...') : t('Import records from Excel') }}</span>
            </button>
          </template>
        </div>
      </div>
    </div>

    <div
      v-if="importStatus"
      :class="[
        'rounded-xl border p-4 sm:p-5',
        importStatus.status === 'failed'
          ? 'border-red-200 bg-red-50'
          : importStatus.status === 'completed'
            ? 'border-emerald-200 bg-emerald-50'
            : 'border-blue-200 bg-blue-50',
      ]"
    >
      <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-sm font-semibold text-slate-900">
            {{
              importStatus.status === 'failed'
                ? t('Import failed')
                : importStatus.status === 'completed'
                  ? t('Import completed')
                  : t('Processing file')
            }}
          </p>
          <p class="mt-1 text-xs text-slate-600">{{ importStatus.file_name }}</p>
        </div>
        <span class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-medium text-slate-700">
          {{ t('Status') }}: {{ statusLabel(importStatus.status) }}
        </span>
      </div>

      <div class="mb-2 flex items-center justify-between text-xs text-slate-700">
        <span>{{ t('Progress') }}</span>
        <span>{{ importStatus.progress_percentage }}%</span>
      </div>
      <div class="h-2 overflow-hidden rounded-full bg-white/80">
        <div
          class="h-full rounded-full bg-blue-600 transition-all duration-300"
          :style="{ width: `${importStatus.progress_percentage}%` }"
        ></div>
      </div>

      <div class="mt-3 text-xs text-slate-700">
        {{ t('Rows processed') }}: {{ importStatus.processed_rows }} / {{ importStatus.total_rows }}
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2">
          <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ t('Updated') }}</p>
          <p class="text-lg font-semibold text-emerald-700">{{ importStatus.updated_count }}</p>
        </div>
        <div class="rounded-lg border border-amber-200 bg-white px-3 py-2">
          <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ t('Skipped') }}</p>
          <p class="text-lg font-semibold text-amber-700">{{ importStatus.skipped_count }}</p>
        </div>
        <div class="rounded-lg border border-rose-200 bg-white px-3 py-2">
          <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ t('Errors') }}</p>
          <p class="text-lg font-semibold text-rose-700">{{ importStatus.errors.length }}</p>
        </div>
      </div>

      <div v-if="importStatus.status === 'failed' && importStatus.error_message" class="mt-4 rounded-lg border border-red-200 bg-white p-3 text-sm text-red-700">
        {{ importStatus.error_message }}
      </div>

      <div v-if="importStatus.errors.length > 0" class="mt-4 rounded-lg border border-slate-200 bg-white p-3">
        <p class="mb-2 text-xs font-semibold text-slate-700">{{ t('Import errors') }}</p>
        <ul class="max-h-40 list-disc space-y-1 overflow-y-auto pl-5 text-xs text-slate-700">
          <li v-for="(error, index) in importStatus.errors.slice(0, 20)" :key="`${index}-${error}`">
            {{ error }}
          </li>
        </ul>
      </div>
    </div>

    <!-- Import feedback notification -->
    <div
      v-if="importFeedback"
      :class="[
        'flex items-start gap-3 rounded-lg border p-4 text-sm',
        importFeedback.success
          ? 'border-green-200 bg-green-50 text-green-800'
          : 'border-red-200 bg-red-50 text-red-800',
      ]"
    >
      <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="importFeedback.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
      </svg>
      <div class="flex-1">{{ importFeedback.message }}</div>
      <button @click="importFeedback = null" class="flex-shrink-0 opacity-60 hover:opacity-100">✕</button>
    </div>

    <!-- Selector de Vistas -->
    <div class="bg-white rounded-lg p-4 border border-slate-200">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-4">
          <label class="text-sm font-medium text-slate-700">
            {{ t('View Mode') }}:
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="level in satisfactionLevels"
              :key="level.key"
              @click="activeSatisfactionLevel = level.key"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                activeSatisfactionLevel === level.key
                  ? 'bg-blue-600 text-white shadow-md'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
              ]"
            >
              <span>{{ t(level.label) }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenido por nivel de satisfacción -->
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <!-- Vista Global -->
      <div v-if="activeSatisfactionLevel === 'global'" class="space-y-6">
        <div class="bg-white rounded-lg p-6">
          <div class="flex items-center gap-3 mb-6">
            <h3 class="text-2xl font-bold text-blue-900">{{ t('Global View') }}</h3>
          </div>
          
          <!-- Distribution Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div
              v-for="level in satisfactionLevels.filter(l => l.key !== 'global')"
              :key="level.key"
              class="p-4 rounded-lg border-2"
              :class="level.cardClass"
            >
              <div class="text-center">
                <p class="text-sm font-medium mb-1">{{ t(level.label) }}</p>
                <p class="text-2xl font-bold">{{ getCountByLevel(level.interpretationKey) }}</p>
                <p class="text-xs opacity-75 mt-1">{{ t('evaluations') }}</p>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="bg-slate-50 rounded-lg p-4 text-center">
            <p class="text-sm text-slate-600">{{ t('Total Evaluations') }}</p>
            <p class="text-3xl font-bold text-slate-900">{{ props.evaluations.length }}</p>
          </div>
        </div>
      </div>

      <!-- Vista Totalmente de acuerdo -->
      <div v-if="activeSatisfactionLevel === 'strongly_agree'" class="space-y-6">
        <div class="bg-white rounded-lg p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="flex-1">
              <h3 class="text-2xl font-bold text-green-900">{{ t('Strongly Agree') }}</h3>
              <p class="text-sm text-green-700 mt-1">
                {{ getEvaluationsByLevel('Totalmente de Acuerdo').length }} {{ t('evaluations') }}
              </p>
            </div>
          </div>
          
          <EvaluationsTable 
            :evaluations="getEvaluationsByLevel('Totalmente de Acuerdo')"
            :empty-message="t('No evaluations in this level')"
          />

          <!-- Bar Charts -->
          <div v-if="getEvaluationsByLevel('Totalmente de Acuerdo').length > 0" class="mt-8 space-y-6">
            <h3 class="text-xl font-bold text-slate-900 mb-4">{{ t('Distribution Analysis') }}</h3>
            <div class="grid grid-cols-1 gap-6">
              <BarChart 
                :title="t('By Area')" 
                :data="getDistributionByLevel('Totalmente de Acuerdo', 'area')"
              />
              <BarChart 
                :title="t('By Position')" 
                :data="getDistributionByLevel('Totalmente de Acuerdo', 'position')"
              />
              <BarChart 
                :title="t('By Contract Type')" 
                :data="getDistributionByLevel('Totalmente de Acuerdo', 'contract')"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Vista De acuerdo -->
      <div v-if="activeSatisfactionLevel === 'agree'" class="space-y-6">
        <div class="bg-white rounded-lg p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="flex-1">
              <h3 class="text-2xl font-bold text-emerald-900">{{ t('Agree') }}</h3>
              <p class="text-sm text-emerald-700 mt-1">
                {{ getEvaluationsByLevel('De Acuerdo').length }} {{ t('evaluations') }}
              </p>
            </div>
          </div>
          
          <EvaluationsTable 
            :evaluations="getEvaluationsByLevel('De Acuerdo')"
            :empty-message="t('No evaluations in this level')"
          />

          <!-- Bar Charts -->
          <div v-if="getEvaluationsByLevel('De Acuerdo').length > 0" class="mt-8 space-y-6">
            <h3 class="text-xl font-bold text-slate-900 mb-4">{{ t('Distribution Analysis') }}</h3>
            <div class="grid grid-cols-1 gap-6">
              <BarChart 
                :title="t('By Area')" 
                :data="getDistributionByLevel('De Acuerdo', 'area')"
              />
              <BarChart 
                :title="t('By Position')" 
                :data="getDistributionByLevel('De Acuerdo', 'position')"
              />
              <BarChart 
                :title="t('By Contract Type')" 
                :data="getDistributionByLevel('De Acuerdo', 'contract')"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Vista Desacuerdo -->
      <div v-if="activeSatisfactionLevel === 'disagree'" class="space-y-6">
        <div class="bg-white rounded-lg p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="flex-1">
              <h3 class="text-2xl font-bold text-orange-900">{{ t('Disagree') }}</h3>
              <p class="text-sm text-orange-700 mt-1">
                {{ getEvaluationsByLevel('Desacuerdo').length }} {{ t('evaluations') }}
              </p>
            </div>
          </div>
          
          <EvaluationsTable 
            :evaluations="getEvaluationsByLevel('Desacuerdo')"
            :empty-message="t('No evaluations in this level')"
          />

          <!-- Bar Charts -->
          <div v-if="getEvaluationsByLevel('Desacuerdo').length > 0" class="mt-8 space-y-6">
            <h3 class="text-xl font-bold text-slate-900 mb-4">{{ t('Distribution Analysis') }}</h3>
            <div class="grid grid-cols-1 gap-6">
              <BarChart 
                :title="t('By Area')" 
                :data="getDistributionByLevel('Desacuerdo', 'area')"
              />
              <BarChart 
                :title="t('By Position')" 
                :data="getDistributionByLevel('Desacuerdo', 'position')"
              />
              <BarChart 
                :title="t('By Contract Type')" 
                :data="getDistributionByLevel('Desacuerdo', 'contract')"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Vista Totalmente en desacuerdo -->
      <div v-if="activeSatisfactionLevel === 'strongly_disagree'" class="space-y-6">
        <div class="bg-white rounded-lg p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="flex-1">
              <h3 class="text-2xl font-bold text-red-900">{{ t('Strongly Disagree') }}</h3>
              <p class="text-sm text-red-700 mt-1">
                {{ getEvaluationsByLevel('Totalmente Desacuerdo').length }} {{ t('evaluations') }}
              </p>
            </div>
          </div>
          
          <EvaluationsTable 
            :evaluations="getEvaluationsByLevel('Totalmente Desacuerdo')"
            :empty-message="t('No evaluations in this level')"
          />

          <!-- Bar Charts -->
          <div v-if="getEvaluationsByLevel('Totalmente Desacuerdo').length > 0" class="mt-8 space-y-6">
            <h3 class="text-xl font-bold text-slate-900 mb-4">{{ t('Distribution Analysis') }}</h3>
            <div class="grid grid-cols-1 gap-6">
              <BarChart 
                :title="t('By Area')" 
                :data="getDistributionByLevel('Totalmente Desacuerdo', 'area')"
              />
              <BarChart 
                :title="t('By Position')" 
                :data="getDistributionByLevel('Totalmente Desacuerdo', 'position')"
              />
              <BarChart 
                :title="t('By Contract Type')" 
                :data="getDistributionByLevel('Totalmente Desacuerdo', 'contract')"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { ChartBarIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/composables/useTranslations';
import EvaluationsTable from './ClimaLaboral/EvaluationsTable.vue';
import BarChart from './ClimaLaboral/BarChart.vue';

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

interface SatisfactionLevel {
  key: string;
  label: string;
  interpretationKey?: string;
  cardClass?: string;
}

interface BulkImportStatus {
  id: number;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  total_rows: number;
  processed_rows: number;
  updated_count: number;
  skipped_count: number;
  progress_percentage: number;
  errors: string[];
  error_message: string | null;
  file_name: string;
}

interface Props {
  evaluations?: Evaluation[];
  organizationId?: string | number;
  workCenterId?: string;
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
  organizationId: () => '',
  workCenterId: () => '',
});

const activeSatisfactionLevel = ref<string>('global');
const isExporting = ref<boolean>(false);
const isImporting = ref<boolean>(false);
const importFileInput = ref<HTMLInputElement | null>(null);
const importFeedback = ref<{ success: boolean; message: string } | null>(null);
const importStatus = ref<BulkImportStatus | null>(null);
const activeImportJobId = ref<number | null>(null);
const pollIntervalId = ref<number | null>(null);

const satisfactionLevels: SatisfactionLevel[] = [
  { 
    key: 'global', 
    label: 'Global'
  },
  { 
    key: 'strongly_agree', 
    label: 'Strongly Agree',
    interpretationKey: 'Totalmente de Acuerdo',
    cardClass: 'bg-green-50 border-green-200 text-green-900'
  },
  { 
    key: 'agree', 
    label: 'Agree',
    interpretationKey: 'De Acuerdo',
    cardClass: 'bg-emerald-50 border-emerald-200 text-emerald-900'
  },
  { 
    key: 'disagree', 
    label: 'Disagree',
    interpretationKey: 'Desacuerdo',
    cardClass: 'bg-orange-50 border-orange-200 text-orange-900'
  },
  { 
    key: 'strongly_disagree', 
    label: 'Strongly Disagree',
    interpretationKey: 'Totalmente Desacuerdo',
    cardClass: 'bg-red-50 border-red-200 text-red-900'
  },
];

// Filter evaluations by satisfaction level
const getEvaluationsByLevel = (interpretationKey?: string): Evaluation[] => {
  if (!interpretationKey) return props.evaluations;
  return props.evaluations.filter(e => e.interpretation === interpretationKey);
};

// Get count by satisfaction level
const getCountByLevel = (interpretationKey?: string): number => {
  if (!interpretationKey) return props.evaluations.length;
  return getEvaluationsByLevel(interpretationKey).length;
};

// Get distribution by level and type
const getDistributionByLevel = (interpretationKey: string, type: 'area' | 'position' | 'contract') => {
  const filteredEvaluations = getEvaluationsByLevel(interpretationKey);
  const distribution: Record<string, number> = {};
  
  filteredEvaluations.forEach(evaluation => {
    let key: string;
    
    if (type === 'area') {
      key = evaluation.demographicData?.department || 'Sin especificar';
    } else if (type === 'position') {
      key = evaluation.demographicData?.position || 'Sin especificar';
    } else {
      key = evaluation.demographicData?.contract_type || 'Sin especificar';
    }
    
    distribution[key] = (distribution[key] || 0) + 1;
  });

  return Object.entries(distribution)
    .map(([label, count]) => ({ label, count }))
    .sort((a, b) => b.count - a.count);
};

const statusLabel = (status: BulkImportStatus['status']): string => {
  if (status === 'pending') {
    return t('Pending');
  }

  if (status === 'processing') {
    return t('Processing');
  }

  if (status === 'completed') {
    return t('Completed');
  }

  return t('Failed');
};

const stopPollingImportStatus = (): void => {
  if (pollIntervalId.value !== null) {
    window.clearInterval(pollIntervalId.value);
    pollIntervalId.value = null;
  }
};

const refreshImportStatus = async (jobId: number): Promise<void> => {
  try {
    const response = await axios.get((window as any).route('bulk-import.status', jobId));
    importStatus.value = response.data as BulkImportStatus;

    if (importStatus.value.status === 'completed') {
      stopPollingImportStatus();
      importFeedback.value = {
        success: true,
        message: t('Import completed summary', {
          updated: importStatus.value.updated_count,
          skipped: importStatus.value.skipped_count,
          errors: importStatus.value.errors.length,
        }),
      };

      router.reload({
        only: ['evaluations', 'dashboardData'],
        preserveScroll: true,
      });
    }

    if (importStatus.value.status === 'failed') {
      stopPollingImportStatus();
      importFeedback.value = {
        success: false,
        message: importStatus.value.error_message || t('Import failed'),
      };
    }
  } catch (error) {
    stopPollingImportStatus();
    importFeedback.value = {
      success: false,
      message: t('Could not fetch import status. Please refresh the page.'),
    };
  }
};

const startPollingImportStatus = (jobId: number): void => {
  stopPollingImportStatus();
  activeImportJobId.value = jobId;

  void refreshImportStatus(jobId);

  pollIntervalId.value = window.setInterval(() => {
    if (activeImportJobId.value !== null) {
      void refreshImportStatus(activeImportJobId.value);
    }
  }, 1500);
};

onBeforeUnmount(() => {
  stopPollingImportStatus();
});

// Export to Excel
const exportToExcel = async () => {
  if (isExporting.value || !props.organizationId) return;
  
  isExporting.value = true;
  
  try {
    const response = await axios.post(
      (window as any).route('organization.clima-laboral.export-compact', props.organizationId),
      {},
      { responseType: 'blob' }
    );

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    // Get filename from Content-Disposition header or use default
    const contentDisposition = response.headers['content-disposition'];
    let filename = `clima_laboral_${new Date().toISOString().split('T')[0]}.xlsx`;
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1].replace(/['"]/g, '');
      }
    }
    
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Export error:', error);
    alert('Error al descargar el archivo. Intente nuevamente.');
  } finally {
    isExporting.value = false;
  }
};

// Import Excel to update records
const onImportFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file || !props.workCenterId) { return; }

  isImporting.value = true;
  importFeedback.value = null;
  importStatus.value = null;

  try {
    const formData = new FormData();
    formData.append('file', file);

    const response = await axios.post(
      (window as any).route('work-centers.clima.bulk-update', props.workCenterId),
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );

    const bulkImportJobId = response.data.bulk_import_job_id as number | undefined;

    importFeedback.value = {
      success: true,
      message: response.data.message || t('File uploaded. Processing started.'),
    };

    if (bulkImportJobId) {
      startPollingImportStatus(bulkImportJobId);
    }
  } catch (error: any) {
    const message = error?.response?.data?.message ?? 'Error al procesar el archivo. Intente nuevamente.';
    importFeedback.value = { success: false, message };
  } finally {
    isImporting.value = false;
    // Reset file input so the same file can be re-selected
    if (importFileInput.value) { importFileInput.value.value = ''; }
  }
};
</script>
    