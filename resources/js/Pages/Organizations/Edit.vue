
<script setup>
import { reactive, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { PhotoIcon, PlusIcon, PencilIcon, TrashIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/solid'
import Dashboard from '../../Layouts/Dashboard.vue'
import Alert from '../../Components/Alert.vue'
import FormInput from "../../Components/FormInput.vue"
import Folios from './components/Folios.vue'
import ImportDataModal from '../../Components/ImportDataModal.vue'
import ClimaExporter from '../../Components/ClimaExporter.vue'
import { defineProps, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Recibimos la organización desde el backend
const { organization } = defineProps({
    organization: {
        type: Object,
        required: true,
    },
});

// Check if user is admin
const page = usePage()
const isAdmin = computed(() => {
    const user = page.props.auth?.user
    const roles = user?.roles || []
    return roles.some(role => ['admin', 'super-admin'].includes(role.name))
})

// Creamos el formulario con datos iniciales basados en la organización
const form = useForm({
    _method: 'put', // Método PUT para actualizar
    // Básicos
    name: organization.name || '',
    folio_organization: organization.folio_organization || '',
    logo: null, // Si el usuario sube uno nuevo, se actualizará; de lo contrario, se mantiene el actual.
    // Identificación
    razon_social: organization.razon_social || '',
    rfc: organization.rfc || '',
    registro_patronal: organization.registro_patronal || '',
    // Domicilio
    calle_numero: organization.calle_numero || '',
    colonia: organization.colonia || '',
    codigo_postal: organization.codigo_postal || '',
    municipio: organization.municipio || '',
    estado: organization.estado || '',
    // Contacto 1
    contacto_nombre: organization.contacto_nombre || '',
    contacto_puesto: organization.contacto_puesto || '',
    contacto_email: organization.contacto_email || '',
    contacto_movil: organization.contacto_movil || '',
    // Responsable
    responsable_nombre: organization.responsable_nombre || '',
    responsable_puesto: organization.responsable_puesto || '',
    responsable_email: organization.responsable_email || '',
    responsable_movil: organization.responsable_movil || '',
    // Actividad
    actividad_principal: organization.actividad_principal || '',
    // Totales y muestra
    total_trabajadores: organization.total_trabajadores ?? '',
    total_hombres: organization.total_hombres ?? '',
    total_mujeres: organization.total_mujeres ?? '',
    muestra_aplicada: organization.muestra_aplicada ?? '',
    muestra_hombres: organization.muestra_hombres ?? '',
    muestra_mujeres: organization.muestra_mujeres ?? '',
    // Comité
    comite_integrantes: organization.comite_integrantes ?? '',
    comite_hombres: organization.comite_hombres ?? '',
    comite_mujeres: organization.comite_mujeres ?? '',
    // Committee members
    committee_members: organization.committee_members && organization.committee_members.length > 0
        ? organization.committee_members.map(member => ({
            nombre: member.nombre || '',
            departamento: member.departamento || '',
            puesto: member.puesto || '',
            factor: member.factor || '',
        }))
        : [
            { nombre: '', departamento: '', puesto: '', factor: '' },
            { nombre: '', departamento: '', puesto: '', factor: '' },
            { nombre: '', departamento: '', puesto: '', factor: '' },
        ],
    // Fecha y justificación
    fecha_aplicacion: organization.fecha_aplicacion || '',
    justificacion_muestra: organization.justificacion_muestra || '',
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

// Estado para modales de importación
const showImportPositionsModal = ref(false)
const showImportAreasModal = ref(false)

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
// Funciones para manejo de committee members

const addCommitteeMember = () => {
    form.committee_members.push({
        nombre: '',
        departamento: '',
        puesto: '',
        factor: '',
    })
}

const removeCommitteeMember = (index) => {
    form.committee_members.splice(index, 1)
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

const confirmForceDelete = () => {
    if (confirm("¿Estás seguro que deseas eliminar esta organización? Esta acción no se puede deshacer.")) {
        form.delete(route('organizations.force-delete', organization));
    }
}

// Funciones para importación
const handleImportPositionsSuccess = () => {
    showImportPositionsModal.value = false
    router.reload({ only: ['organization'] })
}

const handleImportAreasSuccess = () => {
    showImportAreasModal.value = false
    router.reload({ only: ['organization'] })
}

</script>

<template>
    <Dashboard>
        <div class="bg-white px-4 sm:px-6 py-4">
            <Alert
                v-if="$page.props.flash && $page.props.flash.message"
                :type="$page.props.flash.type || 'info'"
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
                    <button
                        @click="changeTab('folios')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'folios' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Folios
                    </button>
                    <button
                        v-if="isAdmin"
                        @click="changeTab('clima-export')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'clima-export' ? 'bg-teal-100 text-teal-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Exportar Clima
                    </button>
                    <button
                        v-if="isAdmin"
                        @click="changeTab('likert-answers')"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="activeTab === 'likert-answers' ? 'bg-purple-100 text-purple-700' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Exportar Respuestas Likert
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
                                <div class="sm:col-span-2">
                                    <FormInput
                                        label="Folio (opcional)"
                                        hint="Si no se define, se mantiene o se genera automáticamente."
                                        id="folio_organization"
                                        name="folio_organization"
                                        autocomplete="off"
                                        v-model="form.folio_organization"
                                        :error="form.errors.folio_organization"
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
                                    <FormInput label="Código Postal" id="codigo_postal" name="codigo_postal" v-model="form.codigo_postal" :error="form.errors.codigo_postal" />
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
                                <!-- Actividad principal -->
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

                                <!-- Integrantes del Comité (detalle) -->
                                <div class="col-span-full">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-sm font-semibold text-gray-900">Integrantes del Comité (Detalle)</h3>
                                        <button
                                            type="button"
                                            @click="addCommitteeMember"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                        >
                                            <PlusIcon class="-ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" />
                                            Agregar integrante
                                        </button>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">Agrega la información de cada integrante del comité.</p>
                                </div>

                                <!-- Lista de integrantes del comité -->
                                <div v-for="(member, index) in form.committee_members" :key="index" class="col-span-full">
                                    <div class="border rounded-lg p-4 bg-gray-50 relative">
                                        <div class="flex justify-between items-start mb-4">
                                            <h4 class="text-sm font-medium text-gray-700">Integrante {{ index + 1 }}</h4>
                                            <button
                                                v-if="form.committee_members.length > 1"
                                                type="button"
                                                @click="removeCommitteeMember(index)"
                                                class="rounded-full bg-white p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                                            >
                                                <TrashIcon class="h-5 w-5" aria-hidden="true" />
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                            <div>
                                                <label :for="`member_nombre_${index}`" class="block text-sm font-medium text-gray-700">Nombre *</label>
                                                <input
                                                    :id="`member_nombre_${index}`"
                                                    v-model="member.nombre"
                                                    type="text"
                                                    placeholder="Nombre completo"
                                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                />
                                                <p v-if="form.errors[`committee_members.${index}.nombre`]" class="mt-1 text-xs text-red-500">
                                                    {{ form.errors[`committee_members.${index}.nombre`] }}
                                                </p>
                                            </div>
                                            <div>
                                                <label :for="`member_departamento_${index}`" class="block text-sm font-medium text-gray-700">Departamento</label>
                                                <input
                                                    :id="`member_departamento_${index}`"
                                                    v-model="member.departamento"
                                                    type="text"
                                                    placeholder="Departamento o área"
                                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                />
                                            </div>
                                            <div>
                                                <label :for="`member_puesto_${index}`" class="block text-sm font-medium text-gray-700">Puesto</label>
                                                <input
                                                    :id="`member_puesto_${index}`"
                                                    v-model="member.puesto"
                                                    type="text"
                                                    placeholder="Puesto o cargo"
                                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                />
                                            </div>
                                            <div>
                                                <label :for="`member_factor_${index}`" class="block text-sm font-medium text-gray-700">Factor</label>
                                                <input
                                                    :id="`member_factor_${index}`"
                                                    v-model="member.factor"
                                                    type="text"
                                                    placeholder="Factor de riesgo"
                                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
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
                                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-8"
                                        @dragover.prevent="handleDragOver"
                                        @drop="handleFileDrop"
                                    >
                                        <div class="text-center">
                                            <div v-if="form.logo || organization.logo" class="mt-2 flex flex-col items-center gap-y-4">
                                                <p class="text-lg text-gray-950">Logo actual:</p>
                                                <img :src="`/storage/${organization.logo}`" alt="Logo de la organización" class="h-18 w-auto" />
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

                        <!-- Si la organización está deshabilitada, mostramos el botón de eliminar -->
                        <button
                            v-if="organization.deleted_at"
                            type="button"
                            @click="confirmForceDelete"
                            class="rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 bg-red-600 hover:bg-red-500"
                        >
                            Eliminar organización permanentemente
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pestaña de Puestos/Ocupaciones -->
            <div v-else-if="activeTab === 'positions'" class="space-y-6">
                <div class="border-b border-gray-900/10 pb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Puestos de la organización</h2>
                            <p class="mt-1 text-sm text-gray-600">Gestiona los puestos/ocupaciones disponibles en esta organización.</p>
                        </div>
                        <button
                            @click="showImportPositionsModal = true"
                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                        >
                            <ArrowUpTrayIcon class="-ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" />
                            Importar desde Excel
                        </button>
                    </div>
                    
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
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Departamentos de la organización</h2>
                            <p class="mt-1 text-sm text-gray-600">Gestiona los departamentos/áreas disponibles en esta organización.</p>
                        </div>
                        <button
                            @click="showImportAreasModal = true"
                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                        >
                            <ArrowUpTrayIcon class="-ml-0.5 mr-1.5 h-4 w-4" aria-hidden="true" />
                            Importar desde Excel
                        </button>
                    </div>
                    
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


            <div v-else-if="activeTab === 'folios'" class="space-y-6">
                <Folios :organization="organization" />
            </div>

            <!-- Pestaña de Exportar Clima (solo admin) -->
            <div v-else-if="activeTab === 'clima-export' && isAdmin" class="space-y-6">
                <ClimaExporter :organization-id="organization.id" />
            </div>

            <!-- Pestaña de Exportar Respuestas Likert (solo admin) -->
            <div v-else-if="activeTab === 'likert-answers' && isAdmin" class="space-y-6">
                <div class="border-b border-gray-900/10 pb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900">Exportar Respuestas Likert</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">
                        Descarga un reporte en Excel con todas las respuestas individuales de las evaluaciones Likert.
                    </p>
                    <div class="mt-6">
                        <p class="text-sm text-gray-600 mb-4">
                            El reporte incluye:
                        </p>
                        <ul class="list-disc list-inside text-sm text-gray-600 mb-6 space-y-1">
                            <li>Folio de la evaluación</li>
                            <li>Nombre de la organización</li>
                            <li>Datos demográficos (Género, Tipo de Contrato, Puesto, Área, Turno)</li>
                            <li>Campos personalizados (Número, Código de Línea, Líder de Línea, Supervisor, Superintendente, Gerente)</li>
                            <li>Las 23 respuestas individuales (P1-P23)</li>
                        </ul>
                        <a 
                            :href="route('organizations.export-likert-answers', organization.id)"
                            class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600"
                        >
                            <ArrowUpTrayIcon class="h-5 w-5" />
                            Descargar Reporte Excel
                        </a>
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

            <!-- Modal de importación de puestos -->
            <ImportDataModal
                :show="showImportPositionsModal"
                title="Importar Puestos desde Excel"
                :download-route="route('occupation-positions.template', organization.id)"
                :upload-route="route('occupation-positions.import', organization.id)"
                :has-data="organization.occupation_positions && organization.occupation_positions.length > 0"
                upload-button-text="Importar Puestos"
                @close="showImportPositionsModal = false"
                @success="handleImportPositionsSuccess"
            />

            <!-- Modal de importación de departamentos -->
            <ImportDataModal
                :show="showImportAreasModal"
                title="Importar Departamentos desde Excel"
                :download-route="route('department-areas.template', organization.id)"
                :upload-route="route('department-areas.import', organization.id)"
                :has-data="organization.department_areas && organization.department_areas.length > 0"
                upload-button-text="Importar Departamentos"
                @close="showImportAreasModal = false"
                @success="handleImportAreasSuccess"
            />
        </div>
    </Dashboard>
</template>

<style scoped>
/* Puedes agregar estilos adicionales aquí */
</style>
