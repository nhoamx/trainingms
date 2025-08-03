<template>
    <div class="bg-slate-50 p-4 rounded-lg">
        <h3 class="font-medium text-slate-900 mb-4">Escala Cisneros</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Pregunta 1</label>
                <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                    <label v-for="option in ['A', 'B', 'C', 'D']" :key="option" class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-white transition-colors">
                        <input
                            type="radio"
                            :value="option"
                            :checked="modelValue.pregunta1 === option"
                            @change="updateField('pregunta1', option)"
                            class="form-radio h-4 w-4 text-slate-800"
                        >
                        <span class="text-sm text-slate-700">{{ option }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const updateField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value
    });
};
</script>
