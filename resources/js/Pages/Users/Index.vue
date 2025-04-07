<script setup>

import Dashboard from "../../Layouts/Dashboard.vue";

import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid'
import {defineProps} from "vue";
const { users } = defineProps({
    users: {
        type: Object,
        required: true,
    },
});

const userRole = (role) => {
    console.log(role);
    switch (role) {
        case 'super-admin':
            return 'Super Admin';
        case 'admin':
            return 'Administrador';
        case 'organization':
            return 'Organización';
        default:
            return 'Invitado';
    }
}

</script>

<template>
    <Dashboard>
        <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="user in users" :key="user.id" class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
                <div class="flex w-full items-center justify-between space-x-6 p-6">
                    <div class="flex-1 truncate">
                        <div class="flex items-center space-x-3">
                            <h3 class="truncate text-sm font-medium text-gray-900">{{ user.name }}</h3>
                            <span class="inline-flex flex-shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ userRole(user.role) }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500">
                            <span class="flex items-center gap-2">
                                <img v-if="user.organization?.logo" :src="user.organization.logo" alt="Logo" class="h-4 w-4 object-contain" />
                                <div>
                                    {{ user.organization.name }} <template v-if="user.temporal_password"> | Contraseña temporal: <span class="font-medium">{{ user.temporal_password }}</span></template>
                                </div>
                            </span>
                        </p>
                    </div>
                </div>
                <div>
                    <div class="-mt-px flex divide-x divide-gray-200">
                        <div class="flex w-0 flex-1">
                            <a :href="route('users.edit', user)" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                                <PencilSquareIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                Editar
                            </a>
                        </div>
                        <div class="-ml-px flex w-0 flex-1">
                            <a href="#" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                                <TrashIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                Desactivar
                            </a>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </Dashboard>
</template>

<style scoped>

</style>
