<script setup>
import Dashboard from '../../Layouts/Dashboard.vue'
import CreationWizard from '../../Components/Organizations/CreationWizard.vue'

</script>

<template>
    <Dashboard>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    Crear Nueva Organización
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Complete la información en dos simples pasos: primero los datos corporativos y luego el centro de trabajo principal.
                </p>
            </div>

            <!-- Creation Wizard -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <CreationWizard />
                </div>
            </div>
        </div>
    </Dashboard>
</template>

