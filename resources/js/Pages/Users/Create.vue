<script setup>

import Dashboard from "../../Layouts/Dashboard.vue";
import { useForm } from '@inertiajs/vue3'
import {reactive, ref, defineProps, computed, watch} from "vue";
import FormInput from "../../Components/FormInput.vue";
import FormSelect from "../../Components/FormSelect.vue";

const { roles, organizations } = defineProps({
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
    name: null,
    email: null,
    role: null,
    organization: null,
    work_centers: [],
})

const clientErrors = ref({})
const workCenters = ref([])
const loadingCenters = ref(false)

// Objeto reactivo para validaciones de cliente (además de las de backend que Inertia maneja)
const errors = reactive({
    name: '',
    email: '',
    role: '',
})

// Computed: Obtener el rol seleccionado
const selectedRole = computed(() => {
    return roles.find(r => r.value === form.role)
})

// Computed: ¿El rol requiere seleccionar organización?
const roleRequiresOrganization = computed(() => {
    if (!selectedRole.value) return false
    return ['organization', 'work_center_user'].includes(selectedRole.value.label)
})

// Computed: ¿El rol requiere seleccionar work centers?
const roleRequiresWorkCenters = computed(() => {
    return selectedRole.value?.label === 'work_center_user'
})

// Watch: Cargar work centers cuando cambia la organización
watch(() => form.organization, async (newOrgId) => {
    if (!newOrgId || !roleRequiresWorkCenters.value) {
        workCenters.value = []
        form.work_centers = []
        return
    }

    loadingCenters.value = true
    try {
        const response = await fetch(`/api/organizations/${newOrgId}/work-centers`)
        workCenters.value = await response.json()

        // Si solo hay 1 centro, auto-seleccionar
        if (workCenters.value.length === 1) {
            form.work_centers = [workCenters.value[0].value]
        }
    } catch (error) {
        console.error('Error cargando centros:', error)
    } finally {
        loadingCenters.value = false
    }
})

// Watch: Limpiar centros si cambia de rol
watch(() => form.role, () => {
    if (!roleRequiresWorkCenters.value) {
        form.work_centers = []
    }
    // Limpiar organización si es admin
    if (selectedRole.value?.label === 'admin') {
        form.organization = null
    }
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

    form.post(route('users.store'), {
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
                        <h2 class="text-base font-semibold text-gray-900">Añadir nuevo usuario</h2>
                        <p class="mt-1 text-sm text-gray-600">Llena el formulario para añadir un nuevo usaurio al sistema.</p>
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

                            <!-- Organización (solo si no es admin) -->
                            <div v-if="roleRequiresOrganization" class="sm:col-span-4">
                                <FormSelect
                                    label="Organización"
                                    id="organization"
                                    name="organization"
                                    v-model="form.organization"
                                    :options="organizations"
                                    :error="form.errors.organization"
                                />
                            </div>

                            <!-- Work Centers (solo para work_center_user) -->
                            <div v-if="roleRequiresWorkCenters && form.organization" class="sm:col-span-4">
                                <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">
                                    Centros de Trabajo Asignados
                                </label>

                                <div v-if="loadingCenters" class="text-sm text-gray-500">
                                    Cargando centros...
                                </div>

                                <div v-else-if="workCenters.length === 0" class="text-sm text-gray-500">
                                    Esta organización no tiene centros de trabajo.
                                </div>

                                <div v-else-if="workCenters.length === 1" class="text-sm text-gray-600 bg-green-50 p-3 rounded">
                                    ✓ Auto-asignado: {{ workCenters[0].label }}
                                </div>

                                <!-- Multi-select si hay múltiples centros -->
                                <div v-else class="space-y-2 border border-gray-300 rounded-md p-4">
                                    <div v-for="center in workCenters" :key="center.value" class="flex items-center">
                                        <input
                                            :id="`center-${center.value}`"
                                            v-model="form.work_centers"
                                            type="checkbox"
                                            :value="center.value"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                        />
                                        <label :for="`center-${center.value}`" class="ml-2 text-sm text-gray-700">
                                            {{ center.label }}
                                        </label>
                                    </div>
                                </div>

                                <p class="mt-1 text-xs text-gray-500">
                                    Selecciona los centros a los que este usuario tendrá acceso
                                </p>
                            </div>

                            <div class="sm:col-span-4">
                                <label :for="id" class="block text-sm font-medium leading-6 text-gray-900">
                                    Contrasñea
                                </label>
                                <p class="text-gray-600 text-sm">La contraseña se generará de forma automatica, esta se le compartira al usuario y el podra actualizarla al iniciar sesión.</p>
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
