<template>
    <div class="bg-slate-50 p-4 rounded-lg">
        <h3 class="font-medium text-slate-900 mb-4">Datos Laborales</h3>
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <p class="text-sm text-blue-700">
                <strong>Nota:</strong> Su ID Personal será asignado automáticamente al completar la evaluación.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <!-- Ocupación / Puesto -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Ocupación / Puesto</label>
                <select
                    :value="modelValue.ocupacion_puesto"
                    @change="updateNestedField('ocupacion_puesto', $event.target.value)"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                <label class="block text-sm font-medium text-slate-700">Departamento / Sección / Área</label>
                <select
                    :value="modelValue.departamento_seccion_area"
                    @change="updateNestedField('departamento_seccion_area', $event.target.value)"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                        <label class="block text-sm font-medium text-slate-700">
                            Tiempo en el puesto actual
                        </label>
                        <select
                            :value="modelValue.experiencia?.tiempo_puesto_actual"
                            @change="updateExperienceField('tiempo_puesto_actual', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                        <label class="block text-sm font-medium text-slate-700">
                            Tiempo de experiencia laboral total
                        </label>
                        <select
                            :value="modelValue.experiencia?.tiempo_experiencia_laboral"
                            @change="updateExperienceField('tiempo_experiencia_laboral', $event.target.value)"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                <!-- Campos normales -->
                <div v-else-if="field !== 'ocupacion_puesto' && field !== 'departamento_seccion_area'" class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">
                        {{ field.replace(/_/g, ' ').charAt(0).toUpperCase() + field.replace(/_/g, ' ').slice(1) }}
                    </label>
                    <select
                        :value="modelValue[field]"
                        @change="updateNestedField(field, $event.target.value)"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
    }
});

const emit = defineEmits(['update:modelValue']);

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
