<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            nombre_comercial: '',
            estado: '',
            ciudad: ''
        })
    },
    organizationName: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);

const localValue = ref({
    nombre_comercial: props.modelValue.nombre_comercial || '',
    estado: props.modelValue.estado || '',
    ciudad: props.modelValue.ciudad || ''
});

const estados = ref({});
const isLoadingEstados = ref(true);
const ciudadesDisponibles = computed(() => {
    if (!localValue.value.estado || !estados.value[localValue.value.estado]) {
        return [];
    }
    return estados.value[localValue.value.estado];
});

// Cargar estados y municipios desde GitHub
onMounted(async () => {
    try {
        const response = await fetch('https://raw.githubusercontent.com/cisnerosnow/json-estados-municipios-mexico/refs/heads/master/estados-municipios.json');
        if (response.ok) {
            estados.value = await response.json();
        }
    } catch (error) {
        console.error('Error cargando estados:', error);
    } finally {
        isLoadingEstados.value = false;
    }
});

// Lista de estados ordenada alfabéticamente
const estadosOrdenados = computed(() => {
    return Object.keys(estados.value).sort();
});

// Función para actualizar campos individuales sin watchers profundos
const updateField = (field, value) => {
    localValue.value[field] = value;
    emit('update:modelValue', { ...localValue.value });
};

// Watch para limpiar ciudad cuando cambia el estado
watch(() => localValue.value.estado, (newEstado, oldEstado) => {
    if (newEstado !== oldEstado && oldEstado !== undefined) {
        localValue.value.ciudad = '';
        emit('update:modelValue', { ...localValue.value });
    }
});

// Watch para actualizar el valor local cuando cambia desde el padre (sin deep watch)
watch(() => props.modelValue, (newValue) => {
    if (JSON.stringify(newValue) !== JSON.stringify(localValue.value)) {
        localValue.value = { ...newValue };
    }
});
</script>

<template>
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4 sm:p-6 shadow-sm">
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-blue-900">Datos de la Organización</h2>
                    <p class="text-xs sm:text-sm text-blue-700">
                        Empresa: <span class="font-semibold">{{ organizationName }}</span>
                    </p>
                </div>
            </div>
            <div class="bg-blue-100 border-l-4 border-blue-500 p-3 rounded">
                <p class="text-xs sm:text-sm text-blue-900">
                    <span class="font-semibold">⚠️ Importante:</span> Por favor completa los siguientes datos de tu ubicación laboral
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Nombre Comercial -->
            <div>
                <label for="nombre_comercial" class="block text-sm font-medium text-blue-900 mb-1.5">
                    Plaza <span class="text-red-600">*</span>
                </label>
                <input
                    id="nombre_comercial"
                    :value="localValue.nombre_comercial"
                    @input="updateField('nombre_comercial', $event.target.value)"
                    type="text"
                    class="w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    placeholder="Ingresa la plaza"
                    required
                />
            </div>

            <!-- Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-blue-900 mb-1.5">
                    Estado <span class="text-red-600">*</span>
                </label>
                <select
                    id="estado"
                    :value="localValue.estado"
                    @change="updateField('estado', $event.target.value)"
                    class="w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    required
                    :disabled="isLoadingEstados"
                >
                    <option value="" disabled>{{ isLoadingEstados ? 'Cargando estados...' : 'Selecciona un estado' }}</option>
                    <option v-for="estado in estadosOrdenados" :key="estado" :value="estado">
                        {{ estado }}
                    </option>
                </select>
            </div>

            <!-- Ciudad -->
            <div>
                <label for="ciudad" class="block text-sm font-medium text-blue-900 mb-1.5">
                    Ciudad / Municipio <span class="text-red-600">*</span>
                </label>
                <select
                    id="ciudad"
                    :value="localValue.ciudad"
                    @change="updateField('ciudad', $event.target.value)"
                    class="w-full px-3 py-2.5 bg-white border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    required
                    :disabled="!localValue.estado || ciudadesDisponibles.length === 0"
                >
                    <option value="" disabled>{{ !localValue.estado ? 'Primero selecciona un estado' : 'Selecciona un municipio' }}</option>
                    <option v-for="ciudad in ciudadesDisponibles" :key="ciudad" :value="ciudad">
                        {{ ciudad }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>
