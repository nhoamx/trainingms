<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const showModal = ref(false);
const showQRModal = ref(false);
const selectedQR = ref(null);
const selectedQRName = ref('');

const form = useForm({
    name: '',
    organization_id: '',
    expires_at: ''
});

const props = defineProps({
    quizzes: Array,
    organizations: Array
});

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const copyToClipboard = (url) => {
    navigator.clipboard.writeText(url);
};

const createQuiz = () => {
    form.post(route('quizzes.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const toggleQuizStatus = (quizId) => {
    router.post(route('quizzes.toggle', quizId));
};

const downloadQR = (dataUrl, quizName) => {
    const base64Data = dataUrl.split(',')[1];
    const svg = atob(base64Data);
    const blob = new Blob([svg], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `qr-${quizName}.svg`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const openQRModal = (qrCode, name) => {
    selectedQR.value = qrCode;
    selectedQRName.value = name;
    showQRModal.value = true;
};
</script>

<template>
    <Dashboard>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Lista de Exámenes</h2>
                    <button 
                        @click="showModal = true"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Crear Examen Temporal
                    </button>
                </div>

                <!-- Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organización</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL Temporal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código QR</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evaluaciones</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Expiración</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="quiz in quizzes" :key="quiz.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ quiz.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ quiz.organization?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-900 truncate max-w-xs">{{ quiz.temp_url }}</span>
                                        <button 
                                            @click="copyToClipboard(quiz.temp_url)"
                                            class="text-blue-600 hover:text-blue-800"
                                            title="Copiar URL"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="relative group">
                                        <div class="flex items-center space-x-2">
                                            <img 
                                                :src="quiz.qr_code" 
                                                class="w-10 h-10 cursor-pointer hover:opacity-75" 
                                                :alt="'QR code for ' + quiz.name"
                                                @click="openQRModal(quiz.qr_code, quiz.name)"
                                            />
                                            <button 
                                                @click="downloadQR(quiz.qr_code, quiz.name)"
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Descargar QR"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ quiz.evaluations_count || 0 }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(quiz.expires_at) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            quiz.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        ]"
                                    >
                                        {{ quiz.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        @click="toggleQuizStatus(quiz.id)"
                                        class="text-sm text-blue-600 hover:text-blue-900"
                                    >
                                        {{ quiz.is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal for creating quiz -->
                <Modal :show="showModal" @close="showModal = false">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo Examen Temporal</h3>
                        <form @submit.prevent="createQuiz">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Nombre del Examen</label>
                                <input 
                                    type="text" 
                                    v-model="form.name"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                    required
                                >
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Organización</label>
                                <select 
                                    v-model="form.organization_id"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                    required
                                >
                                    <option value="">Selecciona una organización</option>
                                    <option v-for="org in organizations" :key="org.id" :value="org.id">
                                        {{ org.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Fecha de Expiración</label>
                                <input 
                                    type="datetime-local" 
                                    v-model="form.expires_at"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                    required
                                >
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button 
                                    type="button"
                                    @click="showModal = false"
                                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                                    :disabled="form.processing"
                                >
                                    Crear
                                </button>
                            </div>
                        </form>
                    </div>
                </Modal>

                <!-- Modal for QR code -->
                <Modal :show="showQRModal" @close="showQRModal = false" maxWidth="sm">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Código QR - {{ selectedQRName }}
                            </h3>
                            <button
                                @click="showQRModal = false"
                                class="text-gray-400 hover:text-gray-500"
                            >
                                <span class="sr-only">Cerrar</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex justify-center">
                            <img 
                                :src="selectedQR" 
                                class="w-64 h-64" 
                                :alt="'QR code for ' + selectedQRName"
                            />
                        </div>
                        <div class="mt-4 flex justify-center">
                            <button
                                @click="downloadQR(selectedQR, selectedQRName)"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Descargar QR
                            </button>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </Dashboard>
</template>