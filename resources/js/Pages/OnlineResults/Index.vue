<template>
    <Dashboard>
        <div class="py-6">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">Resultados En Línea</h2>
                        <p class="text-gray-600 mt-1">{{ organization.name }}</p>
                    </div>
                    <div class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                        {{ participants.length }} participantes
                    </div>
                </div>

                <!-- Participants Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Participante
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Datos Básicos
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quiz
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="participant in participants" :key="participant.personal_id" class="hover:bg-gray-50">
                                    <!-- Participante -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-700">
                                                        {{ participant.personal_id }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    ID: {{ participant.personal_id }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Folio: {{ participant.folio }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Datos Básicos -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <div><strong>Sexo:</strong> {{ participant.sexo }}</div>
                                            <div><strong>Edad:</strong> {{ participant.edad }}</div>
                                            <div><strong>Puesto:</strong> {{ participant.puesto }}</div>
                                        </div>
                                    </td>

                                    <!-- Quiz -->
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ participant.quiz_name }}</div>
                                        <span 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': participant.quiz_type === 'Cisneros',
                                                'bg-orange-100 text-orange-800': participant.quiz_type === 'Reducido',
                                                'bg-blue-100 text-blue-800': participant.quiz_type === 'Completo'
                                            }"
                                        >
                                            {{ participant.quiz_type }}
                                        </span>
                                    </td>

                                    <!-- Fecha -->
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ participant.completed_at }}
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-6 py-4 text-center">
                                        <Link 
                                            :href="route('organization.participant.show', { 
                                                organizationId: organization.id, 
                                                participantId: participant.personal_id 
                                            })"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Ver Detalles
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="participants.length === 0" class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay participantes</h3>
                        <p class="mt-1 text-sm text-gray-500">Esta organización aún no tiene participantes en línea.</p>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import Dashboard from '@/Layouts/Dashboard.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    organization: Object,
    participants: Array
});
</script>