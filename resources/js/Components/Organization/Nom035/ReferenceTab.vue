<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-cyan-100 rounded-lg">
          <DocumentTextIcon class="w-6 h-6 text-cyan-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Instrumentos de Referencia NOM-035</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Guías técnicas y documentos normativos aplicados</p>
    </div>

    <!-- Instrumentos Principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Referencia I -->
      <div class="bg-gradient-to-br from-red-50 to-pink-50 rounded-xl p-8 border border-red-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <HeartIcon class="w-6 h-6 text-red-600" />
              <h3 class="text-2xl font-bold text-red-900">Guía de Referencia I</h3>
            </div>
            <p class="text-sm text-red-700 mt-1">Identificación de trabajadores con ATS</p>
          </div>
        </div>
        <div class="bg-white rounded-lg p-4 space-y-3 border border-red-100">
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Propósito</p>
            <p class="text-sm text-slate-900 mt-1">Detectar eventos traumáticos severos y síntomas de Trastorno de Estrés Postraumático</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Aplicación</p>
            <p class="text-sm text-slate-900 mt-1">A todos los trabajadores de la organización</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Cobertura</p>
            <p class="text-sm text-slate-900 mt-1">Evaluación de trauma laboral y extralaboral</p>
          </div>
        </div>
      </div>

      <!-- Referencia III -->
      <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-8 border border-amber-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <AdjustmentsHorizontalIcon class="w-6 h-6 text-amber-600" />
              <h3 class="text-2xl font-bold text-amber-900">Guía de Referencia III</h3>
            </div>
            <p class="text-sm text-amber-700 mt-1">Factores de riesgo psicosocial</p>
          </div>
        </div>
        <div class="bg-white rounded-lg p-4 space-y-3 border border-amber-100">
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Propósito</p>
            <p class="text-sm text-slate-900 mt-1">Identificación de factores de riesgo psicosocial en el ambiente de trabajo</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Aplicación</p>
            <p class="text-sm text-slate-900 mt-1">Evaluación de todos los trabajadores de riesgos organizacionales</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Cobertura</p>
            <p class="text-sm text-slate-900 mt-1">6+ dimensiones: cargas de trabajo, falta de control, apoyo social, liderazgo</p>
          </div>
        </div>
      </div>

      <!-- Escala Cisneros -->
      <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-8 border border-orange-200 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <ShieldExclamationIcon class="w-6 h-6 text-orange-600" />
              <h3 class="text-2xl font-bold text-orange-900">Escala Cisneros</h3>
            </div>
            <p class="text-sm text-orange-700 mt-1">Evaluación de violencia laboral y acoso</p>
          </div>
        </div>
        <div class="bg-white rounded-lg p-4 space-y-3 border border-orange-100">
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Propósito</p>
            <p class="text-sm text-slate-900 mt-1">Detección de conductas de acoso psicológico (mobbing) en el trabajo</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Aplicación</p>
            <p class="text-sm text-slate-900 mt-1">A todos los trabajadores para identificar violencia laboral</p>
          </div>
          <div>
            <p class="text-xs font-medium text-slate-600 uppercase tracking-wide">Cobertura</p>
            <p class="text-sm text-slate-900 mt-1">43 ítems evaluando acoso, ridiculización, aislamiento y castigo injustificado</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  DocumentTextIcon,
  HeartIcon,
  AdjustmentsHorizontalIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
</script>
