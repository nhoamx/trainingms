<script setup>
import { reactive, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { PhotoIcon, PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/solid'
import Dashboard from '../../Layouts/Dashboard.vue'
import Alert from '../../Components/Alert.vue'
import { defineProps } from 'vue'

// Recibimos la organización desde el backend
const { organization } = defineProps({
    organization: {
        type: Object,
        required: true,
    },
});

// Creamos el formulario con datos iniciales basados en la organización
const form = useForm({
    _method: 'put', // Método PUT para actualizar
    name: organization.name || '',
    logo: null, // Si el usuario sube uno nuevo, se actualizará; de lo contrario, se mantiene el actual.
});

// Manejo de errores de validación en el front
const clientErrors = ref({})

// Objeto reactivo para validaciones de cliente (además de las de backend que Inertia maneja)
const errors = ref({
    name: '',
    logo: '',
})

// Estado para controlar las pestañas activas
const activeTab = ref('info') // Opciones: 'info', 'positions', 'areas'

// Formularios para puestos y áreas
const positionForm = useForm({
    organization_id: organization.id,
    name: '',
})

const areaForm = useForm({
    organization_id: organization.id,
    name: '',
})

// Estado para mostrar/ocultar modal de confirmación
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const deleteType = ref('') // 'position' o 'area'

// Función para cambiar entre pestañas
const changeTab = (tab) => {
    activeTab.value = tab
}

// Funciones para puestos (ocupaciones)
const addPosition = () => {
    // Validación simple
    if (!positionForm.name.trim()) {
        positionForm.setError('name', 'El nombre del puesto es requerido.')
        return
    }
    
    positionForm.post(route('occupation-positions.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            // Si tenemos una respuesta con el nuevo puesto
            if (response && response.position) {
                // Añadimos el nuevo puesto a la lista de manera reactiva
                if (!organization.occupation_positions) {
                    organization.occupation_positions = []
                }
                organization.occupation_positions.push(response.position)
            } else {
                // Si no recibimos el puesto en la respuesta, recargamos la página
                router.reload()
            }
            positionForm.reset()
        },
    })
}

const deletePosition = (position) => {
    itemToDelete.value = position
    deleteType.value = 'position'
    showDeleteModal.value = true
}

const deleteArea = (area) => {
    itemToDelete.value = area
    deleteType.value = 'area'
    showDeleteModal.value = true
}

const confirmDelete = () => {
    if (deleteType.value === 'position') {
        router.delete(route('occupation-positions.destroy', itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                itemToDelete.value = null
                // Recargar para actualizar la lista
                router.reload()
            },
            onError: (errors) => {
                // Si hay errores, mostramos una alerta
                alert('Error al eliminar el puesto: ' + Object.values(errors).join('\n'))
                showDeleteModal.value = false
                itemToDelete.value = null
            }
        })
    } else if (deleteType.value === 'area') {
        router.delete(route('department-areas.destroy', itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                itemToDelete.value = null
                // Recargar para actualizar la lista
                router.reload()
            },
            onError: (errors) => {
                // Si hay errores, mostramos una alerta
                alert('Error al eliminar el departamento: ' + Object.values(errors).join('\n'))
                showDeleteModal.value = false
                itemToDelete.value = null
            }
        })
    }
}

const cancelDelete = () => {
    showDeleteModal.value = false
    itemToDelete.value = null
}

// Funciones para áreas
const addArea = () => {
    // Validación simple
    if (!areaForm.name.trim()) {
        areaForm.setError('name', 'El nombre del departamento es requerido.')
        return
    }
    
    areaForm.post(route('department-areas.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            // Si tenemos una respuesta con el nuevo departamento
            if (response && response.area) {
                // Añadimos el nuevo departamento a la lista de manera reactiva
                if (!organization.department_areas) {
                    organization.department_areas = []
                }
                organization.department_areas.push(response.area)
            } else {
                // Si no recibimos el departamento en la respuesta, recargamos la página
                router.reload()
            }
            areaForm.reset()
        },
    })
}
// Funciones para manejo de archivos

function handleFileDrop(e) {
    e.preventDefault()
    e.stopPropagation()
    const files = e.dataTransfer.files
    if (files.length > 0) {
        form.logo = files[0]
    }
}

function handleDragOver(e) {
    e.preventDefault()
    e.stopPropagation()
}

function handleFileUpload(e) {
    const files = e.target.files
    if (files.length > 0) {
        form.logo = files[0]
    }
}

// Validación simple del formulario en el front
const validate = () => {
    const errs = {}
    if (!form.name) {
        errs.name = 'El nombre de la organización es requerido.'
    }
    clientErrors.value = errs
    return Object.keys(errs).length === 0
}

// Función para enviar el formulario (actualización de la organización)
const submit = () => {
    if (validate()) {
        // Se usa form.post (con _method put) para actualizar
        form.post(route('organizations.update', organization), {
            preserveScroll: true,   // Mantiene la posición en la página
            forceFormData: true,     // Necesario para enviar archivos
            onSuccess: () => {
                // Aquí podrías, por ejemplo, limpiar campos o mostrar un mensaje de éxito
            },
        });
    }
}

