<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h4 class="text-sm font-medium text-gray-700">Campos Personalizados</h4>
            <button
                type="button"
                @click="addCustomField"
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Agregar Campo
            </button>
        </div>

        <div v-if="localCustomFields.length === 0" class="text-sm text-gray-500 italic">
            No hay campos personalizados definidos.
        </div>

        <div v-for="(field, index) in localCustomFields" :key="field.tempId || field.id" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Campo</label>
                    <input
                        type="text"
                        v-model="field.name"
                        @input="updateField(index, 'name', $event.target.value)"
                        class="block w-full border border-gray-300 rounded-md shadow-sm p-2 text-sm"
                        placeholder="Ej: Número de empleado"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Campo</label>
                    <select
                        v-model="field.type"
                        @change="updateField(index, 'type', $event.target.value)"
                        class="block w-full border border-gray-300 rounded-md shadow-sm p-2 text-sm"
                        required
                    >
                        <option value="">Selecciona un tipo</option>
                        <option value="text">Texto</option>
                        <option value="number">Número</option>
                        <option value="textarea">Texto largo</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="button"
                        @click="removeCustomField(index)"
                        class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const localCustomFields = ref([...props.modelValue]);

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    localCustomFields.value = [...newValue];
}, { deep: true });

// Watch for local changes and emit them
watch(localCustomFields, (newValue) => {
    emit('update:modelValue', newValue);
}, { deep: true });

const addCustomField = () => {
    const tempId = Date.now(); // Temporary ID for new fields
    localCustomFields.value.push({
        tempId,
        name: '',
        type: '',
    });
};

const removeCustomField = (index) => {
    localCustomFields.value.splice(index, 1);
};

const updateField = (index, property, value) => {
    if (localCustomFields.value[index]) {
        localCustomFields.value[index][property] = value;
    }
};
</script>
