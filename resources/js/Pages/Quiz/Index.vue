<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CustomFieldsManager from '@/Components/Quiz/CustomFieldsManager.vue';

const showModal = ref(false);
const showQRModal = ref(false);
const selectedQR = ref(null);
const selectedQRName = ref('');
const showCreateForm = ref(false);

const form = useForm({
    name: '',
    organization_id: '',
    expires_at: '',
    quiz_type: 'normal', // valores: normal, reducido, cisneros
    custom_fields: []
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
            showCreateForm.value = false;
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

const toggleCreateForm = () => {
    showCreateForm.value = !showCreateForm.value;
    if (!showCreateForm.value) {
        form.reset();
    }
};
</script>

<template>
    <Dashboard>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Lista de Exámenes</h2>
                    <button
                        @click="toggleCreateForm"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        {{ showCreateForm ? 'Cancelar' : 'Crear Examen Temporal' }}
                    </button>
                </div>

                <!-- Embedded Create Form -->
                <div v-if="showCreateForm" class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Crear Nuevo Examen Temporal</h3>
                    <form @submit.prevent="createQuiz" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre del Examen</label>
                                <input
                                    type="text"
                                    v-model="form.name"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                    required
                                >
                            </div>
                            <div>
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
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha de Expiración</label>
                                <input
                                    type="datetime-local"
                                    v-model="form.expires_at"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Examen</label>
                                <div class="flex space-x-6">
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" value="normal" v-model="form.quiz_type" class="form-radio text-blue-600">
                                        <span class="text-sm">Normal</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" value="reducido" v-model="form.quiz_type" class="form-radio text-orange-600">
                                        <span class="text-sm">Reducido</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" value="cisneros" v-model="form.quiz_type" class="form-radio text-green-600">
                                        <span class="text-sm">Cisneros</span>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    "Reducido" solo incluye preguntas de acontecimientos traumáticos (preguntas 1-6). "Cisneros" muestra la escala Cisneros.
                                </p>
                            </div>
                        </div>

                        <!-- Custom Fields Section -->
                        <div class="border-t pt-6">
                            <CustomFieldsManager v-model="form.custom_fields" />
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="toggleCreateForm"
                                class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Creando...' : 'Crear Examen' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                        Examen
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">
                                        Tipo
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">
                                        Campos Personalizados
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                        Organización
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                                        Acceso
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                        Eval.
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                        Expira
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                        Estado
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="quiz in quizzes" :key="quiz.id" class="hover:bg-gray-50">
                                    <!-- Nombre del examen -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-medium text-gray-900 truncate">
                                            {{ quiz.name }}
                                        </div>
                                    </td>
                                    
                                    <!-- Tipo -->
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-orange-100 text-orange-800': quiz.is_reduced,
                                                'bg-green-100 text-green-800': quiz.is_cisneros,
                                                'bg-blue-100 text-blue-800': !quiz.is_reduced && !quiz.is_cisneros
                                            }"
                                        >
                                            {{ quiz.is_cisneros ? 'Cisneros' : (quiz.is_reduced ? 'Reducido' : 'Completo') }}
                                        </span>
                                    </td>

                                    <!-- Campos Personalizados -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900">
                                            <span v-if="quiz.custom_fields && quiz.custom_fields.length > 0" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ quiz.custom_fields.length }} campos
                                            </span>
                                            <span v-else class="text-gray-400 text-xs">Sin campos</span>
                                        </div>
                                    </td>                                    <!-- Organización -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900 truncate">
                                            {{ quiz.organization?.name || 'N/A' }}
                                        </div>
                                    </td>
                                    
                                    <!-- URL y QR -->
                                    <td class="px-4 py-4">
                                        <div class="flex items-center space-x-3">
                                            <!-- URL -->
                                            <div class="flex items-center space-x-2 flex-1 min-w-0">
                                                <span class="text-xs text-gray-600 truncate max-w-32" :title="quiz.temp_url">
                                                    {{ quiz.temp_url.replace('http://', '').replace('https://', '') }}
                                                </span>
                                                <button 
                                                    @click="copyToClipboard(quiz.temp_url)"
                                                    class="text-blue-600 hover:text-blue-800 flex-shrink-0"
                                                    title="Copiar URL"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            <!-- QR Code -->
                                            <div class="flex items-center space-x-1 flex-shrink-0">
                                                <img 
                                                    :src="quiz.qr_code" 
                                                    class="w-8 h-8 cursor-pointer hover:opacity-75 border border-gray-200 rounded" 
                                                    :alt="'QR code for ' + quiz.name"
                                                    @click="openQRModal(quiz.qr_code, quiz.name)"
                                                />
                                                <button 
                                                    @click="downloadQR(quiz.qr_code, quiz.name)"
                                                    class="text-blue-600 hover:text-blue-800"
                                                    title="Descargar QR"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Evaluaciones -->
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full text-sm font-medium text-gray-900">
                                            {{ quiz.evaluations_count || 0 }}
                                        </span>
                                    </td>
                                    
                                    <!-- Fecha de expiración -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ new Date(quiz.expires_at).toLocaleDateString('es-ES', { 
                                                day: '2-digit', 
                                                month: '2-digit', 
                                                year: '2-digit',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) }}
                                        </div>
                                    </td>
                                    
                                    <!-- Estado -->
                                    <td class="px-4 py-4 text-center">
                                        <span 
                                            :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                                quiz.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ quiz.is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    
                                    <!-- Acciones -->
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a
                                                :href="route('quizzes.show', quiz.id)"
                                                class="text-blue-600 hover:text-blue-800 text-xs font-medium px-2 py-1 rounded-md bg-blue-50 hover:bg-blue-100"
                                                title="Editar examen"
                                            >
                                                Editar
                                            </a>
                                            <button
                                                @click="toggleQuizStatus(quiz.id)"
                                                :class="[
                                                    'text-xs font-medium px-2 py-1 rounded-md transition-colors',
                                                    quiz.is_active
                                                        ? 'text-red-700 bg-red-50 hover:bg-red-100'
                                                        : 'text-green-700 bg-green-50 hover:bg-green-100'
                                                ]"
                                            >
                                                {{ quiz.is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

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