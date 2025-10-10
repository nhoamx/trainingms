<template>
    <div class="participant-report">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Informe de Calificaciones de Participantes</h2>


        <!-- Conteo de participantes -->
        <div class="mb-4 text-gray-700">
            <span class="font-medium">Total de participantes:</span> {{ props.personalCalifications.length }}
        </div>

        <!-- Lista de participantes con calificaciones -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div v-if="loading" class="flex justify-center items-center py-10">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
            </div>

            <div v-else-if="props.personalCalifications.length === 0" class="p-6 text-center text-gray-500">
                No se encontraron datos de participantes
            </div>

            <ul v-else class="divide-y divide-gray-200">
                <li v-for="(participant, index) in props.personalCalifications" :key="participant.personal_folio"
                    class="hover:bg-gray-50 transition-colors duration-150 ease-in-out p-0">
                    <a :href="route('organization.results.detail', { 
                        organization: props.organizationId,
                        personalFolio: participant.personal_folio
                      })" class="flex justify-between items-center p-4 w-full h-full no-underline text-inherit"
                        style="display: flex;">
                        <div class="flex items-center space-x-3">
                            <div
                                class="bg-blue-100 text-blue-800 font-bold rounded-full h-8 w-8 flex items-center justify-center">
                                {{ index + 1 }}
                            </div>
                            <span class="font-medium">Folio {{ participant.personal_folio }}</span>
                        </div>
                        <div class="flex items-center">
                            <div class="hidden sm:block mx-4 w-32 border-b border-dotted border-gray-300"></div>
                            <div :class="getScoreClass(participant.calificacion)"
                                class="px-3 py-1 rounded-full text-white font-medium min-w-[60px] text-center">
                                {{ participant.calificacion }}
                            </div>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Leyenda de colores para niveles de riesgo -->
        <div class="mt-6 bg-white rounded-lg shadow-sm p-4">
            <h3 class="font-medium text-gray-800 mb-2">Niveles de Riesgo</h3>
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center">
                    <span class="inline-block w-4 h-4 mr-2 bg-green-500 rounded-full"></span>
                    <span>Nulo (≤ 50)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-4 h-4 mr-2 bg-blue-500 rounded-full"></span>
                    <span>Bajo (51-75)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-4 h-4 mr-2 bg-yellow-500 rounded-full"></span>
                    <span>Medio (76-99)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-4 h-4 mr-2 bg-orange-500 rounded-full"></span>
                    <span>Alto (100-140)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-4 h-4 mr-2 bg-red-500 rounded-full"></span>
                    <span>Muy Alto (> 140)</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    personalCalifications: {
        type: Array,
        required: true
    },
    organizationId: {
        type: [Number, String],
        required: true
    }
});

// Variable para controlar el estado de carga
const loading = ref(false);

// Función para determinar la clase CSS según el valor de la calificación
const getScoreClass = (score) => {
    if (score <= 50) return 'bg-green-500'; // Nulo
    if (score <= 75) return 'bg-blue-500';  // Bajo
    if (score <= 99) return 'bg-yellow-500'; // Medio
    if (score <= 140) return 'bg-orange-500'; // Alto
    return 'bg-red-500'; // Muy alto
};

console.log('Props:', props.personalCalifications);
</script>