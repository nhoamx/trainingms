<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import Dashboard from '../../Layouts/Dashboard.vue';
import Card from "../../Components/Card.vue";
import { DocumentIcon, CheckCircleIcon, ExclamationCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/solid';

const form = useForm({
    file: null,
});

const processingStatus = ref(null);
const processingMessage = ref('');
const isProcessing = ref(false);
const showStatusPanel = ref(false);
let channel = null;

// Callback para actualizar el campo file cuando se selecciona el archivo
function handleFileChange(e) {
    const files = e.target.files;
    if (files && files[0]) {
        form.file = files[0];
    }
}

function handleDrop(e) {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        form.file = files[0];
    }
}

function handleDragOver(e) {
    e.preventDefault();
}

const submit = () => {
    if (!form.file) return;
    
    // Show status panel when submission starts
    showStatusPanel.value = true;
    isProcessing.value = true;
    
    form.post(route('evaluations.store'), {
        preserveScroll: true,
        onProgress: (progressEvent) => {
            // Actualizamos el progreso del formulario (inertia ya lo asigna a form.progress)
            form.progress = progressEvent.percentage;
            processingStatus.value = 'uploading';
            processingMessage.value = `Subiendo archivo: ${progressEvent.percentage.toFixed(0)}%`;
        },
        onSuccess: () => {
            form.reset('file');
            processingStatus.value = 'queued';
            processingMessage.value = 'Archivo subido. Procesamiento en cola...';
        },
        onError: () => {
            processingStatus.value = 'error';
            processingMessage.value = 'Error al subir el archivo';
            isProcessing.value = false;
        }
    });
};

// Setup Echo listener for real-time updates
onMounted(() => {
    if (window.Echo) {
        channel = window.Echo.channel('evaluation-processing')
            .listen('.evaluation.status', (event) => {
                processingStatus.value = event.status;
                processingMessage.value = event.message;
                
                if (event.status === 'finished' || event.status === 'error') {
                    isProcessing.value = false;
                }
            });
    }
});

// Cleanup Echo listener
onUnmounted(() => {
    if (channel) {
        channel.stopListening('.evaluation.status');
        window.Echo.leaveChannel('evaluation-processing');
    }
});

const getStatusIcon = () => {
    switch (processingStatus.value) {
        case 'running':
        case 'uploading':
        case 'queued':
            return ArrowPathIcon;
        case 'finished':
            return CheckCircleIcon;
        case 'error':
            return ExclamationCircleIcon;
        default:
            return ArrowPathIcon;
    }
};

const getStatusColor = () => {
    switch (processingStatus.value) {
        case 'running':
        case 'uploading':
        case 'queued':
            return 'text-blue-600';
        case 'finished':
            return 'text-green-600';
        case 'error':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};
</script>

<template>
    <Dashboard>
        <Card>
            <form @submit.prevent="submit">
                <div class="col-span-full">
                    <label for="cover-photo" class="block text-sm/6 font-medium text-gray-900">
                        Arrastra el documento PDF con las evaluaciones que serán añadidas al sistema.
                    </label>
                    <div
                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10"
                        @dragover="handleDragOver"
                        @drop="handleDrop"
                    >
                        <div class="text-center">
                            <DocumentIcon class="mx-auto size-12 text-gray-300" aria-hidden="true" />

                            <div class="mt-4 flex justify-center text-sm/6 text-gray-600">
                                <label
                                    for="file-upload"
                                    class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
                                >
                                    <span>Seleccionar archivo</span>
                                    <input
                                        id="file-upload"
                                        name="file-upload"
                                        type="file"
                                        class="sr-only"
                                        accept="application/pdf"
                                        :disabled="isProcessing"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <p class="pl-1">o arrastra y suelta</p>
                            </div>
                            <p class="text-xs/5 text-gray-600">PDF, hasta 10MB</p>
                            <!-- Mostrar el nombre del archivo si se seleccionó -->
                            <div v-if="form.file" class="mt-2 text-sm text-gray-700">
                                Archivo seleccionado: <strong>{{ form.file.name }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Panel with Real-time Updates -->
                <div v-if="showStatusPanel" class="mt-6">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-center gap-3">
                            <component 
                                :is="getStatusIcon()" 
                                :class="['size-6', getStatusColor(), { 'animate-spin': isProcessing }]"
                                aria-hidden="true"
                            />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ processingMessage }}
                                </p>
                                <!-- Upload Progress Bar -->
                                <div v-if="form.processing && processingStatus === 'uploading'" class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: form.progress ? form.progress + '%' : '0%' }"
                                        ></div>
                                    </div>
                                </div>
                                <!-- Processing Animation -->
                                <div v-else-if="isProcessing && processingStatus !== 'uploading'" class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-indigo-600 h-2 rounded-full animate-pulse w-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <!-- Deshabilitar el botón si no se ha seleccionado archivo o se está procesando -->
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                        :disabled="!form.file || isProcessing"
                    >
                        <span v-if="isProcessing">Procesando...</span>
                        <span v-else>Cargar y registrar</span>
                    </button>
                </div>
            </form>
        </Card>
    </Dashboard>
</template>
