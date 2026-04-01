<template>
  <section class="rounded-xl border border-gray-200 bg-white p-6">
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">{{ t('Conclusions') }}</h3>
        <p class="mt-1 text-sm text-gray-600">
          {{ t('Legacy conclusions for this work center.') }}
        </p>
      </div>
      <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ t('Legacy') }}</span>
    </div>

    <div class="space-y-4">
      <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm font-semibold text-gray-800">{{ title }}</p>
        <p class="mt-2 text-sm leading-6 text-gray-700">
          {{ summary }}
        </p>
      </div>

      <div class="rounded-lg border border-gray-200 p-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Priority actions') }}</p>
        <ul class="list-disc space-y-2 pl-5 text-sm text-gray-700">
          <li v-for="(item, index) in actions" :key="`${index}-${item}`">
            {{ item }}
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const props = defineProps<{
  organizationId: string;
}>();

const isPlanta1 = computed(() => props.organizationId === 'a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238');

const title = computed(() => {
  return isPlanta1.value
    ? t('Planta 1 - Work climate conclusions')
    : t('Planta 3 - Work climate conclusions');
});

const summary = computed(() => {
  return isPlanta1.value
    ? t('The climate assessment reflects stable team cohesion with opportunities to strengthen communication between areas and leadership follow-up routines.')
    : t('The climate assessment reflects good collaboration with clear opportunities to improve recognition practices and workload balancing in key areas.');
});

const actions = computed(() => {
  if (isPlanta1.value) {
    return [
      t('Hold monthly alignment sessions between supervisors and teams.'),
      t('Track departmental commitments with a visible action board.'),
      t('Strengthen feedback channels with quarterly climate checkpoints.'),
    ];
  }

  return [
    t('Standardize recognition dynamics for high-impact teams.'),
    t('Set short workload rebalancing cycles with defined owners.'),
    t('Reinforce preventive communication through weekly follow-up briefs.'),
  ];
});
</script>
