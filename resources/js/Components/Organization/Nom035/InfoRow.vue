<template>
  <div class="flex items-center justify-between py-3.5 border-b border-slate-100 last:border-0 hover:bg-slate-50 px-0.5 transition-colors">
    <div class="flex items-center gap-3 flex-1">
      <div class="text-slate-400">
        <component v-if="iconComponent" :is="iconComponent" class="w-4 h-4" />
      </div>
      <span class="text-sm font-medium text-slate-700">{{ label }}</span>
    </div>
    <div class="text-right">
      <span
        v-if="type === 'email' && value"
        class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline cursor-pointer break-all"
      >
        <a :href="`mailto:${value}`">{{ value }}</a>
      </span>
      <span
        v-else-if="type === 'phone' && value"
        class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline cursor-pointer break-all"
      >
        <a :href="`tel:${value}`">{{ value }}</a>
      </span>
      <span v-else :class="['text-sm font-medium', value ? 'text-slate-900' : 'text-slate-400']">
        {{ displayValue }}
      </span>
      <span v-if="badge" class="ml-2 inline-block px-2 py-0.5 text-xs font-medium rounded-full" :class="badgeClass">
        {{ badgeLabel }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  DocumentTextIcon,
  ShoppingBagIcon,
  IdentificationIcon,
  DocumentIcon,
  BriefcaseIcon,
  HashtagIcon,
  CalendarIcon,
  HomeIcon,
  MapIcon,
  EnvelopeIcon,
  ArchiveBoxIcon,
  FlagIcon,
  UserIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

interface Props {
  label: string;
  value?: string | number | null;
  type?: 'text' | 'email' | 'phone';
  badge?: string;
  icon?: string;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
});

const displayValue = computed(() => {
  return props.value || '—';
});

const badgeClass = computed(() => {
  const classes: Record<string, string> = {
    primary: 'bg-blue-100 text-blue-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-yellow-100 text-yellow-700',
    danger: 'bg-red-100 text-red-700',
  };
  return classes[props.badge || 'primary'];
});

const badgeLabel = computed(() => {
  const labels: Record<string, string> = {
    primary: 'Información',
    success: 'Activo',
    warning: 'Pendiente',
    danger: 'Crítico',
  };
  return labels[props.badge || 'primary'];
});

const iconMap: Record<string, any> = {
  DocumentTextIcon,
  ShoppingBagIcon,
  IdentificationIcon,
  DocumentIcon,
  BriefcaseIcon,
  HashtagIcon,
  CalendarIcon,
  HomeIcon,
  MapIcon,
  EnvelopeIcon,
  ArchiveBoxIcon,
  FlagIcon,
  UserIcon,
  CheckCircleIcon,
};

const iconComponent = computed(() => {
  return props.icon ? iconMap[props.icon] : null;
});
</script>
