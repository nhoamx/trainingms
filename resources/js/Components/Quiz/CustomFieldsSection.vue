<template>
    <div v-if="customFields && customFields.length > 0" class="bg-slate-50 p-4 rounded-lg">
        <h3 class="font-medium text-slate-900 mb-4">Datos Adicionales</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <div v-for="field in customFields" :key="field.id" class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">
                    {{ field.name }}
                </label>
                
                <!-- Text field -->
                <input
                    v-if="field.type === 'text'"
                    type="text"
                    :value="modelValue[field.id] || ''"
                    @input="updateField(field.id, $event.target.value)"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                    :placeholder="`Ingrese ${field.name.toLowerCase()}`"
                />
                
                <!-- Number field -->
                <input
                    v-else-if="field.type === 'number'"
                    type="number"
                    :value="modelValue[field.id] || ''"
                    @input="updateField(field.id, $event.target.value)"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                    :placeholder="`Ingrese ${field.name.toLowerCase()}`"
                />
                
                <!-- Textarea field -->
                <textarea
                    v-else-if="field.type === 'textarea'"
                    :value="modelValue[field.id] || ''"
                    @input="updateField(field.id, $event.target.value)"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                    :placeholder="`Ingrese ${field.name.toLowerCase()}`"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    customFields: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const updateField = (fieldId, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [fieldId]: value
    });
};
</script>
