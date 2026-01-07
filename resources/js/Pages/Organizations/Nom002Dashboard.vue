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
import { Link } from '@inertiajs/vue3'
import Dashboard from '@/Layouts/Dashboard.vue'
import CompanyDataTab from '@/Components/Organization/CompanyDataTab.vue'
import { useTranslations } from '@/composables/useTranslations'

const { t } = useTranslations()

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