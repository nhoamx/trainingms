<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Dashboard from '../../Layouts/Dashboard.vue'
import FormInput from "../../Components/FormInput.vue"

const props = defineProps({
    title: String,
    organization: Object
})

const form = useForm({
    code: '',
    name: '',
    type: 'branch',
    is_primary: false,
    legal_name: '',
    tax_id: '',
    employer_registration: '',
    street_address: '',
    neighborhood: '',
    postal_code: '',
    municipality: '',
    state: '',
    phone: '',
    email: ''
})

const clientErrors = ref({})

const workCenterTypes = [
    { value: 'headquarters', label: 'Matriz' },
    { value: 'plant', label: 'Planta' },
    { value: 'branch', label: 'Sucursal' },
    { value: 'warehouse', label: 'Almacén' },
    { value: 'office', label: 'Oficina' },
    { value: 'other', label: 'Otro' }
]

const validate = () => {
    const errors = {}

    if (!form.code || form.code.length !== 4) {
        errors.code = 'El código debe tener exactamente 4 caracteres.'
    }

    if (!form.name) {
        errors.name = 'El nombre del centro de trabajo es requerido.'
    }

    if (!form.type) {
        errors.type = 'El tipo de centro de trabajo es requerido.'
    }

    clientErrors.value = errors
    return Object.keys(errors).length === 0
}

const submit = () => {
    if (validate()) {
        form.post(route('organizations.work-centers.store', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
            }
        })
    }
}
</script>

<template>
    <Dashboard>
        <div class="bg-white px-6 py-4">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Organización: <span class="font-medium">{{ organization.name }}</span>
                </p>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-12">
                    <!-- Información Básica -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Información Básica</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Datos identificativos del centro de trabajo.
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- Código -->
                            <div class="sm:col-span-2">
                                <FormInput
                                    label="Código *"
                                    hint="Código único de 4 caracteres"
                                    id="code"
                                    v-model="form.code"
                                    maxlength="4"
                                    :error="clientErrors.code || form.errors.code"
                                    placeholder="Ej: S001"
                                />
                            </div>

                            <!-- Tipo -->
                            <div class="sm:col-span-2">
                                <label for="type" class="block text-sm font-medium text-gray-900">
                                    Tipo de Centro *
                                </label>
                                <div class="mt-2">
                                    <select
                                        id="type"
                                        v-model="form.type"
                                        class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm"
                                    >
                                        <option v-for="type in workCenterTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                    <p v-if="clientErrors.type || form.errors.type" class="mt-1 text-xs text-red-500">
                                        {{ clientErrors.type || form.errors.type }}
                                    </p>
                                </div>
                            </div>

                            <!-- Es principal -->
                            <div class="sm:col-span-2">
                                <div class="flex items-center h-full pt-8">
                                    <input
                                        id="is_primary"
                                        type="checkbox"
                                        v-model="form.is_primary"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <label for="is_primary" class="ml-2 block text-sm text-gray-900">
                                        Centro principal
                                    </label>
                                </div>
                            </div>

                            <!-- Nombre -->
                            <div class="sm:col-span-6">
                                <FormInput
                                    label="Nombre del Centro de Trabajo *"
                                    hint="Nombre descriptivo del centro"
                                    id="name"
                                    v-model="form.name"
                                    :error="clientErrors.name || form.errors.name"
                                    placeholder="Ej: Sucursal Norte"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Datos Legales (Opcional) -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Datos Legales</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Información legal y fiscal (opcional).
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- Razón Social -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Razón Social"
                                    id="legal_name"
                                    v-model="form.legal_name"
                                    :error="form.errors.legal_name"
                                />
                            </div>

                            <!-- RFC -->
                            <div class="sm:col-span-2">
                                <FormInput
                                    label="RFC"
                                    id="tax_id"
                                    v-model="form.tax_id"
                                    maxlength="13"
                                    :error="form.errors.tax_id"
                                />
                            </div>

                            <!-- Registro Patronal -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Registro Patronal"
                                    id="employer_registration"
                                    v-model="form.employer_registration"
                                    :error="form.errors.employer_registration"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Domicilio (Opcional) -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Domicilio</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Dirección física del centro de trabajo (opcional).
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- Calle y número -->
                            <div class="sm:col-span-6">
                                <FormInput
                                    label="Calle y Número"
                                    id="street_address"
                                    v-model="form.street_address"
                                    :error="form.errors.street_address"
                                />
                            </div>

                            <!-- Colonia -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Colonia"
                                    id="neighborhood"
                                    v-model="form.neighborhood"
                                    :error="form.errors.neighborhood"
                                />
                            </div>

                            <!-- Código Postal -->
                            <div class="sm:col-span-1">
                                <FormInput
                                    label="C.P."
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    maxlength="10"
                                    :error="form.errors.postal_code"
                                />
                            </div>

                            <!-- Municipio -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Municipio"
                                    id="municipality"
                                    v-model="form.municipality"
                                    :error="form.errors.municipality"
                                />
                            </div>

                            <!-- Estado -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Estado"
                                    id="state"
                                    v-model="form.state"
                                    :error="form.errors.state"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Contacto (Opcional) -->
                    <div class="pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Información de Contacto</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Datos de contacto del centro (opcional).
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- Teléfono -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Teléfono"
                                    id="phone"
                                    type="tel"
                                    v-model="form.phone"
                                    maxlength="20"
                                    :error="form.errors.phone"
                                />
                            </div>

                            <!-- Email -->
                            <div class="sm:col-span-3">
                                <FormInput
                                    label="Email"
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :error="form.errors.email"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <Link
                        :href="route('organizations.edit', organization.id)"
                        class="text-sm font-semibold text-gray-900"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar Centro de Trabajo' }}
                    </button>
                </div>
            </form>
        </div>
    </Dashboard>
</template>
