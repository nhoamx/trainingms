<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import Dashboard from '../../Layouts/Dashboard.vue';
import Card from "../../Components/Card.vue";
import { DocumentIcon, CheckCircleIcon, ExclamationCircleIcon, ArrowPathIcon, XMarkIcon, ClockIcon } from '@heroicons/vue/24/solid';

const page = usePage();

const form = useForm({
    files: [],
});

// Estado del lote
const batchId = ref(null);
const totalFiles = ref(0);
const currentFileIndex = ref(0);
const fileStatuses = ref([]);
const isProcessing = ref(false);
const showStatusPanel = ref(false);
const processingComplete = ref(false);
const successCount = ref(0);
const errorCount = ref(0);
let channel = null;

// Progreso del lote
const batchProgress = computed(() => {
    if (totalFiles.value === 0) return 0;
    return Math.round((currentFileIndex.value / totalFiles.value) * 100);
});

// Formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Callback para actualizar el campo files cuando se selecciona archivos
function handleFileChange(e) {
    const newFiles = Array.from(e.target.files);
    addFiles(newFiles);
    e.target.value = ''; // Reset input para permitir re-selección
}

function handleDrop(e) {
    e.preventDefault();
    const newFiles = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
    addFiles(newFiles);
}

function addFiles(newFiles) {
    // Filtrar duplicados por nombre
    const existingNames = form.files.map(f => f.name);
    const uniqueFiles = newFiles.filter(f => !existingNames.includes(f.name));
    form.files = [...form.files, ...uniqueFiles];
}

function removeFile(index) {
    form.files = form.files.filter((_, i) => i !== index);
}

function handleDragOver(e) {
    e.preventDefault();
}

const submit = () => {
    if (form.files.length === 0) return;
    
    // Inicializar estados de archivos
    fileStatuses.value = form.files.map(f => ({
        name: f.name,
        status: 'pending',
        message: 'En cola...'
    }));
    
    showStatusPanel.value = true;
    isProcessing.value = true;
    processingComplete.value = false;
    successCount.value = 0;
    errorCount.value = 0;
    
    form.post(route('evaluations.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Obtener datos del lote desde la sesión flash
            const batch = page.props.flash?.batch;
            if (batch) {
                batchId.value = batch.batchId;
                totalFiles.value = batch.totalFiles;
                currentFileIndex.value = 0;
            }
            form.reset('files');
        },
        onError: (errors) => {
            isProcessing.value = false;
            fileStatuses.value = form.files.map(f => ({
                name: f.name,
                status: 'error',
                message: 'Error al subir archivos'
            }));
        }
    });
};

// Setup Echo listener for real-time updates
onMounted(() => {
    const userId = page.props.auth?.user?.id;
    
    if (window.Echo && userId) {
        channel = window.Echo.private(`evaluation-processing.${userId}`)
            .listen('.evaluation.status', (event) => {
                // Verificar que el evento pertenece a nuestro lote
                if (batchId.value && event.batchId !== batchId.value) return;
                
                currentFileIndex.value = event.currentIndex;
                totalFiles.value = event.totalFiles;
                
                // Actualizar estado del archivo específico
                const fileIndex = fileStatuses.value.findIndex(f => f.name === event.fileName);
                if (fileIndex !== -1) {
                    fileStatuses.value[fileIndex].status = event.status;
                    fileStatuses.value[fileIndex].message = event.message;
                    
                    if (event.status === 'finished') {
                        successCount.value++;
                    } else if (event.status === 'error') {
                        errorCount.value++;
                    }
                }
                
                // Marcar el siguiente archivo como "running" si el actual terminó
                if (event.status === 'finished' || event.status === 'error') {
                    const nextIndex = fileIndex + 1;
                    if (nextIndex < fileStatuses.value.length) {
                        fileStatuses.value[nextIndex].status = 'running';
                        fileStatuses.value[nextIndex].message = 'Iniciando procesamiento...';
                    }
                }
                
                // Verificar si todo el lote terminó
                const allDone = fileStatuses.value.every(f => 
                    f.status === 'finished' || f.status === 'error'
                );
                
                if (allDone) {
                    isProcessing.value = false;
                    processingComplete.value = true;
                }
            });
    }
});

// Cleanup Echo listener
onUnmounted(() => {
    const userId = page.props.auth?.user?.id;
    if (channel && userId) {
        channel.stopListening('.evaluation.status');
        window.Echo.leaveChannel(`private-evaluation-processing.${userId}`);
    }
});

const getFileStatusIcon = (status) => {
    switch (status) {
        case 'running':
            return ArrowPathIcon;
        case 'finished':
            return CheckCircleIcon;
        case 'error':
            return ExclamationCircleIcon;
        default:
            return ClockIcon;
    }
};

