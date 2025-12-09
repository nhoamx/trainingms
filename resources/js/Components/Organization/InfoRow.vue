<template>
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 py-3 last:border-b-0">
    <dt class="text-sm font-medium text-gray-600">
      {{ label }}
    </dt>
    <dd class="mt-1 sm:mt-0 text-sm text-gray-900 font-medium">
      {{ formattedValue }}
    </dd>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label: string;
  value: string | number | null | undefined;
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
});

const formattedValue = computed(() => {
  if (!props.value) {
    return '—';
  }
  return String(props.value);
});
</script>
