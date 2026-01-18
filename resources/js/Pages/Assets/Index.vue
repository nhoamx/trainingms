<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ChevronRightIcon, QrCodeIcon, ArrowDownTrayIcon, ArrowUpTrayIcon, EllipsisVerticalIcon, ClipboardDocumentCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Dashboard from '../../Layouts/Dashboard.vue'
import EmptyState from '../../Components/EmptyState.vue'
import ImportDataModal from '../../Components/ImportDataModal.vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    assets: {
        type: Array,
        default: () => [],
    },
})

const showImportModal = ref(false)
const showActionsMenu = ref(false)
const showBatchInspectionModal = ref(false)

const batchInspectionForm = useForm({
    inspection_date: new Date().toISOString().split('T')[0]
})

function downloadQr(asset) {
    window.location.href = route('organizations.assets.qr.download', {
        organization: props.organization.id,
        asset: asset.id
    })
}

function downloadAllQr() {
    window.location.href = route('organizations.assets.qr.download-all', {
        organization: props.organization.id
    })
}

function submitBatchInspections() {
    batchInspectionForm.post(route('organizations.assets.batch-inspections', props.organization.id), {
        onSuccess: () => {
            showBatchInspectionModal.value = false
            batchInspectionForm.reset()
        }
    })
}

function closeBatchInspectionModal() {
    showBatchInspectionModal.value = false
    batchInspectionForm.reset()
}

</script>

<template>
    <Dashboard>
        <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6">
            <div class="-ml-4 -mt-2 flex flex-wrap items-center justify-between sm:flex-nowrap">
                <div class="ml-4 mt-2">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-2">
                            <li>
                                <Link :href="route('organizations.index')" class="text-sm text-gray-500 hover:text-gray-700">
                                    Organizaciones
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="h-4 w-4 flex-shrink-0 text-gray-400" aria-hidden="true" />
                                    <Link :href="route('organizations.edit', organization)" class="ml-2 text-sm text-gray-500 hover:text-gray-700">
                                        {{ organization.name }}
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="h-4 w-4 flex-shrink-0 text-gray-400" aria-hidden="true" />
                                    <span class="ml-2 text-sm font-medium text-gray-900">Extintores</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h3 class="mt-2 text-base font-semibold leading-6 text-gray-900">
                        Lista de Extintores
                    </h3>
                </div>
                <div class="ml-4 mt-2 flex flex-shrink-0 gap-3">
                    <!-- Dropdown de Otras acciones -->
                    <div class="relative">
                        <button
                            @click="showActionsMenu = !showActionsMenu"
                            type="button"
                            class="relative inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                        >
                            Otras acciones
                            <EllipsisVerticalIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="showActionsMenu"
                            class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                        >
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <button
                                    v-if="assets.length > 0"
                                    @click="downloadAllQr(); showActionsMenu = false"
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                    role="menuitem"
                                >
                                    <ArrowDownTrayIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                    Descargar todos los QR
                                </button>
                                <button
                                    @click="showImportModal = true; showActionsMenu = false"
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                    role="menuitem"
                                >
                                    <ArrowUpTrayIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                    Importar extintores
                                </button>
                                <button
                                    v-if="assets.length > 0"
                                    @click="showBatchInspectionModal = true; showActionsMenu = false"
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                                    role="menuitem"
                                >
                                    <ClipboardDocumentCheckIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                    Generar inspecciones en lote
                                </button>
                            </div>
                        </div>
                    </div>
                    <Link
                        :href="route('organizations.assets.create', organization)"
                        class="relative inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        Nuevo extintor
                    </Link>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="!assets || assets.length === 0"
            title="Sin extintores registrados"
            text="Comienza agregando el primer extintor de la organización."
            buttonText="Agregar extintor"
            :buttonAction="() => $inertia.visit(route('organizations.assets.create', organization))"
            class="py-6 bg-white sm:rounded-b-xl"
        />

        <div v-else class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-b-xl">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                            N° Consecutivo
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Número de Serie
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Ubicación
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Capacidad
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Tipo
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Clase de Fuego
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                            {{ asset.consecutive_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.serial_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.location }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.capacity || '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.asset_type || '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.fire_class || '-' }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="downloadQr(asset)"
                                    type="button"
                                    class="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900"
                                    title="Descargar QR"
                                >
                                    <QrCodeIcon class="h-5 w-5" aria-hidden="true" />
                                    <span class="sr-only">QR</span>
                                </button>
                                <Link
                                    :href="route('organizations.assets.edit', { organization: organization.id, asset: asset.id })"
                                    class="text-indigo-600 hover:text-indigo-900"
                                >
                                    Editar
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Overlay para cerrar el dropdown al hacer click fuera -->
        <div
            v-if="showActionsMenu"
            @click="showActionsMenu = false"
            class="fixed inset-0 z-0"
        />

        <!-- Batch Inspection Modal -->
        <div v-if="showBatchInspectionModal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div class="absolute right-0 top-0 pr-4 pt-4">
                            <button
                                type="button"
                                @click="closeBatchInspectionModal"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <span class="sr-only">Cerrar</span>
                                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <ClipboardDocumentCheckIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
                                    Generar Inspecciones en Lote
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Esta acción creará inspecciones con estado "OK" para todos los extintores de la organización que no tengan inspección en la fecha seleccionada.
                                    </p>
                                    <form @submit.prevent="submitBatchInspections">
                                        <div>
                                            <label for="inspection_date" class="block text-sm font-medium leading-6 text-gray-900">
                                                Fecha de Inspección <span class="text-red-500">*</span>
                                            </label>
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    id="inspection_date"
                                                    v-model="batchInspectionForm.inspection_date"
                                                    required
                                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                    :class="{ 'ring-red-300': batchInspectionForm.errors.inspection_date }"
                                                />
                                            </div>
                                            <p v-if="batchInspectionForm.errors.inspection_date" class="mt-2 text-sm text-red-600">
                                                {{ batchInspectionForm.errors.inspection_date }}
                                            </p>
                                        </div>
                                        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                                            <button
                                                type="button"
                                                @click="closeBatchInspectionModal"
                                                class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto"
                                            >
                                                Cancelar
                                            </button>
                                            <button
                                                type="submit"
                                                :disabled="batchInspectionForm.processing"
                                                class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:w-auto disabled:opacity-50"
                                            >
                                                <span v-if="batchInspectionForm.processing">Generando...</span>
                                                <span v-else>Generar Inspecciones</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <ImportDataModal
            :show="showImportModal"
            title="Importar Extintores"
            :has-data="assets.length > 0"
            :download-route="route('organizations.assets.template', organization.id)"
            :upload-route="route('organizations.assets.import', organization.id)"
            @close="showImportModal = false"
        />
    </Dashboard>
</template>
