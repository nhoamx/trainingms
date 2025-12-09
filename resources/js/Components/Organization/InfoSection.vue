<template>
  <div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      {{ title }}
    </h3>
    <slot />
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>
