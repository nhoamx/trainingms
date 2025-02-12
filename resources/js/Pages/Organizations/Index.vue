<script setup>

import Dashboard from "../../Layouts/Dashboard.vue";
import EmptyState from "../../Components/EmptyState.vue";

const props = defineProps({
    organizations: {
        type: Array,
    },
});

console.log(props.organizations.length);

</script>

<template>
    <Dashboard>
        <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6 ">
            <div class="-ml-4 -mt-2 flex flex-wrap items-center justify-between sm:flex-nowrap">
                <div class="ml-4 mt-2">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Lista de organizaciones</h3>
                </div>
                <div class="ml-4 mt-2 flex-shrink-0">
                    <Link
                        :href="route('organizations.create')"
                        class="relative inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Crear nueva organización </Link>
                </div>
            </div>
        </div>
        <EmptyState
            v-if="!organizations || organizations.length === 0"
            title="Sin organiaciones por el momento"
            text="Puedes comenzar a añadir tu primera organización."
            buttonText="Añade tu primera organización"
            :buttonAction="() => $inertia.visit(route('organizations.create'))"
            class="py-6 bg-white sm:rounded-b-xl"
        />
        <ul v-else role="list" class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm ring-gray-900/5 sm:rounded-b-xl">
            <li v-for="organization in props.organizations" :key="organization.id" class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6">
                <div class="flex min-w-0 gap-x-4">
                    <img v-if="organization.logo" class="h-12 w-auto flex-none rounded-full bg-gray-50" :src="organization.logo" alt="" />
                    <div class="min-w-0 flex-auto">
                        <p class="text-sm font-semibold leading-6 text-gray-900">
                            <a :href="route('organizations.edit', organization)">
                                <span class="absolute inset-x-0 -top-px bottom-0" />
                                {{ organization.name }}
                            </a>
                        </p>
                        <p class="mt-1 flex text-xs leading-5 text-gray-500">
                            {{ organization.id }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-x-4">
                    <div class="hidden sm:flex sm:flex-col sm:items-end">
                        <p class="text-sm leading-6 text-gray-900">Ver información</p>

                        <div v-if="!organization.deleted_at" class="mt-1 flex items-center gap-x-1.5">
                            <div class="flex-none rounded-full bg-emerald-500/20 p-1">
                                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            </div>
                            <p class="text-xs leading-5 text-gray-500">Activo</p>
                        </div>
                        <div v-else class="mt-1 flex items-center gap-x-1.5">
                            <div class="flex-none rounded-full bg-red-500/20 p-1">
                                <div class="h-1.5 w-1.5 rounded-full bg-red-500" />
                            </div>
                            <p class="text-xs leading-5 text-gray-500">Desactivado</p>
                        </div>
                    </div>
                    <ChevronRightIcon class="h-5 w-5 flex-none text-gray-400" aria-hidden="true" />
                </div>
            </li>
        </ul>
    </Dashboard>
</template>

<style scoped>

</style>
