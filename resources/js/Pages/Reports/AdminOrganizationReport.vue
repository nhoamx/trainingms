<template>
    <Dashboard :title="'Reporte de Organización'">
        <div class="mx-auto">
                <div class="p-6">
                    <ReportSummaryDashboard 
                        :currentOrganization="organizationId"
                        :organizations="[]"
                        :isAdmin="true"
                    />
                </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import Dashboard from '@/Layouts/Dashboard.vue';
import ReportSummaryDashboard from '@/Components/ReportSummaryDashboard.vue';

defineProps({
    organizationId: {
        type: String,
        required: true
    }
});
</script>