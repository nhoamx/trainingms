<template>
  <div class="space-y-8">
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-red-100 rounded-lg">
          <ChartBarIcon class="w-6 h-6 text-red-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Etapas - Referencia I (ATS)</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Panorama general y análisis de acontecimientos traumáticos severos</p>
    </div>

    <div class="border-b border-slate-200">
      <nav class="-mb-px flex gap-6">
        <button
          v-for="subTab in subTabs"
          :key="subTab.key"
          @click="activeSubTab = subTab.key"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            activeSubTab === subTab.key
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
          ]"
        >
          <div class="flex items-center gap-2">
            <component :is="subTab.icon" class="w-5 h-5" />
            {{ subTab.label }}
          </div>
        </button>
      </nav>
    </div>

    <div v-if="activeSubTab === 'panorama'" class="space-y-6">
      <div class="bg-white rounded-lg p-6 border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-2">Panorama general de Acontecimientos</h3>
        <p class="text-sm text-slate-600 mb-1">
          Esta gráfica muestra el panorama general de las personas que respondieron sí a alguna de las 6 preguntas de los acontecimientos traumáticos severos..
        </p>
        <p class="text-xs text-slate-500">Participantes considerados: {{ atsPanoramaStatistics.total_evaluations }} ({{ atsPanoramaStatistics.without_traumatic_event_count }} sin acontecimientos traumáticos)</p>

        <div class="mt-6 overflow-x-auto">
          <div class="min-w-[720px] px-2">
            <div class="h-72 flex items-end gap-4 border-b border-slate-200 pb-3">
              <div
                v-for="item in atsPanoramaItems"
                :key="item.index"
                class="flex-1 min-w-[100px] flex flex-col items-center gap-2"
              >
                <span class="text-xs font-semibold text-slate-700">{{ item.yes_count }}</span>
                <div class="w-full max-w-16 rounded-t-md transition-all duration-300" :class="item.colorClass" :style="{ height: item.barHeight }"></div>
                <span class="text-[11px] font-medium text-slate-600">{{ item.shortLabel }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
          <h4 class="text-xl font-semibold text-indigo-900 mb-2">Resumen por acontecimiento</h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
              v-for="item in atsPanoramaItems"
              :key="`legend_${item.index}`"
              class="rounded-md border border-indigo-100 bg-white px-3 py-2"
            >
              <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-full" :class="item.colorClass"></span>
                <p class="text font-semibold text-slate-900">{{ item.shortLabel }}</p>
              </div>
              <p class="mt-1 text-xs text-slate-700"><span class="font-semibold">{{ item.yes_count }}</span> persona(s) han seleccionado sí para {{ item.shortLabel.toLowerCase() }}.</p>
            </div>
          </div>

          <p class="mt-4 text-xs text-indigo-900">
            <span class="font-semibold">{{ atsPanoramaStatistics.without_traumatic_event_count }}</span>
            persona(s) indicaron que no han sufrido un acontecimiento traumático.
          </p>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-4 space-y-4">
          <div class="flex flex-col gap-3">
            <h4 class="text-base font-semibold text-slate-900">Personas evaluadas en acontecimientos</h4>

            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                @click="panoramaResponseFilter = 'all'"
                :class="[
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                  panoramaResponseFilter === 'all'
                    ? 'bg-slate-900 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Todos
              </button>
              <button
                type="button"
                @click="panoramaResponseFilter = 'yes'"
                :class="[
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                  panoramaResponseFilter === 'yes'
                    ? 'bg-rose-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Respondieron sí
              </button>
              <button
                type="button"
                @click="panoramaResponseFilter = 'no'"
                :class="[
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                  panoramaResponseFilter === 'no'
                    ? 'bg-emerald-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Respondieron no
              </button>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                @click="selectedAcontecimientoFilter = 'all'"
                :class="[
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors',
                  selectedAcontecimientoFilter === 'all'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Todos
              </button>

              <button
                v-for="item in atsPanoramaItems"
                :key="`chip_${item.index}`"
                type="button"
                @click="selectedAcontecimientoFilter = String(item.index)"
                :class="[
                  'rounded-full px-4 py-2 text-sm font-medium transition-colors border',
                  selectedAcontecimientoFilter === String(item.index)
                    ? 'text-white border-transparent'
                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                ]"
                :style="selectedAcontecimientoFilter === String(item.index) ? { backgroundColor: item.hexColor } : undefined"
              >
                {{ item.shortLabel }}
              </button>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
              <div class="w-full lg:max-w-sm">
                <label for="panorama-folio-search" class="sr-only">Buscar por folio</label>
                <input
                  id="panorama-folio-search"
                  v-model="panoramaSearch"
                  type="text"
                  placeholder="Buscar por folio..."
                  class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
              </div>

              <div class="flex items-center gap-2">
                <span class="text-sm text-slate-600">Mostrar</span>
                <div class="flex items-center gap-2">
                  <button
                    v-for="size in pageSizeOptions"
                    :key="`size_${size}`"
                    type="button"
                    @click="panoramaPageSize = size"
                    :class="[
                      'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                      panoramaPageSize === size
                        ? 'bg-slate-900 text-white'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                    ]"
                  >
                    {{ size }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-auto rounded-lg border border-slate-200">
            <table class="min-w-[960px] w-full text-sm">
              <thead class="bg-slate-50 text-slate-700">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">Folio</th>
                  <th class="px-3 py-2 text-left font-semibold">Género</th>
                  <th class="px-3 py-2 text-left font-semibold">Edad</th>
                  <th class="px-3 py-2 text-left font-semibold">Puesto</th>
                  <th class="px-3 py-2 text-left font-semibold">Área</th>
                  <th class="px-3 py-2 text-left font-semibold">Tiempo en el puesto actual</th>
                  <th class="px-3 py-2 text-right font-semibold">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-if="paginatedPanoramaParticipants.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                    No hay personas para los filtros seleccionados.
                  </td>
                </tr>
                <tr
                  v-for="person in paginatedPanoramaParticipants"
                  :key="person.id"
                  class="hover:bg-slate-50"
                >
                  <td class="px-3 py-2 font-semibold text-slate-900">{{ person.personal_folio }}</td>
                  <td class="px-3 py-2 text-slate-700">{{ person.demographics.genero }}</td>
                  <td class="px-3 py-2 text-slate-700">{{ person.demographics.edad }}</td>
                  <td class="px-3 py-2 text-slate-700">{{ person.demographics.puesto }}</td>
                  <td class="px-3 py-2 text-slate-700">{{ person.demographics.area }}</td>
                  <td class="px-3 py-2 text-slate-700">{{ person.demographics.tiempo_puesto_actual }}</td>
                  <td class="px-3 py-2 text-right">
                    <button
                      type="button"
                      @click="openPanoramaDetails(person.id)"
                      class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                    >
                      Ver detalles
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <p class="text-sm text-slate-600">
              Mostrando {{ paginationSummary.from }}-{{ paginationSummary.to }} de {{ paginationSummary.total }} personas
            </p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="panoramaPage = Math.max(1, panoramaPage - 1)"
                :disabled="panoramaPage === 1"
                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Anterior
              </button>
              <span class="text-sm text-slate-700">Página {{ panoramaPage }} de {{ totalPanoramaPages }}</span>
              <button
                type="button"
                @click="panoramaPage = Math.min(totalPanoramaPages, panoramaPage + 1)"
                :disabled="panoramaPage >= totalPanoramaPages"
                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Siguiente
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 pt-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <h5 class="text-sm font-semibold text-slate-900 mb-2">Resumen</h5>
              <div class="rounded-md border border-dashed border-slate-300 bg-white px-4 py-6 text-sm text-slate-600">
                Sin datos de la Guía de referencia III.
              </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-4">
              <h5 class="text-sm font-semibold text-slate-900">Distribución por</h5>

              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="distributionMode = 'area'"
                  :class="[
                    'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                    distributionMode === 'area'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                  ]"
                >
                  Área
                </button>
                <button
                  type="button"
                  @click="distributionMode = 'puesto'"
                  :class="[
                    'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                    distributionMode === 'puesto'
                      ? 'bg-slate-900 text-white'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                  ]"
                >
                  Puesto
                </button>
              </div>

              <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                  <thead class="bg-slate-50">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold text-slate-700">Acontecimiento</th>
                      <th class="px-3 py-2 text-right font-semibold text-slate-700">Total participantes</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr
                      v-for="row in distributionAcontecimientoRows"
                      :key="`dist_row_${row.index}`"
                    >
                      <td class="px-3 py-2 text-slate-700">{{ row.label }}</td>
                      <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ row.total }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'analisis'" class="space-y-6">
      <AnalysisFilters :demographics="analysisData.demographics" v-model="analysisFilters" />

      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col gap-5">
          <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Análisis de acontecimientos</h3>
              <p class="text-sm text-slate-600 mt-1">Explora resultados por perfil demográfico y por pregunta para identificar focos de atención.</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
              <span class="font-semibold text-slate-900">{{ eventFilteredEvaluations.length }}</span>
              <span class="ml-1">participantes en el filtro activo</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">Participantes</p>
              <p class="mt-1 text-xl font-bold text-slate-900">{{ eventFilteredEvaluations.length }}</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
              <p class="text-xs uppercase tracking-wide text-emerald-700">Respuestas Sí</p>
              <p class="mt-1 text-xl font-bold text-emerald-800">{{ responseSummary.yesCount }}</p>
            </div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
              <p class="text-xs uppercase tracking-wide text-rose-700">Respuestas No</p>
              <p class="mt-1 text-xl font-bold text-rose-800">{{ responseSummary.noCount }}</p>
            </div>
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3">
              <p class="text-xs uppercase tracking-wide text-indigo-700">% Sí</p>
              <p class="mt-1 text-xl font-bold text-indigo-800">{{ responseSummary.yesPercentage }}%</p>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
              <div class="xl:col-span-2">
                <label for="question-filter" class="block text-sm font-medium text-slate-700 mb-1">Pregunta a analizar</label>
                <select
                  id="question-filter"
                  v-model="selectedQuestionKey"
                  class="w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                >
                  <option value="">Todos (al menos un ATS afirmativo)</option>
                  <option
                    v-for="question in questionStatistics.questions"
                    :key="question.key"
                    :value="question.key"
                  >
                    {{ `ATS ${question.number} - ${question.text}` }}
                  </option>
                </select>
              </div>

              <div>
                <p class="block text-sm font-medium text-slate-700 mb-1">Tipo de gráfica</p>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    @click="chartType = 'pie'"
                    :class="[
                      'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                      chartType === 'pie' ? 'bg-purple-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                    ]"
                  >
                    Pastel
                  </button>
                  <button
                    @click="chartType = 'bar'"
                    :class="[
                      'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                      chartType === 'bar' ? 'bg-purple-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                    ]"
                  >
                    Barras
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="eventFilteredEvaluations.length > 0" class="rounded-xl border border-slate-200 p-4 sm:p-5 bg-white">
            <div class="mb-3 flex items-center justify-between gap-2">
              <h4 class="text-sm font-semibold text-slate-900">Visualización de respuestas</h4>
              <p class="text-xs text-slate-500">Total respuestas consideradas: {{ responseSummary.totalResponses }}</p>
            </div>
            <div class="h-[340px]">
              <canvas ref="analysisChartRef" class="w-full" style="height: 320px"></canvas>
            </div>
          </div>

          <div v-else class="rounded-lg border-2 border-dashed border-slate-300 p-8 text-center">
            <p class="text-sm font-medium text-slate-700">Sin datos para los filtros seleccionados</p>
            <p class="text-xs text-slate-500 mt-1">Ajusta los filtros para visualizar resultados de análisis</p>
          </div>

          <AnalysisWysiwygBlocks
            v-if="organizationId && (canManageAnalysisBlocks || analysisBlocks.referencia_i.length > 0)"
            :organization-id="organizationId"
            instrument-type="referencia_i"
            :blocks="analysisBlocks.referencia_i"
            :can-manage="canManageAnalysisBlocks"
          />
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'acontecimientos_traumaticos'" class="space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
          <div>
            <h3 class="text-lg font-bold text-slate-900">Acontecimientos traumáticos</h3>
            <p class="text-sm text-slate-600 mt-1">
              Se muestran personas con respuestas en Sección II, III y IV, con opción para filtrar quienes requieren valoración clínica.
            </p>
          </div>
          <div class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-sm text-rose-800 font-semibold">
            Requieren valoración clínica: {{ clinicalAssessmentParticipants.requires_clinical_count }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Participantes evaluados</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ clinicalAssessmentParticipants.total }}</p>
          </div>
          <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
            <p class="text-xs uppercase tracking-wide text-rose-700">Requieren valoración clínica</p>
            <p class="mt-1 text-xl font-bold text-rose-800">{{ clinicalAssessmentParticipants.requires_clinical_count }}</p>
          </div>
          <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <p class="text-xs uppercase tracking-wide text-emerald-700">No requieren valoración clínica</p>
            <p class="mt-1 text-xl font-bold text-emerald-800">{{ Math.max(clinicalAssessmentParticipants.total - clinicalAssessmentParticipants.requires_clinical_count, 0) }}</p>
          </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
          <div class="w-full lg:max-w-2xl space-y-2">
            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                @click="clinicalRequirementFilter = 'all'"
                :class="[
                  'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                  clinicalRequirementFilter === 'all'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Todos
              </button>
              <button
                type="button"
                @click="clinicalRequirementFilter = 'requires'"
                :class="[
                  'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                  clinicalRequirementFilter === 'requires'
                    ? 'bg-rose-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Requiere valoración clínica
              </button>
            </div>

            <div>
              <label for="clinical-folio-search" class="sr-only">Buscar por folio</label>
              <input
                id="clinical-folio-search"
                v-model="clinicalSearch"
                type="text"
                placeholder="Buscar por folio..."
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-sm text-slate-600">Mostrar</span>
            <div class="flex items-center gap-2">
              <button
                v-for="size in pageSizeOptions"
                :key="`clinical_size_${size}`"
                type="button"
                @click="clinicalPageSize = size"
                :class="[
                  'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                  clinicalPageSize === size
                    ? 'bg-slate-900 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                {{ size }}
              </button>
            </div>
          </div>
        </div>

        <div class="overflow-auto rounded-lg border border-slate-200">
          <table class="min-w-[1100px] w-full text-sm">
            <thead class="bg-slate-50 text-slate-700">
              <tr>
                <th class="px-3 py-2 text-left font-semibold">Folio</th>
                <th class="px-3 py-2 text-left font-semibold">Género</th>
                <th class="px-3 py-2 text-left font-semibold">Edad</th>
                <th class="px-3 py-2 text-left font-semibold">Puesto</th>
                <th class="px-3 py-2 text-left font-semibold">Área</th>
                <th class="px-3 py-2 text-left font-semibold">Valoración clínica</th>
                <th class="px-3 py-2 text-right font-semibold">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-if="paginatedClinicalParticipants.length === 0">
                <td colspan="10" class="px-4 py-8 text-center text-slate-500">
                  No hay personas para los filtros seleccionados.
                </td>
              </tr>
              <tr
                v-for="person in paginatedClinicalParticipants"
                :key="person.id"
                class="hover:bg-slate-50"
              >
                <td class="px-3 py-2 font-semibold text-slate-900">{{ person.personal_folio }}</td>
                <td class="px-3 py-2 text-slate-700">{{ person.demographics.genero }}</td>
                <td class="px-3 py-2 text-slate-700">{{ person.demographics.edad }}</td>
                <td class="px-3 py-2 text-slate-700">{{ person.demographics.puesto }}</td>
                <td class="px-3 py-2 text-slate-700">{{ person.demographics.area }}</td>
                <td class="px-3 py-2 text-center">
                  <span
                    v-if="person.requires_clinical_assessment"
                    class="rounded-full px-2.5 py-1 text-xs font-semibold bg-rose-100 text-rose-800"
                  >
                    Requiere valoración clínica
                  </span>
                  <span v-else class="text-sm font-semibold text-slate-400">--</span>
                </td>
                <td class="px-3 py-2 text-right">
                  <button
                    type="button"
                    @click="openClinicalDetails(person.id)"
                    class="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                  >
                    Ver detalles
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <p class="text-sm text-slate-600">
            Mostrando {{ clinicalPaginationSummary.from }}-{{ clinicalPaginationSummary.to }} de {{ clinicalPaginationSummary.total }} personas
          </p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="clinicalPage = Math.max(1, clinicalPage - 1)"
              :disabled="clinicalPage === 1"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Anterior
            </button>
            <span class="text-sm text-slate-700">Página {{ clinicalPage }} de {{ totalClinicalPages }}</span>
            <button
              type="button"
              @click="clinicalPage = Math.min(totalClinicalPages, clinicalPage + 1)"
              :disabled="clinicalPage >= totalClinicalPages"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'seguimiento'" class="space-y-6">
      <div class="bg-gradient-to-r from-sky-50 to-indigo-50 rounded-xl p-8 border border-sky-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-sky-100 rounded-lg">
            <ClipboardDocumentListIcon class="w-6 h-6 text-sky-600" />
          </div>
          <h3 class="text-2xl font-bold text-slate-900">Seguimiento</h3>
        </div>
        <div class="bg-white rounded-lg p-8">
          <div class="flex items-center justify-center border-2 border-dashed border-sky-300 rounded-lg p-8">
            <div class="text-center max-w-2xl space-y-2">
              <p class="text-sky-800 font-semibold">Próximamente: expediente de seguimiento clínico por persona.</p>
              <p class="text-sm text-slate-600">
                Esta sección concentrará el expediente de las personas que pasaron a valoración clínica,
                incluyendo responsable del equipo, estatus y campos de seguimiento definidos por el cliente.
              </p>
              <p class="text-xs text-slate-500">En la siguiente fase se incorporarán los campos y acciones del flujo operativo.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'prevencion'" class="space-y-6">
      <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-emerald-100 rounded-lg">
            <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
          </div>
          <h3 class="text-2xl font-bold text-emerald-900">Prevención y recomendaciones</h3>
        </div>
        <div class="bg-white rounded-lg p-8">
          <div class="flex items-center justify-center border-2 border-dashed border-emerald-300 rounded-lg p-8">
            <div class="text-center max-w-xl">
              <PencilSquareIcon class="w-12 h-12 text-emerald-400 mx-auto mb-3" />
              <p class="text-emerald-700 font-semibold">Aún no se han subido los análisis o resultados para esta sección.</p>
              <p class="text-sm text-emerald-600 mt-1">Próxima fase: definición e integración de recomendaciones con el cliente.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeSubTab === 'conclusiones'" class="space-y-6">
      <div class="bg-gradient-to-r from-slate-50 to-indigo-50 rounded-xl p-8 border border-slate-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-indigo-100 rounded-lg">
            <LightBulbIcon class="w-6 h-6 text-indigo-600" />
          </div>
          <h3 class="text-2xl font-bold text-slate-900">Conclusiones</h3>
        </div>
        <div class="bg-white rounded-lg p-8">
          <div class="flex items-center justify-center border-2 border-dashed border-slate-300 rounded-lg p-8">
            <div class="text-center max-w-xl">
              <ArrowPathIcon class="w-12 h-12 text-slate-400 mx-auto mb-3" />
              <p class="text-slate-700 font-semibold">Aún no se han subido los análisis o resultados para esta sección.</p>
              <p class="text-sm text-slate-500 mt-1">Aquí se mostrará el cierre ejecutivo de hallazgos y acuerdos.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div
      v-if="isPanoramaDetailsOpen"
      class="fixed inset-0 z-50 "
      aria-labelledby="panorama-details-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-slate-900/50" @click="closePanoramaDetails"></div>

      <div class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl border-l border-slate-200 overflow-y-auto">
        <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
          <h3 id="panorama-details-title" class="text-lg font-bold text-slate-900">Detalle de persona</h3>
          <button
            type="button"
            @click="closePanoramaDetails"
            class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
          >
            Cerrar
          </button>
        </div>

        <div v-if="selectedPanoramaParticipant" class="p-6 space-y-6">
          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Identificación</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div>
                <p class="text-slate-500">Folio</p>
                <p class="font-semibold text-slate-900">{{ selectedPanoramaParticipant.personal_folio }}</p>
              </div>
              <div>
                <p class="text-slate-500">Nombre</p>
                <p class="font-semibold text-slate-900">{{ selectedPanoramaParticipant.name }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Datos demográficos</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div><p class="text-slate-500">Género</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.genero }}</p></div>
              <div><p class="text-slate-500">Edad</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.edad }}</p></div>
              <div><p class="text-slate-500">Estado civil</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.estado_civil }}</p></div>
              <div><p class="text-slate-500">Estudios</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.estudios }}</p></div>
              <div><p class="text-slate-500">Puesto</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.puesto }}</p></div>
              <div><p class="text-slate-500">Área</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.area }}</p></div>
              <div><p class="text-slate-500">Tipo de puesto</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.tipo_puesto }}</p></div>
              <div><p class="text-slate-500">Tipo de contratación</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.tipo_contratacion }}</p></div>
              <div><p class="text-slate-500">Tipo de personal</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.tipo_personal }}</p></div>
              <div><p class="text-slate-500">Turno</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.turno }}</p></div>
              <div><p class="text-slate-500">Rotación de turnos</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.rotacion_turnos }}</p></div>
              <div><p class="text-slate-500">Tiempo en el puesto actual</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.tiempo_puesto_actual }}</p></div>
              <div class="md:col-span-2"><p class="text-slate-500">Tiempo de experiencia laboral total</p><p class="font-medium text-slate-900">{{ selectedPanoramaParticipant.demographics.tiempo_experiencia_laboral_total }}</p></div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Respuestas de Acontecimientos</h4>
            <div class="space-y-2">
              <div
                v-for="item in atsPanoramaItems"
                :key="`detail_event_${item.index}`"
                class="flex items-center justify-between rounded-md border border-slate-100 px-3 py-2"
              >
                <p class="text-sm text-slate-700">{{ item.shortLabel }}</p>
                <span
                  :class="selectedPanoramaParticipant.events[String(item.index)] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
                  class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                >
                  {{ selectedPanoramaParticipant.events[String(item.index)] ? 'Sí' : 'No' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  <div
      v-if="isClinicalDetailsOpen"
      class="fixed inset-0 z-50"
      aria-labelledby="clinical-details-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-slate-900/50" @click="closeClinicalDetails"></div>

      <div class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white shadow-2xl border-l border-slate-200 overflow-y-auto">
        <div class="sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
          <h3 id="clinical-details-title" class="text-lg font-bold text-slate-900">Detalle clínico de persona</h3>
          <button
            type="button"
            @click="closeClinicalDetails"
            class="rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
          >
            Cerrar
          </button>
        </div>

        <div v-if="selectedClinicalParticipant" class="p-6 space-y-6">
          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Identificación</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div>
                <p class="text-slate-500">Folio</p>
                <p class="font-semibold text-slate-900">{{ selectedClinicalParticipant.personal_folio }}</p>
              </div>
              <div>
                <p class="text-slate-500">Nombre</p>
                <p class="font-semibold text-slate-900">{{ selectedClinicalParticipant.name }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Datos demográficos</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div><p class="text-slate-500">Género</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.genero }}</p></div>
              <div><p class="text-slate-500">Edad</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.edad }}</p></div>
              <div><p class="text-slate-500">Estado civil</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.estado_civil }}</p></div>
              <div><p class="text-slate-500">Estudios</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.estudios }}</p></div>
              <div><p class="text-slate-500">Puesto</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.puesto }}</p></div>
              <div><p class="text-slate-500">Área</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.area }}</p></div>
              <div><p class="text-slate-500">Tipo de puesto</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.tipo_puesto }}</p></div>
              <div><p class="text-slate-500">Tipo de contratación</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.tipo_contratacion }}</p></div>
              <div><p class="text-slate-500">Tipo de personal</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.tipo_personal }}</p></div>
              <div><p class="text-slate-500">Turno</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.turno }}</p></div>
              <div><p class="text-slate-500">Rotación de turnos</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.rotacion_turnos }}</p></div>
              <div><p class="text-slate-500">Tiempo en el puesto actual</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.tiempo_puesto_actual }}</p></div>
              <div class="md:col-span-2"><p class="text-slate-500">Tiempo de experiencia laboral total</p><p class="font-medium text-slate-900">{{ selectedClinicalParticipant.demographics.tiempo_experiencia_laboral_total }}</p></div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Resultado clínico</h4>
            <div class="flex flex-wrap gap-2">
              <span
                :class="selectedClinicalParticipant.requires_clinical_assessment ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'"
                class="rounded-full px-2.5 py-1 text-xs font-semibold"
              >
                {{ selectedClinicalParticipant.requires_clinical_assessment ? 'Requiere valoración clínica' : 'Sin criterio clínico' }}
              </span>
              <span
                v-for="criteria in selectedClinicalParticipant.criteria_met"
                :key="`criteria_${criteria}`"
                class="rounded-full bg-indigo-100 text-indigo-800 px-2.5 py-1 text-xs font-semibold uppercase"
              >
                Sección {{ criteria }}
              </span>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 p-4 space-y-4">
            <h4 class="text-sm font-semibold text-slate-800">Respuestas Sección II, III y IV</h4>

            <div
              v-for="section in clinicalSectionsForDetail"
              :key="section.key"
              class="rounded-lg border border-slate-100 p-3"
            >
              <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-sm font-semibold text-slate-900">{{ section.label }}</p>
                <span class="text-xs text-slate-600">Sí: {{ section.yesCount }} / {{ section.total }}</span>
              </div>

              <div class="space-y-2">
                <div
                  v-for="response in section.responses"
                  :key="response.key"
                  class="flex items-start justify-between gap-3 rounded-md border border-slate-100 px-3 py-2"
                >
                  <div>
                    <p class="text-xs text-slate-500">ATS {{ response.number }}</p>
                    <p class="text-sm text-slate-700">{{ response.text }}</p>
                  </div>
                  <span
                    :class="response.is_yes ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'"
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  >
                    {{ response.is_yes ? 'Sí' : 'No' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AnalysisFilters from './Charts/AnalysisFilters.vue';
import AnalysisWysiwygBlocks from './AnalysisWysiwygBlocks.vue';
import { Chart, registerables } from 'chart.js';
import {
  ArrowPathIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  LightBulbIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';

Chart.register(...registerables);

interface RefIQuestionStatistic {
  key: string;
  number: number;
  text: string;
  yes_count: number;
  no_count: number;
  total_responses: number;
  yes_percentage: number;
}

interface RefIBlockStatistic {
  name: string;
  question_numbers: number[];
  question_count: number;
  yes_count: number;
  no_count: number;
  total_responses: number;
  yes_percentage: number;
}

interface RefIEvaluation {
  id: string;
  personal_folio: string;
  demographics: {
    genero: string;
    puesto: string;
    area: string;
    turno: string;
  };
  answers?: Record<string, unknown>;
  yes_count: number;
  risk_level: string;
}

interface Props {
  analysisData: {
    evaluations: RefIEvaluation[];
    demographics: {
      generos: string[];
      puestos: string[];
      areas: string[];
      turnos: string[];
    };
    colors: Record<string, string>;
    labels: Record<string, string>;
  };
  questionStatistics: {
    questions: RefIQuestionStatistic[];
    total_evaluations: number;
  };
  blockStatistics: {
    blocks: RefIBlockStatistic[];
    total_evaluations: number;
  };
  atsPanoramaStatistics: {
    items: Array<{
      index: number;
      label: string;
      yes_count: number;
      no_count: number;
      total_responses: number;
    }>;
    total_evaluations: number;
    with_traumatic_event_count: number;
    without_traumatic_event_count: number;
  };
  acontecimientoParticipants: {
    participants: Array<{
      id: string;
      personal_folio: string;
      name: string;
      has_any_event: boolean;
      events: Record<string, boolean>;
      demographics: {
        genero: string;
        edad: string;
        estado_civil: string;
        estudios: string;
        puesto: string;
        area: string;
        tipo_puesto: string;
        tipo_contratacion: string;
        tipo_personal: string;
        turno: string;
        rotacion_turnos: string;
        tiempo_puesto_actual: string;
        tiempo_experiencia_laboral_total: string;
      };
    }>;
    total: number;
  };
  clinicalAssessmentParticipants: {
    participants: Array<{
      id: string;
      personal_folio: string;
      name: string;
      has_sections_ii_iii_iv_answers: boolean;
      requires_clinical_assessment: boolean;
      criteria_met: string[];
      sections: {
        ii: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
        iii: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
        iv: {
          label: string;
          yes_count: number;
          answered_count: number;
          threshold: number;
          meets_rule: boolean;
          responses: Array<{ key: string; number: number; text: string; answer: unknown; is_yes: boolean }>;
        };
      };
      demographics: {
        genero: string;
        edad: string;
        estado_civil: string;
        estudios: string;
        puesto: string;
        area: string;
        tipo_puesto: string;
        tipo_contratacion: string;
        tipo_personal: string;
        turno: string;
        rotacion_turnos: string;
        tiempo_puesto_actual: string;
        tiempo_experiencia_laboral_total: string;
      };
    }>;
    total: number;
    requires_clinical_count: number;
  };
  preventionActions?: Array<{
    id: number;
    title: string;
    description: string | null;
    responsible: string | null;
    status: string;
    due_date: string | null;
  }>;
  canManagePreventionActions?: boolean;
  workCenterId?: string;
  organizationId?: string | number;
  analysisBlocks?: {
    referencia_i: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
    referencia_iii: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
  };
  canManageAnalysisBlocks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  preventionActions: () => [],
  canManagePreventionActions: false,
  workCenterId: undefined,
  organizationId: undefined,
  analysisBlocks: () => ({ referencia_i: [], referencia_iii: [] }),
  canManageAnalysisBlocks: false,
  atsPanoramaStatistics: () => ({ items: [], total_evaluations: 0, with_traumatic_event_count: 0, without_traumatic_event_count: 0 }),
  acontecimientoParticipants: () => ({ participants: [], total: 0 }),
  clinicalAssessmentParticipants: () => ({ participants: [], total: 0, requires_clinical_count: 0 }),
});

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const preventionForm = useForm({
  instrument_type: 'referencia_i',
  title: '',
  description: '',
  responsible: '',
  status: 'pendiente',
  due_date: '',
  sort_order: 0,
});

const statusLabels: Record<string, string> = {
  pendiente: 'Pendiente',
  en_proceso: 'En proceso',
  completada: 'Completada',
};

const statusClasses: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800',
  en_proceso: 'bg-blue-100 text-blue-800',
  completada: 'bg-emerald-100 text-emerald-800',
};

const submitPreventionAction = (): void => {
  if (!props.workCenterId) {
    return;
  }

  preventionForm.post(route('work-centers.prevention-actions.store', props.workCenterId), {
    preserveScroll: true,
    onSuccess: () => {
      preventionForm.reset();
      preventionForm.instrument_type = 'referencia_i';
      preventionForm.status = 'pendiente';
      preventionForm.sort_order = 0;
    },
  });
};

const deletePreventionAction = (actionId: number): void => {
  if (!props.workCenterId) {
    return;
  }

  router.delete(route('work-centers.prevention-actions.destroy', [props.workCenterId, actionId]), {
    preserveScroll: true,
  });
};

const activeSubTab = ref<'panorama' | 'analisis' | 'acontecimientos_traumaticos' | 'seguimiento' | 'prevencion' | 'conclusiones'>('panorama');
const chartType = ref<'pie' | 'bar'>('pie');
const selectedQuestionKey = ref('');
const selectedAcontecimientoFilter = ref<'all' | string>('all');
const panoramaResponseFilter = ref<'all' | 'yes' | 'no'>('all');
const panoramaSearch = ref('');
const panoramaPage = ref(1);
const panoramaPageSize = ref(10);
const distributionMode = ref<'area' | 'puesto'>('area');
const isPanoramaDetailsOpen = ref(false);
const selectedPanoramaParticipantId = ref<string | null>(null);
const isClinicalDetailsOpen = ref(false);
const selectedClinicalParticipantId = ref<string | null>(null);
const clinicalSearch = ref('');
const clinicalRequirementFilter = ref<'all' | 'requires'>('all');
const clinicalPage = ref(1);
const clinicalPageSize = ref(10);
const pageSizeOptions = [10, 25, 50];
const analysisFilters = ref({
  genero: '',
  puesto: '',
  area: '',
  turno: '',
});
const analysisChartRef = ref<HTMLCanvasElement | null>(null);
const analysisChartInstance = ref<Chart | null>(null);

const atsColorClasses = [
  'bg-rose-500',
  'bg-amber-500',
  'bg-emerald-500',
  'bg-sky-500',
  'bg-violet-500',
  'bg-fuchsia-500',
];

const maxAtsYesCount = computed(() => {
  const values = props.atsPanoramaStatistics.items.map((item) => item.yes_count);
  return Math.max(...values, 1);
});

const acontecimientoLabels = [
  'Accidente',
  'Asaltos',
  'Actos violentos',
  'Secuestro',
  'Amenazas',
  'Situación de riesgo',
];

const atsPanoramaItems = computed(() => {
  return props.atsPanoramaStatistics.items.map((item, idx) => {
    const ratio = item.yes_count / maxAtsYesCount.value;
    const barPixels = Math.max(12, Math.round(ratio * 220));

    return {
      ...item,
      shortLabel: acontecimientoLabels[idx] ?? `Acontecimiento ${item.index}`,
      colorClass: atsColorClasses[idx % atsColorClasses.length],
      hexColor: atsColorHex[idx % atsColorHex.length],
      barHeight: `${barPixels}px`,
    };
  });
});

const panoramaParticipantsFiltered = computed(() => {
  const query = panoramaSearch.value.trim().toLowerCase();

  return props.acontecimientoParticipants.participants.filter((person) => {
    if (panoramaResponseFilter.value === 'yes' && !person.has_any_event) {
      return false;
    }

    if (panoramaResponseFilter.value === 'no' && person.has_any_event) {
      return false;
    }

    if (selectedAcontecimientoFilter.value !== 'all' && person.events[selectedAcontecimientoFilter.value] !== true) {
      return false;
    }

    if (query.length > 0) {
      return String(person.personal_folio ?? '').toLowerCase().includes(query);
    }

    return true;
  });
});

const totalPanoramaPages = computed(() => {
  const total = panoramaParticipantsFiltered.value.length;
  const pages = Math.ceil(total / panoramaPageSize.value);
  return Math.max(pages, 1);
});

const paginatedPanoramaParticipants = computed(() => {
  const start = (panoramaPage.value - 1) * panoramaPageSize.value;
  const end = start + panoramaPageSize.value;

  return panoramaParticipantsFiltered.value.slice(start, end);
});

const paginationSummary = computed(() => {
  const total = panoramaParticipantsFiltered.value.length;

  if (total === 0) {
    return {
      from: 0,
      to: 0,
      total,
    };
  }

  const from = (panoramaPage.value - 1) * panoramaPageSize.value + 1;
  const to = Math.min(total, panoramaPage.value * panoramaPageSize.value);

  return {
    from,
    to,
    total,
  };
});

const distributionAcontecimientoRows = computed(() => {
  return atsPanoramaItems.value.map((item) => {
    const key = String(item.index);
    const total = panoramaParticipantsFiltered.value.reduce((acc, person) => acc + (person.events[key] ? 1 : 0), 0);

    return {
      index: item.index,
      label: item.shortLabel,
      total,
    };
  });
});

const selectedPanoramaParticipant = computed(() => {
  if (!selectedPanoramaParticipantId.value) {
    return null;
  }

  return props.acontecimientoParticipants.participants.find((person) => person.id === selectedPanoramaParticipantId.value) ?? null;
});

const openPanoramaDetails = (participantId: string): void => {
  selectedPanoramaParticipantId.value = participantId;
  isPanoramaDetailsOpen.value = true;
};

const closePanoramaDetails = (): void => {
  isPanoramaDetailsOpen.value = false;
};

const clinicalParticipantsFiltered = computed(() => {
  const query = clinicalSearch.value.trim().toLowerCase();

  return props.clinicalAssessmentParticipants.participants.filter((person) => {
    if (clinicalRequirementFilter.value === 'requires' && !person.requires_clinical_assessment) {
      return false;
    }

    if (query.length > 0) {
      return String(person.personal_folio ?? '').toLowerCase().includes(query);
    }

    return true;
  });
});

const totalClinicalPages = computed(() => {
  const pages = Math.ceil(clinicalParticipantsFiltered.value.length / clinicalPageSize.value);
  return Math.max(pages, 1);
});

const paginatedClinicalParticipants = computed(() => {
  const start = (clinicalPage.value - 1) * clinicalPageSize.value;
  const end = start + clinicalPageSize.value;

  return clinicalParticipantsFiltered.value.slice(start, end);
});

const clinicalPaginationSummary = computed(() => {
  const total = clinicalParticipantsFiltered.value.length;

  if (total === 0) {
    return {
      from: 0,
      to: 0,
      total,
    };
  }

  const from = (clinicalPage.value - 1) * clinicalPageSize.value + 1;
  const to = Math.min(total, clinicalPage.value * clinicalPageSize.value);

  return {
    from,
    to,
    total,
  };
});

const selectedClinicalParticipant = computed(() => {
  if (!selectedClinicalParticipantId.value) {
    return null;
  }

  return props.clinicalAssessmentParticipants.participants.find((person) => person.id === selectedClinicalParticipantId.value) ?? null;
});

const clinicalSectionsForDetail = computed(() => {
  if (!selectedClinicalParticipant.value) {
    return [];
  }

  return [
    {
      key: 'ii',
      label: selectedClinicalParticipant.value.sections.ii.label,
      responses: selectedClinicalParticipant.value.sections.ii.responses,
      yesCount: selectedClinicalParticipant.value.sections.ii.yes_count,
      total: selectedClinicalParticipant.value.sections.ii.responses.length,
    },
    {
      key: 'iii',
      label: selectedClinicalParticipant.value.sections.iii.label,
      responses: selectedClinicalParticipant.value.sections.iii.responses,
      yesCount: selectedClinicalParticipant.value.sections.iii.yes_count,
      total: selectedClinicalParticipant.value.sections.iii.responses.length,
    },
    {
      key: 'iv',
      label: selectedClinicalParticipant.value.sections.iv.label,
      responses: selectedClinicalParticipant.value.sections.iv.responses,
      yesCount: selectedClinicalParticipant.value.sections.iv.yes_count,
      total: selectedClinicalParticipant.value.sections.iv.responses.length,
    },
  ];
});

const openClinicalDetails = (participantId: string): void => {
  selectedClinicalParticipantId.value = participantId;
  isClinicalDetailsOpen.value = true;
};

const closeClinicalDetails = (): void => {
  isClinicalDetailsOpen.value = false;
};

const filteredEvaluations = computed(() => {
  return props.analysisData.evaluations.filter((evaluation) => {
    if (analysisFilters.value.genero && evaluation.demographics.genero !== analysisFilters.value.genero) {
      return false;
    }
    if (analysisFilters.value.puesto && evaluation.demographics.puesto !== analysisFilters.value.puesto) {
      return false;
    }
    if (analysisFilters.value.area && evaluation.demographics.area !== analysisFilters.value.area) {
      return false;
    }
    if (analysisFilters.value.turno && evaluation.demographics.turno !== analysisFilters.value.turno) {
      return false;
    }

    return true;
  });
});

const eventFilteredEvaluations = computed(() => {
  if (!selectedQuestionKey.value) {
    return filteredEvaluations.value.filter((evaluation) => (evaluation.yes_count ?? 0) > 0);
  }

  return filteredEvaluations.value.filter((evaluation) => isAffirmativeAnswer(evaluation.answers?.[selectedQuestionKey.value]));
});

const responseSummary = computed(() => {
  const participants = eventFilteredEvaluations.value.length;

  if (selectedQuestionKey.value) {
    let totalResponses = 0;
    let yesCount = 0;

    eventFilteredEvaluations.value.forEach((evaluation) => {
      const answer = evaluation.answers?.[selectedQuestionKey.value];

      if (answer === null || answer === undefined) {
        return;
      }

      totalResponses++;
      if (isAffirmativeAnswer(answer)) {
        yesCount++;
      }
    });

    const noCount = Math.max(totalResponses - yesCount, 0);

    return {
      totalResponses,
      yesCount,
      noCount,
      yesPercentage: totalResponses > 0 ? Number(((yesCount / totalResponses) * 100).toFixed(2)) : 0,
      noPercentage: totalResponses > 0 ? Number(((noCount / totalResponses) * 100).toFixed(2)) : 0,
    };
  }

  const totalResponses = participants * 14;
  const yesCount = eventFilteredEvaluations.value.reduce((total, evaluation) => total + (evaluation.yes_count ?? 0), 0);
  const noCount = Math.max(totalResponses - yesCount, 0);

  return {
    totalResponses,
    yesCount,
    noCount,
    yesPercentage: totalResponses > 0 ? Number(((yesCount / totalResponses) * 100).toFixed(2)) : 0,
    noPercentage: totalResponses > 0 ? Number(((noCount / totalResponses) * 100).toFixed(2)) : 0,
  };
});

const isAffirmativeAnswer = (answer: unknown): boolean => {
  if (typeof answer === 'string') {
    const normalizedAnswer = answer.trim().toLowerCase();
    return ['sí', 'si', 'true', '1'].includes(normalizedAnswer);
  }

  return answer === true || answer === 1;
};

const atsColorHex = [
  '#F43F5E',
  '#F59E0B',
  '#10B981',
  '#0EA5E9',
  '#8B5CF6',
  '#D946EF',
];

const destroyAnalysisChart = (): void => {
  if (analysisChartInstance.value) {
    analysisChartInstance.value.destroy();
    analysisChartInstance.value = null;
  }
};

const renderAnalysisChart = async (): Promise<void> => {
  await nextTick();

  if (activeSubTab.value !== 'analisis' || eventFilteredEvaluations.value.length === 0 || !analysisChartRef.value) {
    destroyAnalysisChart();
    return;
  }

  const context = analysisChartRef.value.getContext('2d');
  if (!context) {
    return;
  }

  destroyAnalysisChart();

  analysisChartInstance.value = new Chart(context, {
    type: chartType.value,
    data: {
      labels: ['Sí', 'No'],
      datasets: [
        {
          label: 'Respuestas',
          data: [responseSummary.value.yesCount, responseSummary.value.noCount],
          backgroundColor: ['#10B981', '#EF4444'],
          borderColor: ['#059669', '#DC2626'],
          borderWidth: 1,
          borderRadius: chartType.value === 'bar' ? 8 : 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
        },
        tooltip: {
          callbacks: {
            label: (tooltipItem) => {
              const value = tooltipItem.parsed as number;
              const percentage = responseSummary.value.totalResponses > 0
                ? ((value / responseSummary.value.totalResponses) * 100).toFixed(2)
                : '0.00';

              return `${value} (${percentage}%)`;
            },
          },
        },
      },
      scales: chartType.value === 'bar'
        ? {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          }
        : {},
    },
  });
};

const subTabs = [
  { key: 'panorama', label: 'Panorama general', icon: ChartBarIcon },
  { key: 'acontecimientos_traumaticos', label: 'Acontecimientos traumáticos', icon: UserGroupIcon },
  { key: 'seguimiento', label: 'Seguimiento', icon: ClipboardDocumentListIcon },
  { key: 'analisis', label: 'Análisis', icon: MagnifyingGlassIcon },
  { key: 'prevencion', label: 'Prevención y recomendaciones', icon: ShieldCheckIcon },
  { key: 'conclusiones', label: 'Conclusiones', icon: UserGroupIcon },
] as const;

watch([selectedAcontecimientoFilter, panoramaResponseFilter, panoramaSearch, panoramaPageSize], () => {
  panoramaPage.value = 1;
}, { deep: true });

watch(panoramaResponseFilter, (value) => {
  if (value === 'no' && selectedAcontecimientoFilter.value !== 'all') {
    selectedAcontecimientoFilter.value = 'all';
  }
});

watch([clinicalSearch, clinicalPageSize, clinicalRequirementFilter], () => {
  clinicalPage.value = 1;
}, { deep: true });

watch(totalPanoramaPages, (pages) => {
  if (panoramaPage.value > pages) {
    panoramaPage.value = pages;
  }
});

watch(totalClinicalPages, (pages) => {
  if (clinicalPage.value > pages) {
    clinicalPage.value = pages;
  }
});

watch([activeSubTab, chartType, eventFilteredEvaluations, selectedQuestionKey], () => {
  renderAnalysisChart();
}, { deep: true });

onMounted(() => {
  renderAnalysisChart();
});
</script>
