<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon, DocumentTextIcon, PencilSquareIcon, ArrowPathIcon, XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Dashboard from "../../Layouts/Dashboard.vue";
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    organization: Object,
    evaluations: Array,
});

const page = usePage();

const showReassignModal = ref(false);
const availableOrganizations = ref([]);
const selectedNewOrgId = ref(null);
const isLoadingOrgs = ref(false);
const isReassigning = ref(false);
const fetchError = ref(null);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const groupedEvaluations = computed(() => {
    const groups = {
        'I': [],
        'III': [],
        'V': [],
        'Otras': [] 
    };
    (props.evaluations || []).forEach(evaluation => {
        const guide = evaluation.reference_guide;
        if (guide === 'I' || guide === 'III' || guide === 'V') {
            groups[guide].push(evaluation);
        } else {
            groups['Otras'].push(evaluation);
        }
    });
    return Object.entries(groups)
                 .filter(([guide, evals]) => evals.length > 0)
                 .reduce((acc, [guide, evals]) => {
                     acc[guide] = evals;
                     return acc;
                 }, {});
});

const guideTitles = {
    'I': 'Guía de Referencia I',
    'III': 'Guía de Referencia III',
    'V': 'Guía de Referencia V',
    'Otras': 'Otras Evaluaciones / Sin Guía Asignada'
};

const fetchOrganizations = async () => {
    if (availableOrganizations.value.length > 0) return; 
    isLoadingOrgs.value = true;
    fetchError.value = null;
    try {
        const response = await axios.get(route('api.organizations.list'));
        availableOrganizations.value = response.data.filter(org => org.id !== props.organization.id);
    } catch (error) {
        console.error("Error fetching organizations:", error);
        fetchError.value = 'No se pudo cargar la lista de organizaciones.';
    } finally {
        isLoadingOrgs.value = false;
    }
};

const openReassignModal = () => {
    selectedNewOrgId.value = null; 
    showReassignModal.value = true;
    fetchOrganizations(); 
};

const handleReassignConfirm = () => {
    if (!selectedNewOrgId.value) {
        alert('Por favor, selecciona una organización de destino.');
        return;
    }
    isReassigning.value = true;

    const originalOrgId = props.organization.id; // Guardar ID original
    const targetOrgId = selectedNewOrgId.value; // Guardar ID destino

    // --- Log Frontend Inicio ---
    console.log('[Reasignación] Iniciando POST...');
    console.log('[Reasignación] ID Organización Original:', originalOrgId);
    console.log('[Reasignación] ID Organización Destino (a enviar):', targetOrgId);
    // --- Fin Log Frontend ---

    router.post(route('organizations.evaluations.reassign', { organization: originalOrgId }), {
        new_organization_id: selectedNewOrgId.value
    }, {
        preserveScroll: true,
        onSuccess: (page) => { 
            // --- Log Frontend Success ---
            console.log('[Reasignación] onSuccess ejecutado. Inertia manejará la redirección.');
            // --- Fin Log Frontend Success ---
            showReassignModal.value = false;
        },
        onError: (errors) => {
             // --- Log Frontend Error ---
             console.error('[Reasignación] onError ejecutado.');
             console.error('[Reasignación] Objeto de errores:', errors);
             // Mostrar el primer mensaje de error de validación si existe
            const firstError = errors[Object.keys(errors)[0]];
            alert('Error al reasignar: ' + (firstError || 'Error desconocido'));
             // --- Fin Log Frontend Error ---
        },
        onFinish: () => {
            // --- Log Frontend Finish ---
            console.log('[Reasignación] onFinish ejecutado.');
            // --- Fin Log Frontend Finish ---
            isReassigning.value = false;
        }
    });
};

const handleDeleteEvaluation = (evaluationId) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta evaluación? Esta acción no se puede deshacer.')) {
        router.delete(route('evaluations.destroy', { evaluation: evaluationId }), {
            preserveScroll: true,
            onSuccess: () => {
                // La redirección será manejada por Inertia
            },
            onError: (errors) => {
                alert('Error al eliminar la evaluación: ' + (errors.message || 'Error desconocido'));
            }
        });
    }
};

</script>

