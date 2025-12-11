<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, TrashIcon, ArrowDownTrayIcon, ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/solid'
import axios from 'axios'

const props = defineProps({
    organizationId: {
        type: String,
        required: true,
    },
})

// State
const loading = ref(false)
const exporting = ref(false)
const error = ref('')
const successMessage = ref('')

// Available options from API
const evaluationTypes = ref([])
const demographics = ref([])
const customFields = ref([])
const availableFactors = ref([])

// Selected values for current combination
const selectedFilters = ref([])
const selectedFactors = ref([])

// Stored combinations (max 4)
const combinations = ref([])

// Collapsed state for filter groups (for UI/UX with many options)
const collapsedGroups = ref({})

// Default 10 workplace climate factors
const defaultFactors = [
    'Entorno Laboral Seguro',
    'Seguridad Laboral',
    'Compensación Justa',
    'Comunicación Abierta',
    'Participación de los Empleados',
    'Reconocimiento y Recompensa',
    'Capacitación y Desarrollo',
    'Equilibrio entre Vida Laboral y Personal',
    'Avance Profesional',
    'Apoyo al Empleado',
]

// Computed: group filter options by category for display
const groupedFilterOptions = computed(() => {
    const groups = {}
    
    demographics.value.forEach(demo => {
        if (!groups[demo.label]) {
            groups[demo.label] = {
                label: demo.label,
                type: 'demographic',
                key: demo.key,
                values: [],
            }
        }
        groups[demo.label].values = demo.values
    })
    
    customFields.value.forEach(field => {
        if (field.key === 'numero') return
        if (!groups[field.label]) {
            groups[field.label] = {
                label: field.label,
                type: 'customField',
                key: field.key,
                values: [],
            }
        }
        // Remove number field from custom fields if label is numero
        groups[field.label].values = field.values
    })
    
    return Object.values(groups)
})

// Check if can add more combinations
const canAddCombination = computed(() => {
    console.log('combinations length:', combinations.value.length)
    console.log(combinations.value.length < 4 && selectedFilters.value.length > 0)
    return combinations.value.length < 4 && selectedFilters.value.length > 0
})

// Check if max combinations reached
const maxCombinationsReached = computed(() => {
    return combinations.value.length >= 4
})

// Build combination description
const buildCombinationText = (combination) => {
    const filters = combination.filters || []
    if (filters.length === 0) {
        return 'Todos'
    }
    return filters.map(f => f.value).join(' + ')
}

// Toggle group collapsed state
function toggleGroupCollapse(groupLabel) {
    collapsedGroups.value[groupLabel] = !collapsedGroups.value[groupLabel]
}

// Check if group is collapsed
function isGroupCollapsed(groupLabel) {
    return collapsedGroups.value[groupLabel] === true
}

// Check if group has many values (for auto-collapse)
function hasManyyValues(group) {
    return group.values.length > 10
}

// Fetch export options on mount
onMounted(async () => {
    await fetchExportOptions()
})

// API: Fetch available options
async function fetchExportOptions() {
    loading.value = true
    error.value = ''
    
    try {
        const response = await axios.get(route('organization.clima.export-options', props.organizationId))
        evaluationTypes.value = response.data.evaluationTypes || []
        demographics.value = response.data.demographics || []
        customFields.value = response.data.customFields || []
        availableFactors.value = defaultFactors
        
        // Initialize all factors as selected
        selectedFactors.value = [...availableFactors.value]
        
        // Auto-collapse groups with many values
        groupedFilterOptions.value.forEach(group => {
            if (hasManyyValues(group)) {
                collapsedGroups.value[group.label] = true
            }
        })
    } catch (err) {
        console.error('Error fetching export options:', err)
        error.value = 'Error al cargar las opciones de exportación'
    } finally {
        loading.value = false
    }
}

// Toggle filter selection
function toggleFilter(filterOption) {
    const index = selectedFilters.value.findIndex(
        f => f.type === filterOption.type && 
             f.key === filterOption.key && 
             f.value === filterOption.value
    )
    
    if (index > -1) {
        selectedFilters.value.splice(index, 1)
    } else {
        selectedFilters.value.push({
            type: filterOption.type,
            key: filterOption.key,
            value: filterOption.value,
        })
    }
}

// Check if filter is selected
function isFilterSelected(filterOption) {
    return selectedFilters.value.some(
        f => f.type === filterOption.type && 
             f.key === filterOption.key && 
             f.value === filterOption.value
    )
}

// Toggle factor selection
function toggleFactor(factor) {
    const index = selectedFactors.value.indexOf(factor)
    if (index > -1) {
        selectedFactors.value.splice(index, 1)
    } else {
        selectedFactors.value.push(factor)
    }
}

