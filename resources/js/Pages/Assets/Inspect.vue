<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { FireIcon, MapPinIcon, BuildingOfficeIcon, ClipboardDocumentCheckIcon, ArrowRightOnRectangleIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    isInspector: {
        type: Boolean,
        default: false,
    },
})

const selectedInspection = ref(null)

const selectInspection = (inspection) => {
    selectedInspection.value = inspection
}

const closeInspectionDetail = () => {
    selectedInspection.value = null
}
</script>

<template>
    <Head :title="'Extintor ' + asset.serial_number" />

    <div class="min-h-screen bg-gray-100">
        <div class="py-10">
            <header class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-red-100 flex items-center justify-center">
                        <FireIcon class="h-10 w-10 text-red-600" />
                    </div>
                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                        Extintor
                    </h1>
                    <p class="mt-1 text-lg text-gray-600">
                        {{ asset.serial_number }}
                    </p>
                </div>
            </header>

            <main class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="mt-8 bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <BuildingOfficeIcon class="h-5 w-5 text-gray-400" />
                                    Organización
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.organization?.name || 'No especificada' }}
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Número Consecutivo</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.consecutive_number }}
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                    <MapPinIcon class="h-5 w-5 text-gray-400" />
                                    Ubicación
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.location }}
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Capacidad</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.capacity || 'No especificada' }}
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Tipo de Extintor</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.asset_type || 'No especificado' }}
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Clase de Fuego</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.fire_class || 'No especificada' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Botón para nueva inspección (solo para inspectores autenticados) -->
                <div v-if="isInspector" class="mt-6">
                    <Link
                        :href="route('assets.inspections.create', asset.id)"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                    >
                        <ClipboardDocumentCheckIcon class="h-5 w-5" />
                        Nueva Inspección
                    </Link>
                </div>

                <!-- Mensaje para usuarios no autenticados -->
                <div v-else-if="!isAuthenticated" class="mt-6 rounded-md bg-yellow-50 p-4 border border-yellow-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <ArrowRightOnRectangleIcon class="h-5 w-5 text-yellow-400" />
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-yellow-800">Iniciar Sesión para Inspeccionar</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p class="mb-3">
                                    Para realizar una inspección de este extintor, debe iniciar sesión con una cuenta de inspector autorizado.
                                </p>
                                <Link
                                    :href="route('login') + '?redirect=' + encodeURIComponent(route('assets.inspections.create', asset.id))"
                                    class="inline-flex items-center gap-2 rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                                >
                                    <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                    Iniciar Sesión
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de inspecciones -->
                <div v-if="asset.inspections && asset.inspections.length > 0" class="mt-6 bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">
                            Últimas Inspecciones
                        </h3>
                        <ul role="list" class="divide-y divide-gray-200">
                            <li 
                                v-for="inspection in asset.inspections" 
                                :key="inspection.id" 
                                class="py-4 cursor-pointer hover:bg-gray-50 -mx-4 px-4 transition-colors rounded-lg"
                                @click="selectInspection(inspection)"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ inspection.inspector_name }}</p>
                                        <p class="text-sm text-gray-500">{{ new Date(inspection.inspection_date).toLocaleDateString('es-MX') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Completada
                                        </span>
                                        <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Detalle de la inspección seleccionada -->
                <div v-if="selectedInspection" class="mt-6 bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">
                                Detalle de Inspección
                            </h3>
                            <button
                                @click="closeInspectionDetail"
                                class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Información del inspector -->
                        <div class="mb-4 pb-4 border-b border-gray-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Inspector</p>
                                    <p class="text-sm text-gray-900">{{ selectedInspection.inspector_name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Fecha de Inspección</p>
                                    <p class="text-sm text-gray-900">{{ new Date(selectedInspection.inspection_date).toLocaleDateString('es-MX') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist results -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Procedimientos de Revisión</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No.</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr 
                                            v-for="(item, index) in selectedInspection.checklist_results" 
                                            :key="index"
                                            class="hover:bg-gray-50"
                                        >
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ index }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">
                                                {{ item.date ? new Date(item.date).toLocaleDateString('es-MX') : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ item.result || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Seguimiento de anomalías -->
                        <div v-if="selectedInspection.anomalies_followup" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Seguimiento de Anomalías</h4>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ selectedInspection.anomalies_followup }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-md bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Información</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>
                                    Este extintor está registrado para inspección bajo la norma NOM-002-STPS.
                                    <span v-if="isInspector">Utiliza el botón "Nueva Inspección" para registrar una revisión.</span>
                                    <span v-else-if="isAuthenticated">Solo inspectores autorizados pueden realizar revisiones.</span>
                                    <span v-else>Inicia sesión como inspector para realizar revisiones.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
