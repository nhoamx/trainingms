<template>
    <div>
        <label :for="id" class="block text-sm font-medium leading-6 text-gray-900">
            {{ label }}
        </label>
        <select
            :id="id"
            :name="name"
            v-model="selectedValue"
            class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
        >
            <option value="" selected>Selecciona una opción</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-xs text-red-500">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    id: { type: String, required: true },
    name: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true }, // Array de objetos { value: '...', label: '...' }
    error: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const selectedValue = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})
</script>
