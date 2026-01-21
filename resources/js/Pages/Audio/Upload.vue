<template>
    <Dashboard>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900">Subir Archivo de Audio</h1>
                </div>
                <p class="text-gray-600">Sube archivos de audio para las preguntas de evaluación. Formatos soportados: MP3, M4A, WAV, OGG</p>
            </div>

            <!-- Upload Form -->
            <div class="bg-white shadow rounded-lg p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Question Type Selection -->
                    <div>
                        <label for="question_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Pregunta <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="question_type"
                            v-model="form.question_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-300': form.errors.question_type }"
                        >
                            <option value="">Selecciona un tipo</option>
                            <option v-for="(config, key) in questionTypes" :key="key" :value="key">
                                {{ config.description }}
                            </option>
                        </select>
                        <p v-if="form.errors.question_type" class="mt-1 text-sm text-red-600">{{ form.errors.question_type }}</p>
                    </div>

                    <!-- Question Index -->
                    <div>
                        <label for="question_index" class="block text-sm font-medium text-gray-700 mb-2">
                            Índice de Pregunta (0-based) <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="question_index"
                            v-model.number="form.question_index"
                            type="number"
                            min="0"
                            placeholder="Ejemplo: 0, 1, 2..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            :class="{ 'border-red-300': form.errors.question_index }"
                        />
                        <p v-if="form.errors.question_index" class="mt-1 text-sm text-red-600">{{ form.errors.question_index }}</p>
                        <p class="mt-1 text-xs text-gray-500">La primera pregunta es 0, la segunda es 1, etc.</p>
                    </div>

                    <!-- File Upload Area -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Archivo de Audio <span class="text-red-500">*</span>
                        </label>
                        <div
                            @drop.prevent="handleDrop"
                            @dragover.prevent="isDragging = true"
                            @dragleave="isDragging = false"
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md transition-colors"
                            :class="{
                                'border-blue-400 bg-blue-50': isDragging,
                                'border-red-300 bg-red-50': form.errors.audio_file
                            }"
                        >
                            <div class="space-y-1 text-center">
                                <svg
                                    v-if="!selectedFile"
                                    class="mx-auto h-12 w-12 text-gray-400"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 48 48"
                                >
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                
                                <div v-if="selectedFile" class="flex items-center justify-center gap-3">
                                    <svg class="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="text-left">
                                        <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                                    </div>
                                </div>

                                <div class="flex text-sm text-gray-600">
                                    <label
                                        for="file-upload"
                                        class="relative cursor-pointer rounded-md bg-white font-medium text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 hover:text-blue-500"
                                    >
                                        <span>{{ selectedFile ? 'Cambiar archivo' : 'Selecciona un archivo' }}</span>
                                        <input
                                            id="file-upload"
                                            name="file-upload"
                                            type="file"
                                            accept="audio/mpeg,audio/mp4,audio/wav,audio/ogg,.mp3,.m4a,.wav,.ogg"
                                            class="sr-only"
                                            @change="handleFileSelect"
                                        />
                                    </label>
                                    <p class="pl-1">o arrastra y suelta</p>
                                </div>
                                <p class="text-xs text-gray-500">MP3, M4A, WAV, OGG hasta {{ Math.round(maxFileSize / 1024) }}MB</p>
                            </div>
                        </div>
                        <p v-if="form.errors.audio_file" class="mt-1 text-sm text-red-600">{{ form.errors.audio_file }}</p>
                    </div>

                    <!-- Audio Preview -->
                    <div v-if="selectedFile && audioPreviewUrl" class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Vista previa:</p>
                        <audio :src="audioPreviewUrl" controls class="w-full"></audio>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t">
                        <Link :href="route('audio.index')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                            ← Volver a la biblioteca
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Subiendo...' : 'Subir Audio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Dashboard from '@/Layouts/Dashboard.vue';

const props = defineProps({
    questionTypes: Object,
    supportedFormats: Array,
    maxFileSize: Number,
});

const form = useForm({
    question_type: '',
    question_index: null,
    audio_file: null,
});

const selectedFile = ref(null);
const audioPreviewUrl = ref(null);
const isDragging = ref(false);

const canSubmit = computed(() => {
    return form.question_type && form.question_index !== null && selectedFile.value;
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        setFile(file);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        setFile(file);
    }
};

const setFile = (file) => {
    // Validate file type with support for M4A variants
    const validTypes = ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/ogg', 'audio/x-m4a', 'audio/aac', 'audio/m4a', 'audio/x-aac'];
    const validExtensions = ['.mp3', '.m4a', '.wav', '.ogg', '.aac'];
    
    if (!validTypes.includes(file.type) && !validExtensions.some(ext => file.name.toLowerCase().endsWith(ext))) {
        form.setError('audio_file', 'Por favor, sube un archivo de audio válido (MP3, M4A, WAV, OGG)');
        return;
    }
    
    // Validate file size
    if (file.size > props.maxFileSize * 1024) {
        form.setError('audio_file', `El archivo no debe superar los ${Math.round(props.maxFileSize / 1024)} MB`);
        return;
    }
    
    form.clearErrors('audio_file');
    selectedFile.value = file;
    form.audio_file = file;
    
    // Create preview URL
    if (audioPreviewUrl.value) {
        URL.revokeObjectURL(audioPreviewUrl.value);
    }
    audioPreviewUrl.value = URL.createObjectURL(file);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const handleSubmit = () => {
    form.post(route('audio.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedFile.value = null;
            if (audioPreviewUrl.value) {
                URL.revokeObjectURL(audioPreviewUrl.value);
                audioPreviewUrl.value = null;
            }
        },
    });
};

// Cleanup on unmount
watch(() => audioPreviewUrl.value, (newVal, oldVal) => {
    if (oldVal && oldVal !== newVal) {
        URL.revokeObjectURL(oldVal);
    }
});
</script>
