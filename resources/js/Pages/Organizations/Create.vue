<script setup>
import { reactive, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { PhotoIcon, UserCircleIcon } from '@heroicons/vue/24/solid'
import Dashboard from '../../Layouts/Dashboard.vue'
import FormInput from "../../Components/FormInput.vue";

// Creamos el formulario con useForm (inicialmente, todos los campos vacíos)
const form = useForm({
    // Básicos
    name: '',
    logo: null,
    folio_organization: '',
    // Identificación
    razon_social: '',
    rfc: '',
    registro_patronal: '',
    // Domicilio
    calle_numero: '',
    colonia: '',
    codigo_postal: '',
    municipio: '',
    estado: '',
    // Contacto 1
    contacto_nombre: '',
    contacto_puesto: '',
    contacto_email: '',
    contacto_movil: '',
    // Responsable
    responsable_nombre: '',
    responsable_puesto: '',
    responsable_email: '',
    responsable_movil: '',
    // Actividad
    actividad_principal: '',
    // Totales y muestra
    total_trabajadores: '',
    total_hombres: '',
    total_mujeres: '',
    muestra_aplicada: '',
    muestra_hombres: '',
    muestra_mujeres: '',
    // Comité
    comite_integrantes: '',
    comite_hombres: '',
    comite_mujeres: '',
    // Fechas y justificación
    fecha_aplicacion: '',
    justificacion_muestra: '',
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
                            <!-- Identificación -->
                            <div class="sm:col-span-3">
                                <FormInput label="Razón Social" id="razon_social" name="razon_social" v-model="form.razon_social" :error="form.errors.razon_social" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="RFC" id="rfc" name="rfc" v-model="form.rfc" :error="form.errors.rfc" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="Registro Patronal" id="registro_patronal" name="registro_patronal" v-model="form.registro_patronal" :error="form.errors.registro_patronal" />
                            </div>
                            <!-- Domicilio -->
                            <div class="sm:col-span-4">
                                <FormInput label="Calle y Número" id="calle_numero" name="calle_numero" v-model="form.calle_numero" :error="form.errors.calle_numero" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="Colonia" id="colonia" name="colonia" v-model="form.colonia" :error="form.errors.colonia" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="Código Postal" id="codigo_postal" name="codigo_postal" autocomplete="postal-code" v-model="form.codigo_postal" :error="form.errors.codigo_postal" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="Municipio" id="municipio" name="municipio" v-model="form.municipio" :error="form.errors.municipio" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput label="Estado" id="estado" name="estado" v-model="form.estado" :error="form.errors.estado" />
                            </div>
                            <!-- Contacto principal -->
                            <div class="col-span-full">
                                <h3 class="text-sm font-semibold text-gray-900">Contacto</h3>
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Nombre" id="contacto_nombre" name="contacto_nombre" v-model="form.contacto_nombre" :error="form.errors.contacto_nombre" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Puesto" id="contacto_puesto" name="contacto_puesto" v-model="form.contacto_puesto" :error="form.errors.contacto_puesto" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput type="email" label="Correo electrónico" id="contacto_email" name="contacto_email" v-model="form.contacto_email" :error="form.errors.contacto_email" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Móvil" id="contacto_movil" name="contacto_movil" v-model="form.contacto_movil" :error="form.errors.contacto_movil" />
                            </div>
                            <!-- Responsable de la norma -->
                            <div class="col-span-full">
                                <h3 class="text-sm font-semibold text-gray-900">Responsable de la norma</h3>
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Nombre" id="responsable_nombre" name="responsable_nombre" v-model="form.responsable_nombre" :error="form.errors.responsable_nombre" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Puesto" id="responsable_puesto" name="responsable_puesto" v-model="form.responsable_puesto" :error="form.errors.responsable_puesto" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput type="email" label="Correo electrónico" id="responsable_email" name="responsable_email" v-model="form.responsable_email" :error="form.errors.responsable_email" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput label="Móvil" id="responsable_movil" name="responsable_movil" v-model="form.responsable_movil" :error="form.errors.responsable_movil" />
                            </div>
                            <!-- Actividad -->
                            <div class="sm:col-span-4">
                                <FormInput label="Actividad Principal" id="actividad_principal" name="actividad_principal" v-model="form.actividad_principal" :error="form.errors.actividad_principal" />
                            </div>
                            <!-- Totales colaboradores -->
                            <div class="col-span-full">
                                <h3 class="text-sm font-semibold text-gray-900">Colaboradores</h3>
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Total trabajadores" id="total_trabajadores" name="total_trabajadores" v-model="form.total_trabajadores" :error="form.errors.total_trabajadores" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Hombres" id="total_hombres" name="total_hombres" v-model="form.total_hombres" :error="form.errors.total_hombres" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Mujeres" id="total_mujeres" name="total_mujeres" v-model="form.total_mujeres" :error="form.errors.total_mujeres" />
                            </div>
                            <!-- Muestra aplicada -->
                            <div class="col-span-full">
                                <h3 class="text-sm font-semibold text-gray-900">Muestra aplicada</h3>
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Total muestra" id="muestra_aplicada" name="muestra_aplicada" v-model="form.muestra_aplicada" :error="form.errors.muestra_aplicada" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Hombres" id="muestra_hombres" name="muestra_hombres" v-model="form.muestra_hombres" :error="form.errors.muestra_hombres" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Mujeres" id="muestra_mujeres" name="muestra_mujeres" v-model="form.muestra_mujeres" :error="form.errors.muestra_mujeres" />
                            </div>
                            <div class="col-span-full">
                                <label for="justificacion_muestra" class="block text-sm font-medium text-gray-900">Justificación de la muestra (opcional)</label>
                                <textarea id="justificacion_muestra" name="justificacion_muestra" v-model="form.justificacion_muestra" rows="3" class="mt-2 block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"></textarea>
                                <p v-if="form.errors.justificacion_muestra" class="mt-1 text-xs text-red-500">{{ form.errors.justificacion_muestra }}</p>
                            </div>
                            <!-- Comité -->
                            <div class="col-span-full">
                                <h3 class="text-sm font-semibold text-gray-900">Comité de atención y seguimiento</h3>
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Integrantes (Total)" id="comite_integrantes" name="comite_integrantes" v-model="form.comite_integrantes" :error="form.errors.comite_integrantes" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Hombres" id="comite_hombres" name="comite_hombres" v-model="form.comite_hombres" :error="form.errors.comite_hombres" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput type="number" label="Mujeres" id="comite_mujeres" name="comite_mujeres" v-model="form.comite_mujeres" :error="form.errors.comite_mujeres" />
                            </div>
                            <!-- Fechas -->
                            <div class="sm:col-span-3">
                                <FormInput type="date" label="Fecha de aplicación" id="fecha_aplicacion" name="fecha_aplicacion" v-model="form.fecha_aplicacion" :error="form.errors.fecha_aplicacion" />
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
