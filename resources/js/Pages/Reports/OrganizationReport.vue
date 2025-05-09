<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <ReportSummaryDashboard 
            :currentOrganization="organizationId"
            :organizations="[]"
            :isAdmin="true"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import ReportSummaryDashboard from '@/Components/ReportSummaryDashboard.vue';

defineProps({
  organizationId: {
    type: String,
    required: true
  }
});
</script>
