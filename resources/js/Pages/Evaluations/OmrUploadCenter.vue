<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    ArrowPathIcon,
    CheckCircleIcon,
    ClockIcon,
    CloudArrowUpIcon,
    DocumentIcon,
    ExclamationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/solid';
import Dashboard from '../../Layouts/Dashboard.vue';

const props = defineProps({
    maxBatchFiles: {
        type: Number,
        default: 5,
    },
});

const page = usePage();
const STORAGE_KEY = 'omr_upload_center_state';
const MAX_FILES = computed(() => Math.max(1, Number(props.maxBatchFiles || 5)));
const confirmSameInstrument = ref(false);

const INSTRUMENT_OPTIONS = [
    {
        value: 'gri',
        shortLabel: 'Formato A',
        label: 'Referencia I',
        summary: 'Cuestionario de eventos traumáticos severos.',
    },
    {
        value: 'griii',
        shortLabel: 'Formato B',
        label: 'Referencia III',
        summary: 'Factores de riesgo psicosocial en el trabajo.',
    },
    {
        value: 'grv',
        shortLabel: 'Formato C',
        label: 'Referencia V',
        summary: 'Datos demográficos y laborales de la persona.',
    },
];

const form = useForm({
    files: [],
    instrument: '',
});

const batchId = ref(null);
const totalFiles = ref(0);
const currentFileIndex = ref(0);
const fileStatuses = ref([]);
const isProcessing = ref(false);
const processingComplete = ref(false);
const showStatusPanel = ref(false);
const successCount = ref(0);
const errorCount = ref(0);
let channel = null;

const batchProgress = computed(() => {
    if (totalFiles.value <= 0) {
        return 0;
    }

    return Math.round((currentFileIndex.value / totalFiles.value) * 100);
});

const selectedInstrument = computed(() => INSTRUMENT_OPTIONS.find((option) => option.value === form.instrument) ?? null);
const canUpload = computed(() => !!form.instrument && !isProcessing.value);
const canConfirm = computed(() => canUpload.value && form.files.length > 0);
const canSubmit = computed(() => canConfirm.value && confirmSameInstrument.value && !form.processing);

const runningCount = computed(() => fileStatuses.value.filter((file) => file.status === 'running').length);

function formatFileSize(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function inferStage(message, status) {
    const normalizedMessage = String(message || '').toLowerCase();

    if (status === 'finished') {
        return 'registrado';
    }

    if (status === 'error') {
        return 'fallo';
    }

    if (normalizedMessage.includes('enviando pdf')) {
        return 'subiendo';
    }

    if (normalizedMessage.includes('análisis ocr') || normalizedMessage.includes('analisis ocr')) {
        return 'ocr';
    }

    if (normalizedMessage.includes('guardando resultados')) {
        return 'persistiendo';
    }

    if (status === 'running') {
        return 'procesando';
    }

    return 'en cola';
}

function stageLabel(stage) {
    const labels = {
        'en cola': 'En cola',
        subiendo: 'Subiendo al OCR',
        ocr: 'Extrayendo respuestas',
        persistiendo: 'Registrando en base',
        procesando: 'Procesando',
        registrado: 'Completado',
        fallo: 'Con error',
    };

    return labels[stage] ?? 'En cola';
}

function getFileStatusIcon(status) {
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
}

function statusTone(status) {
    if (status === 'running') {
        return 'text-sky-700 bg-sky-50 border-sky-200';
    }

    if (status === 'finished') {
        return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    }

    if (status === 'error') {
        return 'text-rose-700 bg-rose-50 border-rose-200';
    }

    return 'text-stone-600 bg-stone-50 border-stone-200';
}

function saveStateToStorage() {
    const state = {
        batchId: batchId.value,
        totalFiles: totalFiles.value,
        currentFileIndex: currentFileIndex.value,
        fileStatuses: fileStatuses.value,
        isProcessing: isProcessing.value,
        showStatusPanel: showStatusPanel.value,
        processingComplete: processingComplete.value,
        successCount: successCount.value,
        errorCount: errorCount.value,
        savedAt: Date.now(),
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function clearStateFromStorage() {
    localStorage.removeItem(STORAGE_KEY);
}

function restoreStateFromStorage() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) {
            return false;
        }

        const state = JSON.parse(saved);
        const maxAge = 24 * 60 * 60 * 1000;
        if (Date.now() - state.savedAt > maxAge) {
            clearStateFromStorage();
            return false;
        }

        if (state.batchId && !state.processingComplete) {
            batchId.value = state.batchId;
            totalFiles.value = state.totalFiles;
            currentFileIndex.value = state.currentFileIndex;
            fileStatuses.value = state.fileStatuses;
            isProcessing.value = state.isProcessing;
            showStatusPanel.value = state.showStatusPanel;
            processingComplete.value = state.processingComplete;
            successCount.value = state.successCount;
            errorCount.value = state.errorCount;
            return true;
        }

        return false;
    } catch {
        return false;
    }
}

