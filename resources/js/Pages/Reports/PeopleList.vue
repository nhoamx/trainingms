<script setup>
import Dashboard from '../../Layouts/Dashboard.vue'; // Assuming same layout
import { defineProps, computed } from 'vue'; // Import computed
import { Link } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    categoryName: String, // Provided when called from category context
    domainName: String,   // Provided when called from domain context
    dimensionName: String, // Added dimension name
    demographicField: String, // Added demographic field name
    demographicValue: String, // Added demographic value label
    answerLabel: String,
    peopleDetails: Array, // Array of { personal_id, guide_iii_evaluation_id, organization_id }
    title: String // Passed from controller for layout header
});

// Determine the context based on provided props
const contextName = computed(() => props.dimensionName || props.domainName || props.categoryName || props.demographicField || 'Elemento');
const contextType = computed(() => {
    if (props.demographicField) return props.demographicField; // Use the field name directly
    if (props.dimensionName) return 'Dimensión';
    if (props.domainName) return 'Dominio';
    return 'Categoría';
});

const answerDisplay = computed(() => props.answerLabel || props.demographicValue || '-');

const pageTitle = computed(() => {
    return `Personal - ${contextType.value}: ${contextName.value} (Respuesta: ${answerDisplay.value})`;
});

const goBack = () => {
    window.history.back();
};

</script>

<template>
    <Dashboard :title="pageTitle"> <!-- Use dynamic title -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                 <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Personal que respondió '{{ answerDisplay }}' en {{ contextName }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Mostrando {{ peopleDetails.length }} personas únicas que dieron al menos una respuesta de tipo '{{ answerDisplay }}' en este {{ contextType.toLowerCase() }}.
                        </p>
                    </div>
                    <button @click="goBack" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <ArrowUturnLeftIcon class="-ml-1 mr-2 h-5 w-5 text-gray-500" aria-hidden="true" />
                        Volver
                    </button>
                </div>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <div v-if="peopleDetails && peopleDetails.length > 0">
                    <ul role="list" class="divide-y divide-gray-200">
                        <li v-for="person in peopleDetails" :key="person.guide_iii_evaluation_id" class="py-3 flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">
                                ID Personal: {{ person.personal_id }}
                            </span>
                            <Link
                                :href="route('organization.results.detail', { organization: person.organization_id, evaluation: person.guide_iii_evaluation_id })"
                                class="ml-4 inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Ver Evaluación (Guía III)
                            </Link>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-center text-gray-500">
                    No se encontró personal para esta selección.
                </div>
            </div>
        </div>
    </Dashboard>
</template>
