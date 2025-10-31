<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Editar Datos Laborales
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Folio: <span class="font-semibold">{{ evaluation.folio }}</span>
                                </p>
                                
                                <!-- Ocupación/Puesto -->
                                <div class="mb-4">
                                    <label for="ocupacion" class="block text-sm font-medium text-gray-700 mb-1">
                                        Ocupación / Puesto
                                    </label>
                                    <select
                                        id="ocupacion"
                                        v-model="form.ocupacion"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        :class="{ 'border-red-300': form.errors.ocupacion }"
                                    >
                                        <option value="">Seleccione un puesto</option>
                                        <option v-for="position in occupationPositions" :key="position.id" :value="position.name">
                                            {{ position.name }}
                                        </option>
                                        <option value="__custom__">Otro (escribir manualmente)</option>
                                    </select>
                                    
                                    <!-- Campo de texto personalizado si selecciona "Otro" -->
                                    <input
                                        v-if="form.ocupacion === '__custom__'"
                                        type="text"
                                        v-model="customOcupacion"
                                        placeholder="Escriba la ocupación/puesto"
                                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        maxlength="100"
                                    />
                                    
                                    <p v-if="form.errors.ocupacion" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.ocupacion }}
                                    </p>
                                </div>

                                <!-- Departamento/Sección/Área -->
                                <div class="mb-4">
                                    <label for="departamento" class="block text-sm font-medium text-gray-700 mb-1">
                                        Departamento / Sección / Área
                                    </label>
                                    <select
                                        id="departamento"
                                        v-model="form.departamento"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        :class="{ 'border-red-300': form.errors.departamento }"
                                    >
                                        <option value="">Seleccione un departamento</option>
                                        <option v-for="dept in departmentAreas" :key="dept.id" :value="dept.name">
                                            {{ dept.name }}
                                        </option>
                                        <option value="__custom__">Otro (escribir manualmente)</option>
                                    </select>
                                    
                                    <!-- Campo de texto personalizado si selecciona "Otro" -->
                                    <input
                                        v-if="form.departamento === '__custom__'"
                                        type="text"
                                        v-model="customDepartamento"
                                        placeholder="Escriba el departamento/sección/área"
                                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        maxlength="100"
                                    />
                                    
                                    <p v-if="form.errors.departamento" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.departamento }}
                                    </p>
                                </div>

                                <!-- Success message -->
                                <div v-if="successMessage" class="mt-4 rounded-md bg-green-50 p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="ml-3 text-sm font-medium text-green-800">
                                            {{ successMessage }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </button>
                    <button
                        type="button"
                        @click="closeModal"
                        :disabled="form.processing"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface DemographicData {
    ocupacion?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
    departamento?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
}

interface Evaluation {
    id: string;
    folio: string;
    demographic_data?: DemographicData | null;
}

interface OccupationPosition {
    id: number;
    name: string;
}

interface DepartmentArea {
    id: number;
    name: string;
}

interface Props {
    show: boolean;
    evaluation: Evaluation;
    occupationPositions: OccupationPosition[];
    departmentAreas: DepartmentArea[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated'): void;
}>();

const successMessage = ref<string>('');
const customOcupacion = ref<string>('');
const customDepartamento = ref<string>('');

// Helper function to extract text from demographic data structure
const extractText = (data: any): string => {
    if (!data) return '';
    if (typeof data === 'string') return data;
    if (typeof data === 'object') {
        // Combinar fila1 y fila2 si existen
        const parts = [];
        if (data.fila1) parts.push(data.fila1);
        if (data.fila2) parts.push(data.fila2);
        return parts.join(' ');
    }
    return '';
};

const form = useForm({
    ocupacion: '',
    departamento: '',
});

// Reset form when modal opens
watch(() => props.show, (newValue) => {
    if (newValue) {
        const demographicData = props.evaluation.demographic_data;
        const extractedOcupacion = extractText(demographicData?.ocupacion);
        const extractedDepartamento = extractText(demographicData?.departamento);
        
        // Verificar si el valor extraído existe en las listas
        const ocupacionExists = props.occupationPositions.some(p => p.name === extractedOcupacion);
        const departamentoExists = props.departmentAreas.some(d => d.name === extractedDepartamento);
        
        // Si no existe en la lista, usar el modo personalizado
        if (extractedOcupacion && !ocupacionExists) {
            form.ocupacion = '__custom__';
            customOcupacion.value = extractedOcupacion;
        } else {
            form.ocupacion = extractedOcupacion;
            customOcupacion.value = '';
        }
        
        if (extractedDepartamento && !departamentoExists) {
            form.departamento = '__custom__';
            customDepartamento.value = extractedDepartamento;
        } else {
            form.departamento = extractedDepartamento;
            customDepartamento.value = '';
        }
        
        form.clearErrors();
        successMessage.value = '';
    }
});

const submitForm = () => {
    if (form.processing) return;

    // Si el usuario seleccionó "Otro", usar el valor personalizado
    const finalOcupacion = form.ocupacion === '__custom__' ? customOcupacion.value : form.ocupacion;
    const finalDepartamento = form.departamento === '__custom__' ? customDepartamento.value : form.departamento;

    // Actualizar el formulario con los valores finales
    form.ocupacion = finalOcupacion;
    form.departamento = finalDepartamento;

    form.patch(route('paper-evaluations.update-demographic-data', props.evaluation.id), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Datos laborales actualizados exitosamente';
            emit('updated');
            
            setTimeout(() => {
                closeModal();
            }, 1500);
        },
        onError: (errors: any) => {
            console.error('Error updating demographic data:', errors);
        },
    });
};

const closeModal = () => {
    if (!form.processing) {
        emit('close');
    }
};
</script>
