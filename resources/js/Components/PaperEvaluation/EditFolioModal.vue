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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Editar Folio Personal
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Current folio -->
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <p class="text-sm font-medium text-gray-700">Folio Actual</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ evaluation.folio }}</p>
                                </div>

                                <!-- Folio breakdown -->
                                <div class="bg-blue-50 p-4 rounded-md">
                                    <p class="text-xs font-medium text-blue-800 mb-2">Composición del Folio</p>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <p class="text-xs text-blue-600">Tipo Evaluación</p>
                                            <p class="text-lg font-bold text-blue-900">{{ evaluationTypeCode }}</p>
                                            <p class="text-xs text-blue-500">(Fijo)</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-blue-600">Organización</p>
                                            <p class="text-lg font-bold text-blue-900">{{ organizationCode }}</p>
                                            <p class="text-xs text-blue-500">(Fijo)</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-green-600">Folio Personal</p>
                                            <p class="text-lg font-bold text-green-900">{{ currentPersonalFolio }}</p>
                                            <p class="text-xs text-green-500">(Editable)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input for new personal folio -->
                                <div>
                                    <label for="personal_folio" class="block text-sm font-medium text-gray-700">
                                        Nuevo Folio Personal (4 dígitos)
                                    </label>
                                    <input
                                        type="text"
                                        id="personal_folio"
                                        v-model="form.personal_folio"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-lg"
                                        :class="{ 
                                            'border-red-300': form.errors.personal_folio || validationError,
                                            'border-green-300': isValidFormat && !checking && isAvailable
                                        }"
                                        placeholder="0000"
                                        maxlength="4"
                                        pattern="\d{4}"
                                        @input="onInput"
                                        @keyup.enter="submitForm"
                                    />
                                    
                                    <!-- Validation messages -->
                                    <div class="mt-2 space-y-1">
                                        <p v-if="form.errors.personal_folio" class="text-sm text-red-600">
                                            {{ form.errors.personal_folio }}
                                        </p>
                                        <p v-else-if="validationError" class="text-sm text-red-600">
                                            {{ validationError }}
                                        </p>
                                        <p v-else-if="checking" class="text-sm text-blue-600 flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Verificando disponibilidad...
                                        </p>
                                        <p v-else-if="isValidFormat && isAvailable" class="text-sm text-green-600 flex items-center">
                                            <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Folio disponible
                                        </p>
                                    </div>
                                </div>

                                <!-- New folio preview -->
                                <div v-if="isValidFormat" class="bg-green-50 p-4 rounded-md border border-green-200">
                                    <p class="text-sm font-medium text-green-700">Nuevo Folio Completo</p>
                                    <p class="text-2xl font-bold text-green-900 mt-1 font-mono">{{ newFolioPreview }}</p>
                                </div>

                                <!-- Warnings -->
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                <strong>Importante:</strong> El folio debe tener exactamente 4 dígitos y no puede estar duplicado.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Success message -->
                                <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
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
                        :disabled="form.processing || !canSubmit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Actualizando...' : 'Actualizar Folio' }}
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
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

interface Evaluation {
    id: string;
    folio: string;
    evaluation_type_code: string;
    organization_code: string;
    personal_folio: string;
}

interface Props {
    show: boolean;
    evaluation: Evaluation;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated'): void;
}>();

const successMessage = ref<string>('');
const validationError = ref<string>('');
const checking = ref<boolean>(false);
const isAvailable = ref<boolean>(false);
let checkTimeout: NodeJS.Timeout | null = null;

const form = useForm({
    personal_folio: props.evaluation.personal_folio || '',
});

// Extract folio components
const evaluationTypeCode = computed(() => props.evaluation.evaluation_type_code);
const organizationCode = computed(() => props.evaluation.organization_code);
const currentPersonalFolio = computed(() => props.evaluation.personal_folio);

// Validate format (exactly 4 digits)
const isValidFormat = computed(() => {
    return /^\d{4}$/.test(form.personal_folio);
});

// Generate new folio preview
const newFolioPreview = computed(() => {
    if (isValidFormat.value) {
        return evaluationTypeCode.value + organizationCode.value + form.personal_folio;
    }
    return '';
});

// Check if form can be submitted
const canSubmit = computed(() => {
    return isValidFormat.value && 
           isAvailable.value && 
           !checking.value && 
           !validationError.value &&
           form.personal_folio !== currentPersonalFolio.value;
});

// Reset form when modal opens
watch(() => props.show, (newValue: boolean) => {
    if (newValue) {
        form.personal_folio = props.evaluation.personal_folio || '';
        form.clearErrors();
        successMessage.value = '';
        validationError.value = '';
        isAvailable.value = false;
    }
});

// Check folio availability when input changes
const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    // Only allow digits
    target.value = target.value.replace(/\D/g, '');
    form.personal_folio = target.value;

    // Clear previous validation
    validationError.value = '';
    isAvailable.value = false;

    // Clear previous timeout
    if (checkTimeout) {
        clearTimeout(checkTimeout);
    }

    // Validate format first
    if (target.value.length > 0 && target.value.length < 4) {
        validationError.value = 'El folio debe tener exactamente 4 dígitos';
        return;
    }

    if (!isValidFormat.value) {
        return;
    }

    // Check if it's the same as current folio
    if (form.personal_folio === currentPersonalFolio.value) {
        validationError.value = 'El folio es el mismo que el actual';
        return;
    }

    // Debounce the availability check
    checkTimeout = setTimeout(() => {
        checkFolioAvailability();
    }, 500);
};

const checkFolioAvailability = async () => {
    if (!isValidFormat.value) return;

    checking.value = true;
    validationError.value = '';
    isAvailable.value = false;

    try {
        const response = await axios.post(route('paper-evaluations.check-folio', props.evaluation.id), {
            personal_folio: form.personal_folio,
        });

        if (response.data.available) {
            isAvailable.value = true;
        } else {
            validationError.value = response.data.message || 'Este folio ya está en uso';
        }
    } catch (error: any) {
        if (error.response?.data?.message) {
            validationError.value = error.response.data.message;
        } else {
            validationError.value = 'Error al verificar disponibilidad del folio';
        }
    } finally {
        checking.value = false;
    }
};

const submitForm = () => {
    if (!canSubmit.value || form.processing) return;

    form.patch(route('paper-evaluations.update-folio', props.evaluation.id), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Folio actualizado exitosamente';
            form.reset();
            emit('updated');
            
            setTimeout(() => {
                closeModal();
            }, 1500);
        },
        onError: (errors: any) => {
            console.error('Error updating folio:', errors);
        },
    });
};

const closeModal = () => {
    if (!form.processing) {
        emit('close');
        successMessage.value = '';
        validationError.value = '';
        isAvailable.value = false;
        if (checkTimeout) {
            clearTimeout(checkTimeout);
        }
    }
};
</script>
