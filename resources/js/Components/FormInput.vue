<template>
    <div>
        <div class="flex flex-col gap-y-1">
            <label :for="id" class="block text-sm font-medium text-gray-900">
                {{ label }}
            </label>
            <small v-if="hint" class="text-gray-600">{{ hint }}</small>
        </div>
        <div class="mt-2">
            <input
                :type="type"
                :id="id"
                :name="name"
                :autocomplete="autocomplete"
                v-model="inputValue"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
            />
            <p v-if="error" class="mt-1 text-xs text-red-500">
                {{ error }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
    label: { type: String, required: true },
    hint: { type: String, default: '' },
    id: { type: String, required: true },
    name: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    autocomplete: { type: String, default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const inputValue = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})
</script>
