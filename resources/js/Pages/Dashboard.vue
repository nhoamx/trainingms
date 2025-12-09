<script setup>
import DashboardLayout from "../Layouts/Dashboard.vue"; // Layout principal
import AdminDashboard from '../Components/AdminDashboard.vue';
import OrganizationOverviewDashboard from '../Components/OrganizationOverviewDashboard.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3'
import Dashboard from "./Organizations/Dashboard.vue";


const props = defineProps({
    // Admin props
    evaluations: { default: () => [] },
    organizations: { default: () => [] },
    demographic_data: { default: () => ({}) },
    category_qualifications: { default: () => [] },
    domain_qualifications: { default: () => [] },
    demographic_distributions: { default: () => ([]) },
    isAdmin: { default: false },
    isSuperAdmin: { default: false },
    // Organization dashboard props
    organization: { default: () => null },
    evaluation_stats: { default: () => [] },
    instrument_routes: { default: () => ({}) },
    recent_evaluations: { default: () => [] },
    onboarding_tips: { default: () => [] },
});

const page = usePage();
const currentOrganization = computed(() => page.props.currentOrganization);


// Determina si la vista actual es para un admin o superadmin
const showAdminView = computed(() => props.isAdmin || props.isSuperAdmin);

</script>

<template>
    <DashboardLayout title="Dashboard">
        <div v-if="showAdminView">
            <AdminDashboard 
                :evaluations="props.evaluations" 
                :organizations="props.organizations"
                :demographic_data="props.demographic_data"
                :category_qualifications="props.category_qualifications"
                :domain_qualifications="props.domain_qualifications"
                :demographic_distributions="props.demographic_distributions"
            />
        </div>
        <div v-else>
            <Dashboard /> 
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Estilos que eran específicos de Dashboard.vue y no se movieron pueden permanecer o eliminarse si no son necesarios */
</style>
