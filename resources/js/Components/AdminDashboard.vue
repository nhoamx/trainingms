<template>
  <div>
    <!-- Header with search and filters -->
    <div class="mb-6 space-y-4">
      <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">Organizaciones</h2>
      </div>

      <!-- Search and Filters -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search Input -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Buscar organización
          </label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por nombre..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>

        <!-- Evaluation Type Filters -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Filtrar por tipo
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="filter in availableFilters"
              :key="filter.key"
              @click="toggleFilter(filter.key)"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-md transition-all',
                activeFilters.includes(filter.key)
                  ? filter.activeClass
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ filter.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="filteredOrganizations.length === 0" class="bg-white p-8 rounded-lg shadow text-center">
      <div class="text-gray-400 text-6xl mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="w-16 h-16 mx-auto">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
      </div>
      <p class="text-gray-500 text-lg">
        {{ searchQuery || activeFilters.length > 0 
          ? 'No se encontraron organizaciones con los filtros aplicados.' 
          : 'No hay organizaciones registradas.' 
        }}
      </p>
      <button 
        v-if="searchQuery || activeFilters.length > 0"
        @click="clearFilters"
        class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
      >
        Limpiar filtros
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="org in filteredOrganizations" :key="org.id"
        class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-visible">
        <div class="p-5 rounded-t-lg">
          <div class="flex items-start justify-between space-x-4">
            <div class="flex items-center space-x-3 flex-1 min-w-0">
              <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center bg-gray-100 rounded-full">
                <img v-if="org.logo" :src="`/storage/${org.logo}`" alt="Logo" class="h-9 w-9 object-contain" />
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-base text-gray-900 truncate">{{ org.name }}</h3>
                <div class="flex items-center text-xs text-gray-500 mt-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span>{{ org.evaluations_count }} evaluaciones</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Badges Section -->
          <div class="flex flex-wrap gap-1.5 mt-3">
            <span
              v-if="org.has_nom_035"
              :class="getBadgeClass('nom035')"
            >
              NOM-035
            </span>
            <span
              v-if="org.has_clima_laboral"
              :class="getBadgeClass('clima')"
            >
              Clima Laboral
            </span>
            <span
              v-if="org.has_online_evaluations"
              :class="getBadgeClass('online')"
            >
              En Línea ({{ org.online_evaluations_count }})
            </span>
            <span
              v-if="org.has_nom_002"
              :class="getBadgeClass('nom002')"
            >
              NOM-002
            </span>
            <span
              v-if="!org.has_nom_035 && !org.has_clima_laboral && !org.has_online_evaluations && !org.has_nom_002"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
            >
              Sin evaluaciones
            </span>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3 rounded-b-lg">
          <div class="flex gap-2">
            
            <!-- Primary Action Button -->
            <a 
              :href="route('organization.dashboard', { organization: org.id })"
              class="flex-1 flex items-center justify-center text-white bg-blue-600 hover:bg-blue-700 font-medium py-2.5 px-4 transition-colors rounded-md shadow-sm hover:shadow-md"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <span class="text-sm">Dashboard</span>
            </a>

            <!-- Dropdown Menu -->
            <Menu as="div" class="relative inline-block text-left">
              <MenuButton
                class="flex items-center justify-center text-gray-700 bg-white hover:bg-gray-50 font-medium py-2.5 px-3 transition-colors border border-gray-300 rounded-md shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </MenuButton>

              <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <MenuItems
                  class="absolute right-0 mt-2 w-60 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                >
                  <!-- Evaluaciones Section -->
                  <div class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                      <a
                        :href="route('organization.results.list', { organization: org.id })"
                        :class="[
                          active ? 'bg-gray-50 text-gray-900' : 'text-gray-900',
                          'group flex w-full items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-gray-600" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Evaluaciones
                      </a>
                    </MenuItem>
                    
                    <MenuItem v-if="org.has_online_evaluations" v-slot="{ active }">
                      <a
                        :href="route('organization.online-results', { id: org.id })"
                        :class="[
                          active ? 'bg-emerald-50 text-emerald-900' : 'text-gray-900',
                          'group flex w-full items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-emerald-600" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Evaluaciones En Línea
                      </a>
                    </MenuItem>
                  </div>

                  <!-- Reportes Section -->
                  <div class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                      <a
                        :href="route(org.is_likert_only ? 'organization.likert.report' : 'organization.report', { id: org.id })"
                        :class="[
                          active ? 'bg-blue-50 text-blue-900' : 'text-gray-900',
                          'group flex w-full items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Reporte General
                      </a>
                    </MenuItem>

                    <MenuItem v-if="org.has_online_evaluations" v-slot="{ active }">
                      <a
                        :href="route('organization.online-results.report', { id: org.id })"
                        :class="[
                          active ? 'bg-indigo-50 text-indigo-900' : 'text-gray-900',
                          'group flex w-full items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-indigo-600" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Reporte En Línea
                      </a>
                    </MenuItem>
                  </div>

                  <!-- NOM-002 Section -->
                  <div v-if="org.has_nom_002" class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                      <a
                        :href="route('organizations.assets.index', { organization: org.id })"
                        :class="[
                          active ? 'bg-red-50 text-red-900' : 'text-gray-900',
                          'group flex w-full items-center rounded-md px-3 py-2 text-sm font-medium'
                        ]"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-3 text-red-600" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                        </svg>
                        NOM-002 Incendios
                      </a>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { ref, computed } from 'vue';

const props = defineProps({
  organizations: {
    type: Array,
    default: () => []
  }
});

defineEmits(['see-report']);

// Search and filter state
const searchQuery = ref('');
const activeFilters = ref([]);

// Available filters
const availableFilters = [
  { 
    key: 'nom_035', 
    label: 'NOM-035', 
    activeClass: 'bg-blue-600 text-white',
    checkFn: (org) => org.has_nom_035 
  },
  { 
    key: 'clima_laboral', 
    label: 'Clima Laboral', 
    activeClass: 'bg-purple-600 text-white',
    checkFn: (org) => org.has_clima_laboral 
  },
  { 
    key: 'online', 
    label: 'En Línea', 
    activeClass: 'bg-emerald-600 text-white',
    checkFn: (org) => org.has_online_evaluations 
  },
  { 
    key: 'nom_002', 
    label: 'NOM-002', 
    activeClass: 'bg-red-600 text-white',
    checkFn: (org) => org.has_nom_002 
  }
];

// Badge color classes
const getBadgeClass = (type) => {
  const classes = {
    nom035: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ring-1 ring-inset ring-blue-600/20',
    clima: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ring-1 ring-inset ring-purple-600/20',
    online: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-600/20',
    nom002: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20'
  };
  return classes[type] || classes.nom035;
};

// Toggle filter
const toggleFilter = (filterKey) => {
  const index = activeFilters.value.indexOf(filterKey);
  if (index > -1) {
    activeFilters.value.splice(index, 1);
  } else {
    activeFilters.value.push(filterKey);
  }
};

// Clear all filters
const clearFilters = () => {
  searchQuery.value = '';
  activeFilters.value = [];
};

// Filtered organizations
const filteredOrganizations = computed(() => {
  let filtered = props.organizations;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(org => 
      org.name.toLowerCase().includes(query)
    );
  }

  // Apply type filters (OR logic - show if matches ANY active filter)
  if (activeFilters.value.length > 0) {
    filtered = filtered.filter(org => {
      return activeFilters.value.some(filterKey => {
        const filter = availableFilters.find(f => f.key === filterKey);
        return filter && filter.checkFn(org);
      });
    });
  }

  return filtered;
});
</script>
