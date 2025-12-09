<template>
  <div :class="['bg-gray-50 rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow', customClass]">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
      {{ label }}
    </label>
    <div class="text-lg font-medium text-gray-900 break-words">
      {{ formattedValue }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label: string;
  value: string | number | null | undefined;
  customClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  customClass: '',
});

const formattedValue = computed(() => {
  if (!props.value) {
    return '—';
  }
  return String(props.value);
});
</script>
