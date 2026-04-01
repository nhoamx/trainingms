<script setup>
import { computed, ref } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { DocumentArrowUpIcon, DocumentArrowDownIcon, CheckCircleIcon, PlusIcon, TrashIcon, XMarkIcon, UserGroupIcon } from '@heroicons/vue/24/solid'
import Dashboard from '../../Layouts/Dashboard.vue'
import Alert from '../../Components/Alert.vue'
import FormInput from "../../Components/FormInput.vue"

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    committeeMembers: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const isOrganizationView = computed(() => page.props.auth?.user?.roles?.some((role) => role.name === 'organization') ?? false)
const displayValue = (value) => {
    if (value === 0) {
        return '0'
    }

    return value || 'No especificado'
}
const generalTabs = computed(() => {
    const tabs = [
        { key: 'empresa', label: 'Empresa' },
        { key: 'comite', label: 'Comité' },
    ]

    if (!isOrganizationView.value) {
        tabs.push({ key: 'politica', label: 'Política' })
    }

    return tabs
})

// Form for company data
const form = useForm({
    // General Information
    name: props.organization.name || '',
    razon_social: props.organization.razon_social || '',
    rfc: props.organization.rfc || '',
    registro_patronal: props.organization.registro_patronal || '',
    actividad_principal: props.organization.actividad_principal || '',
    fecha_aplicacion: props.organization.fecha_aplicacion || '',
    
    // Address
    calle_numero: props.organization.calle_numero || '',
    colonia: props.organization.colonia || '',
    codigo_postal: props.organization.codigo_postal || '',
    municipio: props.organization.municipio || '',
    estado: props.organization.estado || '',
    
    // Workers
    total_trabajadores: props.organization.total_trabajadores ?? '',
    total_hombres: props.organization.total_hombres ?? '',
    total_mujeres: props.organization.total_mujeres ?? '',
    
    // Sample
    muestra_aplicada: props.organization.muestra_aplicada ?? '',
    muestra_hombres: props.organization.muestra_hombres ?? '',
    muestra_mujeres: props.organization.muestra_mujeres ?? '',
    justificacion_muestra: props.organization.justificacion_muestra || '',
    
    // Contact
    contacto_nombre: props.organization.contacto_nombre || '',
    contacto_puesto: props.organization.contacto_puesto || '',
    contacto_email: props.organization.contacto_email || '',
    contacto_movil: props.organization.contacto_movil || '',
    
    // Responsible
    responsable_nombre: props.organization.responsable_nombre || '',
    responsable_puesto: props.organization.responsable_puesto || '',
    responsable_email: props.organization.responsable_email || '',
    responsable_movil: props.organization.responsable_movil || '',
    
    // Committee
    comite_integrantes: props.organization.comite_integrantes ?? '',
    comite_hombres: props.organization.comite_hombres ?? '',
    comite_mujeres: props.organization.comite_mujeres ?? '',
})

// Form for policy documents
const policyDraftForm = useForm({
    policy_draft: null,
})

const policyApprovedForm = useForm({
    policy_approved: null,
})

// Active tab state
const activeTab = ref('empresa')

// Committee member modal state
const showCommitteeMemberModal = ref(false)
const memberToDelete = ref(null)
const showDeleteConfirmModal = ref(false)

// Form for committee member
const committeeMemberForm = useForm({
    name: '',
    department_area: '',
    position: '',
    factor: '',
})

// Open modal to add committee member
const openCommitteeMemberModal = () => {
    committeeMemberForm.reset()
    committeeMemberForm.clearErrors()
    showCommitteeMemberModal.value = true
}

// Close committee member modal
const closeCommitteeMemberModal = () => {
    showCommitteeMemberModal.value = false
    committeeMemberForm.reset()
}

// Submit committee member
const submitCommitteeMember = () => {
    committeeMemberForm.post(route('committee-members.store', props.organization.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCommitteeMemberModal()
            router.reload({ only: ['committeeMembers'] })
        },
    })
}

// Confirm delete committee member
const confirmDeleteMember = (member) => {
    memberToDelete.value = member
    showDeleteConfirmModal.value = true
}

