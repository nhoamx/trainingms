<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar Component -->
        <Sidebar 
            ref="sidebarRef"
            :navigation="navigation"
            :user-navigation="userNavigation"
            :user="user"
            :csrf-token="csrfToken"
            @toggle-sidebar="handleSidebarToggle"
        />

        <!-- Main content area -->
        <div 
            :class="[
                'transition-all duration-300 ease-in-out',
                sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'
            ]"
        >
            <!-- Top bar (mobile menu + page title) -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Mobile menu button -->
                    <button
                        @click="toggleMobileMenu"
                        class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        aria-label="Abrir menú"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page title -->
                    <div class="flex-1">
                        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl">{{ title }}</h1>
                    </div>

                    <!-- Right side actions -->
                    <div class="flex items-center gap-2">
                        <!-- Notification Center -->
                        <NotificationCenter />

                        <!-- Action button (if provided) -->
                        <div v-if="action">
                            <Link 
                                :href="action.route" 
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ action.title }}
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main content -->
            <main class="p-4 sm:p-6 lg:p-8">
                    <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Sidebar from '../Components/Sidebar.vue';
import NotificationCenter from '../Components/NotificationCenter.vue';


const page = usePage();
const user = computed(() => page.props.auth.user);
const title = computed(() => page.props.title || 'Dashboard');
const action = computed(() => page.props.action || null);
const csrfToken = computed(() => page.props.csrf_token);

const sidebarRef = ref(null);
const sidebarCollapsed = ref(false);

const handleSidebarToggle = (collapsed) => {
  sidebarCollapsed.value = collapsed;
};

const toggleMobileMenu = () => {
  if (sidebarRef.value) {
    sidebarRef.value.mobileMenuOpen = !sidebarRef.value.mobileMenuOpen;
  }
};

const navigation = computed(() => {
    // Check if user has work_center_user role
    const isWorkCenterUser = user.value.roles?.some(role => role.name === 'work_center_user');
    
    if (isWorkCenterUser) {
        return [
            { 
                name: 'Dashboard', 
                href: route('my-work-centers'), 
                current: route().current('my-work-centers'),
                icon: 'home'
            },
        ];
    }

    // Check if user has organization role
    const isOrganizationUser = user.value.roles?.some(role => role.name === 'organization');

    if (isOrganizationUser) {
        const orgId = user.value.organization_id;
        
        const navItems = [
            { 
                name: 'Dashboard', 
                href: route('dashboard'), 
                current: route().current('dashboard'),
                icon: 'home'
            },
        ];
        
        if (orgId) {
            navItems.push({ 
                name: 'Datos de la empresa', 
                href: route('company-data.edit', orgId), 
                current: route().current('company-data.*'),
                icon: 'building'
            });
        }
        
        return navItems;
    }

    // Admin/super-admin users see full menu with icons
    return [
        { 
            name: 'Dashboard', 
            href: route('dashboard'), 
            current: route().current('dashboard'),
            icon: 'home'
        },
        { 
            name: 'Organizaciones', 
            href: route('organizations.index'), 
            current: route().current('organizations.*'),
            icon: 'building',
            items: [
                { name: 'Listado', href: route('organizations.index'), current: route().current('organizations.index') },
                { name: 'Crear', href: route('organizations.create'), current: route().current('organizations.create') },
            ] 
        },
        { 
            name: 'Evaluaciones', 
            href: route('evaluations.load'), 
            current: route().current('evaluations.*'),
            icon: 'document'
        },
        { 
            name: 'Gestión de Audio', 
            href: route('audio.index'), 
            current: route().current('audio.*'),
            icon: 'microphone',
            items: [
                { name: 'Biblioteca', href: route('audio.index'), current: route().current('audio.index') },
                { name: 'Subir Archivos', href: route('audio.upload'), current: route().current('audio.upload') },
            ]
        },
        { 
            name: 'Exámenes', 
            href: route('quiz.index'), 
            current: route().current('quiz.*'),
            icon: 'calendar'
        },
        { 
            name: 'Usuarios', 
            href: route('users.index'), 
            current: route().current('users.index'),
            icon: 'users'
        },
    ];
});

const userNavigation = [
    { name: 'Mi Perfil', href: route('profile') },
    { name: 'Cerrar sesión', href: route('logout'), method: 'post' },
];
</script>

