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
          <label for="search-input" class="block text-sm font-medium text-gray-700 mb-2">
            Buscar organización
          </label>
          <input
            id="search-input"
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por nombre..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            aria-describedby="search-help"
          />
          <p id="search-help" class="sr-only">Escribe el nombre de la organización que buscas</p>
        </div>

        <!-- Evaluation Type Filters -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Filtrar por tipo
            <span v-if="activeFilters.length > 0" class="text-blue-600 ml-2 font-semibold">
              ({{ activeFilters.length }} activo{{ activeFilters.length > 1 ? 's' : '' }})
            </span>
          </label>
          <div class="flex flex-wrap gap-2" role="group" aria-label="Filtros de tipo de evaluación">
            <button
              v-for="filter in availableFilters"
              :key="filter.key"
              @click="toggleFilter(filter.key)"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded-md transition-all inline-flex items-center gap-1',
                activeFilters.includes(filter.key)
                  ? filter.activeClass
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
              :aria-label="`Filtrar por ${filter.label}`"
              :aria-pressed="activeFilters.includes(filter.key)"
            >
              {{ filter.label }}
              <svg 
                v-if="activeFilters.includes(filter.key)" 
                class="h-3.5 w-3.5" 
                fill="currentColor" 
                viewBox="0 0 20 20"
                aria-hidden="true"
              >
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Estado de filtros aplicados -->
      <div 
        v-if="searchQuery || activeFilters.length > 0" 
        class="flex items-center justify-between p-3 bg-blue-50 rounded-md border border-blue-200"
      >
        <p class="text-sm text-blue-800">
          Mostrando <span class="font-semibold">{{ filteredOrganizations.length }}</span> de <span class="font-semibold">{{ props.organizations.length }}</span> organizaciones
        </p>
        <button 
          @click="clearFilters"
          class="text-sm text-blue-600 hover:text-blue-800 font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded px-2 py-1"
        >
          Limpiar todos los filtros
        </button>
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

    <!-- Compact card list -->
    <div v-else class="space-y-2">
      <div v-for="org in filteredOrganizations" :key="org.id"
        class="bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-md transition-all group"
      >
        <div class="flex items-center gap-4 p-3">
          <!-- Logo -->
          <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-50 rounded-lg group-hover:bg-blue-50 transition-colors">
            <img v-if="org.logo" :src="`/storage/${org.logo}`" :alt="`Logo de ${org.name}`" class="h-8 w-8 object-contain" />
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-3">
              <h3 class="font-semibold text-gray-900 truncate">{{ org.name }}</h3>
              <span class="text-xs text-gray-500 flex-shrink-0">
                {{ org.evaluations_count }} evaluaciones
              </span>
            </div>
            
            <!-- Inline badges -->
            <div class="flex items-center gap-1.5 mt-1">
              <span v-if="org.has_nom_035" :class="getBadgeClass('nom035')">NOM-035</span>
              <span v-if="org.has_clima_laboral" :class="getBadgeClass('clima')">Clima</span>
              <span v-if="org.has_online_evaluations" :class="getBadgeClass('online')">
                En Línea ({{ org.online_evaluations_count }})
              </span>
              <span v-if="org.has_nom_002" :class="getBadgeClass('nom002')">NOM-002</span>
              <span
                v-if="!org.has_nom_035 && !org.has_clima_laboral && !org.has_online_evaluations && !org.has_nom_002"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500"
              >
                Sin evaluaciones
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex-shrink-0 flex items-center gap-2">
            <!-- Primary CTA -->
            <Link 
              :href="route('organization.dashboard', { organization: org.id })"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Ver detalles
            </Link>

            <!-- Dropdown Menu (compact) -->
            <Menu as="div" class="relative">
              <MenuButton
                class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Más opciones"
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
                  class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                >
                  <!-- Evaluaciones -->
                  <div class="p-1">
                    <MenuItem v-slot="{ active }">
                      <Link
                        :href="route('organization.results.list', { organization: org.id })"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'group flex items-center rounded px-3 py-2 text-sm text-gray-900'
                        ]"
                      >
                        <svg class="h-4 w-4 mr-2 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Evaluaciones
                      </Link>
                    </MenuItem>
                    
                    <MenuItem v-if="org.has_online_evaluations" v-slot="{ active }">
                      <Link
                        :href="route('organization.online-results', { id: org.id })"
                        :class="[
                          active ? 'bg-emerald-50' : '',
                          'group flex items-center rounded px-3 py-2 text-sm text-gray-900'
                        ]"
                      >
                        <svg class="h-4 w-4 mr-2 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Evaluaciones En Línea
                      </Link>
                    </MenuItem>
                  </div>

                  <!-- Reportes -->
                  <div class="p-1">
                    <MenuItem v-slot="{ active }">
                      <Link
                        :href="route(org.is_likert_only ? 'organization.likert.report' : 'organization.report', { id: org.id })"
                        :class="[
                          active ? 'bg-blue-50' : '',
                          'group flex items-center rounded px-3 py-2 text-sm text-gray-900'
                        ]"
                      >
                        <svg class="h-4 w-4 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Reporte General
                      </Link>
                    </MenuItem>

                    <MenuItem v-if="org.has_online_evaluations" v-slot="{ active }">
                      <Link
                        :href="route('organization.online-results.report', { id: org.id })"
                        :class="[
                          active ? 'bg-indigo-50' : '',
                          'group flex items-center rounded px-3 py-2 text-sm text-gray-900'
                        ]"
                      >
                        <svg class="h-4 w-4 mr-2 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Reporte En Línea
                      </Link>
                    </MenuItem>
                  </div>

                  <!-- NOM-002 -->
                  <div v-if="org.has_nom_002" class="p-1">
                    <MenuItem v-slot="{ active }">
                      <Link
                        :href="route('organizations.assets.index', { organization: org.id })"
                        :class="[
                          active ? 'bg-red-50' : '',
                          'group flex items-center rounded px-3 py-2 text-sm text-gray-900'
                        ]"
                      >
                        <svg class="h-4 w-4 mr-2 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                        </svg>
                        NOM-002 Incendios
                      </Link>
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
import { Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  organizations: {
    type: Array,
    default: () => []
  }
});

defineEmits(['see-report']);

// Search and filter state
const searchQuery = ref('');
const debouncedSearchQuery = ref('');
const activeFilters = ref([]);

// Debounce search function (300ms delay)
let debounceTimeout = null;
watch(searchQuery, (newValue) => {
  if (debounceTimeout) {
    clearTimeout(debounceTimeout);
  }
  debounceTimeout = setTimeout(() => {
    debouncedSearchQuery.value = newValue;
  }, 300);
});

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

// Badge color classes (compact version)
const getBadgeClass = (type) => {
  const classes = {
    nom035: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700',
    clima: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700',
    online: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700',
    nom002: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700'
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

  // Apply search filter (using debounced query)
  if (debouncedSearchQuery.value) {
    const query = debouncedSearchQuery.value.toLowerCase();
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
