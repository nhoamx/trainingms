<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import Dashboard from "../../Layouts/Dashboard.vue";

const props = defineProps({
    organization: Object,
    evaluations: Array,
});
</script>

<template>
    <Dashboard>
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <Link :href="route('evaluations.index')" class="text-blue-600 hover:text-blue-800">
                        ← Volver a organizaciones
                    </Link>
                </div>
                <h2 class="text-xl font-semibold mt-2">
                    Evaluaciones de {{ organization.name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ evaluations?.length || 0 }} evaluaciones encontradas
                </p>
            </div>
            
            <div class="divide-y divide-gray-200">
                <Link 
                    v-for="evaluation in evaluations" 
                    :key="evaluation.id"
                    :href="route('evaluations.show', evaluation.id)"
                    class="p-4 hover:bg-gray-50 block"
                >
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                Folio: {{ evaluation.folio }}
                            </p>
                            <p class="text-sm text-gray-500">
                                ID: {{ evaluation.id }}
                            </p>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                    </div>
                </Link>
            </div>

            <div v-if="!evaluations?.length" class="p-8 text-center text-gray-500">
                No se encontraron evaluaciones para esta organización
            </div>
        </div>
    </Dashboard>
</template>