<template>
    <Dashboard>
        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-gray-200 shadow sm:grid sm:grid-cols-2 sm:gap-px sm:divide-y-0">
            <div
                v-for="(action, actionIdx) in actions"
                :key="action.title"
                :class="[actionIdx === 0 ? 'rounded-tl-lg rounded-tr-lg sm:rounded-tr-none' : '', actionIdx === 1 ? 'sm:rounded-tr-lg' : '', actionIdx === actions.length - 2 ? 'sm:rounded-bl-lg' : '', actionIdx === actions.length - 1 ? 'rounded-bl-lg rounded-br-lg sm:rounded-bl-none' : '', 'group relative bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500']"
            >
                <div>
          <span
              :class="[action.iconBackground, action.iconForeground, 'inline-flex rounded-lg p-3 ring-4 ring-white']"
          >
            <component :is="action.icon" class="h-6 w-6" aria-hidden="true" />
          </span>
                </div>
                <div class="mt-8">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">
                        <a
                            href="#"
                            @click.prevent="action.title === 'Cargar Evaluaciones' && openModal(action)"
                            class="focus:outline-none"
                        >
                            <span class="absolute inset-0" aria-hidden="true" />
                            {{ action.title }}
                        </a>
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aut eaque eos fuga incidunt inventore magnam maiores quaerat rem sapiente sint.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <ModalComponent
            :open="isModalOpen"
            @close="closeModal"
        >
            <template #default>
                <h3 class="text-lg font-semibold mb-4">
                    Subir Archivos para {{ modalAction?.title || 'Acción Desconocida' }}
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Input de archivos -->
                    <div v-if="uploadProgress === 0">
                        <label for="files" class="block text-sm font-medium text-gray-700">Seleccionar Archivos</label>
                        <input
                            id="files"
                            type="file"
                            multiple
                            accept=".pdf,image/*"
                            @change="handleFileChange"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                    </div>

                    <!-- Barra de carga -->
                    <div v-if="uploadProgress > 0" class="mt-4">
                        <div class="relative pt-1">
                            <div class="flex mb-2 items-center justify-between">
                                <div>
                <span v-if="uploadProgress < 100" class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-indigo-600 bg-indigo-200">
                  Subiendo {{ uploadProgress }}%
                </span>
                                    <span v-else-if="uploadProgress === 100" class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-green-600 bg-green-200">
                  Archivos subidos con éxito.
                </span>
                                </div>
                            </div>
                            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-indigo-200">
                                <div
                                    :style="{ width: `${uploadProgress}%` }"
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500"
                                ></div>
                            </div>
                        </div>
                    </div>
                </form>
            </template>
            <template #footer>
                <button
                    type="button"
                    class="rounded-md bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300 focus:outline-none"
                    @click="closeModal"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-white shadow hover:bg-indigo-700 focus:outline-none"
                    @click="submitForm"
                    :disabled="uploadProgress > 0"
                >
                    Subir Archivos
                </button>
            </template>
        </ModalComponent>
    </Dashboard>
</template>

<script setup>
import { ref } from 'vue';
import Dashboard from '../../Layouts/Dashboard.vue';
import ModalComponent from '../../Components/ModalComponent.vue';
import axios from 'axios';
import {
    AcademicCapIcon,
    ClockIcon,
    ReceiptRefundIcon,
} from '@heroicons/vue/24/outline';

const isModalOpen = ref(false);
const modalAction = ref(null);
const selectedFiles = ref([]); // Archivos seleccionados
const uploadProgress = ref(0);

const openModal = (action) => {
    modalAction.value = action; // Guardar la acción seleccionada
    isModalOpen.value = true;  // Abrir el modal
};

const closeModal = () => {
    modalAction.value = null;  // Limpiar la acción actual
    isModalOpen.value = false; // Cerrar el modal
};

const handleFileChange = (event) => {
    selectedFiles.value = Array.from(event.target.files); // Convierte FileList a Array
};

const submitForm = async () => {
    if (selectedFiles.value.length === 0) {
        alert('Por favor, selecciona al menos un archivo.');
        return;
    }

    const formData = new FormData();

    // Agregar archivos al FormData
    selectedFiles.value.forEach((file) => {
        formData.append('files[]', file);
    });

    try {
        uploadProgress.value = 0; // Reiniciar el progreso

        const response = await axios.post(route('evaluations.uploadFiles'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                const total = progressEvent.total || 1; // Evitar divisiones por 0
                uploadProgress.value = Math.round((progressEvent.loaded / total) * 100);
            },
        });

        console.log('Respuesta del servidor:', response.data);

        // Limpia los archivos seleccionados y cierra el modal
        selectedFiles.value = [];
        // uploadProgress.value = 0;
        // closeModal();

        window.location.href = route('evaluations.results');
    } catch (error) {
        console.error('Error al subir los archivos:', error.response?.data || error.message);
        alert('Ocurrió un error al subir los archivos.');
        uploadProgress.value = 0; // Reiniciar en caso de error
    }
};

const actions = [
    {
        title: 'Cargar Evaluaciones',
        href: '#',
        icon: AcademicCapIcon,
        iconForeground: 'text-teal-700',
        iconBackground: 'bg-teal-50',
    },
    {
        title: 'Correr script',
        href: '#',
        icon: ClockIcon,
        iconForeground: 'text-sky-700',
        iconBackground: 'bg-sky-50',
    },
    {
        title: 'Ver resultados',
        href: '#',
        icon: ReceiptRefundIcon,
        iconForeground: 'text-purple-700',
        iconBackground: 'bg-purple-50',
    },
    {
        title: 'lorem ipsum',
        href: '#',
        icon: ReceiptRefundIcon,
        iconForeground: 'text-purple-700',
        iconBackground: 'bg-purple-50',
    },
]
</script>