// Delete committee member
const deleteMember = () => {
    if (memberToDelete.value) {
        router.delete(route('committee-members.destroy', [props.organization.id, memberToDelete.value.id]), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmModal.value = false
                memberToDelete.value = null
                router.reload({ only: ['committeeMembers'] })
            },
        })
    }
}

// Submit company data
const submitCompanyData = () => {
    form.post(route('company-data.update', props.organization.id), {
        preserveScroll: true,
    })
}

// Handle policy draft upload
const handlePolicyDraftUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        policyDraftForm.policy_draft = file
        policyDraftForm.post(route('company-data.policy.upload-draft', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => {
                policyDraftForm.reset()
                event.target.value = null
            },
        })
    }
}

// Handle policy approved upload
const handlePolicyApprovedUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        policyApprovedForm.policy_approved = file
        policyApprovedForm.post(route('company-data.policy.upload-approved', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => {
                policyApprovedForm.reset()
                event.target.value = null
            },
        })
    }
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

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Datos de la Empresa</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ isOrganizationView
                        ? 'Consulta la capa general de tu organización y la integración del comité.'
                        : 'Administra la información de tu organización, comité y política.' }}
                </p>
            </div>

            <div class="mb-8 bg-white rounded-2xl border border-gray-200 shadow-sm">
                <div class="px-6 pt-6 pb-3 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900">Capa General</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ isOrganizationView
                            ? 'Información general de la organización para consulta.'
                            : 'Información general de la organización para gestión administrativa.' }}
                    </p>
                </div>

                <div class="px-6 pt-4">
                    <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
                        <button
                            v-for="tab in generalTabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                                activeTab === tab.key
                                    ? 'bg-blue-600 text-white shadow-lg hover:bg-blue-700'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
                            ]"
                            :aria-current="activeTab === tab.key ? 'page' : undefined"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="p-6 sm:p-8">
                    <!-- Empresa Tab -->
                    <div v-show="activeTab === 'empresa'" class="space-y-8">
                        <div v-if="isOrganizationView" class="space-y-6">
                            <section class="rounded-lg border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-2 h-1 w-12 rounded-full bg-blue-500/80"></div>
                                <h3 class="text-base font-semibold text-slate-900">Información General</h3>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre comercial</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.name) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Razón social</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.razon_social) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">RFC</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.rfc) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registro patronal</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.registro_patronal) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de evaluación</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.fecha_aplicacion) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 sm:col-span-2 lg:col-span-1">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actividad principal</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.actividad_principal) }}</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-2 h-1 w-12 rounded-full bg-emerald-500/80"></div>
                                <h3 class="text-base font-semibold text-slate-900">Domicilio</h3>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 lg:col-span-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Calle y número</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.calle_numero) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Código postal</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.codigo_postal) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Colonia</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.colonia) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Municipio</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.municipio) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.estado) }}</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-2 h-1 w-12 rounded-full bg-amber-500/80"></div>
                                <h3 class="text-base font-semibold text-slate-900">Colaboradores y muestra</h3>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total trabajadores</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.total_trabajadores) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hombres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.total_hombres) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mujeres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.total_mujeres) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Muestra aplicada</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.muestra_aplicada) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Muestra hombres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.muestra_hombres) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Muestra mujeres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.muestra_mujeres) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Justificación de muestra</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ displayValue(form.justificacion_muestra) }}</p>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-2 h-1 w-12 rounded-full bg-indigo-500/80"></div>
                                <h3 class="text-base font-semibold text-slate-900">Contacto y responsable</h3>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contacto</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.contacto_nombre) }}</p>
                                        <p class="text-xs text-slate-600">{{ displayValue(form.contacto_puesto) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email contacto</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900 break-all">{{ displayValue(form.contacto_email) }}</p>
                                        <p class="text-xs text-slate-600">{{ displayValue(form.contacto_movil) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Responsable</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900">{{ displayValue(form.responsable_nombre) }}</p>
                                        <p class="text-xs text-slate-600">{{ displayValue(form.responsable_puesto) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email responsable</p>
                                        <p class="mt-1 text-sm font-medium text-slate-900 break-all">{{ displayValue(form.responsable_email) }}</p>
                                        <p class="text-xs text-slate-600">{{ displayValue(form.responsable_movil) }}</p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <form v-else @submit.prevent="submitCompanyData">
                    <!-- Información General -->
                    <div class="border-b border-gray-900/10 pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Información General</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <FormInput v-model="form.name" label="Nombre comercial" :error="form.errors.name" required :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-3">
                                <FormInput v-model="form.razon_social" label="Razón social" :error="form.errors.razon_social" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.rfc" label="RFC" :error="form.errors.rfc" maxlength="13" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.registro_patronal" label="Registro patronal" :error="form.errors.registro_patronal" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.fecha_aplicacion" label="Fecha de evaluación" type="date" :error="form.errors.fecha_aplicacion" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-6">
                                <FormInput v-model="form.actividad_principal" label="Actividad principal" :error="form.errors.actividad_principal" :disabled="isOrganizationView" />
                            </div>
                        </div>
                    </div>

                    <!-- Domicilio -->
                    <div class="border-b border-gray-900/10 pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Domicilio</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                            <div class="sm:col-span-4">
                                <FormInput v-model="form.calle_numero" label="Calle y número" :error="form.errors.calle_numero" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.codigo_postal" label="Código postal" :error="form.errors.codigo_postal" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.colonia" label="Colonia" :error="form.errors.colonia" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.municipio" label="Municipio" :error="form.errors.municipio" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-2">
                                <FormInput v-model="form.estado" label="Estado" :error="form.errors.estado" :disabled="isOrganizationView" />
                            </div>
                        </div>
                    </div>

                    <!-- Colaboradores -->
                    <div class="border-b border-gray-900/10 pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Colaboradores</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-3">
                            <div>
                                <FormInput v-model.number="form.total_trabajadores" label="Total de trabajadores" type="number" :error="form.errors.total_trabajadores" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model.number="form.total_hombres" label="Hombres" type="number" :error="form.errors.total_hombres" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model.number="form.total_mujeres" label="Mujeres" type="number" :error="form.errors.total_mujeres" :disabled="isOrganizationView" />
                            </div>
                        </div>
                    </div>

                    <!-- Muestra -->
                    <div class="border-b border-gray-900/10 pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Muestra Aplicada</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-3">
                            <div>
                                <FormInput v-model.number="form.muestra_aplicada" label="Total" type="number" :error="form.errors.muestra_aplicada" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model.number="form.muestra_hombres" label="Hombres" type="number" :error="form.errors.muestra_hombres" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model.number="form.muestra_mujeres" label="Mujeres" type="number" :error="form.errors.muestra_mujeres" :disabled="isOrganizationView" />
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Justificación de la muestra
                                </label>
                                <textarea
                                    v-model="form.justificacion_muestra"
                                    rows="3"
                                    :readonly="isOrganizationView"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    :class="form.errors.justificacion_muestra ? 'border-red-300' : ''"
                                />
                                <p v-if="form.errors.justificacion_muestra" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.justificacion_muestra }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="border-b border-gray-900/10 pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                            <div>
                                <FormInput v-model="form.contacto_nombre" label="Nombre" :error="form.errors.contacto_nombre" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.contacto_puesto" label="Puesto" :error="form.errors.contacto_puesto" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.contacto_email" label="Email" type="email" :error="form.errors.contacto_email" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.contacto_movil" label="Móvil" :error="form.errors.contacto_movil" :disabled="isOrganizationView" />
                            </div>
                        </div>
                    </div>

                    <!-- Responsable -->
                    <div class="pb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Responsable de la Norma</h2>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                            <div>
                                <FormInput v-model="form.responsable_nombre" label="Nombre" :error="form.errors.responsable_nombre" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.responsable_puesto" label="Puesto" :error="form.errors.responsable_puesto" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.responsable_email" label="Email" type="email" :error="form.errors.responsable_email" :disabled="isOrganizationView" />
                            </div>
                            <div>
                                <FormInput v-model="form.responsable_movil" label="Móvil" :error="form.errors.responsable_movil" :disabled="isOrganizationView" />
                            </div>
                        </div>
                    </div>

                            <!-- Submit Button -->
                            <div v-if="!isOrganizationView" class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Comité Tab -->
                    <div v-show="activeTab === 'comite'" class="space-y-8">
                        <div v-if="isOrganizationView" class="space-y-6">
                            <section class="rounded-lg border border-slate-200 bg-slate-50/70 p-5">
                                <div class="mb-2 h-1 w-12 rounded-full bg-blue-500/80"></div>
                                <h3 class="text-base font-semibold text-slate-900">Resumen del Comité</h3>
                                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Integrantes</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.comite_integrantes) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hombres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.comite_hombres) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mujeres</p>
                                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ displayValue(form.comite_mujeres) }}</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 bg-white p-5">
                                <div class="mb-5">
                                    <h3 class="text-lg font-semibold text-gray-900">Miembros del Comité</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Consulta de miembros y responsabilidades del comité.
                                    </p>
                                </div>

                                <div v-if="committeeMembers && committeeMembers.length > 0" class="overflow-hidden bg-white shadow ring-1 ring-gray-200 sm:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factor</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="member in committeeMembers" :key="member.id" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ member.name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.department_area }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.position }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ member.factor }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                    <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay miembros registrados</h3>
                                    <p class="mt-1 text-sm text-gray-500">No existen miembros del comité registrados en este momento.</p>
                                </div>
                            </section>
                        </div>

                        <form v-else @submit.prevent="submitCompanyData">
                            <div class="">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Integrantes del Comité</h2>
                                <p class="text-sm text-gray-600 mb-6">
                                    Información sobre la composición del comité de seguridad y salud en el trabajo.
                                </p>
                                <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-3">
                                    <div>
                                        <FormInput v-model.number="form.comite_integrantes" label="Total de integrantes" type="number" :error="form.errors.comite_integrantes" :disabled="isOrganizationView" />
                                    </div>
                                    <div>
                                        <FormInput v-model.number="form.comite_hombres" label="Hombres" type="number" :error="form.errors.comite_hombres" :disabled="isOrganizationView" />
                                    </div>
                                    <div>
                                        <FormInput v-model.number="form.comite_mujeres" label="Mujeres" type="number" :error="form.errors.comite_mujeres" :disabled="isOrganizationView" />
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div v-if="!isOrganizationView" class="flex justify-end my-8">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                                </button>
                            </div>
                        </form>

                        <!-- Committee Members Section -->
                        <div class="border-t border-gray-900/10 pt-8">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Miembros del Comité</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Gestiona los miembros individuales del comité y sus responsabilidades.
                                    </p>
                                </div>
                                <button
                                    v-if="!isOrganizationView"
                                    @click="openCommitteeMemberModal"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                >
                                    <PlusIcon class="h-5 w-5" />
                                    Agregar Miembro
                                </button>
                            </div>

                            <!-- Committee Members Table -->
                            <div v-if="committeeMembers && committeeMembers.length > 0" class="overflow-hidden bg-white shadow ring-1 ring-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puesto</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factor</th>
                                            <th v-if="!isOrganizationView" scope="col" class="relative px-6 py-3">
                                                <span class="sr-only">Acciones</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="member in committeeMembers" :key="member.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ member.name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ member.department_area }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ member.position }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ member.factor }}
                                            </td>
                                            <td v-if="!isOrganizationView" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button
                                                    @click="confirmDeleteMember(member)"
                                                    type="button"
                                                    class="text-red-600 hover:text-red-900 transition-colors"
                                                >
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay miembros registrados</h3>
                                <p class="mt-1 text-sm text-gray-500">Comienza agregando un miembro del comité.</p>
                                <div v-if="!isOrganizationView" class="mt-6">
                                    <button
                                        @click="openCommitteeMemberModal"
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                    >
                                        <PlusIcon class="h-5 w-5" />
                                        Agregar Primer Miembro
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Política Tab -->
                    <div v-if="!isOrganizationView" v-show="activeTab === 'politica'" class="space-y-8">
                        <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Documentos de Política</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Sube tu borrador de política para revisión y descarga la versión aprobada.
                    </p>

                    <!-- Draft Policy Section -->
                    <div class="border rounded-lg p-6 mb-6 bg-gray-50">
                        <h3 class="text-md font-medium text-gray-900 mb-3 flex items-center gap-2">
                            <DocumentArrowUpIcon class="h-5 w-5 text-blue-600" />
                            Borrador de Política
                        </h3>
                        
                        <div class="space-y-4">
                            <div v-if="organization.policy_draft_path" class="flex items-center justify-between bg-white p-4 rounded border">
                                <div class="flex items-center gap-2">
                                    <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                    <span class="text-sm text-gray-700">Borrador cargado</span>
                                </div>
                                <a
                                    :href="route('company-data.policy.download-draft', organization.id)"
                                    class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    <DocumentArrowDownIcon class="h-4 w-4" />
                                    Descargar
                                </a>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ organization.policy_draft_path ? 'Reemplazar borrador' : 'Subir borrador' }}
                                </label>
                                <input
                                    type="file"
                                    @change="handlePolicyDraftUpload"
                                    accept=".pdf,.doc,.docx"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    :disabled="policyDraftForm.processing"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    PDF, DOC o DOCX. Máximo 10MB.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Policy Section -->
                    <div class="border rounded-lg p-6 bg-green-50">
                        <h3 class="text-md font-medium text-gray-900 mb-3 flex items-center gap-2">
                            <CheckCircleIcon class="h-5 w-5 text-green-600" />
                            Política Aprobada
                        </h3>
                        
                        <div v-if="organization.policy_approved_path" class="space-y-4">
                            <div class="flex items-center justify-between bg-white p-4 rounded border border-green-200">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                        <span class="text-sm font-medium text-gray-900">Política aprobada disponible</span>
                                    </div>
                                    <p v-if="organization.policy_approved_at" class="text-xs text-gray-500 ml-7">
                                        Aprobada el {{ new Date(organization.policy_approved_at).toLocaleDateString('es-MX') }}
                                    </p>
                                </div>
                                <a
                                    :href="route('company-data.policy.download-approved', organization.id)"
                                    class="inline-flex items-center gap-2 text-sm text-green-600 hover:text-green-700 font-medium"
                                >
                                    <DocumentArrowDownIcon class="h-4 w-4" />
                                    Descargar
                                </a>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-600">
                                Aún no hay una política aprobada. El administrador subirá la versión aprobada una vez revisada.
                            </p>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Committee Member Modal -->
            <div v-if="!isOrganizationView && showCommitteeMemberModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Agregar Miembro del Comité</h3>
                        <button @click="closeCommitteeMemberModal" type="button" class="text-gray-400 hover:text-gray-500">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitCommitteeMember" class="space-y-4">
                        <div>
                            <FormInput 
                                v-model="committeeMemberForm.name" 
                                label="Nombre" 
                                :error="committeeMemberForm.errors.name" 
                                required 
                            />
                        </div>
                        <div>
                            <FormInput 
                                v-model="committeeMemberForm.department_area" 
                                label="Área" 
                                :error="committeeMemberForm.errors.department_area" 
                                required 
                            />
                        </div>
                        <div>
                            <FormInput 
                                v-model="committeeMemberForm.position" 
                                label="Puesto" 
                                :error="committeeMemberForm.errors.position" 
                                required 
                            />
                        </div>
                        <div>
                            <FormInput 
                                v-model="committeeMemberForm.factor" 
                                label="Factor" 
                                :error="committeeMemberForm.errors.factor" 
                                required 
                            />
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="closeCommitteeMemberModal"
                                type="button"
                                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="committeeMemberForm.processing"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                            >
                                {{ committeeMemberForm.processing ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div v-if="!isOrganizationView && showDeleteConfirmModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">Eliminar Miembro</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Estás seguro de que deseas eliminar a <strong>{{ memberToDelete?.name }}</strong>? Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 flex justify-end gap-3">
                        <button 
                            @click="showDeleteConfirmModal = false" 
                            type="button"
                            class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="deleteMember" 
                            type="button"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
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
/* Add any custom styles here */
</style>