const getFileStatusColor = (status) => {
    switch (status) {
        case 'running':
            return 'text-blue-600';
        case 'finished':
            return 'text-green-600';
        case 'error':
            return 'text-red-600';
        default:
            return 'text-gray-400';
    }
};

const goToResults = () => {
    window.location.href = route('dashboard');
};

const resetForm = () => {
    showStatusPanel.value = false;
    processingComplete.value = false;
    fileStatuses.value = [];
    batchId.value = null;
    totalFiles.value = 0;
    currentFileIndex.value = 0;
    successCount.value = 0;
    errorCount.value = 0;
};
</script>

<template>
    <Dashboard>
        <Card>
            <form @submit.prevent="submit">
                <div class="col-span-full">
                    <label for="cover-photo" class="block text-sm/6 font-medium text-gray-900">
                        Arrastra los documentos PDF con las evaluaciones que serán añadidas al sistema.
                    </label>
                    <div
                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10"
                        :class="{ 'border-indigo-400 bg-indigo-50': form.files.length > 0 }"
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
                                    <span>Seleccionar archivos</span>
                                    <input
                                        id="file-upload"
                                        name="files[]"
                                        type="file"
                                        class="sr-only"
                                        accept="application/pdf"
                                        multiple
                                        :disabled="isProcessing"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <p class="pl-1">o arrastra y suelta</p>
                            </div>
                            <p class="text-xs/5 text-gray-600">PDF, hasta 10MB por archivo</p>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de archivos seleccionados -->
                <div v-if="form.files.length > 0 && !showStatusPanel" class="mt-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">
                        Archivos seleccionados ({{ form.files.length }})
                    </h4>
                    <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                        <li 
                            v-for="(file, index) in form.files" 
                            :key="file.name"
                            class="flex items-center justify-between py-3 px-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-3">
                                <DocumentIcon class="size-5 text-gray-400" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                                </div>
                            </div>
                            <button 
                                type="button"
                                @click="removeFile(index)"
                                class="text-gray-400 hover:text-red-500 transition-colors"
                            >
                                <XMarkIcon class="size-5" />
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Panel de estado del lote -->
                <div v-if="showStatusPanel" class="mt-6 space-y-4">
                    <!-- Resumen del progreso -->
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <ArrowPathIcon 
                                    v-if="isProcessing"
                                    class="size-5 text-indigo-600 animate-spin" 
                                />
                                <CheckCircleIcon 
                                    v-else-if="processingComplete && errorCount === 0"
                                    class="size-5 text-green-600" 
                                />
                                <ExclamationCircleIcon 
                                    v-else-if="processingComplete && errorCount > 0"
                                    class="size-5 text-yellow-600" 
                                />
                                <span class="font-medium text-gray-900">
                                    <template v-if="isProcessing">
                                        Procesando archivo {{ currentFileIndex }} de {{ totalFiles }}
                                    </template>
                                    <template v-else-if="processingComplete">
                                        Procesamiento completado
                                    </template>
                                </span>
                            </div>
                            <span class="text-sm text-gray-500">
                                {{ successCount }} exitosos, {{ errorCount }} con errores
                            </span>
                        </div>
                        
                        <!-- Barra de progreso del lote -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="processingComplete && errorCount === 0 ? 'bg-green-600' : 'bg-indigo-600'"
                                :style="{ width: batchProgress + '%' }"
                            ></div>
                        </div>
                        
                        <!-- Lista de archivos con estado -->
                        <ul class="space-y-2 max-h-64 overflow-y-auto">
                            <li 
                                v-for="file in fileStatuses" 
                                :key="file.name"
                                class="flex items-center gap-3 py-2 px-3 rounded-md"
                                :class="{
                                    'bg-blue-50': file.status === 'running',
                                    'bg-green-50': file.status === 'finished',
                                    'bg-red-50': file.status === 'error',
                                    'bg-white': file.status === 'pending'
                                }"
                            >
                                <component 
                                    :is="getFileStatusIcon(file.status)"
                                    :class="[
                                        'size-5 flex-shrink-0',
                                        getFileStatusColor(file.status),
                                        { 'animate-spin': file.status === 'running' }
                                    ]"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ file.message }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Botones de acción al completar -->
                    <div v-if="processingComplete" class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="resetForm"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                        >
                            Cargar más archivos
                        </button>
                        <button
                            type="button"
                            @click="goToResults"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        >
                            Ver resultados
                        </button>
                    </div>
                </div>

                <div v-if="!showStatusPanel" class="mt-6 flex items-center justify-end gap-x-6">
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                        :disabled="form.files.length === 0 || isProcessing"
                    >
                        <span v-if="isProcessing">Procesando...</span>
                        <span v-else>Cargar y registrar ({{ form.files.length }})</span>
                    </button>
                </div>
            </form>
        </Card>
    </Dashboard>
</template>