<template>
    <Dashboard>
         <!-- Flash Messages Display (Corrected Access) -->
         <div v-if="page.props.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ page.props.success }}
        </div>
        <div v-if="page.props.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ page.props.error }}
        </div>
         <div v-if="page.props.info" class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
            {{ page.props.info }}
        </div>

        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center gap-4 mb-2">
                    <Link :href="route('evaluations.index')" class="text-sm text-blue-600 hover:text-blue-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Volver a organizaciones
                    </Link>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-semibold flex items-center gap-2">
                        <span>Evaluaciones de: {{ organization.name }}</span>
                        <Link
                            v-if="organization.id !== 'no-org'"
                            :href="route('organizations.edit', { organization: organization.id })"
                            class="text-blue-600 hover:text-blue-800 p-1 rounded-full hover:bg-blue-100 transition duration-150 ease-in-out"
                            title="Editar Organización"
                        >
                            <PencilSquareIcon class="h-5 w-5" />
                        </Link>
                    </h2>
                    <button
                        v-if="evaluations?.length > 0" @click="openReassignModal"
                        class="inline-flex items-center px-3 py-1.5 border border-orange-300 text-xs font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition"
                        title="Reasignar todas estas evaluaciones a otra organización"
                        :disabled="isReassigning"
                    >
                        <ArrowPathIcon class="h-4 w-4 mr-1"/>
                        Reasignar Evaluaciones
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    Total: {{ evaluations?.length || 0 }} evaluaciones encontradas
                </p>
            </div>
        </div>

        <div v-if="Object.keys(groupedEvaluations).length > 0" class="space-y-8">
            <div v-for="(groupEvaluations, guide) in groupedEvaluations" :key="guide">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">{{ guideTitles[guide] || 'Guía Desconocida' }} ({{ groupEvaluations.length }})</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="evaluation in groupEvaluations"
                        :key="evaluation.id"
                        class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:border-blue-500 transition-colors duration-300"
                    >
                        <div class="border-b border-gray-200 bg-sky-50 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-900 truncate" :title="evaluation.folio">
                                    Folio: {{ evaluation.folio }}
                                </h4>
                                <span v-if="guide !== 'Otras'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-blue-800">
                                    Guía {{ guide }}
                                </span>
                            </div>
                        </div>
                        <div class="px-4 py-4 sm:px-6">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(evaluation.created_at) }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">ID Personal</dt>
                                    <dd class="mt-1 text-sm text-gray-900 truncate" :title="evaluation.personal_id">{{ evaluation.personal_id }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                    <dd class="mt-1 text-sm text-gray-900">Completada</dd>
                                </div>
                            </dl>
                            <div class="mt-4 flex justify-end space-x-2">
                                <Link
                                    :href="route('organization.results.detail', {
                                        organization: evaluation.organization_id || organization.id,
                                        evaluation: evaluation.id
                                    })"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-500" />
                                    Ver detalle
                                </Link>
                                <button
                                    @click="handleDeleteEvaluation(evaluation.id)"
                                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    <TrashIcon class="h-5 w-5 mr-2 text-red-500" />
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
            No se encontraron evaluaciones para esta organización.
        </div>

        <div v-if="showReassignModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Reasignar Evaluaciones</h3>
                    <button @click="showReassignModal = false" class="text-gray-400 hover:text-gray-600">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Selecciona la nueva organización a la que se asignarán las <strong>{{ evaluations?.length || 0 }}</strong> evaluaciones actualmente mostradas de <strong>{{ organization.name }}</strong>.
                    </p>

                    <div>
                        <label for="newOrgSelect" class="block text-sm font-medium text-gray-700 mb-1">Nueva Organización:</label>
                        <select
                            id="newOrgSelect"
                            v-model="selectedNewOrgId"
                            :disabled="isLoadingOrgs || isReassigning"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md disabled:bg-gray-100"
                        >
                            <option :value="null" disabled>-- Selecciona --</option>
                            <option v-if="isLoadingOrgs" :value="null" disabled>Cargando...</option>
                            <template v-else>
                                <option v-if="fetchError" :value="null" disabled>{{ fetchError }}</option>
                                <option v-for="org in availableOrganizations" :key="org.id" :value="org.id">
                                    {{ org.name }}
                                </option>
                                <option v-if="!isLoadingOrgs && availableOrganizations.length === 0 && !fetchError" :value="null" disabled>No hay otras organizaciones disponibles.</option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                    <button
                        @click="showReassignModal = false"
                        type="button"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        :disabled="isReassigning"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="handleReassignConfirm"
                        type="button"
                        class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50"
                        :disabled="!selectedNewOrgId || isLoadingOrgs || isReassigning || availableOrganizations.length === 0"
                    >
                        <span v-if="isReassigning">Reasignando...</span>
                        <span v-else>Confirmar Reasignación</span>
                    </button>
                </div>
            </div>
        </div>
    </Dashboard>
</template>