// Add current selection as a combination
function addCombination() {
    // Check max combinations limit
    if (combinations.value.length >= 4) {
        error.value = 'Ya has alcanzado el máximo de 4 combinaciones'
        setTimeout(() => error.value = '', 3000)
        return
    }
    
    if (selectedFilters.value.length === 0) {
        error.value = 'Debe seleccionar al menos un filtro'
        setTimeout(() => error.value = '', 3000)
        return
    }
    
    // Check for duplicate
    const newFilters = [...selectedFilters.value]
    const isDuplicate = combinations.value.some(combo => {
        if (combo.filters.length !== newFilters.length) return false
        return combo.filters.every(f => 
            newFilters.some(nf => 
                nf.type === f.type && nf.key === f.key && nf.value === f.value
            )
        )
    })
    
    if (isDuplicate) {
        error.value = 'Esta combinación ya ha sido agregada'
        setTimeout(() => error.value = '', 3000)
        return
    }
    
    combinations.value.push({
        id: Date.now(),
        filters: newFilters,
        description: newFilters.map(f => f.value).join(' + '),
    })
    
    // Clear current selection
    selectedFilters.value = []
    successMessage.value = 'Combinación agregada'
    setTimeout(() => successMessage.value = '', 2000)
}

// Remove a combination
function removeCombination(index) {
    combinations.value.splice(index, 1)
}

// Export the multi-sheet Excel
async function exportExcel() {
    if (combinations.value.length === 0) {
        error.value = 'Debe agregar al menos una combinación'
        setTimeout(() => error.value = '', 3000)
        return
    }
    
    if (selectedFactors.value.length === 0) {
        error.value = 'Debe seleccionar al menos un factor'
        setTimeout(() => error.value = '', 3000)
        return
    }
    
    exporting.value = true
    error.value = ''
    
    try {
        const response = await axios.post(
            route('organization.clima.export-multi', props.organizationId),
            {
                combinations: combinations.value,
                factors: selectedFactors.value,
            },
            {
                responseType: 'blob',
            }
        )
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        
        // Extract filename from Content-Disposition header or use default
        const contentDisposition = response.headers['content-disposition']
        let filename = 'clima_combinaciones.xlsx'
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1].replace(/['"]/g, '')
            }
        }
        
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        successMessage.value = 'Archivo descargado exitosamente'
        setTimeout(() => successMessage.value = '', 3000)
    } catch (err) {
        console.error('Export error:', err)
        if (err.response && err.response.data) {
            // Try to read error from blob
            try {
                const text = await err.response.data.text()
                const json = JSON.parse(text)
                error.value = json.error || 'Error al exportar'
            } catch {
                error.value = 'Error al exportar el archivo'
            }
        } else {
            error.value = 'Error al exportar el archivo'
        }
    } finally {
        exporting.value = false
    }
}

// Select all factors
function selectAllFactors() {
    selectedFactors.value = [...availableFactors.value]
}

