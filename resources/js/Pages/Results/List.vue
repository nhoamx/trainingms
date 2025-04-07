<template>

    <Dashboard>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <Table
                        :data="evaluations"
                        :columns="columns"
                        class="w-full"
                    >
                        <template #folio="{ row }">
                            <Link
                                :href="route('organization.results.detail', { organization: organization.id, evaluation: row.id })"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                {{ row.folio }}
                            </Link>
                        </template>
                    </Table>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Table from '@/Components/Table.vue'
import Dashboard from "../../Layouts/Dashboard.vue";

defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluations: {
        type: Array,
        required: true
    }
})

const columns = [
    {
        header: 'Folio',
        key: 'folio'
    },
    {
        header: 'Fecha',
        key: 'created_at'
    },
    {
        header: 'Puntaje Total',
        key: 'total_score'
    }
]
</script>
