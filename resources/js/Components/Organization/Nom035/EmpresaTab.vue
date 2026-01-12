<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-blue-100 rounded-lg">
          <BuildingOfficeIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Información General de Empresa</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Datos de la organización y contactos</p>
    </div>

    <!-- Grid de Secciones -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Información General -->
      <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border border-blue-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-blue-100 rounded-lg">
            <BuildingOfficeIcon class="w-5 h-5 text-blue-600" />
          </div>
          <h3 class="text-lg font-bold text-blue-900">Información General</h3>
        </div>
        <div class="space-y-4">
          <InfoRow label="Razón Social" :value="companyData.general?.razon_social" icon="DocumentTextIcon" />
          <InfoRow label="Nombre Comercial" :value="companyData.general?.name" icon="DocumentTextIcon" />
          <InfoRow label="RFC" :value="companyData.general?.rfc" icon="DocumentTextIcon" />
          <InfoRow label="Registro Patronal" :value="companyData.general?.registro_patronal" icon="DocumentTextIcon" />
          <InfoRow label="Actividad Principal" :value="companyData.general?.actividad_principal" icon="DocumentTextIcon" />
          <InfoRow label="Folio de Organización" :value="companyData.general?.folio_organization?.toString()" icon="HashtagIcon" />
          <InfoRow
            label="Fecha de Evaluación"
            :value="formatDate(companyData.evaluation_date)"
            icon="CalendarIcon"
          />
        </div>
      </div>

      <!-- Domicilio -->
      <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-green-100 rounded-lg">
            <MapPinIcon class="w-5 h-5 text-green-600" />
          </div>
          <h3 class="text-lg font-bold text-green-900">Domicilio</h3>
        </div>
        <div class="space-y-4">
          <InfoRow label="Calle y Número" :value="companyData.address?.calle_numero" icon="HomeIcon" />
          <InfoRow label="Colonia" :value="companyData.address?.colonia" icon="MapIcon" />
          <InfoRow label="Código Postal" :value="companyData.address?.codigo_postal" icon="DocumentIcon" />
          <InfoRow label="Municipio" :value="companyData.address?.municipio" icon="BuildingOfficeIcon" />
          <InfoRow label="Estado" :value="companyData.address?.estado" icon="FlagIcon" />
        </div>
      </div>

      <!-- Colaboradores -->
      <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-purple-100 rounded-lg">
            <UserGroupIcon class="w-5 h-5 text-purple-600" />
          </div>
          <h3 class="text-lg font-bold text-purple-900">Total de Colaboradores</h3>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <StatCard label="Total" :value="companyData.workforce?.total_trabajadores" icon="UserGroupIcon" color="purple" />
          <StatCard label="Mujeres" :value="companyData.workforce?.total_mujeres" icon="UserGroupIcon" color="pink" />
          <StatCard label="Hombres" :value="companyData.workforce?.total_hombres" icon="UserGroupIcon" color="blue" />
        </div>
        <div class="text-sm text-slate-600 mt-4 p-3 bg-white rounded-lg border border-slate-200">
          <p v-if="companyData.workforce?.total_trabajadores" class="font-medium">
            📊 Plantilla total: <span class="font-bold text-slate-900">{{ companyData.workforce.total_trabajadores }}</span> personas
          </p>
          <p v-else class="text-slate-500">Sin datos de plantilla registrados</p>
        </div>
      </div>

      <!-- Mínima Muestra -->
      <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-6 border border-orange-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-orange-100 rounded-lg">
            <ClipboardDocumentListIcon class="w-5 h-5 text-orange-600" />
          </div>
          <h3 class="text-lg font-bold text-orange-900">Mínima Muestra Evaluada</h3>
        </div>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <StatCard label="Total" :value="companyData.sample?.muestra_aplicada" icon="ClipboardDocumentListIcon" color="orange" />
          <StatCard label="Mujeres" :value="companyData.sample?.muestra_mujeres" icon="UserGroupIcon" color="pink" />
          <StatCard label="Hombres" :value="companyData.sample?.muestra_hombres" icon="UserGroupIcon" color="blue" />
        </div>
        <div v-if="companyData.sample?.justificacion_muestra" class="bg-white rounded-lg p-3 border border-orange-200">
          <p class="text-sm font-medium text-slate-700 mb-1">Justificación:</p>
          <p class="text-sm text-slate-600">{{ companyData.sample.justificacion_muestra }}</p>
        </div>
      </div>

      <!-- Contacto -->
      <div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-6 border border-indigo-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-indigo-100 rounded-lg">
            <PhoneIcon class="w-5 h-5 text-indigo-600" />
          </div>
          <h3 class="text-lg font-bold text-indigo-900">Contacto Principal</h3>
        </div>
        <div class="space-y-4">
          <InfoRow label="Nombre" :value="companyData.contact?.nombre" icon="UserIcon" />
          <InfoRow label="Puesto" :value="companyData.contact?.puesto" icon="BriefcaseIcon" />
          <InfoRow label="E-mail" :value="companyData.contact?.email" type="email" icon="EnvelopeIcon" />
          <InfoRow label="Móvil" :value="companyData.contact?.movil" type="phone" icon="PhoneIcon" />
        </div>
      </div>

      <!-- Responsable -->
      <div class="bg-gradient-to-br from-rose-50 to-red-50 rounded-xl p-6 border border-red-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-2 mb-4">
          <div class="p-2 bg-red-100 rounded-lg">
            <ShieldCheckIcon class="w-5 h-5 text-red-600" />
          </div>
          <h3 class="text-lg font-bold text-red-900">Responsable</h3>
        </div>
        <div class="space-y-4">
          <InfoRow label="Nombre" :value="companyData.responsible?.nombre" icon="UserIcon" />
          <InfoRow label="Puesto" :value="companyData.responsible?.puesto" icon="BriefcaseIcon" />
          <InfoRow label="E-mail" :value="companyData.responsible?.email" type="email" icon="EnvelopeIcon" />
          <InfoRow label="Móvil" :value="companyData.responsible?.movil" type="phone" icon="PhoneIcon" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InfoRow from './InfoRow.vue';
