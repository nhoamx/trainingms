<script setup>

import Dashboard from "../../Layouts/Dashboard.vue";
import { useForm } from '@inertiajs/vue3'
import {reactive, ref, defineProps} from "vue";
import FormInput from "../../Components/FormInput.vue";
import FormSelect from "../../Components/FormSelect.vue";
import { Switch } from '@headlessui/vue'

const enabled = ref(false)

const { roles, organizations, user } = defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    organizations: {
        type: Array,
        required: true,
    },
})
const form = useForm({
    _method: 'put',
    name: user.name || null,
    email: user.email || '',
    role: user.roles && user.roles.length ? user.roles[0].id : '', // Si tiene roles, usamos el primero
    organization: user.organization ? user.organization.id : '', // Puede ser opcional
    reset_password: false,
})

const clientErrors = ref({})

// Objeto reactivo para validaciones de cliente (además de las de backend que Inertia maneja)
const errors = reactive({
    name: '',
    email: '',
    role: '',
})


const validate = () => {
    const errors = {}

    if (!form.name) {
        errors.name = 'El nombre del usuario es requerido.'
    }

    if (!form.email) {
        errors.email = 'El correo electrónico del usuario es requerido.'
    }

    if (!form.role) {
        errors.role = 'El rol del usuario es requerido.'
    }

    clientErrors.value = errors
    return Object.keys(errors).length === 0
}

const submit = () => {
    if (!validate()) {
        return
    }

    form.post(route('users.update', user), {
        preserveScroll: true,
        onSuccess: () => {
            // router.push(route('organizations.index'))
        }
    })
}


</script>

<template>
    <Dashboard>
        <div class="bg-white px-6 py-4">
            <!-- Se puede usar @submit.prevent en el form, pero aquí el botón lo maneja -->
            <form @submit.prevent="submit" enctype="multipart/form-data">
                <div class="space-y-12">
                    <!-- Sección de organización -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Actualizar usuario</h2>
                        <p class="mt-1 text-sm text-gray-600">Llena el formulario para actualizar la información del usuario.</p>
                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <!-- Campo: Nombre de la organización -->
                            <div class="sm:col-span-4">
                                <FormInput
                                    label="Nombre del usuario"
                                    id="name"
                                    name="name"
                                    autocomplete="name"
                                    v-model="form.name"
                                    :error="clientErrors.name || form.errors.name"
                                />
                            </div>
                            <div class="sm:col-span-4">
                                <FormInput
                                    label="Correo electrónico"
                                    id="email"
                                    name="email"
                                    autocomplete="email"
                                    v-model="form.email"
                                    :error="clientErrors.email || form.errors.email"
                                />
                            </div>
                            <div class="sm:col-span-4">
                                <FormSelect
                                    label="Rol"
                                    id="role"
                                    name="role"
                                    v-model="form.role"
                                    :options="roles"
                                    :error="clientErrors.role || form.errors.role"
                                />
                            </div>
                            <div class="sm:col-span-4">
                                <FormSelect
                                    label="Organicación (opcional)"
                                    id="organization"
                                    name="organization"
                                    v-model="form.organization"
                                    :options="organizations"
                                    error=""
                                />
                            </div>

                            <div class="sm:col-span-4">
                                <label :for="id" class="block text-sm font-medium leading-6 text-gray-900">
                                    Contraseña
                                </label>
                                <Switch
                                    v-model="form.reset_password"
                                    :class="[form.reset_password ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                    <span class="sr-only">Resetear contraseña</span>
                                    <span
                                        aria-hidden="true"
                                        :class="[form.reset_password ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"
                                    />
                                </Switch>
                                <p class="mt-2 text-sm text-gray-600">
                                    Marcar para resetear la contraseña automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón de envío -->
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        Guardar información
                    </button>
                </div>
            </form>
        </div>
    </Dashboard>
</template>

<style scoped>

</style>
