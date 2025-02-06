<script setup>
import { defineProps } from "vue";
import Dashboard from "../../Layouts/Dashboard.vue";
import { ChevronRightIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
    assessees: Object
});
</script>

<template>
    <Dashboard>

        <div class="bg-white px-6 py-3 rounded-md shadow-md">
            <ul role="list" class="divide-y divide-gray-100">
                <li v-for="person in props.assessees" :key="person.id" class="relative flex justify-between gap-x-6 py-5">
                    <div class="flex min-w-0 gap-x-4">
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-gray-900">
                                <a href="#">
                                    <span class="absolute inset-x-0 -top-px bottom-0" />
                                    Folio: {{ person.folio }}
                                </a>
                            </p>
                            <p class="mt-1 flex text-xs leading-5 text-gray-500">
                                Identificador: {{ person.id }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4">
                        <div class="hidden sm:flex sm:flex-col sm:items-end">
                            <p class="text-sm leading-6 text-gray-900">{{ person.empresa }}</p>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 flex-none text-gray-400" aria-hidden="true" />
                    </div>
                </li>
            </ul>
        </div>

    </Dashboard>
</template>

<style scoped>

</style>
