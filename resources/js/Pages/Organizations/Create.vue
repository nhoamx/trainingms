<script setup>
import { reactive, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { PhotoIcon, UserCircleIcon } from '@heroicons/vue/24/solid'
import Dashboard from '../../Layouts/Dashboard.vue'
import FormInput from "../../Components/FormInput.vue";

// Creamos el formulario con useForm (inicialmente, todos los campos vacíos)
const form = useForm({
    name: null,
    logo: null,
    folio_organization: null,
})

const clientErrors = ref({})

// Objeto reactivo para validaciones de cliente (además de las de backend que Inertia maneja)
const errors = reactive({
    name: '',
    logo: '',
    folio_organization: '',
})

// Funciones para manejo de archivos

// Si se suelta un archivo en el área de drop, lo asignamos al campo "image"
function handleFileDrop(e) {
    e.preventDefault()
    e.stopPropagation()
    const files = e.dataTransfer.files
    if (files.length > 0) {
        form.logo = files[0]
    }
}

// Evitamos el comportamiento por defecto al arrastrar el archivo
function handleDragOver(e) {
    e.preventDefault()
    e.stopPropagation()
}

// Cuando se selecciona un archivo desde el input
function handleFileUpload(e) {
    const files = e.target.files
    if (files.length > 0) {
        form.logo = files[0]
    }
}


// Validación simple del formulario en el front
const validate = () => {
    const errors = {}

    if (!form.name) {
        errors.name = 'El nombre de la organización es requerido.'
    }

    clientErrors.value = errors
    return Object.keys(errors).length === 0
}


// Función para enviar el formulario
const submit = () => {
    if (validate()) {
        form.post(route('organizations.store'), {
            preserveScroll: true, // Mantiene la posición en la página
            forceFormData: true, // ✅ Necesario para enviar archivos
            onSuccess: () => {
                form.reset();
            },
        });
    }
};
</script>

<template>
    <Dashboard>
        <div class="bg-white px-6 py-4">
            <!-- Se puede usar @submit.prevent en el form, pero aquí el botón lo maneja -->
            <form @submit.prevent="submit" enctype="multipart/form-data">
                <div class="space-y-12">
                    <!-- Sección de organización -->
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold text-gray-900">Organización</h2>
                        <p class="mt-1 text-sm text-gray-600">Esta es la información de la organización.</p>
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
                                    <!-- Se muestra el error de validación local o el error que devuelva el backend -->
                                    <p v-if="clientErrors.name || form.errors.name" class="mt-1 text-xs text-red-500">
                                        {{ clientErrors.name || form.errors.name }}
                                    </p>
                                </div>
                            </div>
                            <div class="md:col-span-4">
                                <FormInput
                                    label="Folio (opcional)"
                                    hint="El folio servira para identificar la organización dentro de las evaluaciones, es un numero único. Si no ingresas uno, este será generado automáticamente."
                                    id="folio_organization"
                                    name="folio_organization"
                                    autocomplete="folio_organization"
                                    v-model="form.folio_organization"
                                    :error="clientErrors.folio_organization || form.errors.folio_organization"
                                />
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
                                        <PhotoIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                                        <div class="mt-4 flex text-sm text-gray-600 justify-center">
                                            <label
                                                for="file-upload"
                                                class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
                                            >
                                                <span>Sube una imagen</span>
                                                <input
                                                    id="file-upload"
                                                    name="logo"
                                                    type="file"
                                                    class="sr-only"
                                                    @change="handleFileUpload"
                                                    accept="image/png, image/jpeg, image/gif"
                                                />
                                            </label>
                                            <p class="pl-1">para el logo o arrástrala</p>
                                        </div>
                                        <p class="text-xs text-gray-600">PNG, JPG, GIF hasta 10MB</p>
                                        <p v-if="errors.logo || form.errors.logo" class="mt-1 text-xs text-red-500">
                                            {{ errors.logo || form.errors.logo }}
                                        </p>
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
/* Puedes agregar estilos adicionales aquí */
</style>
