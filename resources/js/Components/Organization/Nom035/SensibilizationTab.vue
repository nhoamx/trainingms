<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-amber-100 rounded-lg">
          <LightBulbIcon class="w-6 h-6 text-amber-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Sensibilización y Capacitación</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Programas de conciencia sobre riesgos psicosociales</p>
    </div>

    <!-- Presentación -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-blue-100 rounded-lg">
          <PresentationChartBarIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="text-2xl font-bold text-blue-900">Presentación</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <BriefcaseIcon class="w-8 h-8 text-purple-600" />
            <div class="p-2 bg-purple-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-purple-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Gerencia</h4>
          <p class="text-sm text-slate-600">Presentación ejecutiva para nivel directivo</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <UserGroupIcon class="w-8 h-8 text-blue-600" />
            <div class="p-2 bg-blue-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-blue-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Mandos Medios</h4>
          <p class="text-sm text-slate-600">Capacitación para supervisores y coordinadores</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <UsersIcon class="w-8 h-8 text-emerald-600" />
            <div class="p-2 bg-emerald-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-emerald-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Trabajadores</h4>
          <p class="text-sm text-slate-600">Material informativo para personal operativo</p>
        </div>
      </div>
    </div>

    <!-- Videos -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-8 border border-amber-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-amber-100 rounded-lg">
          <VideoCameraIcon class="w-6 h-6 text-amber-600" />
        </div>
        <h3 class="text-2xl font-bold text-amber-900">Videos</h3>
      </div>
      <div class="bg-white rounded-lg p-6">
        <div class="flex items-center justify-center p-8 border-2 border-dashed border-amber-300 rounded-lg">
          <div class="text-center">
            <Cog6ToothIcon class="w-12 h-12 text-amber-400 mx-auto mb-3 animate-spin" />
            <p class="text-amber-700 font-medium">En desarrollo</p>
            <p class="text-sm text-amber-600 mt-1">Contenido multimedia en preparación</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  LightBulbIcon,
  PresentationChartBarIcon,
  BriefcaseIcon,
  UserGroupIcon,
  UsersIcon,
  CheckIcon,
  VideoCameraIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
</script>
