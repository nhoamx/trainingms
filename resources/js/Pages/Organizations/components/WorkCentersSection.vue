<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { PlusIcon, PencilIcon, TrashIcon, BuildingOffice2Icon, CheckCircleIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/solid'
import ImportDataModal from '../../../Components/ImportDataModal.vue'

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    workCenterTypes: {
        type: Array,
        required: true
    }
})

// Estado para formulario de nuevo work center
const showAddForm = ref(false)
const editingWorkCenter = ref(null)
const showImportModal = ref(false)

// Formulario para crear/editar work center
const workCenterForm = useForm({
    organization_id: props.organization.id,
    code: '',
    name: '',
    type: '',
    is_primary: false,
    // Fiscal data
    legal_name: '',
    tax_id: '',
    employer_registration: '',
    // Address
    street_address: '',
    neighborhood: '',
    postal_code: '',
    municipality: '',
    state: '',
    // Contact
    phone: '',
    email: '',
    notes: ''
})

// Computed para separar work center primario de secundarios
const primaryWorkCenter = computed(() => {
    return props.organization.work_centers?.find(wc => wc.is_primary)
})

const secondaryWorkCenters = computed(() => {
    return props.organization.work_centers?.filter(wc => !wc.is_primary) || []
})

// Generar código automático
const generateCode = () => {
    const existingCodes = props.organization.work_centers?.map(wc => parseInt(wc.code)) || []
    const maxCode = existingCodes.length > 0 ? Math.max(...existingCodes) : 0
    const nextCode = (maxCode + 1).toString().padStart(4, '0')
    workCenterForm.code = nextCode
}

// Función para abrir formulario de nuevo work center
const openAddForm = () => {
    workCenterForm.reset()
    workCenterForm.organization_id = props.organization.id
    generateCode()
    editingWorkCenter.value = null
    showAddForm.value = true
}

// Función para editar work center
const editWorkCenter = (workCenter) => {
    if (workCenter.is_primary) {
        alert('El centro de trabajo primario no puede editarse desde aquí. Los datos se sincronizan automáticamente con la organización.')
        return
    }
    
    workCenterForm.reset()
    workCenterForm.code = workCenter.code
    workCenterForm.name = workCenter.name
    workCenterForm.type = workCenter.type
    workCenterForm.is_primary = workCenter.is_primary
    workCenterForm.legal_name = workCenter.legal_name || ''
    workCenterForm.tax_id = workCenter.tax_id || ''
    workCenterForm.employer_registration = workCenter.employer_registration || ''
    workCenterForm.street_address = workCenter.street_address || ''
    workCenterForm.neighborhood = workCenter.neighborhood || ''
    workCenterForm.postal_code = workCenter.postal_code || ''
    workCenterForm.municipality = workCenter.municipality || ''
    workCenterForm.state = workCenter.state || ''
    workCenterForm.phone = workCenter.phone || ''
    workCenterForm.email = workCenter.email || ''
    workCenterForm.notes = workCenter.notes || ''
    
    editingWorkCenter.value = workCenter
    showAddForm.value = true
}

// Función para guardar work center
const saveWorkCenter = () => {
    if (!workCenterForm.name || !workCenterForm.type || !workCenterForm.code) {
        alert('Por favor completa los campos requeridos: Código, Nombre y Tipo')
        return
    }

    if (editingWorkCenter.value) {
        // Actualizar
        workCenterForm.put(route('organizations.work-centers.update', [props.organization.id, editingWorkCenter.value.id]), {
            preserveScroll: true,
            onSuccess: () => {
                showAddForm.value = false
                workCenterForm.reset()
                editingWorkCenter.value = null
                router.reload({ only: ['organization'] })
            }
        })
    } else {
        // Crear
        workCenterForm.post(route('organizations.work-centers.store', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => {
                showAddForm.value = false
                workCenterForm.reset()
                router.reload({ only: ['organization'] })
            }
        })
    }
}

// Función para cancelar
const cancelForm = () => {
    showAddForm.value = false
    workCenterForm.reset()
    editingWorkCenter.value = null
}

