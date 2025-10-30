<template>
    <div class="mb-6 flex justify-end">
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: String,
        required: true
    }
});

defineEmits(['update:modelValue']);
</script>
