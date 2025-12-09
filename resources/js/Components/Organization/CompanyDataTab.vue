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
        <h2 class="text-2xl font-bold text-gray-900">Información General</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <InfoCard label="Nombre de la Empresa" :value="companyData.general.name" />
        <InfoCard label="Razón Social" :value="companyData.general.razon_social" />
        <InfoCard label="RFC" :value="companyData.general.rfc" />
        <InfoCard label="Registro Patronal" :value="companyData.general.registro_patronal" />
        <InfoCard label="Actividad Principal" :value="companyData.general.actividad_principal" />
        <InfoCard label="Folio de Organización" :value="companyData.general.folio_organization" />
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
        <h2 class="text-2xl font-bold text-gray-900">Domicilio</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <InfoCard label="Calle y Número" :value="companyData.address.calle_numero" />
        <InfoCard label="Colonia" :value="companyData.address.colonia" />
        <InfoCard label="Código Postal" :value="companyData.address.codigo_postal" />
        <InfoCard label="Municipio" :value="companyData.address.municipio" />
        <InfoCard label="Estado" :value="companyData.address.estado" class="md:col-span-2" />
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
          <h2 class="text-2xl font-bold text-gray-900">Contacto Principal</h2>
        </div>
        <div class="space-y-6">
          <InfoCard label="Nombre" :value="companyData.contact.nombre" />
          <InfoCard label="Puesto" :value="companyData.contact.puesto" />
          <InfoCard label="Email" :value="companyData.contact.email" />
          <InfoCard label="Móvil" :value="companyData.contact.movil" />
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
          <h2 class="text-2xl font-bold text-gray-900">Responsable</h2>
        </div>
        <div class="space-y-6">
          <InfoCard label="Nombre" :value="companyData.responsible.nombre" />
          <InfoCard label="Puesto" :value="companyData.responsible.puesto" />
          <InfoCard label="Email" :value="companyData.responsible.email" />
          <InfoCard label="Móvil" :value="companyData.responsible.movil" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import InfoCard from '@/Components/Organization/InfoCard.vue';

interface CompanyData {
  general: {
    name: string | null;
    razon_social: string | null;
    rfc: string | null;
    registro_patronal: string | null;
    actividad_principal: string | null;
    folio_organization: number | null;
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
</script>
