<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="text-center mb-8">
      <div class="text-5xl mb-4">📄</div>
      <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ t('Organization Reports') }}</h2>
    </div>

    <!-- Planta 1 -->
    <div v-if="organizationId == 'a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238'" class="grid md:grid-cols-1 gap-6 max-w-xl mx-auto">
      <!-- Spanish Report (shown when locale is 'es') -->
      <div v-if="locale === 'es'"
        class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-8 border border-red-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Informe</h3>
          </div>
          <span class="text-3xl"></span>
        </div>

        <div class="space-y-4 mb-6">
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Identificación de Clima Laboral</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Análisis cuantitativo y cualitativo</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Recomendaciones de intervención</span>
          </div>
        </div>

        <button @click="downloadReport('es')" :disabled="isLoading"
          class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span v-if="isLoading">Descargando...</span>
          <span v-else>Descargar Reporte</span>
        </button>
      </div>

      <!-- English Report (shown when locale is 'en') -->
      <div v-else
        class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Report</h3>
            <p class="text-gray-700 text-sm">Comprehensive psychosocial risk factor analysis according to
              NOM-035-STPS-2018</p>
          </div>
          <span class="text-3xl"></span>
        </div>

        <div class="space-y-4 mb-6">
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Psychosocial Risk Identification</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Quantitative & Qualitative Analysis</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Intervention Recommendations</span>
          </div>
        </div>

        <button @click="downloadReport('en')" :disabled="isLoading"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span v-if="isLoading">Downloading...</span>
          <span v-else>Download Report</span>
        </button>
      </div>
    </div>

    <!-- Planta 3-->
     <div v-if="organizationId == 'a06fe33d-6955-4d24-98d1-a375ecb55645'" class="grid md:grid-cols-1 gap-6 max-w-xl mx-auto">
      <!-- Spanish Report (shown when locale is 'es') -->
      <div v-if="locale === 'es'"
        class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-8 border border-red-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Informe</h3>
          </div>
          <span class="text-3xl"></span>
        </div>

        <div class="space-y-4 mb-6">
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Identificación de Clima Laboral</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Análisis cuantitativo y cualitativo</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Recomendaciones de intervención</span>
          </div>
        </div>

        <button @click="downloadReport('es')" :disabled="isLoading"
          class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span v-if="isLoading">Descargando...</span>
          <span v-else>Descargar Reporte</span>
        </button>
      </div>

      <!-- English Report (shown when locale is 'en') -->
      <div v-else
        class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Report</h3>
            <p class="text-gray-700 text-sm">Comprehensive psychosocial risk factor analysis according to
              NOM-035-STPS-2018</p>
          </div>
          <span class="text-3xl"></span>
        </div>

        <div class="space-y-4 mb-6">
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Psychosocial Risk Identification</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Quantitative & Qualitative Analysis</span>
          </div>
          <div class="flex items-center text-gray-700">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                clip-rule="evenodd" />
            </svg>
            <span>Intervention Recommendations</span>
          </div>
        </div>

        <button @click="downloadReport('en')" :disabled="isLoading"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
          <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span v-if="isLoading">Downloading...</span>
          <span v-else>Download Report</span>
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

interface Props {
  organizationId: string;
  organizationName: string;
}

const props = defineProps<Props>();

const { t, locale } = useTranslations();
const isLoading = ref(false);

const downloadReport = async (language: 'es' | 'en') => {
  isLoading.value = true;

  try {
    // Construct the path to the PDF
    const pdfPath = `/assets/plantas/${props.organizationId}/${language === 'es' ? 'spanish' : 'english'}.pdf`;

    // Create a temporary link and click it
    const link = document.createElement('a');
    link.href = pdfPath;
    link.download = `${language === 'es' ? 'reporte_clima_laboral' : 'work_climate_report'}_${new Date().getFullYear()}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Error downloading report:', error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
button:disabled {
  cursor: not-allowed;
}
</style>
