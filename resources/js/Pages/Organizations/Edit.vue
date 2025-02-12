<script setup>
import { reactive, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { PhotoIcon } from '@heroicons/vue/24/solid'
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
        <div class="bg-white px-6 py-4">
            <Alert
                v-if="$page.props.flash"
                :type="$page.props.flash.type"
                :title="$page.props.flash.title"
                :message="$page.props.flash.message"
                class="my-4"
            />

            <form @submit.prevent="submit" enctype="multipart/form-data">
                <div class="space-y-12">
                    <!-- Sección de Organización -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Organización</h2>
                        <p class="mt-1 text-sm text-gray-600">Edita la información de la organización.</p>
                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
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
                                    class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10"
                                    @dragover.prevent="handleDragOver"
                                    @drop="handleFileDrop"
                                >
                                    <div class="text-center">
                                        <div v-if="form.logo || organization.logo" class="mt-2 flex flex-col items-center gap-y-4">
                                            <p class="text-lg text-gray-950">Logo actual:</p>
                                            <img :src="`/${organization.logo}`" alt="Logo de la organización" class="h-18 w-auto" />
                                        </div>
                                        <PhotoIcon v-else class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                                        <div class="mt-4 flex text-sm text-gray-600 justify-center">
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
                <div class="mt-6 flex items-center justify-end gap-x-6">
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
    </Dashboard>
</template>

<style scoped>
/* Puedes agregar estilos adicionales aquí */
</style>