// Función para eliminar work center
const deleteWorkCenter = (workCenter) => {
    if (workCenter.is_primary) {
        alert('No se puede eliminar el centro de trabajo primario.')
        return
    }

    if (confirm(`¿Estás seguro de eliminar el centro de trabajo "${workCenter.name}"?`)) {
        router.delete(route('organizations.work-centers.destroy', [props.organization.id, workCenter.id]), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['organization'] })
            }
        })
    }
}

// Helper para obtener label del tipo
const getTypeLabel = (type) => {
    const typeObj = props.workCenterTypes.find(t => t.value === type)
    return typeObj ? typeObj.label : type
}

// Handler para éxito de importación
const handleImportSuccess = () => {
    showImportModal.value = false
    router.reload({ only: ['organization'] })
}
</script>

<template>
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-semibold text-gray-900">Centros de Trabajo</h3>
            <p class="mt-1 text-sm text-gray-600">
                Gestiona los centros de trabajo de la organización. El centro primario se sincroniza automáticamente con los datos de la organización.
            </p>
        </div>

        <!-- Work Center Primario (Read-only) -->
        <div v-if="primaryWorkCenter" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3">
                    <BuildingOffice2Icon class="h-6 w-6 text-blue-600 mt-1" />
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-lg font-semibold text-blue-900">{{ primaryWorkCenter.name }}</h4>
                            <span class="inline-flex items-center gap-x-1 rounded-md bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">
                                <CheckCircleIcon class="h-3 w-3" />
                                PRIMARIO
                            </span>
                        </div>
                        <p class="text-sm text-blue-700 mt-1">Código: {{ primaryWorkCenter.code }}</p>
                        <p class="text-sm text-blue-600 mt-1">Tipo: {{ getTypeLabel(primaryWorkCenter.type) }}</p>
                        
                        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-blue-900">
                            <div v-if="primaryWorkCenter.legal_name">
                                <span class="font-medium">Razón Social:</span> {{ primaryWorkCenter.legal_name }}
                            </div>
                            <div v-if="primaryWorkCenter.tax_id">
                                <span class="font-medium">RFC:</span> {{ primaryWorkCenter.tax_id }}
                            </div>
                            <div v-if="primaryWorkCenter.street_address">
                                <span class="font-medium">Dirección:</span> {{ primaryWorkCenter.street_address }}, {{ primaryWorkCenter.neighborhood }}
                            </div>
                            <div v-if="primaryWorkCenter.postal_code">
                                <span class="font-medium">CP:</span> {{ primaryWorkCenter.postal_code }}, {{ primaryWorkCenter.municipality }}, {{ primaryWorkCenter.state }}
                            </div>
                            <div v-if="primaryWorkCenter.email || primaryWorkCenter.phone" class="flex gap-4">
                                <span v-if="primaryWorkCenter.email">📧 {{ primaryWorkCenter.email }}</span>
                                <span v-if="primaryWorkCenter.phone">📞 {{ primaryWorkCenter.phone }}</span>
                            </div>
                        </div>

                        <div class="mt-3 bg-blue-100 rounded p-2">
                            <p class="text-xs text-blue-800">
                                ℹ️ Este centro se sincroniza automáticamente con los datos de la organización. Para editarlo, actualiza la información en la pestaña "Información General".
                            </p>
                        </div>

                        <div class="mt-3">
                            <Link
                                :href="route('work-centers.dashboard.nom-035-index', primaryWorkCenter.id)"
                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
                            >
                                Ver Dashboard NOM-035
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones para agregar y/o importar work centers -->
        <div class="flex justify-between items-center">
            <h4 class="text-md font-medium text-gray-900">Centros Adicionales</h4>
            <div class="flex gap-2">
                <button
                    @click="showImportModal = true"
                    type="button"
                    class="inline-flex items-center gap-x-2 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                >
                    <ArrowUpTrayIcon class="h-4 w-4" />
                    Importar desde Excel
                </button>
                <button
                    @click="openAddForm"
                    type="button"
                    class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    <PlusIcon class="h-4 w-4" />
                    Agregar Centro de Trabajo
                </button>
            </div>
        </div>

        <!-- Formulario de agregar/editar work center -->
        <div v-if="showAddForm" class="bg-gray-50 border border-gray-200 rounded-lg p-6 space-y-4">
            <h5 class="text-md font-semibold text-gray-900">
                {{ editingWorkCenter ? 'Editar Centro de Trabajo' : 'Nuevo Centro de Trabajo' }}
            </h5>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Código -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código *</label>
                    <input
                        v-model="workCenterForm.code"
                        type="text"
                        maxlength="10"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': workCenterForm.errors.code }"
                    />
                    <p v-if="workCenterForm.errors.code" class="mt-1 text-sm text-red-600">{{ workCenterForm.errors.code }}</p>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo *</label>
                    <select
                        v-model="workCenterForm.type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': workCenterForm.errors.type }"
                    >
                        <option value="">Selecciona un tipo</option>
                        <option v-for="type in workCenterTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                    <p v-if="workCenterForm.errors.type" class="mt-1 text-sm text-red-600">{{ workCenterForm.errors.type }}</p>
                </div>

                <!-- Nombre -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nombre del Centro *</label>
                    <input
                        v-model="workCenterForm.name"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :class="{ 'border-red-500': workCenterForm.errors.name }"
                    />
                    <p v-if="workCenterForm.errors.name" class="mt-1 text-sm text-red-600">{{ workCenterForm.errors.name }}</p>
                </div>

                <!-- Datos Fiscales (opcionales) -->
                <div class="md:col-span-2 pt-2 border-t border-gray-200">
                    <h6 class="text-sm font-medium text-gray-700 mb-3">Datos Fiscales (Opcional)</h6>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Razón Social</label>
                            <input
                                v-model="workCenterForm.legal_name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">RFC</label>
                            <input
                                v-model="workCenterForm.tax_id"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Registro Patronal</label>
                            <input
                                v-model="workCenterForm.employer_registration"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Dirección (opcional) -->
                <div class="md:col-span-2 pt-2 border-t border-gray-200">
                    <h6 class="text-sm font-medium text-gray-700 mb-3">Dirección (Opcional)</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600">Calle y Número</label>
                            <input
                                v-model="workCenterForm.street_address"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Colonia</label>
                            <input
                                v-model="workCenterForm.neighborhood"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Código Postal</label>
                            <input
                                v-model="workCenterForm.postal_code"
                                type="text"
                                maxlength="5"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Municipio</label>
                            <input
                                v-model="workCenterForm.municipality"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Estado</label>
                            <input
                                v-model="workCenterForm.state"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Contacto (opcional) -->
                <div class="md:col-span-2 pt-2 border-t border-gray-200">
                    <h6 class="text-sm font-medium text-gray-700 mb-3">Contacto (Opcional)</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Teléfono</label>
                            <input
                                v-model="workCenterForm.phone"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Email</label>
                            <input
                                v-model="workCenterForm.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600">Notas</label>
                    <textarea
                        v-model="workCenterForm.notes"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    ></textarea>
                </div>
            </div>

            <!-- Botones del formulario -->
            <div class="flex justify-end gap-3 pt-4">
                <button
                    @click="cancelForm"
                    type="button"
                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    Cancelar
                </button>
                <button
                    @click="saveWorkCenter"
                    type="button"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    :disabled="workCenterForm.processing"
                >
                    {{ editingWorkCenter ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>

        <!-- Lista de work centers secundarios -->
        <div v-if="secondaryWorkCenters.length > 0" class="space-y-3">
            <div
                v-for="workCenter in secondaryWorkCenters"
                :key="workCenter.id"
                class="bg-white border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <BuildingOffice2Icon class="h-5 w-5 text-gray-400" />
                            <h5 class="text-md font-semibold text-gray-900">{{ workCenter.name }}</h5>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                {{ workCenter.code }}
                            </span>
                            <span class="inline-flex items-center rounded-md bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700">
                                {{ getTypeLabel(workCenter.type) }}
                            </span>
                        </div>

                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600">
                            <div v-if="workCenter.legal_name">
                                <span class="font-medium">Razón Social:</span> {{ workCenter.legal_name }}
                            </div>
                            <div v-if="workCenter.tax_id">
                                <span class="font-medium">RFC:</span> {{ workCenter.tax_id }}
                            </div>
                            <div v-if="workCenter.street_address" class="md:col-span-2">
                                <span class="font-medium">Dirección:</span> {{ workCenter.street_address }}
                                <span v-if="workCenter.neighborhood">, {{ workCenter.neighborhood }}</span>
                                <span v-if="workCenter.postal_code"> (CP: {{ workCenter.postal_code }})</span>
                            </div>
                            <div v-if="workCenter.email">
                                <span class="font-medium">Email:</span> {{ workCenter.email }}
                            </div>
                            <div v-if="workCenter.phone">
                                <span class="font-medium">Teléfono:</span> {{ workCenter.phone }}
                            </div>
                            <div v-if="workCenter.notes" class="md:col-span-2 text-gray-500 italic">
                                {{ workCenter.notes }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 ml-4">
                        <Link
                            :href="route('work-centers.dashboard.nom-035-index', workCenter.id)"
                            class="px-2 py-1 text-xs font-medium rounded-md bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors"
                            title="Ver dashboard NOM-035"
                        >
                            Dashboard
                        </Link>
                        <button
                            @click="editWorkCenter(workCenter)"
                            class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                            title="Editar"
                        >
                            <PencilIcon class="h-5 w-5" />
                        </button>
                        <button
                            @click="deleteWorkCenter(workCenter)"
                            class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                            title="Eliminar"
                        >
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!showAddForm" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <BuildingOffice2Icon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay centros adicionales</h3>
            <p class="mt-1 text-sm text-gray-500">
                Comienza agregando un centro de trabajo adicional (sucursal, planta, bodega, etc.)
            </p>
            <div class="mt-6">
                <button
                    @click="openAddForm"
                    type="button"
                    class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    <PlusIcon class="h-4 w-4" />
                    Agregar Centro de Trabajo
                </button>
            </div>
        </div>

        <!-- Modal de importación de centros de trabajo -->
        <ImportDataModal
            :show="showImportModal"
            title="Importar Centros de Trabajo desde Excel"
            :download-route="route('organizations.work-centers.template', organization.id)"
            :upload-route="route('organizations.work-centers.import', organization.id)"
            :has-data="secondaryWorkCenters.length > 0"
            upload-button-text="Importar Centros de Trabajo"
            @close="showImportModal = false"
            @success="handleImportSuccess"
        >
            <template #description>
                <div class="text-sm text-gray-600 space-y-2">
                    <p><strong>Campos del Excel:</strong></p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li><strong>Código</strong>: Identificador del centro (4 dígitos). Si está vacío, se genera automáticamente.</li>
                        <li><strong>Nombre *</strong>: Nombre del centro de trabajo (requerido).</li>
                        <li><strong>Tipo *</strong>: Tipo de centro (requerido). Valores: Matriz, Planta, Sucursal, Almacén, Oficina, Otro.</li>
                        <li><strong>Razón Social</strong>: Razón social del centro (opcional).</li>
                        <li><strong>RFC</strong>: Registro Federal de Contribuyentes (opcional).</li>
                        <li><strong>Registro Patronal</strong>: Número de registro patronal (opcional).</li>
                        <li><strong>Calle y Número</strong>: Dirección física (opcional).</li>
                        <li><strong>Colonia</strong>: Colonia o barrio (opcional).</li>
                        <li><strong>Código Postal</strong>: CP del centro (opcional).</li>
                        <li><strong>Municipio</strong>: Municipio o delegación (opcional).</li>
                        <li><strong>Estado</strong>: Estado de la República (opcional).</li>
                        <li><strong>Teléfono</strong>: Número de contacto (opcional).</li>
                        <li><strong>Email</strong>: Correo electrónico (opcional).</li>
                        <li><strong>Notas</strong>: Observaciones adicionales (opcional).</li>
                    </ul>
                    <p class="mt-3 text-amber-600 font-medium">
                        ⚠️ El centro primario (código 0001) no puede ser modificado o eliminado mediante importación.
                    </p>
                </div>
            </template>
        </ImportDataModal>
    </div>
</template>
