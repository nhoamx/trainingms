<script setup>
import { ref } from "vue";
import Dashboard from "../../Layouts/Dashboard.vue";
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import Alert from '../../Components/Alert.vue';

const props = defineProps({
    organizations: Array,
    noOrgEvaluations: Array,
    successMessage: String
});

const navigateToOrganizationEvaluations = (org) => {
    router.visit(route('organizations.evaluations', org.id));
};
</script>

<template>
    <Dashboard>
        <Alert
            v-if="$page.props.flash"
            :type="$page.props.flash.type"
            :title="$page.props.flash.title"
            :message="$page.props.flash.message"
            class="my-4"
        />
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Organization Cards -->
            <div v-for="org in organizations" :key="org.id"
                 @click="navigateToOrganizationEvaluations(org)"
                 class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">{{ org.name }}</h3>
                    <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    {{ org.evaluations?.length || 0 }} evaluaciones
                </p>
            </div>

            <!-- No Organization Card -->
            <div v-if="noOrgEvaluations?.length > 0"
                 @click="navigateToOrganizationEvaluations({ id: 'no-org', name: 'Sin Organización', evaluations: noOrgEvaluations })"
                 class="bg-gray-50 rounded-lg shadow-md p-6 cursor-pointer hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-700">Sin Organización</h3>
                    <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    {{ noOrgEvaluations.length }} evaluaciones
                </p>
            </div>
        </div>
    </Dashboard>
</template>

<style scoped>

</style>