// Deselect all factors
function deselectAllFactors() {
    selectedFactors.value = []
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Exportador Multi-Factor de Clima Laboral</h3>
            <p class="mt-1 text-sm text-gray-500">
                Genera un archivo Excel con múltiples hojas, cada una filtrada por una combinación de datos demográficos y campos personalizados.
            </p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <span class="ml-3 text-gray-600">Cargando opciones...</span>
        </div>

        <!-- No Likert evaluations -->
        <div v-else-if="!evaluationTypes.includes('likert')" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">
                No hay evaluaciones de clima laboral (Likert) disponibles para esta organización.
            </p>
        </div>

        <!-- Main Content -->
        <div v-else class="space-y-6">
            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">{{ error }}</p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800">{{ successMessage }}</p>
            </div>

            <!-- Filter Selection -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Seleccionar Filtros para Combinación</h4>
                        <p class="text-xs text-gray-500">Seleccione los valores que desea filtrar. Puede agregar hasta 4 combinaciones.</p>
                    </div>
                    <!-- Combination counter badge -->
                    <div :class="[
                        'px-3 py-1 rounded-full text-sm font-medium',
                        maxCombinationsReached 
                            ? 'bg-amber-100 text-amber-800' 
                            : 'bg-indigo-100 text-indigo-800'
                    ]">
                        {{ combinations.length }}/4 combinaciones
                    </div>
                </div>
                
                <!-- Max combinations warning -->
                <div v-if="maxCombinationsReached" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                    <p class="text-sm text-amber-800">
                        <strong>Límite alcanzado:</strong> Ya tienes 4 combinaciones. Elimina una para agregar otra.
                    </p>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <div v-for="group in groupedFilterOptions" :key="group.label" class="border border-gray-200 rounded-lg bg-white">
                        <!-- Group header (collapsible) -->
                        <button
                            @click="toggleGroupCollapse(group.label)"
                            class="w-full flex items-center justify-between px-3 py-2 text-left hover:bg-gray-50 rounded-t-lg"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-700">{{ group.label }}</span>
                                <span class="text-xs text-gray-400">({{ group.values.length }} opciones)</span>
                            </div>
                            <ChevronDownIcon v-if="isGroupCollapsed(group.label)" class="h-4 w-4 text-gray-400" />
                            <ChevronUpIcon v-else class="h-4 w-4 text-gray-400" />
                        </button>
                        
                        <!-- Group values (collapsible content) -->
                        <div v-show="!isGroupCollapsed(group.label)" class="px-3 pb-3">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="value in group.values"
                                    :key="value"
                                    @click="toggleFilter({ type: group.type, key: group.key, value })"
                                    :disabled="selectedFilters.length >= 4 && !isFilterSelected({ type: group.type, key: group.key, value })"
                                    :class="[
                                        'px-3 py-1.5 text-sm rounded-full border transition-colors',
                                        isFilterSelected({ type: group.type, key: group.key, value })
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : selectedFilters.length >= 4
                                                ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                                : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400'
                                    ]"
                                >
                                    {{ value }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected filters preview -->
                <div v-if="selectedFilters.length > 0" class="mt-4 p-3 bg-indigo-50 border border-indigo-200 rounded-md">
                    <p class="text-sm text-indigo-800">
                        <strong>Selección actual:</strong> {{ selectedFilters.map(f => f.value).join(' + ') }}
                    </p>
                </div>

                <!-- Add Combination Button -->
                <div class="mt-4 flex items-center gap-4">
                    <button
                        @click="addCombination"
                        :disabled="maxCombinationsReached || selectedFilters.length === 0"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors',
                            !maxCombinationsReached && selectedFilters.length > 0
                                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                        ]"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Agregar Combinación
                    </button>
                </div>
            </div>

            <!-- Saved Combinations -->
            <div v-if="combinations.length > 0" class="bg-white border border-gray-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Combinaciones Guardadas ({{ combinations.length }}/4)</h4>
                <div class="space-y-2">
                    <div
                        v-for="(combo, index) in combinations"
                        :key="combo.id"
                        class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2"
                    >
                        <span class="text-sm text-gray-700">
                            <span class="font-medium text-indigo-600">Hoja {{ index + 1 }}:</span>
                            {{ buildCombinationText(combo) }}
                        </span>
                        <button
                            @click="removeCombination(index)"
                            class="text-red-500 hover:text-red-700 p-1"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Factors Selection -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Factores de Clima Laboral</h4>
                        <p class="text-xs text-gray-500">Seleccione los factores a incluir. Se mostrará el puntaje y nivel de cada factor seleccionado.</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="selectAllFactors"
                            class="text-xs text-indigo-600 hover:text-indigo-800"
                        >
                            Seleccionar todos
                        </button>
                        <span class="text-gray-300">|</span>
                        <button
                            @click="deselectAllFactors"
                            class="text-xs text-gray-600 hover:text-gray-800"
                        >
                            Deseleccionar todos
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    <label
                        v-for="factor in availableFactors"
                        :key="factor"
                        class="flex items-center gap-2 p-2 rounded-md hover:bg-gray-100 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :checked="selectedFactors.includes(factor)"
                            @change="toggleFactor(factor)"
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700">{{ factor }}</span>
                    </label>
                </div>
            </div>

            <!-- Export Button -->
            <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                <button
                    @click="exportExcel"
                    :disabled="exporting || combinations.length === 0 || selectedFactors.length === 0"
                    :class="[
                        'inline-flex items-center gap-2 px-6 py-3 rounded-md text-base font-medium transition-colors',
                        combinations.length > 0 && selectedFactors.length > 0 && !exporting
                            ? 'bg-teal-600 text-white hover:bg-teal-700'
                            : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                    ]"
                >
                    <ArrowDownTrayIcon v-if="!exporting" class="h-5 w-5" />
                    <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    {{ exporting ? 'Generando...' : 'Generar Excel' }}
                </button>
                
                <span v-if="combinations.length === 0" class="text-sm text-gray-500">
                    Agregue al menos una combinación para exportar
                </span>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                <h5 class="text-sm font-medium text-blue-800 mb-2">Información del Reporte</h5>
                <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                    <li>Cada combinación generará una hoja separada en el archivo Excel</li>
                    <li>Nombre de hoja: valores seleccionados separados por "+"</li>
                    <li>Columnas: Folio, Nombre, Demografía, Campos personalizados, Puntaje y Nivel por factor, Comentarios</li>
                    <li>Los niveles (Totalmente de Acuerdo, De Acuerdo, Desacuerdo, Totalmente Desacuerdo) se calculan según el puntaje</li>
                    <li>Si una combinación no tiene datos, la hoja aparecerá vacía con encabezados</li>
                </ul>
            </div>
        </div>
    </div>
</template>
