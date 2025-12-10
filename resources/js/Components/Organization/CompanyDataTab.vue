<template>
  <div class="space-y-8">
    <!-- Sección: Información General -->
    <div>
      <div class="flex items-center mb-6">
        <div class="bg-blue-100 rounded-lg p-3 mr-4">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.5m0 0H9m0 0H3.5m0 0H2m5.5 0v-7.5a2 2 0 014 0V21" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('General Information') }}</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <InfoCard :label="t('Company Name')" :value="companyData.general.name" />
        <InfoCard :label="t('Legal Name')" :value="companyData.general.razon_social" />
        <InfoCard :label="t('Tax ID')" :value="companyData.general.rfc" />
        <InfoCard :label="t('Employer Registration')" :value="companyData.general.registro_patronal" />
        <InfoCard :label="t('Main Activity')" :value="companyData.general.actividad_principal" />
        <InfoCard :label="t('Organization Folio')" :value="companyData.general.folio_organization" />
      </div>
    </div>

    <hr class="border-gray-200" />

    <!-- Sección: Colaboradores -->
    <div>
      <div class="flex items-center mb-6">
        <div class="bg-indigo-100 rounded-lg p-3 mr-4">
          <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('Employees') }}</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <InfoCard :label="t('Total Workers')" :value="companyData.workforce.total_trabajadores" />
        <InfoCard :label="t('Men')" :value="companyData.workforce.total_hombres" />
        <InfoCard :label="t('Women')" :value="companyData.workforce.total_mujeres" />
      </div>
    </div>

    <hr class="border-gray-200" />

    <!-- Sección: Muestra Aplicada y Comité -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Muestra Aplicada -->
      <div>
        <div class="flex items-center mb-6">
          <div class="bg-pink-100 rounded-lg p-3 mr-4">
            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900">{{ t('Applied Sample') }}</h2>
        </div>
        <div class="space-y-6">
          <InfoCard :label="t('Total Samples')" :value="companyData.sample.muestra_aplicada" />
          <InfoCard :label="t('Men')" :value="companyData.sample.muestra_hombres" />
          <InfoCard :label="t('Women')" :value="companyData.sample.muestra_mujeres" />
          <InfoCard :label="t('Sample Justification')" :value="companyData.sample.justificacion_muestra" />
        </div>
      </div>

      <!-- Comité -->
      <div>
        <div class="flex items-center mb-6">
          <div class="bg-yellow-100 rounded-lg p-3 mr-4">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M6 20h5v-2a3 3 0 00-5.856-1.487M11 10a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900">{{ t('Committee') }}</h2>
        </div>
        <div class="space-y-6">
          <InfoCard :label="t('Total Members')" :value="companyData.committee.comite_integrantes" />
          <InfoCard :label="t('Men')" :value="companyData.committee.comite_hombres" />
          <InfoCard :label="t('Women')" :value="companyData.committee.comite_mujeres" />
          <InfoCard v-if="companyData.evaluation_date" :label="t('Application Date')" :value="formatDate(companyData.evaluation_date)" />
        </div>
      </div>
    </div>

    <hr class="border-gray-200" />

    <!-- Sección: Domicilio -->
    <div>
      <div class="flex items-center mb-6">
        <div class="bg-green-100 rounded-lg p-3 mr-4">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">{{ t('Address') }}</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <InfoCard :label="t('Street and Number')" :value="companyData.address.calle_numero" />
        <InfoCard :label="t('Neighborhood')" :value="companyData.address.colonia" />
        <InfoCard :label="t('Postal Code')" :value="companyData.address.codigo_postal" />
        <InfoCard :label="t('Municipality')" :value="companyData.address.municipio" />
        <InfoCard :label="t('State')" :value="companyData.address.estado" class="md:col-span-2" />
      </div>
    </div>

    <hr class="border-gray-200" />

    <!-- Sección: Contactos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Contacto Principal -->
      <div>
        <div class="flex items-center mb-6">
          <div class="bg-purple-100 rounded-lg p-3 mr-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900">{{ t('Primary Contact') }}</h2>
        </div>
        <div class="space-y-6">
          <InfoCard :label="t('Name')" :value="companyData.contact.nombre" />
          <InfoCard :label="t('Position')" :value="companyData.contact.puesto" />
          <InfoCard :label="t('Email')" :value="companyData.contact.email" />
          <InfoCard :label="t('Mobile')" :value="companyData.contact.movil" />
        </div>
      </div>

      <!-- Responsable -->
      <div>
        <div class="flex items-center mb-6">
          <div class="bg-orange-100 rounded-lg p-3 mr-4">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900">{{ t('Responsible') }}</h2>
        </div>
        <div class="space-y-6">
          <InfoCard :label="t('Name')" :value="companyData.responsible.nombre" />
          <InfoCard :label="t('Position')" :value="companyData.responsible.puesto" />
          <InfoCard :label="t('Email')" :value="companyData.responsible.email" />
          <InfoCard :label="t('Mobile')" :value="companyData.responsible.movil" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InfoCard from '@/Components/Organization/InfoCard.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t, locale } = useTranslations();

interface CompanyData {
  general: {
    name: string | null;
    razon_social: string | null;
    rfc: string | null;
    registro_patronal: string | null;
    actividad_principal: string | null;
    folio_organization: number | null;
  };
  workforce: {
    total_trabajadores: number | null;
    total_hombres: number | null;
    total_mujeres: number | null;
  };
  sample: {
    muestra_aplicada: number | null;
    muestra_hombres: number | null;
    muestra_mujeres: number | null;
    justificacion_muestra: string | null;
  };
  evaluation_date: string | null;
  committee: {
    comite_integrantes: number | null;
    comite_hombres: number | null;
    comite_mujeres: number | null;
  };
  address: {
    calle_numero: string | null;
    colonia: string | null;
    codigo_postal: string | null;
    municipio: string | null;
    estado: string | null;
  };
  contact: {
    nombre: string | null;
    puesto: string | null;
    email: string | null;
    movil: string | null;
  };
  responsible: {
    nombre: string | null;
    puesto: string | null;
    email: string | null;
    movil: string | null;
  };
}

interface Props {
  companyData: CompanyData;
}

const props = defineProps<Props>();

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A';
  const localeCode = locale.value === 'es' ? 'es-MX' : 'en-US';
  return new Date(date).toLocaleDateString(localeCode, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>
