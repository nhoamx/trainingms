<script setup>
import { Head } from '@inertiajs/vue3'
import { FireIcon, MapPinIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
})
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
                                <dt class="text-sm font-medium text-gray-500">Clase de Fuego</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ asset.fire_class || 'No especificada' }}
                                </dd>
                            </div>
                        </dl>
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
                                    El sistema de inspecciones estará disponible próximamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
