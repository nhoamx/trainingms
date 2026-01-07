<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          {{ organization.name }}
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
          Selecciona el tipo de evaluación que deseas consultar
        </p>
      </div>

      <!-- Evaluation Types Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Link
          v-for="evaluationType in evaluationTypes"
          :key="evaluationType.id"
          :href="evaluationType.route"
          class="group"
        >
          <div class="bg-white dark:bg-slate-900 rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 h-full flex flex-col justify-between">
            <!-- Icon or Badge -->
            <div class="mb-4">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900">
                <svg
                  class="w-6 h-6 text-blue-600 dark:text-blue-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                  />
                </svg>
              </div>
            </div>

            <!-- Content -->
            <div>
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ evaluationType.name }}
              </h2>
              <p class="text-gray-600 dark:text-gray-400 text-sm">
                {{ evaluationType.description }}
              </p>
            </div>

            <!-- Arrow Icon -->
            <div class="mt-4 flex items-center text-blue-600 dark:text-blue-400 group-hover:translate-x-1 transition-transform">
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </div>
          </div>
        </Link>
      </div>

      <!-- Back Link -->
      <div class="text-center mt-12">
        <Link
          href="/dashboard"
          class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
        >
          ← Volver al Dashboard
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  title: String,
  organization: {
    type: Object,
    required: true,
  },
  evaluationTypes: {
    type: Array,
    required: true,
  },
})
</script>
