<script setup>
import { ref } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { ChevronRightIcon, QrCodeIcon, TrashIcon } from '@heroicons/vue/24/outline'
import Dashboard from '../../Layouts/Dashboard.vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    asset: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    asset_type: props.asset.asset_type,
    serial_number: props.asset.serial_number,
    location: props.asset.location,
    capacity: props.asset.capacity || '',
    fire_class: props.asset.fire_class || '',
})

const capacityOptions = ['5 lbs', '10 lbs', '20 lbs', '30 lbs', '50 lbs']
const fireClassOptions = ['Clase A', 'Clase B', 'Clase C', 'Clase ABC', 'Clase BC', 'Clase K']

const showDeleteModal = ref(false)

function submit() {
    form.put(route('organizations.assets.update', {
        organization: props.organization.id,
        asset: props.asset.id
    }))
}

function downloadQr() {
    window.location.href = route('organizations.assets.qr.download', {
        organization: props.organization.id,
        asset: props.asset.id
    })
}

function deleteAsset() {
    router.delete(route('organizations.assets.destroy', {
        organization: props.organization.id,
        asset: props.asset.id
    }))
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
                                    <Link :href="route('organizations.assets.index', organization)" class="ml-2 text-sm text-gray-500 hover:text-gray-700">
                                        Extintores
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="h-4 w-4 flex-shrink-0 text-gray-400" aria-hidden="true" />
                                    <span class="ml-2 text-sm font-medium text-gray-900">{{ asset.serial_number }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h3 class="mt-2 text-base font-semibold leading-6 text-gray-900">
                        Editar Extintor
                    </h3>
                </div>
                <div class="ml-4 mt-2 flex flex-shrink-0 gap-3">
                    <button
                        @click="downloadQr"
                        type="button"
                        class="relative inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        <QrCodeIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                        Descargar QR
                    </button>
                    <button
                        @click="showDeleteModal = true"
                        type="button"
                        class="relative inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                    >
                        <TrashIcon class="h-5 w-5" aria-hidden="true" />
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-b-xl">
                <form @submit.prevent="submit" class="px-4 py-6 sm:px-6">
                    <div class="space-y-6">
                        <div>
                            <label for="serial_number" class="block text-sm font-medium leading-6 text-gray-900">
                                Número de Serie <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2">
                                <input
                                    type="text"
                                    id="serial_number"
                                    v-model="form.serial_number"
                                    placeholder="Ej: EXT-12345"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    :class="{ 'ring-red-300': form.errors.serial_number }"
                                />
                            </div>
                            <p v-if="form.errors.serial_number" class="mt-2 text-sm text-red-600">
                                {{ form.errors.serial_number }}
                            </p>
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-medium leading-6 text-gray-900">
                                Ubicación <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2">
                                <input
                                    type="text"
                                    id="location"
                                    v-model="form.location"
                                    placeholder="Ej: Oficina Principal - Pasillo 2"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    :class="{ 'ring-red-300': form.errors.location }"
                                />
                            </div>
                            <p v-if="form.errors.location" class="mt-2 text-sm text-red-600">
                                {{ form.errors.location }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="capacity" class="block text-sm font-medium leading-6 text-gray-900">
                                    Capacidad
                                </label>
                                <div class="mt-2">
                                    <select
                                        id="capacity"
                                        v-model="form.capacity"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    >
                                        <option value="">Seleccionar...</option>
                                        <option v-for="capacity in capacityOptions" :key="capacity" :value="capacity">
                                            {{ capacity }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="fire_class" class="block text-sm font-medium leading-6 text-gray-900">
                                    Clase de Fuego
                                </label>
                                <div class="mt-2">
                                    <select
                                        id="fire_class"
                                        v-model="form.fire_class"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    >
                                        <option value="">Seleccionar...</option>
                                        <option v-for="fireClass in fireClassOptions" :key="fireClass" :value="fireClass">
                                            {{ fireClass }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-x-4">
                        <Link
                            :href="route('organizations.assets.index', organization)"
                            class="text-sm font-semibold leading-6 text-gray-900"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Vista previa del QR</h4>
                <div class="flex justify-center">
                    <img
                        :src="route('organizations.assets.qr', { organization: organization.id, asset: asset.id })"
                        alt="QR Code"
                        class="w-48 h-48"
                    />
                </div>
                <p class="mt-4 text-xs text-center text-gray-500">
                    Este código QR identifica al extintor y puede ser escaneado para futuras inspecciones.
                </p>
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
                                    Eliminar extintor
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        ¿Estás seguro de que deseas eliminar este extintor? Esta acción no se puede deshacer.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button
                                type="button"
                                @click="deleteAsset"
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
    </Dashboard>
</template>