// Función para alternar el estado (soft delete o restaurar)
// Se asume que existe la ruta 'organizations.toggle' que se encarga de aplicar o revertir el soft delete
const toggleOrganization = () => {
    // Si organization.deleted_at existe, la organización está deshabilitada
    const isDisabled = organization.deleted_at ? true : false;
    const confirmationMessage = isDisabled ?
        "¿Estás seguro que deseas activar esta organización?" :
        "¿Estás seguro que deseas deshabilitar esta organización?";
    if (!confirm(confirmationMessage)) {
        return;
    }

    if (isDisabled) {
        // Restaurar organización
        form.post(route('organizations.restore', organization));
    } else {
        // Deshabilitar organización
        form.delete(route('organizations.destroy', organization));
    }
};

</script>

<template>
    <Dashboard>
        <div class="bg-white px-4 sm:px-6 py-4">
            <Alert
                v-if="$page.props.flash"
                :type="$page.props.flash.type"
                :title="$page.props.flash.title"
                :message="$page.props.flash.message"
                class="my-4"
            />
            
            <!-- Pestañas de navegación -->
            <div class="border-b border-gray-200 mb-6">
                <div class="flex overflow-x-auto whitespace-nowrap py-2 space-x-4">
                    <button 
                        @click="changeTab('info')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'info' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Información
                    </button>
                    <button 
                        @click="changeTab('positions')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'positions' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Puestos
                    </button>
                    <button 
                        @click="changeTab('areas')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'areas' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Departamentos
                    </button>
                </div>
            </div>

            <!-- Contenido de la pestaña activa -->
            <div v-if="activeTab === 'info'">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="space-y-12">
                        <!-- Sección de Organización -->
                        <div class="border-b border-gray-900/10 pb-12">
                            <h2 class="text-base font-semibold text-gray-900">Organización</h2>
                            <p class="mt-1 text-sm text-gray-600">Edita la información de la organización.</p>
                            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                                <!-- Campo: Nombre de la organización -->
                                <div class="sm:col-span-4">
                                    <label for="name" class="block text-sm font-medium text-gray-900">
                                        Nombre de la organización
                                    </label>
                                    <div class="mt-2">
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            v-model="form.name"
                                            autocomplete="organization-name"
                                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                        />
                                        <p v-if="clientErrors.name || form.errors.name" class="mt-1 text-xs text-red-500">
                                            {{ clientErrors.name || form.errors.name }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Campo: Subir logotipo (opcional) -->
                                <div class="col-span-full">
                                    <label for="cover-photo" class="block text-sm font-medium text-gray-900">
                                        Subir logotipo (opcional)
                                    </label>
                                    <div
                                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-8"
                                        @dragover.prevent="handleDragOver"
                                        @drop="handleFileDrop"
                                    >
                                        <div class="text-center">
                                            <div v-if="form.logo || organization.logo" class="mt-2 flex flex-col items-center gap-y-4">
                                                <p class="text-lg text-gray-950">Logo actual:</p>
                                                <img :src="`/${organization.logo}`" alt="Logo de la organización" class="h-18 w-auto" />
                                            </div>
                                            <PhotoIcon v-else class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                                            <div class="mt-4 flex flex-wrap text-sm text-gray-600 justify-center">
                                                <label
                                                    for="file-upload"
                                                    class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
                                                >
                                                    <span>Da clic aquí</span>
                                                    <input
                                                        id="file-upload"
                                                        name="logo"
                                                        type="file"
                                                        class="sr-only"
                                                        @change="handleFileUpload"
                                                        accept="image/png, image/jpeg, image/gif"
                                                    />
                                                </label>
                                                <p class="pl-1">para actualizar el logotipo o arrástra la imagen dentro del recuadro.</p>
                                            </div>
                                            <p class="text-xs text-gray-600">PNG, JPG, GIF hasta 10MB</p>
                                            <p v-if="errors.logo || form.errors.logo" class="mt-1 text-xs text-red-500">
                                                {{ errors.logo || form.errors.logo }}
                                            </p>
                                            <!-- Si se ha seleccionado uno nuevo, mostramos el nombre del archivo -->
                                            <div v-if="form.logo" class="mt-2">
                                                <p class="text-sm text-gray-700">
                                                    Archivo seleccionado: {{ form.logo.name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de envío -->
                    <div class="mt-6 flex items-center justify-end gap-x-4">
                        <button
                            type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Guardar información
                        </button>
                        <button
                            type="button"
                            @click="toggleOrganization"
                            class="rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                            :class="organization.deleted_at ? 'bg-green-600 hover:bg-green-500' : 'bg-red-600 hover:bg-red-500'"
                        >
                            {{ organization.deleted_at ? 'Activar organización' : 'Deshabilitar organización' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña de Puestos/Ocupaciones -->
            <div v-else-if="activeTab === 'positions'" class="space-y-6">
                <div class="border-b border-gray-900/10 pb-6">
                    <h2 class="text-base font-semibold text-gray-900">Puestos de la organización</h2>
                    <p class="mt-1 text-sm text-gray-600">Gestiona los puestos/ocupaciones disponibles en esta organización.</p>
                    
                    <!-- Formulario para agregar nuevo puesto -->
                    <div class="mt-6 border rounded-lg p-4 bg-gray-50">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Agregar nuevo puesto</h3>
                        <form @submit.prevent="addPosition" class="space-y-4">
                            <div>
                                <label for="position-name" class="block text-sm font-medium text-gray-700">Nombre del puesto</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        type="text"
                                        id="position-name"
                                        v-model="positionForm.name"
                                        placeholder="Ej: Gerente de Operaciones"
                                        class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                    />
                                </div>
                                <p v-if="positionForm.errors.name" class="mt-1 text-xs text-red-500">
                                    {{ positionForm.errors.name }}
                                </p>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                    :disabled="positionForm.processing"
                                >
                                    <PlusIcon class="-ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" />
                                    Agregar puesto
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de puestos existentes -->
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Puestos registrados</h3>
                        <div v-if="organization.occupation_positions && organization.occupation_positions.length > 0" class="overflow-hidden bg-white shadow sm:rounded-md">
                            <ul role="list" class="divide-y divide-gray-200">
                                <li v-for="position in organization.occupation_positions" :key="position.id" class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6">
                                    <div class="flex min-w-0 gap-x-4">
                                        <div class="min-w-0 flex-auto">
                                            <p class="text-sm font-semibold leading-6 text-gray-900">{{ position.name }}</p>
                                            <p class="mt-1 flex text-xs leading-5 text-gray-500">
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    ID: {{ position.identifier }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-x-2">
                                        <button 
                                            @click="deletePosition(position)"
                                            type="button" 
                                            class="rounded-full bg-white p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                        >
                                            <TrashIcon class="h-5 w-5" aria-hidden="true" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay puestos registrados</h3>
                                <p class="mt-1 text-sm text-gray-500">Comienza agregando un nuevo puesto para esta organización.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Departamentos/Áreas -->
            <div v-else-if="activeTab === 'areas'" class="space-y-6">
                <div class="border-b border-gray-900/10 pb-6">
                    <h2 class="text-base font-semibold text-gray-900">Departamentos de la organización</h2>
                    <p class="mt-1 text-sm text-gray-600">Gestiona los departamentos/áreas disponibles en esta organización.</p>
                    
                    <!-- Formulario para agregar nuevo departamento -->
                    <div class="mt-6 border rounded-lg p-4 bg-gray-50">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Agregar nuevo departamento</h3>
                        <form @submit.prevent="addArea" class="space-y-4">
                            <div>
                                <label for="area-name" class="block text-sm font-medium text-gray-700">Nombre del departamento</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        type="text"
                                        id="area-name"
                                        v-model="areaForm.name"
                                        placeholder="Ej: Recursos Humanos"
                                        class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                    />
                                </div>
                                <p v-if="areaForm.errors.name" class="mt-1 text-xs text-red-500">
                                    {{ areaForm.errors.name }}
                                </p>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                    :disabled="areaForm.processing"
                                >
                                    <PlusIcon class="-ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" />
                                    Agregar departamento
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de departamentos existentes -->
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Departamentos registrados</h3>
                        <div v-if="organization.department_areas && organization.department_areas.length > 0" class="overflow-hidden bg-white shadow sm:rounded-md">
                            <ul role="list" class="divide-y divide-gray-200">
                                <li v-for="area in organization.department_areas" :key="area.id" class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6">
                                    <div class="flex min-w-0 gap-x-4">
                                        <div class="min-w-0 flex-auto">
                                            <p class="text-sm font-semibold leading-6 text-gray-900">{{ area.name }}</p>
                                            <p class="mt-1 flex text-xs leading-5 text-gray-500">
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
                                                    ID: {{ area.identifier }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-x-2">
                                        <button 
                                            @click="deleteArea(area)"
                                            type="button" 
                                            class="rounded-full bg-white p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                        >
                                            <TrashIcon class="h-5 w-5" aria-hidden="true" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay departamentos registrados</h3>
                                <p class="mt-1 text-sm text-gray-500">Comienza agregando un nuevo departamento para esta organización.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de confirmación de eliminación -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">Confirmar eliminación</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Estás seguro que deseas eliminar {{ deleteType === 'position' ? 'este puesto' : 'este departamento' }}?
                                    Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 flex justify-end gap-3">
                        <button 
                            type="button" 
                            @click="cancelDelete" 
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="button" 
                            @click="confirmDelete" 
                            class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>
/* Puedes agregar estilos adicionales aquí */
</style>
