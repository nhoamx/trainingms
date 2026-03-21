<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import { useForm } from '@inertiajs/vue3';
import CustomFieldsManager from '@/Components/Quiz/CustomFieldsManager.vue';

const props = defineProps({
    quiz: Object,
    organizations: Array
});

const form = useForm({
    name: props.quiz.name,
    organization_id: props.quiz.organization.id,
    expires_at: props.quiz.expires_at,
    quiz_type: props.quiz.is_cisneros ? 'cisneros' : (props.quiz.is_reduced ? 'reducido' : 'normal'),
    custom_fields: props.quiz.custom_fields || []
});

const updateQuiz = () => {
    form.put(route('quizzes.update', props.quiz.id), {
        onSuccess: () => {
            // Success handled automatically by Inertia
        }
    });
};
</script>

<template>
    <Dashboard>
        <div class="py-6">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-4">
                            <li>
                                <div>
                                    <a href="/quizzes" class="text-gray-400 hover:text-gray-500">
                                        Lista de Exámenes
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="flex-shrink-0 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z"/>
                                    </svg>
                                    <span class="ml-4 text-sm font-medium text-gray-500">Editar</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                </div>

                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                            Editar Examen: {{ quiz.name }}
                        </h3>

                        <form @submit.prevent="updateQuiz" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre del Examen</label>
                                    <input
                                        type="text"
                                        v-model="form.name"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                                        required
                                    >
                                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
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
                                    <div v-if="form.errors.organization_id" class="mt-1 text-sm text-red-600">{{ form.errors.organization_id }}</div>
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
                                    <div v-if="form.errors.expires_at" class="mt-1 text-sm text-red-600">{{ form.errors.expires_at }}</div>
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
                                    <div v-if="form.errors.quiz_type" class="mt-1 text-sm text-red-600">{{ form.errors.quiz_type }}</div>
                                </div>
                            </div>

                            <!-- Custom Fields Section -->
                            <div class="border-t pt-6">
                                <CustomFieldsManager v-model="form.custom_fields" />
                                <div v-if="form.errors.custom_fields" class="mt-1 text-sm text-red-600">{{ form.errors.custom_fields }}</div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a
                                    href="/quizzes"
                                    class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300"
                                >
                                    Cancelar
                                </a>
                                <button
                                    type="submit"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>
