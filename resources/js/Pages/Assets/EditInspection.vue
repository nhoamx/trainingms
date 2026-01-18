<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ArrowLeftIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { ref } from 'vue'

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
    inspection: {
        type: Object,
        required: true,
    },
    checklist: {
        type: Object,
        required: true,
    },
})

const showDeleteModal = ref(false)

const form = useForm({
    inspector_name: props.inspection.inspector_name,
    inspection_date: props.inspection.inspection_date,
    extinguisher_weight: props.inspection.extinguisher_weight || '',
    checklist_results: props.inspection.checklist_results,
    anomalies_followup: props.inspection.anomalies_followup || '',
})

const submit = () => {
    form.put(route('assets.inspections.update', props.inspection.id))
}

const deleteInspection = () => {
    form.delete(route('assets.inspections.destroy', props.inspection.id))
}
</script>

<template>
    <Head :title="'Editar Inspección - Extintor ' + asset.consecutive_number" />

    <div class="min-h-screen bg-gray-50">
        <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
            <!-- Header -->
            <div class="mb-4 sm:mb-6">
                <Link
                    :href="route('organizations.assets.edit', { organization: asset.organization_id, asset: asset.id })"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-3 sm:mb-4 touch-manipulation"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Volver al extintor
                </Link>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Inspección</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Extintor {{ asset.consecutive_number }} - {{ asset.location }}
                        </p>
                    </div>
                    <button
                        @click="showDeleteModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                    >
                        <TrashIcon class="h-5 w-5" />
                        <span class="hidden sm:inline">Eliminar</span>
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Inspector Info -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Información del Inspector</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="inspector_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del Inspector <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="inspector_name"
                                v-model="form.inspector_name"
                                type="text"
                                required
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-3 px-4"
                            />
                            <p v-if="form.errors.inspector_name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.inspector_name }}
                            </p>
                        </div>

                        <div>
                            <label for="inspection_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Fecha de Inspección <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="inspection_date"
                                v-model="form.inspection_date"
                                type="date"
                                required
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-3 px-4"
                            />
                            <p v-if="form.errors.inspection_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.inspection_date }}
                            </p>
                        </div>

                        <div>
                            <label for="extinguisher_weight" class="block text-sm font-medium text-gray-700 mb-1">
                                Peso del Extintor (kg)
                            </label>
                            <input
                                id="extinguisher_weight"
                                v-model="form.extinguisher_weight"
                                type="text"
                                placeholder="Ej: 4.5 kg"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-3 px-4"
                            />
                            <p v-if="form.errors.extinguisher_weight" class="mt-1 text-sm text-red-600">
                                {{ form.errors.extinguisher_weight }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Checklist Items as Cards -->
                <div class="space-y-3">
                    <h3 class="text-base font-semibold text-gray-900 px-1">
                        Procedimientos de Revisión / Mantenimiento
                    </h3>
                    
                    <div 
                        v-for="(procedure, index) in checklist" 
                        :key="index"
                        class="bg-white shadow rounded-lg p-4"
                    >
                        <div class="flex items-start gap-3 mb-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold text-red-600">{{ index }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 leading-tight">
                                    {{ procedure }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 ml-11">
                            <div>
                                <label :for="'date_' + index" class="block text-xs font-medium text-gray-600 mb-1">
                                    Fecha
                                </label>
                                <input
                                    :id="'date_' + index"
                                    v-model="form.checklist_results[index].date"
                                    type="date"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-2.5 px-3"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">
                                    Estado
                                </label>
                                <div class="flex gap-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            :id="'status_ok_' + index"
                                            v-model="form.checklist_results[index].status"
                                            type="radio"
                                            :name="'status_' + index"
                                            value="ok"
                                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Todo OK</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            :id="'status_issue_' + index"
                                            v-model="form.checklist_results[index].status"
                                            type="radio"
                                            :name="'status_' + index"
                                            value="issue"
                                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Hay problema</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.checklist_results[index].status === 'issue'">
                                <label :for="'result_' + index" class="block text-xs font-medium text-gray-600 mb-1">
                                    Detalle del Problema <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    :id="'result_' + index"
                                    v-model="form.checklist_results[index].result"
                                    rows="3"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-2.5 px-3"
                                    placeholder="Describa el problema encontrado..."
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anomalies Followup -->
                <div class="bg-white shadow rounded-lg p-4">
                    <label for="anomalies_followup" class="block text-sm font-medium text-gray-700 mb-2">
                        Seguimiento de Anomalías
                    </label>
                    <textarea
                        id="anomalies_followup"
                        v-model="form.anomalies_followup"
                        rows="5"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-3 px-4"
                        placeholder="Describa las anomalías encontradas y el seguimiento necesario..."
                    />
                    <p v-if="form.errors.anomalies_followup" class="mt-1 text-sm text-red-600">
                        {{ form.errors.anomalies_followup }}
                    </p>
                </div>

                <!-- Footer Buttons -->
                <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-3 sm:mx-0 sm:rounded-lg sm:border-0 sm:shadow flex flex-col-reverse sm:flex-row gap-3">
                    <Link
                        :href="route('organizations.assets.edit', { organization: asset.organization_id, asset: asset.id })"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 touch-manipulation"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Actualizar Inspección</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <TrashIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                Eliminar inspección
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Estás seguro de que deseas eliminar esta inspección? Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="deleteInspection"
                            class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        >
                            Eliminar
                        </button>
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
