<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRightIcon, QrCodeIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import Dashboard from '../../Layouts/Dashboard.vue'
import EmptyState from '../../Components/EmptyState.vue'

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
                    <button
                        v-if="assets.length > 0"
                        @click="downloadAllQr"
                        type="button"
                        class="relative inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                        Descargar todos los QR
                    </button>
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
                            Número de Serie
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Ubicación
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                            Capacidad
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
                            {{ asset.serial_number }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.location }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ asset.capacity || '-' }}
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
    </Dashboard>
</template>
