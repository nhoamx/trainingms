<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
    checklist: {
        type: Object,
        required: true,
    },
    canInspect: {
        type: Boolean,
        default: false,
    },
})

const form = useForm({
    inspector_name: '',
    inspection_date: new Date().toISOString().split('T')[0],
    checklist_results: {},
    anomalies_followup: '',
})

// Inicializar checklist_results con estructura vacía y fecha actual
const currentDate = new Date().toISOString().split('T')[0]
Object.keys(props.checklist).forEach(key => {
    form.checklist_results[key] = {
        date: currentDate,
        result: '',
    }
})

const submit = () => {
    form.post(route('assets.inspections.store', props.asset.id))
}
</script>

<template>
    <Head :title="'Nueva Inspección - Extintor ' + asset.consecutive_number" />

    <div v-if="canInspect" class="min-h-screen bg-gray-50">
        <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8 py-4 sm:py-8">
            <!-- Header -->
            <div class="mb-4 sm:mb-6">
                <a
                    :href="route('assets.inspect', asset.id)"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 mb-3 sm:mb-4 touch-manipulation"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Volver al extintor
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nueva Inspección</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Extintor {{ asset.consecutive_number }} - {{ asset.location }}
                </p>
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
                                <label :for="'result_' + index" class="block text-xs font-medium text-gray-600 mb-1">
                                    Resultados / Anomalías
                                </label>
                                <textarea
                                    :id="'result_' + index"
                                    v-model="form.checklist_results[index].result"
                                    rows="3"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-base py-2.5 px-3"
                                    placeholder="Observaciones..."
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
                    <a
                        :href="route('assets.inspect', asset.id)"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 touch-manipulation"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation"
                    >
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar Inspección</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Si no puede inspeccionar -->
    <div v-else class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white shadow sm:rounded-lg p-6 text-center">
            <div class="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Acceso Restringido</h3>
            <p class="text-sm text-gray-600 mb-6">
                Solo los inspectores autorizados pueden realizar inspecciones.
            </p>
            <a
                :href="route('assets.inspect', asset.id)"
                class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
            >
                <ArrowLeftIcon class="h-4 w-4" />
                Volver a los datos del extintor
            </a>
        </div>
    </div>
</template>
