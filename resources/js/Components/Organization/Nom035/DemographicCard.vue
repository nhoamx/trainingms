<template>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Card Header -->
    <div class="px-6 py-4 border-b border-slate-100" :class="headerBg">
      <h4 class="text-lg font-semibold text-slate-900">{{ title }}</h4>
    </div>

    <!-- Empty -->
    <div v-if="!hasData" class="p-6 text-center text-sm text-slate-400">
      Sin datos disponibles
    </div>

    <!-- Bar Chart -->
    <div v-else class="p-6 space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="[label, count] in sortedEntries"
        :key="label"
        class="group"
      >
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm font-medium text-slate-700 truncate mr-3">{{ label }}</span>
          <span class="flex-shrink-0 text-sm font-bold" :class="valueColor">
            {{ count }}
            <span class="text-xs font-normal text-slate-400 ml-0.5">
              ({{ percentage(count) }}%)
            </span>
          </span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="barColor"
            :style="{ width: percentage(count) + '%' }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  title: string;
  data: Record<string, number>;
  color: string;
  total: number;
}>();

const hasData = computed(() => Object.keys(props.data).length > 0);

const sortedEntries = computed(() => {
  return Object.entries(props.data).sort(([, a], [, b]) => b - a);
});

const percentage = (count: number): string => {
  if (props.total === 0) {
    return '0';
  }
  return ((count / props.total) * 100).toFixed(1);
};

const colorMap: Record<string, { header: string; bar: string; value: string }> = {
  blue: { header: 'bg-blue-50', bar: 'bg-blue-500', value: 'text-blue-700' },
  purple: { header: 'bg-purple-50', bar: 'bg-purple-500', value: 'text-purple-700' },
  green: { header: 'bg-green-50', bar: 'bg-green-500', value: 'text-green-700' },
  amber: { header: 'bg-amber-50', bar: 'bg-amber-500', value: 'text-amber-700' },
  orange: { header: 'bg-orange-50', bar: 'bg-orange-500', value: 'text-orange-700' },
  indigo: { header: 'bg-indigo-50', bar: 'bg-indigo-500', value: 'text-indigo-700' },
  red: { header: 'bg-red-50', bar: 'bg-red-500', value: 'text-red-700' },
};

const resolved = computed(() => colorMap[props.color] ?? colorMap.blue);

const headerBg = computed(() => resolved.value.header);
const barColor = computed(() => resolved.value.bar);
const valueColor = computed(() => resolved.value.value);
</script>