function addFiles(newFiles) {
    const existingNames = form.files.map((file) => file.name);
    const uniqueFiles = newFiles.filter((file) => !existingNames.includes(file.name));
    const availableSlots = MAX_FILES.value - form.files.length;
    const filesToAdd = uniqueFiles.slice(0, availableSlots);

    form.files = [...form.files, ...filesToAdd];
}

function handleFileChange(event) {
    const newFiles = Array.from(event.target.files);
    addFiles(newFiles);
    event.target.value = '';
}

function handleDrop(event) {
    event.preventDefault();
    const newFiles = Array.from(event.dataTransfer.files).filter((file) => file.type === 'application/pdf');
    addFiles(newFiles);
}

function handleDragOver(event) {
    event.preventDefault();
}

function removeFile(index) {
    form.files = form.files.filter((_, fileIndex) => fileIndex !== index);
}

function resetFlow() {
    form.reset('files');
    form.reset('instrument');
    form.clearErrors();
    confirmSameInstrument.value = false;
    batchId.value = null;
    totalFiles.value = 0;
    currentFileIndex.value = 0;
    fileStatuses.value = [];
    isProcessing.value = false;
    processingComplete.value = false;
    showStatusPanel.value = false;
    successCount.value = 0;
    errorCount.value = 0;
    clearStateFromStorage();
}

function submit() {
    if (!canSubmit.value) {
        return;
    }

    fileStatuses.value = form.files.map((file) => ({
        name: file.name,
        status: 'pending',
        message: 'En cola para envío',
        stage: 'en cola',
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
            const batch = page.props.flash?.batch;
            if (batch) {
                batchId.value = batch.batchId;
                totalFiles.value = batch.totalFiles;
                currentFileIndex.value = 0;
                saveStateToStorage();
            }

            form.reset('files');
            confirmSameInstrument.value = false;
        },
        onError: () => {
            isProcessing.value = false;
            processingComplete.value = true;
            fileStatuses.value = fileStatuses.value.map((file) => ({
                ...file,
                status: 'error',
                message: 'No se pudo iniciar el procesamiento del archivo.',
                stage: 'fallo',
            }));
            errorCount.value = fileStatuses.value.length;
            clearStateFromStorage();
        },
    });
}

onMounted(() => {
    const userId = page.props.auth?.user?.id;
    restoreStateFromStorage();

    if (!window.Echo || !userId) {
        return;
    }

    channel = window.Echo.private(`evaluation-processing.${userId}`).listen('.evaluation.status', (event) => {
        if (batchId.value && event.batchId !== batchId.value) {
            return;
        }

        currentFileIndex.value = event.currentIndex;
        totalFiles.value = event.totalFiles;

        const fileIndex = fileStatuses.value.findIndex((file) => file.name === event.fileName);
        if (fileIndex !== -1) {
            const nextStage = inferStage(event.message, event.status);
            fileStatuses.value[fileIndex] = {
                ...fileStatuses.value[fileIndex],
                status: event.status,
                message: event.message,
                stage: nextStage,
            };

            if (event.status === 'finished') {
                successCount.value += 1;
            }

            if (event.status === 'error') {
                errorCount.value += 1;
            }
        }

        if (event.status === 'finished' || event.status === 'error') {
            const nextIndex = fileIndex + 1;
            if (nextIndex < fileStatuses.value.length && fileStatuses.value[nextIndex].status === 'pending') {
                fileStatuses.value[nextIndex] = {
                    ...fileStatuses.value[nextIndex],
                    status: 'running',
                    message: 'Iniciando envío al backend OCR...',
                    stage: 'subiendo',
                };
            }
        }

        const allDone = fileStatuses.value.length > 0 && fileStatuses.value.every((file) => ['finished', 'error'].includes(file.status));
        if (allDone) {
            isProcessing.value = false;
            processingComplete.value = true;
            clearStateFromStorage();
        } else {
            saveStateToStorage();
        }
    });
});

