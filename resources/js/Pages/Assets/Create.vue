<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import Dashboard from '../../Layouts/Dashboard.vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    asset_type: '',
    asset_category: 'extintor',
    consecutive_number: '',
    serial_number: '',
    location: '',
    capacity: '',
    fire_class: '',
})

const capacityOptions = ['5 lbs', '10 lbs', '20 lbs', '30 lbs', '50 lbs']
const assetTypeOptions = ['PQS', 'CO2', 'Agua', 'Espuma', 'Agente Limpio']
const fireClassOptions = ['Clase A', 'Clase B', 'Clase C', 'Clase ABC', 'Clase BC', 'Clase K']

function submit() {
    form.post(route('organizations.assets.store', props.organization))
}
</script>

<template>
    <Dashboard>
        <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6">
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
                            <span class="ml-2 text-sm font-medium text-gray-900">Nuevo</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h3 class="mt-2 text-base font-semibold leading-6 text-gray-900">
                Nuevo Extintor
            </h3>
        </div>

        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-b-xl">
            <form @submit.prevent="submit" class="px-4 py-6 sm:px-6">
                <div class="space-y-6">
                    <div>
                        <label for="consecutive_number" class="block text-sm font-medium leading-6 text-gray-900">
                            Número Consecutivo <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-2">
                            <input
                                type="text"
                                id="consecutive_number"
                                v-model="form.consecutive_number"
                                placeholder="Ej: 001, EXT-001"
                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                :class="{ 'ring-red-300': form.errors.consecutive_number }"
                            />
                        </div>
                        <p v-if="form.errors.consecutive_number" class="mt-2 text-sm text-red-600">
                            {{ form.errors.consecutive_number }}
                        </p>
                    </div>

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

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
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
                            <label for="asset_type" class="block text-sm font-medium leading-6 text-gray-900">
                                Tipo de Extintor
                            </label>
                            <div class="mt-2">
                                <select
                                    id="asset_type"
                                    v-model="form.asset_type"
                                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                                    <option value="">Seleccionar...</option>
                                    <option v-for="type in assetTypeOptions" :key="type" :value="type">
                                        {{ type }}
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
                        <span v-else>Guardar extintor</span>
                    </button>
                </div>
            </form>
        </div>
    </Dashboard>
</template>
