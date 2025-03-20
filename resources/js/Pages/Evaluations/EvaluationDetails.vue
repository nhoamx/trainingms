<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dashboard from "../../Layouts/Dashboard.vue";

const props = defineProps({
    evaluation: Object,
    answers: Array,
});
</script>

<template>
    <Dashboard>
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="border-b pb-4 mb-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Detalles de Evaluación</h2>
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('organizations.evaluations', evaluation.organization?.id || 'no-org')"
                            class="text-blue-600 hover:text-blue-800 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver a la lista de evaluaciones
                        </Link>
                    </div>
                </div>
                <p class="text-gray-600 mt-2">Folio: {{ evaluation.folio }}</p>
                <p v-if="evaluation.organization" class="text-gray-600">
                    Organización: {{ evaluation.organization.name }}
                </p>
                <p class="text-gray-600">
                    Guia de Referencia {{ evaluation.reference_guide }}
                </p>
                <p v-if="evaluation.personal_id" class="text-gray-600">
                    ID Personal: {{ evaluation.personal_id }}
                </p>
            </div>

            <div class="space-y-6">
                <div v-if="answers && answers.length > 0">
                    <div v-for="answer in answers" :key="answer.id" class="border-b pb-4 mb-4 last:border-0">
                        <h3 class="font-medium text-gray-900">{{ answer.question }}</h3>
                        <p class="mt-1 text-gray-600">Respuesta: {{ answer.answer }}</p>
                        <p class="mt-1 text-sm text-gray-500">Puntaje: {{ answer.score }}</p>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    No hay respuestas disponibles para esta evaluación
                </div>
            </div>
        </div>
    </Dashboard>
</template>