onUnmounted(() => {
    const userId = page.props.auth?.user?.id;
    if (channel && userId && window.Echo) {
        channel.stopListening('.evaluation.status');
        window.Echo.leaveChannel(`private-evaluation-processing.${userId}`);
    }
});
</script>

<template>
    <Dashboard>
        <section class="mx-auto w-full max-w-5xl px-4 pb-8 pt-2 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl border border-stone-200 bg-gradient-to-br from-amber-50 via-orange-50 to-stone-50 p-6 shadow-sm sm:p-8">
                <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-orange-200/40 blur-3xl" aria-hidden="true" />
                <div class="absolute -bottom-10 left-12 h-36 w-36 rounded-full bg-amber-200/30 blur-3xl" aria-hidden="true" />

                <div class="relative space-y-5">
                    <p class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-orange-800">
                        Centro de captura OMR
                    </p>

                    <div class="max-w-3xl space-y-2">
                        <h1 class="text-balance text-2xl font-semibold tracking-tight text-stone-900 sm:text-3xl">
                            Carga guiada de evaluaciones en papel
                        </h1>
                        <p class="text-sm leading-6 text-stone-700 sm:text-base">
                            Completa este formulario de arriba hacia abajo. Cada sección se habilita cuando la anterior está lista.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-5">
                <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-500">Estado global</h2>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-4">
                        <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
                            <dt class="text-stone-500">Total</dt>
                            <dd class="mt-1 text-xl font-semibold text-stone-900">{{ totalFiles || form.files.length }}</dd>
                        </div>
                        <div class="rounded-lg border border-sky-200 bg-sky-50 p-3">
                            <dt class="text-sky-700">En proceso</dt>
                            <dd class="mt-1 text-xl font-semibold text-sky-800">{{ runningCount }}</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <dt class="text-emerald-700">Exitosos</dt>
                            <dd class="mt-1 text-xl font-semibold text-emerald-800">{{ successCount }}</dd>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                            <dt class="text-rose-700">Con error</dt>
                            <dd class="mt-1 text-xl font-semibold text-rose-800">{{ errorCount }}</dd>
                        </div>
                    </dl>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-semibold text-stone-900">1. Instrumento a seleccionar</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="option in INSTRUMENT_OPTIONS"
                                :key="option.value"
                                type="button"
                                class="rounded-xl border px-4 py-4 text-left transition"
                                :class="form.instrument === option.value
                                    ? 'border-amber-500 bg-amber-50 shadow-sm'
                                    : 'border-stone-200 bg-white hover:border-amber-300 hover:bg-amber-50/50'"
                                :aria-pressed="form.instrument === option.value"
                                @click="form.instrument = option.value"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">{{ option.shortLabel }}</p>
                                <p class="mt-1 text-sm font-semibold text-stone-900">{{ option.label }}</p>
                            </button>
                        </div>
                        <p v-if="form.errors.instrument" class="mt-2 text-xs text-rose-600">{{ form.errors.instrument }}</p>
                    </article>

                    <article
                        class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                        :class="{ 'opacity-60': !canUpload }"
                    >
                        <h2 class="text-lg font-semibold text-stone-900">2. Cargar archivos</h2>
                        <div
                            class="mt-4 rounded-xl border border-dashed border-stone-300 bg-stone-50/80 px-5 py-8 transition"
                            :class="{ 'border-amber-400 bg-amber-50': form.files.length > 0 }"
                            @dragover="handleDragOver"
                            @drop="canUpload ? handleDrop : () => {}"
                        >
                            <div class="mx-auto max-w-lg text-center">
                                <CloudArrowUpIcon class="mx-auto h-12 w-12 text-amber-600" aria-hidden="true" />
                                <div class="mt-4 flex items-center justify-center gap-2 text-sm text-stone-600">
                                    <label
                                        for="omr-file-upload"
                                        class="cursor-pointer rounded-lg border border-amber-300 bg-white px-3 py-2 font-medium text-amber-800 transition hover:bg-amber-100"
                                    >
                                        Elegir archivos
                                    </label>
                                    <span>o arrástralos aquí</span>
                                </div>

                                <input
                                    id="omr-file-upload"
                                    type="file"
                                    name="files[]"
                                    class="sr-only"
                                    accept="application/pdf"
                                    multiple
                                    :disabled="!canUpload || form.files.length >= MAX_FILES"
                                    @change="handleFileChange"
                                />

                                <p class="mt-3 text-xs text-stone-500" aria-live="polite">
                                    Máximo {{ MAX_FILES }} archivos por carga. Llevas {{ form.files.length }}/{{ MAX_FILES }}.
                                </p>
                            </div>
                        </div>

                        <ul v-if="form.files.length > 0" class="mt-4 divide-y divide-stone-200 rounded-xl border border-stone-200">
                            <li
                                v-for="(file, index) in form.files"
                                :key="file.name"
                                class="flex items-center justify-between gap-3 px-4 py-3"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <DocumentIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-stone-900">{{ file.name }}</p>
                                        <p class="text-xs text-stone-500">{{ formatFileSize(file.size) }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-md p-1 text-stone-400 transition hover:bg-rose-50 hover:text-rose-600"
                                    @click="removeFile(index)"
                                    :aria-label="`Quitar ${file.name}`"
                                >
                                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </li>
                        </ul>
                    </article>

                    <article
                        class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                        :class="{ 'opacity-60': !canConfirm }"
                    >
                        <h2 class="text-lg font-semibold text-stone-900">3. Confirmación</h2>
                        <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm">
                            <p class="text-stone-700">
                                Vas a procesar
                                <span class="font-semibold text-stone-900">{{ form.files.length }} archivo(s)</span>
                                con
                                <span class="font-semibold text-stone-900">{{ selectedInstrument?.shortLabel }} - {{ selectedInstrument?.label }}</span>.
                            </p>
                        </div>
                        <label class="mt-4 flex items-start gap-3 rounded-lg border border-stone-200 bg-white p-3">
                            <input
                                v-model="confirmSameInstrument"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                                :disabled="!canConfirm"
                            >
                            <span class="text-sm text-stone-700">
                                Confirmo que todos los archivos del lote son del mismo formato.
                            </span>
                        </label>

                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100"
                                @click="resetFlow"
                            >
                                Limpiar formulario
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-400"
                                :disabled="!canSubmit"
                            >
                                Empezar carga
                            </button>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-semibold text-stone-900">4. Progreso</h2>
                        <div class="mt-4">
                            <div class="mb-2 flex items-center justify-between text-xs text-stone-600">
                                <span>Progreso de la carga</span>
                                <span>{{ batchProgress }}%</span>
                            </div>
                            <div
                                class="h-2 w-full rounded-full bg-stone-200"
                                role="progressbar"
                                :aria-valuenow="batchProgress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Progreso de procesamiento"
                            >
                                <div
                                    class="h-2 rounded-full bg-gradient-to-r from-amber-500 to-emerald-500 transition-all duration-500"
                                    :style="{ width: `${batchProgress}%` }"
                                />
                            </div>
                        </div>

                        <p v-if="isProcessing" class="mt-4 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700" aria-live="polite">
                            Procesando un archivo a la vez para minimizar errores.
                        </p>

                        <ul v-if="showStatusPanel" class="mt-4 max-h-[24rem] space-y-2 overflow-y-auto pr-1">
                            <li
                                v-for="file in fileStatuses"
                                :key="file.name"
                                class="rounded-xl border px-3 py-3"
                                :class="statusTone(file.status)"
                            >
                                <div class="flex items-start gap-2">
                                    <component
                                        :is="getFileStatusIcon(file.status)"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0"
                                        :class="{ 'animate-spin': file.status === 'running' }"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ file.name }}</p>
                                        <p class="mt-1 text-xs">{{ stageLabel(file.stage) }}</p>
                                        <p class="mt-1 truncate text-xs opacity-90">{{ file.message }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-xs text-stone-500">Aquí verás el avance de cada archivo cuando inicie la carga.</p>
                    </article>
                </form>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                    <p class="font-semibold">Ruta legacy</p>
                    <p class="mt-1">
                        La pantalla anterior sigue disponible en
                        <code class="rounded bg-amber-100 px-1 py-0.5 text-[11px]">/evaluaciones/cargar-evaluacion</code>
                        para transición operativa.
                    </p>
                </div>
            </div>
        </section>
    </Dashboard>
</template>
