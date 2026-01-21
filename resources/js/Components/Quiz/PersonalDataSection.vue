<template>
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 sm:p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h3 class="text-base sm:text-lg font-bold text-blue-900">Datos del Trabajador</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <!-- Sexo -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Sexo</label>
                <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                    <label
                        v-for="option in referenceData.sexo"
                        :key="option"
                        class="flex items-center space-x-2 cursor-pointer p-2 rounded-lg hover:bg-white transition-colors"
                    >
                        <input
                            type="radio"
                            :value="option"
                            :checked="modelValue.sexo === option"
                            @change="updateField('sexo', option)"
                            class="form-radio h-4 w-4 text-blue-600"
                        >
                        <span class="text-sm text-blue-900">{{ option }}</span>
                    </label>
                </div>
            </div>

            <!-- Edad -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Edad</label>
                <input
                    type="number"
                    :value="modelValue.edad"
                    @change="updateField('edad', $event.target.value)"
                    min="1"
                    max="120"
                    placeholder="Ingrese su edad"
                    class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                >
            </div>

            <!-- Estado Civil -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Estado Civil</label>
                <select
                    :value="modelValue.estado_civil"
                    @change="updateField('estado_civil', $event.target.value)"
                    class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                >
                    <option value="">Seleccione su estado civil</option>
                    <option
                        v-for="estado in referenceData.estado_civil"
                        :key="estado"
                        :value="estado"
                    >
                        {{ estado }}
                    </option>
                </select>
            </div>

            <!-- Nivel de Estudios -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Nivel de Estudios</label>
                <select
                    :value="modelValue.nivel_estudios"
                    @change="updateField('nivel_estudios', $event.target.value)"
                    class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                >
                    <option value="">Seleccione su nivel de estudios</option>
                    <template v-for="(nivel, key) in referenceData.nivel_estudios" :key="key">
                        <template v-if="Array.isArray(nivel)">
                            <optgroup :label="key">
                                <option
                                    v-for="subnivel in nivel"
                                    :key="subnivel"
                                    :value="`${key} - ${subnivel}`"
                                >
                                    {{ key }} - {{ subnivel }}
                                </option>
                            </optgroup>
                        </template>
                        <option v-else :value="nivel">{{ nivel }}</option>
                    </template>
                </select>
            </div>
            <!-- INE Frente -->
            <!-- <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Foto INE (Frente)</label>
                <input
                    type="file"
                    accept="image/*"
                    @change="onFileChange('ine_frente', $event)"
                    class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                >
                <div v-if="modelValue.ine_frente" class="text-xs text-slate-500 truncate">Archivo seleccionado: {{ modelValue.ine_frente.name }}</div>
            </div> -->

            <!-- INE Reverso -->
            <!-- <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Foto INE (Reverso)</label>
                <input
                    type="file"
                    accept="image/*"
                    @change="onFileChange('ine_reverso', $event)"
                    class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                >
                <div v-if="modelValue.ine_reverso" class="text-xs text-slate-500 truncate">Archivo seleccionado: {{ modelValue.ine_reverso.name }}</div>
            </div> -->
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    referenceData: {
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

const onFileChange = (field, event) => {
    const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: file
    });
};
</script>
