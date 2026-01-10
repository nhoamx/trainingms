<template>
  <Dashboard>
    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <!-- Logo -->
            <div v-if="orgLogo" class="flex-shrink-0">
              <img
                :src="orgLogo"
                :alt="`${orgName} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div v-else class="flex-shrink-0">
              <div>
                <h1 class="text-4xl font-bold text-gray-900">{{ orgName }}</h1>
                <p class="mt-2 text-gray-600">{{ t('Organization Dashboard') }}</p>
              </div>
            </div>
            <!-- Language Switcher -->
            <div class="sm:ml-auto">
              <!-- <LanguageSwitcher /> -->
            </div>
          </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="mb-8">
          <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
            <button
              v-for="tab in translatedTabs"
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

        <!-- Tab Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <!-- Company Data Tab -->
          <section v-if="activeTab === 'company'" class="p-8 sm:p-10">
            <header class="mb-6">
              <h2 class="text-2xl font-bold text-gray-900">
                {{ t('Company Data') }}
              </h2>
              <p class="text-gray-600 mt-2">{{ t('Información general de la compañía') }}</p>
            </header>

            <CompanyDataTab v-if="hasCompanyData" :company-data="companyData" />

            <div v-else class="p-8 text-center border border-dashed border-gray-200 rounded-lg">
              <p class="text-gray-600">{{ t('Aún no hay datos de compañía para mostrar.') }}</p>
            </div>
          </section>

          <!-- Reports Tab -->
          <section v-else-if="activeTab === 'reports'" class="p-8 sm:p-10">
            <header class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ t('Reporte') }}</h2>
                <p class="text-gray-600 mt-1">{{ t('Inventario de activos e inspecciones registradas') }}</p>
              </div>
              <div v-if="organization?.id" class="flex flex-col sm:flex-row gap-2">
                <!-- Vista previa solo para admins y super-admins -->
                <a
                  v-if="canViewPreview"
                  :href="`/reportes/pdf/nom002/${organization.id}?preview`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ t('Vista previa') }}
                </a>
                <button
                  @click="handleDownloadPdf"
                  :disabled="isDownloading"
                  :class="[
                    'inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg shadow-sm transition-all duration-200',
                    isDownloading
                      ? 'bg-gray-400 cursor-not-allowed text-white'
                      : 'bg-red-600 hover:bg-red-700 text-white'
                  ]"
                >
                  <!-- Spinner durante carga -->
                  <svg v-if="isDownloading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <!-- Icono normal -->
                  <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                  </svg>
                  <!-- Texto dinámico según estado -->
                  <span v-if="downloadState === 'generating'">{{ t('Generando...') }}</span>
                  <span v-else-if="downloadState === 'downloading'">{{ t('Descargando...') }}</span>
                  <span v-else>{{ t('Descargar PDF') }}</span>
                </button>
              </div>
            </header>

            <div v-if="assets.length" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      {{ t('Activo') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      {{ t('Tipo') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      {{ t('Estado') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      {{ t('Acciones') }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="asset in assets" :key="asset.id || asset.name">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ asset.name || t('Sin nombre') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ asset.type || 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm">
                      <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                        :class="statusClasses(asset.status)">
                        {{ asset.status || t('Pendiente') }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                      <Link
                        v-if="asset.reportUrl"
                        :href="asset.reportUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg"
                      >
                        {{ t('Ver reporte') }}
                      </Link>
                      <span v-else class="text-gray-500">{{ t('Sin enlace de reporte') }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="p-8 text-center border border-dashed border-gray-200 rounded-lg">
              <p class="text-gray-600">{{ t('Aún no hay activos registrados.') }}</p>
            </div>
          </section>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Dashboard from '@/Layouts/Dashboard.vue'
import CompanyDataTab from '@/Components/Organization/CompanyDataTab.vue'
import { useTranslations } from '@/composables/useTranslations'

const { t } = useTranslations()
const page = usePage()

const props = defineProps({
  title: String,
  organization: {
    type: Object,
    required: true,
  },
  dashboardData: {
    type: [Object, Array],
    default: () => ({}),
  },
  evaluations: {
    type: Array,
    default: () => [],
  },
})

const activeTab = ref('company')
const isDownloading = ref(false)
const downloadState = ref('idle') // 'idle' | 'generating' | 'downloading'

// Check if user can view preview (only admin and super-admin)
const canViewPreview = computed(() => {
  const auth = page.props.auth
  const userRoles = auth?.user?.roles || []
  return userRoles.some(role => role.name === 'admin' || role.name === 'super-admin')
})

// Handle PDF download with loading states
async function handleDownloadPdf() {
  if (isDownloading.value) return
  
  try {
    isDownloading.value = true
    downloadState.value = 'generating'
    
    const response = await fetch(`/reportes/pdf/nom002/${props.organization.id}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf',
      },
    })
    
    if (!response.ok) {
      throw new Error('Error al generar el PDF')
    }
    
    downloadState.value = 'downloading'
    
    // Get the blob from response
    const blob = await response.blob()
    
    // Create a download link
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    // Generate filename
    const orgName = props.organization?.name || 'organization'
    const date = new Date().toISOString().split('T')[0]
    link.download = `informe-nom002-${orgName}-${date}.pdf`
    
    // Trigger download
    document.body.appendChild(link)
    link.click()
    
    // Cleanup
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
  } catch (error) {
    console.error('Error downloading PDF:', error)
    alert(t('Error al descargar el PDF. Por favor, intenta nuevamente.'))
  } finally {
    isDownloading.value = false
    downloadState.value = 'idle'
  }
}

const orgName = computed(() => props.dashboardData?.organization?.name || props.organization?.name || t('Organización'))
const orgLogo = computed(() => props.dashboardData?.organization?.logo || props.organization?.logo || null)

const translatedTabs = computed(() => [
  { key: 'company', label: t('Company Data') },
  { key: 'reports', label: t('Reporte') },
])

const companyData = computed(() => ({
  general: props.dashboardData?.company_data?.general || {},
  workforce: props.dashboardData?.company_data?.workforce || {},
  sample: props.dashboardData?.company_data?.sample || {},
  evaluation_date: props.dashboardData?.company_data?.evaluation_date || null,
  committee: props.dashboardData?.company_data?.committee || {},
  address: props.dashboardData?.company_data?.address || {},
  contact: props.dashboardData?.company_data?.contact || {},
  responsible: props.dashboardData?.company_data?.responsible || {},
}))

const hasCompanyData = computed(() =>
  Object.values(companyData.value).some(section => {
    if (section === null || section === undefined) return false
    if (typeof section === 'object') {
      return Object.keys(section).length > 0
    }
    return Boolean(section)
  }),
)

const assets = computed(() => props.dashboardData?.assets || [])

function statusClasses(status) {
  switch (status) {
    case 'Completado':
    case 'Completed':
      return 'bg-green-50 text-green-700'
    case 'En progreso':
    case 'In Progress':
      return 'bg-yellow-50 text-yellow-700'
    case 'Pendiente':
    case 'Pending':
    default:
      return 'bg-gray-100 text-gray-700'
  }
}
</script>