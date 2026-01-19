<template>
  <div class="space-y-3">
    <h4 class="text-sm font-semibold text-slate-700 mb-4">
      Distribución por Nivel de Riesgo
    </h4>
    
    <div
      v-for="level in orderedLevels"
      :key="level"
      @click="() => emits('showDetails', level)"
      class="px-4 py-4 rounded-lg shadow-sm transition-all hover:shadow-md cursor-pointer hover:scale-105"
      :style="{ backgroundColor: colors[level] }"
    >
      <div class="flex items-center justify-between">
        <div>
          <div class="text-3xl font-bold" :class="getTextColor(level)">
            {{ distribution[level] || 0 }}
          </div>
          <div class="text-sm font-medium mt-1" :class="getTextColor(level)">
            {{ labels[level] }}
          </div>
        </div>
        <div class="text-sm opacity-75" :class="getTextColor(level)">
          {{ getPercentage(level) }}%
        </div>
      </div>
    </div>

    <div class="mt-4 pt-4 border-t border-slate-200">
      <div class="flex items-center justify-between text-sm">
        <span class="font-medium text-slate-700">Total</span>
        <span class="font-bold text-slate-900">{{ totalEvaluations }} evaluaciones</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  distribution: Record<string, number>;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

const props = defineProps<Props>();
const emits = defineEmits<{
  showDetails: [level: string];
}>();

// Orden de severidad: nulo -> muy_alto
const orderedLevels = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

const totalEvaluations = computed(() => {
  return Object.values(props.distribution).reduce((sum, count) => sum + count, 0);
});

const getPercentage = (level: string): string => {
  if (totalEvaluations.value === 0) return '0.0';
  const percentage = ((props.distribution[level] || 0) / totalEvaluations.value) * 100;
  return percentage.toFixed(1);
};

// Determinar si usar texto negro o blanco según el color de fondo
const getTextColor = (level: string): string => {
  // Solo el nivel Medio (amarillo) usa texto negro por ser color claro
  return level === 'medio' ? 'text-slate-900' : 'text-white';
};
</script>
