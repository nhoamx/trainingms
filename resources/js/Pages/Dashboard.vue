<script setup>
import Dashboard from "../Layouts/Dashboard.vue";
import { Link } from '@inertiajs/vue3';
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import { defineProps } from 'vue';

defineProps({
    evaluations: {
        type: Array,
        default: () => []
    },
    organizations: {
        type: Array,
        default: () => []
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <Dashboard>
        <!-- Vista para admin/superadmin -->
        <div v-if="isAdmin && organizations">
            <div v-for="organization in organizations" :key="organization.id" class="mb-8">
                <div class="flex items-center mb-4">
                    <img v-if="organization.logo" :src="organization.logo" class="h-8 w-8 rounded-full mr-3" :alt="organization.name">
                    <h2 class="text-xl font-semibold text-gray-900">{{ organization.name }}</h2>
                </div>

                <div v-if="organization.evaluations.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="evaluation in organization.evaluations" :key="evaluation.id" class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                    <DocumentTextIcon class="h-6 w-6 text-white" aria-hidden="true" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Folio</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ evaluation.folio }}</dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de evaluación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ evaluation.created_at }}</dd>
                                </dl>
                                <dl class="mt-2" v-if="evaluation.total_score != 0">
                                    <dt class="text-sm font-medium text-gray-500">Puntaje total: <strong class="mt-1 text-sm text-gray-900">{{ evaluation.total_score }}</strong></dt>
                                    <dd class="mt-1 text-sm text-gray-900"></dd>
                                </dl>
                                <dl class="mt-2">
                                    <dt class="text-sm font-medium text-gray-500">Guía de referencia <strong class="mt-1 text-sm text-gray-900">{{ evaluation.reference_guide }}</strong></dt>
                                    <dd ></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-4 sm:px-6">
                            <div class="text-sm">
                                <Link :href="route('organization.results.detail', { organization: evaluation.organization_id, evaluation: evaluation.id })" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Ver detalles
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6 bg-white rounded-lg shadow">
                    <DocumentTextIcon class="mx-auto h-8 w-8 text-gray-400" />
                    <p class="mt-1 text-sm text-gray-500">Esta organización aún no tiene evaluaciones registradas.</p>
                </div>
            </div>
        </div>

        <!-- Vista para usuario de organización -->
        <div v-else>
            <div v-if="evaluations && evaluations.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="evaluation in evaluations" :key="evaluation.id" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <DocumentTextIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Folio</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ evaluation.folio }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-4">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Fecha de evaluación</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ evaluation.created_at }}</dd>
                            </dl>
                            <dl class="mt-2">
                                <dt class="text-sm font-medium text-gray-500">Puntaje total</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ evaluation.total_score }}</dd>
                            </dl>
                            <dl class="mt-2">
                                <dt class="text-sm font-medium text-gray-500">Guía de referencia</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ evaluation.reference_guide }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6">
                        <div class="text-sm">
                            <Link :href="route('organization.results.detail', { organization: evaluation.organization_id, evaluation: evaluation.id })" class="font-medium text-indigo-600 hover:text-indigo-500">
                                Ver detalles
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-12">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay evaluaciones</h3>
                <p class="mt-1 text-sm text-gray-500">Esta organización aún no tiene evaluaciones registradas.</p>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>

</style>
