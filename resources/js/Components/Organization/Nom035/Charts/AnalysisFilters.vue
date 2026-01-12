<template>
  <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Filtros Demográficos</h3>
      <button
        v-if="hasActiveFilters"
        @click="clearFilters"
        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors"
      >
        Limpiar filtros
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Género -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Género
        </label>
        <select
          :value="modelValue.genero"
          @change="updateFilter('genero', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
          <option value="">Todos</option>
          <option v-for="option in demographics.generos" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>

      <!-- Puesto -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Puesto
        </label>
        <select
          :value="modelValue.puesto"
          @change="updateFilter('puesto', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
          <option value="">Todos</option>
          <option v-for="option in demographics.puestos" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>

      <!-- Área -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Área
        </label>
        <select
          :value="modelValue.area"
          @change="updateFilter('area', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
          <option value="">Todas</option>
          <option v-for="option in demographics.areas" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>

      <!-- Turno -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          Turno
        </label>
        <select
          :value="modelValue.turno"
          @change="updateFilter('turno', ($event.target as HTMLSelectElement).value)"
          class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
          <option value="">Todos</option>
          <option v-for="option in demographics.turnos" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Demographics {
  generos: string[];
  puestos: string[];
  areas: string[];
  turnos: string[];
}

interface Filters {
  genero: string;
  puesto: string;
  area: string;
  turno: string;
}

interface Props {
  demographics: Demographics;
  modelValue: Filters;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'update:modelValue', value: Filters): void;
}>();

const hasActiveFilters = computed(() => {
  return Object.values(props.modelValue).some(value => value !== '');
});

const updateFilter = (key: keyof Filters, value: string) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [key]: value,
  });
};

const clearFilters = () => {
  emit('update:modelValue', {
    genero: '',
    puesto: '',
    area: '',
    turno: '',
  });
};
</script>
