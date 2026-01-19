<template>
  <div class="bg-white rounded-lg border border-slate-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-slate-600 mb-1">{{ label }}</p>
        <p class="text-3xl font-bold" :class="colorClass">{{ displayValue }}</p>
      </div>
      <div :class="[iconBgClass, 'rounded-lg p-3 flex-shrink-0']">
        <component v-if="iconComponent" :is="iconComponent" class="w-6 h-6" :class="iconColorClass" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  UserGroupIcon,
  CheckCircleIcon,
  DocumentIcon,
  ChartBarIcon,
  StarIcon,
  ShieldCheckIcon,
  LightBulbIcon,
  SparklesIcon,
  CheckIcon,
  AdjustmentsHorizontalIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline';

interface Props {
  label: string;
  value?: number | null;
  icon?: string;
  color?: 'blue' | 'green' | 'purple' | 'orange' | 'pink' | 'slate' | 'red' | 'amber';
}

const props = withDefaults(defineProps<Props>(), {
  color: 'blue',
});

const displayValue = computed(() => {
  return props.value !== null && props.value !== undefined ? props.value : '—';
});

const colorClass = computed(() => {
  const classes: Record<string, string> = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    purple: 'text-purple-600',
    orange: 'text-orange-600',
    pink: 'text-pink-600',
    slate: 'text-slate-900',
    red: 'text-red-600',
    amber: 'text-amber-600',
  };
  return classes[props.color];
});

const iconBgClass = computed(() => {
  const classes: Record<string, string> = {
    blue: 'bg-blue-50',
    green: 'bg-green-50',
    purple: 'bg-purple-50',
    orange: 'bg-orange-50',
    pink: 'bg-pink-50',
    slate: 'bg-slate-50',
    red: 'bg-red-50',
    amber: 'bg-amber-50',
  };
  return classes[props.color];
});

const iconColorClass = computed(() => {
  const classes: Record<string, string> = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    purple: 'text-purple-600',
    orange: 'text-orange-600',
    pink: 'text-pink-600',
    slate: 'text-slate-600',
    red: 'text-red-600',
    amber: 'text-amber-600',
  };
  return classes[props.color];
});

const iconMap: Record<string, any> = {
  UserGroupIcon,
  CheckCircleIcon,
  DocumentIcon,
  ChartBarIcon,
  StarIcon,
  ShieldCheckIcon,
  LightBulbIcon,
  SparklesIcon,
  CheckIcon,
  AdjustmentsHorizontalIcon,
  DocumentTextIcon,
};

const iconComponent = computed(() => {
  return props.icon ? iconMap[props.icon] : null;
});
</script>
