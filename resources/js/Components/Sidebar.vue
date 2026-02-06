<template>
  <div>
    <!-- Mobile backdrop -->
    <div 
      v-if="mobileMenuOpen" 
      class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
      @click="mobileMenuOpen = false"
    ></div>

    <!-- Sidebar -->
    <div 
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col bg-gray-900 transition-all duration-300 ease-in-out',
        sidebarCollapsed ? 'w-20' : 'w-64',
        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo header -->
      <div class="flex h-16 items-center justify-between px-4 border-b border-gray-800">
        <Link :href="route('dashboard')" class="flex items-center">
          <span :class="['text-white font-bold text-xl', sidebarCollapsed ? 'hidden' : '']">
            T & MS
          </span>
          <span :class="['text-white font-bold text-xl', sidebarCollapsed ? '' : 'hidden']">
            T
          </span>
        </Link>
        
        <!-- Desktop toggle -->
        <button
          @click="toggleSidebar"
          class="hidden lg:flex p-1.5 rounded-md text-gray-400 hover:text-white hover:bg-gray-800"
          :aria-label="sidebarCollapsed ? 'Expandir sidebar' : 'Colapsar sidebar'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path 
              v-if="!sidebarCollapsed"
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
            <path 
              v-else
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M13 5l7 7-7 7M5 5l7 7-7 7"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <template v-for="item in navigation" :key="item.name">
          <!-- Item sin subitems -->
          <Link
            v-if="!item.items"
            :href="item.href"
            :class="[
              'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors group',
              item.current 
                ? 'bg-gray-800 text-white' 
                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            ]"
            :title="sidebarCollapsed ? item.name : ''"
          >
            <component :is="getIcon(item.icon)" class="w-5 h-5 flex-shrink-0" />
            <span :class="['truncate', sidebarCollapsed ? 'hidden' : '']">{{ item.name }}</span>
          </Link>

          <!-- Item con subitems (expandible) -->
          <div v-else>
            <button
              @click="toggleSubmenu(item.name)"
              :class="[
                'flex items-center justify-between w-full px-3 py-2 rounded-md text-sm font-medium transition-colors group',
                item.current 
                  ? 'bg-gray-800 text-white' 
                  : 'text-gray-300 hover:bg-gray-800 hover:text-white'
              ]"
              :title="sidebarCollapsed ? item.name : ''"
            >
              <div class="flex items-center gap-3">
                <component :is="getIcon(item.icon)" class="w-5 h-5 flex-shrink-0" />
                <span :class="['truncate', sidebarCollapsed ? 'hidden' : '']">{{ item.name }}</span>
              </div>
              <svg 
                v-if="!sidebarCollapsed"
                :class="['w-4 h-4 transition-transform', openSubmenus.includes(item.name) ? 'rotate-90' : '']"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <!-- Submenu items -->
            <div 
              v-if="openSubmenus.includes(item.name) && !sidebarCollapsed"
              class="ml-8 mt-1 space-y-1"
            >
              <Link
                v-for="subItem in item.items"
                :key="subItem.name"
                :href="subItem.href"
                :class="[
                  'flex items-center px-3 py-2 rounded-md text-sm transition-colors',
                  subItem.current 
                    ? 'text-white bg-gray-800' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                ]"
              >
                {{ subItem.name }}
              </Link>
            </div>
          </div>
        </template>
      </nav>

      <!-- User section (bottom) -->
      <div class="border-t border-gray-800 p-4">
        <Menu as="div" class="relative">
          <MenuButton 
            :class="[
              'flex items-center gap-3 w-full px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors',
              sidebarCollapsed ? 'justify-center' : ''
            ]"
          >
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-700 text-white text-sm font-medium flex-shrink-0">
              {{ user.name?.charAt(0).toUpperCase() }}
            </div>
            <span :class="['truncate', sidebarCollapsed ? 'hidden' : '']">{{ user.name }}</span>
          </MenuButton>

          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
          >
            <MenuItems class="absolute bottom-full left-0 mb-2 w-48 origin-bottom-left rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
              <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                <template v-if="item.method">
                  <form :action="item.href" method="post">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit" :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-gray-700']">
                      {{ item.name }}
                    </button>
                  </form>
                </template>
                <template v-else>
                  <Link :href="item.href" :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">
                    {{ item.name }}
                  </Link>
                </template>
              </MenuItem>
            </MenuItems>
          </transition>
        </Menu>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';

const props = defineProps({
  navigation: {
    type: Array,
    required: true
  },
  userNavigation: {
    type: Array,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  csrfToken: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['toggle-sidebar']);

const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const openSubmenus = ref([]);

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  emit('toggle-sidebar', sidebarCollapsed.value);
};

const toggleSubmenu = (name) => {
  const index = openSubmenus.value.indexOf(name);
  if (index > -1) {
    openSubmenus.value.splice(index, 1);
  } else {
    openSubmenus.value.push(name);
  }
};

// Icon mapping
const getIcon = (iconName) => {
  const icons = {
    home: 'svg',
    building: 'svg',
    document: 'svg',
    microphone: 'svg',
    calendar: 'svg',
    users: 'svg'
  };
  
  // Return inline SVG component
  return {
    template: getIconSvg(iconName)
  };
};

const getIconSvg = (iconName) => {
  const svgs = {
    home: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
    building: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>',
    document: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
    microphone: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" /></svg>',
    calendar: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>',
    users: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>'
  };
  
  return svgs[iconName] || svgs.home;
};

// Expose mobile menu control
defineExpose({
  mobileMenuOpen
});
</script>
