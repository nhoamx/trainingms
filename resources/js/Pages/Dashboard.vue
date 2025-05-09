<script setup>
import DashboardLayout from "../Layouts/Dashboard.vue"; // Asumo que este es el Layout principal
import AdminDashboard from '../Components/AdminDashboard.vue';
import ReportSummaryDashboard from '../Components/ReportSummaryDashboard.vue'; // Componente para la vista de reportes
import { ref, computed } from 'vue'; // computed podría no ser necesario aquí directamente
import { usePage } from '@inertiajs/vue3'


const props = defineProps({
    evaluations: {
        default: () => []
    },
    organizations: { // Necesario para AdminDashboard
        default: () => []
    },
    demographic_data: {
        default: () => ({})
    },
    category_qualifications: {
        default: () => []
    },
    domain_qualifications: {
        default: () => []
    },
    demographic_distributions: {
        default: () => ([])
    },
    isAdmin: {
        default: false
    },
    isSuperAdmin: {
        default: false
    }
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
            <ReportSummaryDashboard
                :organizations="props.organizations"
                :is-admin="props.isAdmin"
                :is-super-admin="props.isSuperAdmin"
                :current-organization="currentOrganization"
            />
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Estilos que eran específicos de Dashboard.vue y no se movieron pueden permanecer o eliminarse si no son necesarios */
</style>
