<template>
    <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">
                    Actualización Masiva de Datos
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Instrucciones -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-md">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="text-sm font-medium text-blue-800">Instrucciones</h4>
                            <div class="mt-2 text-sm text-blue-700">
                                <ol class="list-decimal list-inside space-y-1">
                                    <li>Descarga la plantilla con los datos actuales</li>
                                    <li>Edita el archivo Excel con los datos a actualizar</li>
                                    <li>Sube el archivo modificado usando el botón de abajo</li>
                                </ol>
                                <p class="mt-2 text-xs">
                                    <strong>Nota:</strong> Si el dato existe, se actualizará. Si no existe, se agregará.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descargar Plantilla -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Paso 1: Descargar Plantilla
                    </label>
                    <button
                        @click="downloadTemplate"
                        :disabled="downloading"
                        class="w-full inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="!downloading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ downloading ? 'Descargando...' : 'Descargar Plantilla Excel' }}
                    </button>
                </div>

                <!-- Subir Archivo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Paso 2: Subir Archivo Modificado
                    </label>
                    
                    <!-- Drag and Drop Area -->
                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        :class="[
                            'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                            isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                        ]"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls"
                            @change="handleFileSelect"
                            class="hidden"
                        />

                        <div v-if="!selectedFile">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                Arrastra el archivo aquí o
                                <button
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    selecciona un archivo
                                </button>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Excel (.xlsx, .xls) hasta 10MB</p>
                        </div>

                        <div v-else class="flex items-center justify-between bg-gray-50 p-4 rounded-md">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div class="text-left">
                                    <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                                </div>
                            </div>
                            <button
                                @click="removeFile"
                                class="text-red-600 hover:text-red-800"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    <div v-if="uploadError" class="mt-4 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ uploadError }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Cancelar
                </button>
                <button
                    @click="uploadFile"
                    :disabled="!selectedFile || uploading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="!uploading">Actualizar Datos</span>
                    <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    organizationId: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['close', 'success'])

const downloading = ref(false)
const uploading = ref(false)
const isDragging = ref(false)
const selectedFile = ref(null)
const uploadError = ref(null)
const fileInput = ref(null)

const downloadTemplate = () => {
    downloading.value = true
    window.location.href = route('organization.results.template', { organization: props.organizationId })
    setTimeout(() => {
        downloading.value = false
    }, 2000)
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        validateAndSetFile(file)
    }
}

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file) {
        validateAndSetFile(file)
    }
}

const validateAndSetFile = (file) => {
    uploadError.value = null
    
    // Validate file type
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
    if (!validTypes.includes(file.type) && !file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
        uploadError.value = 'Por favor, sube un archivo Excel válido (.xlsx o .xls)'
        return
    }
    
    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
        uploadError.value = 'El archivo no debe superar los 10MB'
        return
    }
    
    selectedFile.value = file
}

const removeFile = () => {
    selectedFile.value = null
    uploadError.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const uploadFile = () => {
    if (!selectedFile.value) return
    
    uploading.value = true
    uploadError.value = null
    
    router.post(
        route('organization.results.bulk-update', { organization: props.organizationId }),
        {
            file: selectedFile.value
        },
        {
            onSuccess: (page) => {
                uploading.value = false
                selectedFile.value = null
                emit('success')
                emit('close')
            },
            onError: (errors) => {
                uploading.value = false
                uploadError.value = errors.file || 'Ocurrió un error al procesar el archivo'
            },
            onFinish: () => {
                uploading.value = false
            }
        }
    )
}
</script>