import StatCard from './StatCard.vue';
import {
  BuildingOfficeIcon,
  DocumentTextIcon,
  MapPinIcon,
  UserGroupIcon,
  ClipboardDocumentListIcon,
  PhoneIcon,
  EnvelopeIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

interface CompanyData {
  general?: {
    name?: string | null;
    razon_social?: string | null;
    rfc?: string | null;
    registro_patronal?: string | null;
    actividad_principal?: string | null;
    folio_organization?: number | null;
  };
  address?: {
    calle_numero?: string | null;
    colonia?: string | null;
    codigo_postal?: string | null;
    municipio?: string | null;
    estado?: string | null;
  };
  workforce?: {
    total_trabajadores?: number | null;
    total_hombres?: number | null;
    total_mujeres?: number | null;
  };
  sample?: {
    muestra_aplicada?: number | null;
    muestra_hombres?: number | null;
    muestra_mujeres?: number | null;
    justificacion_muestra?: string | null;
  };
  contact?: {
    nombre?: string | null;
    puesto?: string | null;
    email?: string | null;
    movil?: string | null;
  };
  responsible?: {
    nombre?: string | null;
    puesto?: string | null;
    email?: string | null;
    movil?: string | null;
  };
  evaluation_date?: string | null;
}

interface Organization {
  id: string;
  name: string;
  logo?: string | null;
}

interface Props {
  companyData: CompanyData;
  organization: Organization;
}

withDefaults(defineProps<Props>(), {});

const formatDate = (date: string | null | undefined): string => {
  if (!date) return '—';
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  } catch {
    return date;
  }
};

const sections = computed(() => [
  {
    title: 'Información General',
    icon: BuildingOfficeIcon,
    gradient: 'from-blue-50 to-cyan-50',
    iconColor: 'text-blue-600',
    borderColor: 'border-blue-200',
    headingColor: 'text-blue-900',
    fields: [
      { label: 'Razón Social', value: CompanyData.general?.razon_social },
      { label: 'Nombre Comercial', value: companyData.general?.name },
      { label: 'RFC', value: companyData.general?.rfc },
      { label: 'Registro Patronal', value: companyData.general?.registro_patronal },
      { label: 'Actividad Principal', value: companyData.general?.actividad_principal },
      { label: 'Folio de Organización', value: companyData.general?.folio_organization?.toString() },
    ],
  },
]);
</script>

<style scoped>
.grid {
  display: grid;
}

.space-y-8 > * + * {
  margin-top: 2rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}
</style>
