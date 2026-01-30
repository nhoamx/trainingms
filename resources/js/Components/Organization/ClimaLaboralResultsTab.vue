<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-blue-100 rounded-lg">
          <ChartBarIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">{{ t('Work Climate Results') }}</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">{{ t('Analysis by satisfaction level') }}</p>
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
import { ref, computed } from 'vue';
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

interface Props {
  evaluations?: Evaluation[];
  organizationId?: string | number;
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
  organizationId: () => '',
});

const activeSatisfactionLevel = ref<string>('global');

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
</script>
    