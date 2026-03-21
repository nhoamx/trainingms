<template>
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 sm:p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex-shrink-0 w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-base sm:text-lg font-bold text-blue-900">Datos Laborales</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <!-- Ocupación / Puesto -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Ocupación / Puesto</label>
                <select
                    :value="modelValue.ocupacion_puesto"
                    @change="updateNestedField('ocupacion_puesto', $event.target.value)"
                    class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                >
                    <option value="">Seleccione su ocupación o puesto</option>
                    <option 
                        v-for="(name, id) in organization.occupation_positions" 
                        :key="id" 
                        :value="name"
                    >
                        {{ name }}
                    </option>
                    <option v-if="Object.keys(organization.occupation_positions || {}).length === 0" value="No especificado">
                        No hay puestos configurados
                    </option>
                </select>
            </div>

            <!-- Departamento / Sección / Área -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-blue-900 mb-1.5">Departamento / Sección / Área</label>
                <select
                    :value="modelValue.departamento_seccion_area"
                    @change="updateNestedField('departamento_seccion_area', $event.target.value)"
                    class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                >
                    <option value="">Seleccione su departamento, sección o área</option>
                    <option 
                        v-for="(name, id) in organization.department_areas" 
                        :key="id" 
                        :value="name"
                    >
                        {{ name }}
                    </option>
                    <option v-if="Object.keys(organization.department_areas || {}).length === 0" value="No especificado">
                        No hay departamentos configurados
                    </option>
                </select>
            </div>

            <!-- Campos de selección dinámicos -->
            <template v-for="(options, field) in laboralData" :key="field">
                <!-- Campos de experiencia -->
                <template v-if="field === 'experiencia'">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-blue-900 mb-1.5">
                            Tiempo en el puesto actual
                        </label>
                        <select
                            :value="modelValue.experiencia?.tiempo_puesto_actual"
                            @change="updateExperienceField('tiempo_puesto_actual', $event.target.value)"
                            class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                        >
                            <option value="">Seleccione una opción</option>
                            <option
                                v-for="opt in options.tiempo_puesto_actual"
                                :key="opt"
                                :value="opt"
                            >
                                {{ opt }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-blue-900 mb-1.5">
                            Tiempo de experiencia laboral total
                        </label>
                        <select
                            :value="modelValue.experiencia?.tiempo_experiencia_laboral"
                            @change="updateExperienceField('tiempo_experiencia_laboral', $event.target.value)"
                            class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                        >
                            <option value="">Seleccione una opción</option>
                            <option
                                v-for="opt in options.tiempo_experiencia_laboral"
                                :key="opt"
                                :value="opt"
                            >
                                {{ opt }}
                            </option>
                        </select>
                    </div>
                </template>
                <!-- Turno (configurable) -->
                <div v-else-if="field === 'tipo_jornada' && isWorkScheduleActive" class="space-y-2">
                    <label class="block text-sm font-medium text-blue-900 mb-1.5">
                        Turno
                    </label>
                    <select
                        :value="modelValue.tipo_jornada"
                        @change="updateNestedField('tipo_jornada', $event.target.value)"
                        class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    >
                        <option value="">Seleccione una opción</option>
                        <option
                            v-for="(optionLabel, optionValue) in workScheduleOptions"
                            :key="String(optionValue)"
                            :value="String(optionValue)"
                        >
                            {{ optionLabel }}
                        </option>
                    </select>
                </div>
                <!-- Campos normales -->
                <div v-else-if="field !== 'ocupacion_puesto' && field !== 'departamento_seccion_area' && field !== 'tipo_jornada'" class="space-y-2">
                    <label class="block text-sm font-medium text-blue-900 mb-1.5">
                        {{ field.replace(/_/g, ' ').charAt(0).toUpperCase() + field.replace(/_/g, ' ').slice(1) }}
                    </label>
                    <select
                        :value="modelValue[field]"
                        @change="updateNestedField(field, $event.target.value)"
                        class="block w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    >
                        <option value="">Seleccione una opción</option>
                        <option
                            v-for="option in options"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </option>
                    </select>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    laboralData: {
        type: Object,
        required: true
    },
    organization: {
        type: Object,
        required: true
    },
    workScheduleConfig: {
        type: Object,
        default: () => ({
            active: false,
            options: {}
        })
    }
});

const emit = defineEmits(['update:modelValue']);

const isWorkScheduleActive = props.workScheduleConfig?.active === true;
const workScheduleOptions = props.workScheduleConfig?.options || {};

const updateNestedField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value
    });
};

const updateExperienceField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        experiencia: {
            ...props.modelValue.experiencia,
            [field]: value
        }
    });
};
</script>
