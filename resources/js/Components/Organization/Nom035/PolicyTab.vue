<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-purple-100 rounded-lg">
          <DocumentTextIcon class="w-6 h-6 text-purple-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Política de Prevención de Riesgos Psicosociales</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Marco normativo y compromiso organizacional NOM-035-STPS-2018</p>
    </div>

    <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-8 border border-purple-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-4 mb-6">
        <div class="p-3 bg-purple-100 rounded-lg">
          <ShieldCheckIcon class="w-6 h-6 text-purple-600" />
        </div>
        <div>
          <h3 class="text-2xl font-bold text-purple-900">Contenido en Preparación</h3>
          <p class="text-purple-700 mt-1">Se está desarrollando el módulo de política organizacional para NOM-035</p>
        </div>
      </div>
      <div class="flex justify-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-full border border-purple-300">
          <Cog6ToothIcon class="w-4 h-4 animate-spin" />
          <span class="text-sm font-medium">En desarrollo</span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
      <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
          <CheckCircleIcon class="w-8 h-8 text-green-600" />
          <div class="p-2 bg-green-50 rounded-lg">
            <CheckIcon class="w-4 h-4 text-green-600" />
          </div>
        </div>
        <h4 class="font-bold text-slate-900 mb-2">Compromiso de Dirección</h4>
        <p class="text-sm text-slate-600">Declaración del compromiso organizacional con la prevención</p>
      </div>
      <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
          <StarIcon class="w-8 h-8 text-blue-600" />
          <div class="p-2 bg-blue-50 rounded-lg">
            <CheckIcon class="w-4 h-4 text-blue-600" />
          </div>
        </div>
        <h4 class="font-bold text-slate-900 mb-2">Objetivos y Metas</h4>
        <p class="text-sm text-slate-600">Definición de metas medibles para la prevención de riesgos</p>
      </div>
      <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
          <DocumentIcon class="w-8 h-8 text-slate-600" />
          <div class="p-2 bg-slate-100 rounded-lg">
            <CheckIcon class="w-4 h-4 text-slate-600" />
          </div>
        </div>
        <h4 class="font-bold text-slate-900 mb-2">Normatividad</h4>
        <p class="text-sm text-slate-600">Marco legal y referencias de NOM-035-STPS-2018</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  DocumentTextIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  CheckIcon,
  StarIcon,
  DocumentIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
</script>
