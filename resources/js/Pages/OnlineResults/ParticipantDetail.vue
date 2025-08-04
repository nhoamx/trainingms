<template>
    <Dashboard>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <Link 
                            :href="route('organization.online-results', { id: organization.id })"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-2 inline-flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Volver a la lista
                        </Link>
                        <h2 class="text-2xl font-semibold text-gray-900">Detalles del Participante</h2>
                        <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                    </div>
                </div>

                <!-- Participant Info Card -->
                <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">ID Personal</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ participant.personal_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Folio</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ participant.folio }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Quiz</label>
                            <p class="mt-1 text-sm text-gray-900">{{ participant.quiz_name }}</p>
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1"
                                :class="{
                                    'bg-green-100 text-green-800': participant.quiz_type === 'Cisneros',
                                    'bg-orange-100 text-orange-800': participant.quiz_type === 'Reducido',
                                    'bg-blue-100 text-blue-800': participant.quiz_type === 'Completo'
                                }"
                            >
                                {{ participant.quiz_type }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Fecha de Completado</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ participant.completed_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- INE Images Section -->
                <div v-if="ine_images.ine_frente || ine_images.ine_reverso" class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Imágenes de Identificación (INE)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- INE Frente -->
                        <div v-if="ine_images.ine_frente" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">INE Frente</label>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <img 
                                    v-if="ine_images.ine_frente.exists"
                                    :src="ine_images.ine_frente.url" 
                                    alt="INE Frente" 
                                    class="max-w-full h-auto rounded cursor-pointer hover:opacity-90 transition-opacity"
                                    @click="openImageModal(ine_images.ine_frente.url, 'INE Frente')"
                                />
                                <div v-else class="text-center py-8 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <p>Imagen no encontrada</p>
                                    <p class="text-xs">{{ ine_images.ine_frente.path }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- INE Reverso -->
                        <div v-if="ine_images.ine_reverso" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">INE Reverso</label>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <img 
                                    v-if="ine_images.ine_reverso.exists"
                                    :src="ine_images.ine_reverso.url" 
                                    alt="INE Reverso" 
                                    class="max-w-full h-auto rounded cursor-pointer hover:opacity-90 transition-opacity"
                                    @click="openImageModal(ine_images.ine_reverso.url, 'INE Reverso')"
                                />
                                <div v-else class="text-center py-8 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <p>Imagen no encontrada</p>
                                    <p class="text-xs">{{ ine_images.ine_reverso.path }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Answers Sections -->
                <div class="space-y-6">
                    <!-- Datos Personales (Referencia V) -->
                    <div v-if="answers.V && answers.V.length > 0" class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Datos Personales (Referencia V)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="answer in answers.V" :key="answer.question_key" class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ formatQuestionKey(answer.question_key) }}
                                </label>
                                
                                <!-- Render JSON objects -->
                                <div v-if="isJsonObject(answer.answer_value)" class="text-sm text-gray-900">
                                    <div v-for="(value, key) in parseJsonValue(answer.answer_value)" :key="key" class="ml-4 mb-1">
                                        <span class="font-medium text-gray-600">{{ formatQuestionKey(key) }}:</span>
                                        <span class="ml-2">{{ value }}</span>
                                    </div>
                                </div>
                                
                                <!-- Render simple values -->
                                <p v-else class="text-sm text-gray-900">{{ answer.formatted_value || answer.answer_value }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cuestionario Principal (Referencia III) -->
                    <div v-if="answers.III && answers.III.length > 0" class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Cuestionario Principal (Referencia III)</h3>
                        <div class="space-y-3">
                            <div v-for="answer in answers.III" :key="answer.question_key" class="border-b border-gray-100 pb-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-sm font-medium text-gray-700">{{ formatQuestionKey(answer.question_key) }}</span>
                                    
                                    <!-- Render JSON objects -->
                                    <div v-if="isJsonObject(answer.answer_value)" class="text-sm text-gray-900 ml-4">
                                        <div v-for="(value, key) in parseJsonValue(answer.answer_value)" :key="key" class="mb-1">
                                            <span class="font-medium text-gray-600">{{ formatQuestionKey(key) }}:</span>
                                            <span class="ml-2">{{ value }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Render simple values -->
                                    <span v-else class="text-sm text-gray-900 ml-4">{{ answer.formatted_value || answer.answer_value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preguntas Adicionales (Referencia I) -->
                    <div v-if="answers.I && answers.I.length > 0" class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Preguntas Adicionales (Referencia I)</h3>
                        <div class="space-y-3">
                            <div v-for="answer in answers.I" :key="answer.question_key" class="border-b border-gray-100 pb-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-sm font-medium text-gray-700">{{ formatQuestionKey(answer.question_key) }}</span>
                                    
                                    <!-- Render JSON objects -->
                                    <div v-if="isJsonObject(answer.answer_value)" class="text-sm text-gray-900 ml-4">
                                        <div v-for="(value, key) in parseJsonValue(answer.answer_value)" :key="key" class="mb-1">
                                            <span class="font-medium text-gray-600">{{ formatQuestionKey(key) }}:</span>
                                            <span class="ml-2">{{ value }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Render simple values -->
                                    <span v-else class="text-sm text-gray-900 ml-4">{{ answer.formatted_value || answer.answer_value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Escala Cisneros -->
                    <div v-if="answers.Cisneros && answers.Cisneros.length > 0" class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Escala Cisneros</h3>
                        <div class="space-y-3">
                            <div v-for="answer in answers.Cisneros" :key="answer.question_key" class="border-b border-gray-100 pb-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-sm font-medium text-gray-700">{{ formatQuestionKey(answer.question_key) }}</span>
                                    
                                    <!-- Render JSON objects -->
                                    <div v-if="isJsonObject(answer.answer_value)" class="text-sm text-gray-900 ml-4">
                                        <div v-for="(value, key) in parseJsonValue(answer.answer_value)" :key="key" class="mb-1">
                                            <span class="font-medium text-gray-600">{{ formatQuestionKey(key) }}:</span>
                                            <span class="ml-2">{{ value }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Render simple values -->
                                    <span v-else class="text-sm text-gray-900 ml-4">{{ answer.formatted_value || answer.answer_value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Modal -->
                <div v-if="showImageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" @click="closeImageModal">
                    <div class="max-w-4xl max-h-full p-4">
                        <div class="bg-white rounded-lg p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ selectedImageTitle }}</h3>
                                <button @click="closeImageModal" class="text-gray-400 hover:text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <img :src="selectedImageUrl" :alt="selectedImageTitle" class="max-w-full max-h-96 mx-auto" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import Dashboard from '@/Layouts/Dashboard.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    organization: Object,
    participant: Object,
    answers: Object,
    ine_images: Object
});

const showImageModal = ref(false);
const selectedImageUrl = ref('');
const selectedImageTitle = ref('');

const openImageModal = (url, title) => {
    selectedImageUrl.value = url;
    selectedImageTitle.value = title;
    showImageModal.value = true;
};

const closeImageModal = () => {
    showImageModal.value = false;
    selectedImageUrl.value = '';
    selectedImageTitle.value = '';
};

const formatQuestionKey = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
        .replace(/Datos Laborales/g, 'Datos Laborales:');
};

const isJsonObject = (value) => {
    if (typeof value !== 'string') return false;
    try {
        const parsed = JSON.parse(value);
        return typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed);
    } catch {
        return false;
    }
};

const parseJsonValue = (value) => {
    try {
        return JSON.parse(value);
    } catch {
        return {};
    }
};
</script>