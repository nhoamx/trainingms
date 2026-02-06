<script setup>
import { ref, computed } from 'vue'
import Dashboard from '../../Layouts/Dashboard.vue'
import { PencilSquareIcon, LockClosedIcon, MagnifyingGlassIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
})

const searchQuery = ref('')
const selectedRole = ref('')

const userRole = (role) => {
    const roleMap = {
        'super-admin': { label: 'Super Admin', color: 'purple' },
        'admin': { label: 'Administrador', color: 'blue' },
        'organization': { label: 'Organización', color: 'green' },
        'work_center_user': { label: 'Usuario de Centro de Trabajo', color: 'gray' },
    }
    return roleMap[role] || { label: 'Invitado', color: 'gray' }
}

const getRoleBadgeClass = (role) => {
    const roleInfo = userRole(role)
    const colorMap = {
        purple: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        blue: 'bg-blue-50 text-blue-700 ring-blue-600/20',
        green: 'bg-green-50 text-green-700 ring-green-600/20',
        gray: 'bg-gray-50 text-gray-700 ring-gray-600/20',
    }
    return colorMap[roleInfo.color] || colorMap.gray
}

const uniqueRoles = computed(() => {
    const roles = [...new Set(props.users.map(u => u.role))]
    return roles.map(role => ({
        value: role,
        label: userRole(role).label
    }))
})

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        const matchesSearch = searchQuery.value === '' ||
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.organization.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        
        const matchesRole = selectedRole.value === '' || user.role === selectedRole.value
        
        return matchesSearch && matchesRole
    })
})

const handleDisableUser = (user) => {
    if (confirm(`¿Estás seguro de que deseas ${user.is_disabled ? 'activar' : 'desactivar'} a ${user.name}?`)) {
        const routeName = user.is_disabled ? 'users.enable' : 'users.disable'
        router.post(route(routeName, user.id), {}, {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <Dashboard>
        <div class="space-y-6">
            <!-- Header con búsqueda y filtros -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Búsqueda -->
                    <div class="flex-1">
                        <label for="search" class="sr-only">Buscar usuarios</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                            </div>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="search"
                                placeholder="Buscar por nombre, email u organización..."
                                class="block w-full rounded-md border-gray-300 pl-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    <!-- Filtro de rol -->
                    <div class="md:w-64">
                        <label for="role-filter" class="sr-only">Filtrar por rol</label>
                        <select
                            id="role-filter"
                            v-model="selectedRole"
                            class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        >
                            <option value="">Todos los roles</option>
                            <option v-for="role in uniqueRoles" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="mt-4 text-sm text-gray-500">
                    Mostrando {{ filteredUsers.length }} de {{ users.length }} usuarios
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Usuario
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Organización
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rol
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No se encontraron usuarios
                                </td>
                            </tr>
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <!-- Usuario -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            <div v-if="user.temporal_password" class="flex items-center gap-1 text-xs text-orange-600 mt-1">
                                                <LockClosedIcon class="h-3 w-3" />
                                                <span>Contraseña temporal: <span class="font-mono font-semibold">{{ user.temporal_password }}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ user.email || 'Sin email' }}</div>
                                </td>

                                <!-- Organización -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col items-start gap-2">
                                        <img v-if="user.organization?.logo" :src="user.organization.logo" alt="Logo" class="h-6 object-contain" />
                                        <span v-else class="text-sm text-wrap text-gray-900">{{ user.organization.name }}</span>
                                    </div>
                                </td>

                                <!-- Rol -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        getRoleBadgeClass(user.role)
                                    ]">
                                        {{ userRole(user.role).label }}
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="!user.is_disabled" class="inline-flex items-center gap-1 text-sm text-green-700">
                                        <CheckCircleIcon class="h-4 w-4" />
                                        Activo
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 text-sm text-red-700">
                                        <XCircleIcon class="h-4 w-4" />
                                        Desactivado
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex flex-col items-start justify-end gap-3">
                                        <a
                                            :href="route('users.edit', user.id)"
                                            class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-1"
                                            title="Editar usuario"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                            Editar
                                        </a>
                                        <button
                                            type="button"
                                            @click="handleDisableUser(user)"
                                            :class="[
                                                'inline-flex items-center gap-1',
                                                user.is_disabled 
                                                    ? 'text-green-600 hover:text-green-900' 
                                                    : 'text-red-600 hover:text-red-900'
                                            ]"
                                            :title="user.is_disabled ? 'Activar usuario' : 'Desactivar usuario'"
                                        >
                                            <XCircleIcon v-if="!user.is_disabled" class="h-4 w-4" />
                                            <CheckCircleIcon v-else class="h-4 w-4" />
                                            {{ user.is_disabled ? 'Activar' : 'Desactivar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Dashboard>
</template>
