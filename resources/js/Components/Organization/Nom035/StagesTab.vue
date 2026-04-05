<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-indigo-100 rounded-lg">
          <ChartBarIcon class="w-6 h-6 text-indigo-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Etapas del Cumplimiento NOM-035</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Proceso de implementación y ejecución de la norma</p>
    </div>

    <!-- Sub-tabs -->
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

    <!-- Contenido de Sub-tabs -->
    <div>
      <!-- Identificar Tab -->
      <div v-if="activeSubTab === 'identificar'" class="space-y-6">
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
          
          <!-- Contenido si hay datos -->
          <div v-if="props.domainStatistics && Object.keys(props.domainStatistics.domains || {}).length > 0" class="space-y-6">
            <!-- Selector de Vistas -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="flex flex-col gap-4">
                <div class="flex items-center gap-4">
                  <span class="text-sm font-medium text-slate-700">Vista:</span>
                  <div class="flex flex-wrap gap-2">
                    <button
                      @click="identificarViewMode = 'global'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'global'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Global
                    </button>
                    <button
                      @click="identificarViewMode = 'categories'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'categories'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Categorías
                    </button>
                    <button
                      @click="identificarViewMode = 'domains'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'domains'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Dominios
                    </button>
                    <button
                      @click="identificarViewMode = 'dimensions'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'dimensions'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Dimensiones
                    </button>
                    <button
                      @click="identificarViewMode = 'questions'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'questions'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Preguntas
                    </button>
                    <button
                      @click="identificarViewMode = 'blocks'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        identificarViewMode === 'blocks'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Bloques
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Vista Global -->
            <div v-if="identificarViewMode === 'global'" class="bg-white rounded-lg p-6">
              <GlobalCharts 
                :globalStatistics="props.globalStatistics"
              />
            </div>

            <!-- Gráficas de dominios -->
            <div v-if="identificarViewMode === 'domains'" class="bg-white rounded-lg p-6">
              <DomainCharts 
                :domains="props.domainStatistics.domains"
                :total-evaluations="props.domainStatistics.total_evaluations"
                :colors="props.domainStatistics.colors"
                :labels="props.domainStatistics.labels"
              />
            </div>
            
            <!-- Gráficas de categorías -->
            <div v-if="identificarViewMode === 'categories'" class="bg-white rounded-lg p-6">
              <CategoryCharts 
                :categories="props.categoryStatistics?.categories || {}"
                :total-evaluations="props.categoryStatistics?.total_evaluations || 0"
                :colors="props.categoryStatistics?.colors || {}"
                :labels="props.categoryStatistics?.labels || {}"
              />
            </div>

            <!-- Vista Dimensiones -->
            <div v-if="identificarViewMode === 'dimensions'" class="bg-white rounded-lg p-6">
              <DimensionCharts 
                :dimensions="props.dimensionStatistics?.dimensions || {}"
                :total-evaluations="props.dimensionStatistics?.total_evaluations || 0"
                :colors="props.dimensionStatistics?.colors || {}"
                :labels="props.dimensionStatistics?.labels || {}"
              />
            </div>

            <!-- Vista Preguntas -->
            <div v-if="identificarViewMode === 'questions'" class="bg-white rounded-lg p-6">
              <QuestionsCharts 
                :questions-data="props.questionStatistics?.questions || {}"
                :total-evaluations="props.questionStatistics?.total_evaluations || 0"
              />
            </div>

            <!-- Vista Bloques -->
            <div v-if="identificarViewMode === 'blocks'">
              <BlocksCharts
                :blocks-data="props.blockStatistics?.blocks || {}"
                :total-evaluations="props.blockStatistics?.total_evaluations || 0"
              />
            </div>
          </div>
          
          <!-- Mostrar mensaje si no hay datos -->
          <div v-else class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-blue-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-blue-400 mx-auto mb-3 animate-spin" />
                <p class="text-blue-700 font-medium">Sin datos disponibles</p>
                <p class="text-sm text-blue-600 mt-1">No se han encontrado evaluaciones de Referencia III para mostrar estadísticas</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
              <h4 class="font-bold text-slate-900">Cuestionarios</h4>
            </div>
            <p class="text-sm text-slate-600">Instrumentos de evaluación para identificar factores de riesgo</p>
          </div>
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <UserGroupIcon class="w-6 h-6 text-blue-600" />
              <h4 class="font-bold text-slate-900">Entrevistas</h4>
            </div>
            <p class="text-sm text-slate-600">Conversaciones con trabajadores para detectar situaciones de riesgo</p>
          </div>

          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
              <h4 class="font-bold text-slate-900">Violencia Laboral</h4>
            </div>
            <p class="text-sm text-slate-600 mb-4">Monitorea el dominio de violencia laboral (preguntas 57 a 64) y conecta hallazgos con acciones preventivas.</p>
            <button
              type="button"
              @click="openViolenceAnalysisView"
              class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
            >
              Ver analisis de violencia laboral
            </button>
          </div>
        </div>
      </div>

      <!-- Analizar Tab -->
      <div v-if="activeSubTab === 'analizar'" class="space-y-6">
        <div class="bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl p-8 border border-purple-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-purple-100 rounded-lg">
              <ChartBarIcon class="w-6 h-6 text-purple-600" />
            </div>
            <h3 class="text-2xl font-bold text-purple-900">Analizar Resultados</h3>
          </div>

          <!-- Filtros Demográficos -->
          <div v-if="props.analysisData && props.analysisData.evaluations.length > 0" class="space-y-6">
            <AnalysisFilters
              :demographics="props.analysisData.demographics"
              v-model="analysisFilters"
            />

            <!-- Toggle Dominios/Categorías y Selector de Dominio -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="flex flex-col gap-4">
                <!-- Vista Toggle -->
                <div class="flex items-center gap-4">
                  <span class="text-sm font-medium text-slate-700">Vista:</span>
                  <div class="flex gap-2">
                    <button
                      @click="analysisViewMode = 'general_report'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        analysisViewMode === 'general_report'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Reporte General
                    </button>
                    <button
                      @click="analysisViewMode = 'domains'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        analysisViewMode === 'domains'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Dominios
                    </button>
                    <button
                      @click="analysisViewMode = 'categories'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        analysisViewMode === 'categories'
                          ? 'bg-indigo-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Categorías
                    </button>
                    <button
                      @click="analysisViewMode = 'violencia'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        analysisViewMode === 'violencia'
                          ? 'bg-red-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      Violencia Laboral
                    </button>
                  </div>
                  <div class="ml-auto text-sm text-slate-600">
                    <span class="font-semibold">{{ analysisViewMode === 'violencia' ? filteredViolenceParticipants.length : (analysisViewMode === 'general_report' ? (props.generalReport?.total_evaluations ?? 0) : filteredEvaluations.length) }}</span>
                    {{ analysisViewMode === 'general_report' ? 'evaluaciones incluidas' : 'evaluaciones filtradas' }}
                  </div>
                </div>

                <!-- Domain Selector (only in domains view) -->
                <div v-if="analysisViewMode === 'domains' && sortedDomainsByRisk.length > 0" class="flex items-center gap-4">
                  <span class="text-sm font-medium text-slate-700">Dominio:</span>
                  <select
                    v-model="selectedDomain"
                    class="flex-1 px-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                  >
                    <option value="">Global (Todos los dominios)</option>
                    <option
                      v-for="{ domainName } in sortedDomainsByRisk"
                      :key="domainName"
                      :value="domainName"
                    >
                      {{ domainName }}
                    </option>
                  </select>
                </div>

                <!-- Category Selector (only in categories view) -->
                <div v-if="analysisViewMode === 'categories' && sortedCategoriesByRisk.length > 0" class="flex items-center gap-4">
                  <span class="text-sm font-medium text-slate-700">Categoría:</span>
                  <select
                    v-model="selectedCategory"
                    class="flex-1 px-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                  >
                    <option value="">Global (Todas las categorías)</option>
                    <option
                      v-for="{ categoryName } in sortedCategoriesByRisk"
                      :key="categoryName"
                      :value="categoryName"
                    >
                      {{ categoryName }}
                    </option>
                  </select>
                </div>

                <!-- Chart Type Toggle -->
                <div v-if="analysisViewMode !== 'general_report'" class="flex items-center gap-4">
                  <span class="text-sm font-medium text-slate-700">Tipo de Gráfica:</span>
                  <div class="flex gap-2">
                    <button
                      @click="chartType = 'pie'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2',
                        chartType === 'pie'
                          ? 'bg-purple-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                      </svg>
                      {{ analysisViewMode === 'violencia' ? 'Dona' : 'Pastel' }}
                    </button>
                    <button
                      @click="chartType = 'bar'"
                      :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2',
                        chartType === 'bar'
                          ? 'bg-purple-600 text-white'
                          : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                      ]"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                      </svg>
                      Barras
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="analysisViewMode === 'general_report'" class="bg-white rounded-lg p-6 border border-slate-200 space-y-4">
              <div class="flex flex-col gap-1">
                <h4 class="text-lg font-semibold text-slate-900">Detalle por Categoría, Dominio y Dimensión</h4>
                <p class="text-sm text-slate-600">Niveles NOM calculados con puntajes acumulados por suma. El promedio 0-4 se conserva únicamente en ítems.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Calificación Final Promedio</p>
                  <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ formatScore(finalGlobalSummary.averageScore) }} / {{ formatIntegerScore(finalGlobalSummary.maxScore) }}
                  </p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nivel de Riesgo Global</p>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide shadow-sm" :style="getRiskBadgeSolidStyle(finalGlobalSummary.riskLevel)">
                      {{ finalGlobalSummary.riskLabel }}
                    </span>
                    <span class="text-lg font-bold text-slate-900">{{ formatScore(finalGlobalSummary.percentage) }}%</span>
                  </div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Evaluaciones Consideradas</p>
                  <p class="mt-2 text-2xl font-bold text-slate-900">{{ finalGlobalSummary.totalEvaluations }}</p>
                </div>
              </div>

              <div v-if="props.generalReport && props.generalReport.rows.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                  <thead class="bg-slate-50">
                    <tr>
                      <th class="px-4 py-3 text-center border border-slate-200 text-xs font-medium text-slate-500 uppercase">Categoría</th>
                      <th class="px-4 py-3 text-center border border-slate-200 text-xs font-medium text-slate-500 uppercase">Dominio</th>
                      <th class="px-4 py-3 text-center border border-slate-200 text-xs font-medium text-slate-500 uppercase">Dimensión</th>
                      <th class="px-4 py-3 text-center border border-slate-200 text-xs font-medium text-slate-500 uppercase">Ítem</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-slate-200">
                    <template v-for="(cat, catIdx) in groupedGeneralReport" :key="`cat_${catIdx}`">
                      <template v-for="(dom, domIdx) in cat.dominios" :key="`dom_${catIdx}_${domIdx}`">
                        <template v-for="(dim, dimIdx) in dom.dimensiones" :key="`dim_${catIdx}_${domIdx}_${dimIdx}`">
                          <template v-for="(item, itemIdx) in dim.items" :key="`item_${catIdx}_${domIdx}_${dimIdx}_${itemIdx}`">
                            <tr>
                              <td v-if="domIdx === 0 && dimIdx === 0 && itemIdx === 0" :rowspan="cat.rowspan" class="px-4 py-3 border border-slate-200 text-center align-middle bg-slate-50">
                                <div class="rounded-lg border px-3 py-2" :style="getRiskContainerStyle(cat.nivel_riesgo)">
                                  <div class="font-medium text-slate-900">{{ cat.nombre }}</div>
                                  <div class="mt-2 flex items-center justify-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide shadow-sm" :style="getRiskBadgeSolidStyle(cat.nivel_riesgo)">
                                      {{ getRiskLevelLabel(normalizeRiskLevel(cat.nivel_riesgo)) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                                      {{ getAverageByEvaluations(cat.score) }}
                                    </span>
                                    <div class="relative group">
                                      <button
                                        type="button"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] font-bold text-slate-500 transition-colors hover:text-slate-700"
                                        :aria-label="`Ver detalle de ${cat.nombre}`"
                                      >
                                        ?
                                      </button>
                                      <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-56 -translate-x-1/2 rounded-lg border border-slate-200 bg-slate-900 p-3 text-left text-xs text-white shadow-lg group-hover:block group-focus-within:block">
                                        <p><span class="font-semibold">Suma total:</span> {{ formatIntegerScore(cat.score) }}</p>
                                        <p><span class="font-semibold"># de evaluaciones:</span> {{ totalEvaluationsForTooltips }}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>

                              <td v-if="dimIdx === 0 && itemIdx === 0" :rowspan="dom.rowspan" class="px-4 py-3 border border-slate-200 text-center align-middle">
                                <div class="rounded-lg border px-3 py-2" :style="getRiskContainerStyle(dom.nivel_riesgo)">
                                  <div class="font-medium text-slate-900">{{ dom.nombre }}</div>
                                  <div class="mt-2 flex items-center justify-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide shadow-sm" :style="getRiskBadgeSolidStyle(dom.nivel_riesgo)">
                                      {{ getRiskLevelLabel(normalizeRiskLevel(dom.nivel_riesgo)) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                                      {{ getAverageByEvaluations(dom.score) }}
                                    </span>
                                    <div class="relative group">
                                      <button
                                        type="button"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] font-bold text-slate-500 transition-colors hover:text-slate-700"
                                        :aria-label="`Ver detalle de ${dom.nombre}`"
                                      >
                                        ?
                                      </button>
                                      <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-56 -translate-x-1/2 rounded-lg border border-slate-200 bg-slate-900 p-3 text-left text-xs text-white shadow-lg group-hover:block group-focus-within:block">
                                        <p><span class="font-semibold">Suma total:</span> {{ formatIntegerScore(dom.score) }}</p>
                                        <p><span class="font-semibold"># de evaluaciones:</span> {{ totalEvaluationsForTooltips }}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>

                              <td v-if="itemIdx === 0" :rowspan="dim.rowspan" class="px-4 py-3 border border-slate-200 text-center align-middle text-slate-700">
                                <div class="rounded-lg border px-3 py-2" :style="getRiskContainerStyle(dim.nivel_riesgo)">
                                  <div class="font-medium text-slate-900">{{ dim.nombre }}</div>
                                  <div class="mt-2 flex items-center justify-center gap-2">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide shadow-sm" :style="getRiskBadgeSolidStyle(dim.nivel_riesgo)">
                                      {{ getRiskLevelLabel(normalizeRiskLevel(dim.nivel_riesgo)) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700">
                                      {{ getAverageByEvaluations(dim.score) }}
                                    </span>
                                    <div class="relative group">
                                      <button
                                        type="button"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-300 bg-white text-[10px] font-bold text-slate-500 transition-colors hover:text-slate-700"
                                        :aria-label="`Ver detalle de ${dim.nombre}`"
                                      >
                                        ?
                                      </button>
                                      <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-56 -translate-x-1/2 rounded-lg border border-slate-200 bg-slate-900 p-3 text-left text-xs text-white shadow-lg group-hover:block group-focus-within:block">
                                        <p><span class="font-semibold">Suma total:</span> {{ formatIntegerScore(dim.score) }}</p>
                                        <p><span class="font-semibold"># de evaluaciones:</span> {{ totalEvaluationsForTooltips }}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>

                              <td class="px-4 py-3 border border-slate-200 text-slate-800">
                                <div class="flex items-center justify-between gap-3">
                                  <div class="text-left">
                                    <span class="font-semibold text-indigo-600">{{ item.item_numero }}.</span>
                                    {{ item.nombre }}
                                  </div>
                                  <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide" :style="getRiskBadgeSolidStyle(getItemAverageRiskLevel(item.puntaje))">
                                      {{ getRiskLevelLabel(getItemAverageRiskLevel(item.puntaje)) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                      {{ formatScore(item.puntaje) }}
                                    </span>
                                    <div class="relative group shrink-0">
                                      <button
                                        type="button"
                                        class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-slate-300 bg-white text-[9px] font-bold leading-none text-slate-500 transition-colors hover:text-slate-700"
                                        :aria-label="`Ver detalle del ítem ${item.item_numero}`"
                                      >
                                        ?
                                      </button>
                                      <div class="pointer-events-none absolute bottom-full right-0 z-20 mb-2 hidden w-56 rounded-lg border border-slate-200 bg-slate-900 p-3 text-left text-xs text-white shadow-lg group-hover:block group-focus-within:block">
                                        <p><span class="font-semibold">Suma total:</span> {{ formatIntegerScore(getItemTotalScore(item.puntaje)) }}</p>
                                        <p><span class="font-semibold"># de evaluaciones:</span> {{ totalEvaluationsForTooltips }}</p>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>
                            </tr>
                          </template>
                        </template>
                      </template>
                    </template>
                  </tbody>
                </table>
              </div>

              <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                <p class="text-slate-600">No hay datos suficientes para generar el reporte general.</p>
              </div>
            </div>

            <!-- Distribución y Gráfica -->
            <div v-if="analysisViewMode !== 'violencia' && analysisViewMode !== 'general_report'" class="bg-white rounded-lg p-6 border border-slate-200">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Cards de distribución -->
                <div>
                  <RiskDistributionCards
                    :distribution="filteredDistribution"
                    :colors="props.analysisData.colors"
                    :labels="props.analysisData.labels"
                    @showDetails="showRiskDetailsModal"
                  />
                </div>

                <!-- Gráfica de pastel o barras -->
                <div>
                  <RiskPieChart
                    v-if="chartType === 'pie'"
                    :distribution="filteredDistribution"
                    :colors="props.analysisData.colors"
                    :labels="props.analysisData.labels"
                    :title="analysisViewMode === 'domains' ? (selectedDomain || 'Distribución Global') : (selectedCategory || 'Distribución Global')"
                  />
                  <RiskBarChart
                    v-else
                    :distribution="filteredDistribution"
                    :colors="props.analysisData.colors"
                    :labels="props.analysisData.labels"
                    :title="analysisViewMode === 'domains' ? (selectedDomain || 'Distribución Global') : (selectedCategory || 'Distribución Global')"
                  />
                </div>
              </div>
            </div>

            <div v-if="analysisViewMode === 'violencia'" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Total evaluados (dominio)</p>
                  <p class="mt-1 text-2xl font-bold text-red-900">{{ filteredViolenceParticipants.length }}</p>
                </div>
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Personas con violencia laboral muy alta</p>
                  <p class="mt-1 text-2xl font-bold text-orange-900">{{ filteredViolenceVeryHighCount }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg p-6 border border-slate-200">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                  <div>
                    <RiskDistributionCards
                      :distribution="filteredViolenceDistribution"
                      :colors="violenceColors"
                      :labels="violenceLabels"
                      @showDetails="showViolenceRiskDetailsModal"
                    />
                  </div>

                  <div>
                    <RiskPieChart
                      v-if="chartType === 'pie'"
                      :distribution="filteredViolenceDistribution"
                      :colors="violenceColors"
                      :labels="violenceLabels"
                      :title="'Violencia Laboral (57-64)'"
                      variant="doughnut"
                    />
                    <RiskBarChart
                      v-else
                      :distribution="filteredViolenceDistribution"
                      :colors="violenceColors"
                      :labels="violenceLabels"
                      :title="'Violencia Laboral (57-64)'"
                    />
                  </div>
                </div>
              </div>

              <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                  <h4 class="text-sm font-semibold text-slate-900">Tabla por pregunta (57-64)</h4>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                      <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-700">Pregunta</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Nulo</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Bajo</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Medio</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Alto</th>
                        <th class="px-4 py-3 text-center font-semibold text-slate-700">Muy Alto</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                      <tr v-for="question in filteredViolenceQuestions" :key="question.number">
                        <td class="px-4 py-3 text-slate-800">
                          <p class="font-semibold">{{ question.number }}. {{ question.text }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">{{ question.distribution.nulo }}</td>
                        <td class="px-4 py-3 text-center">{{ question.distribution.bajo }}</td>
                        <td class="px-4 py-3 text-center">{{ question.distribution.medio }}</td>
                        <td class="px-4 py-3 text-center">{{ question.distribution.alto }}</td>
                        <td class="px-4 py-3 text-center">{{ question.distribution.muy_alto }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="flex justify-end pb-6">
                <button
                  type="button"
                  @click="openParticipantsWithViolenceRisk"
                  class="inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500"
                >
                  Ver participantes que han sufrido violencia laboral muy alta
                </button>
              </div>
            </div>

          </div>

          <!-- Sin datos -->
          <div v-else class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-purple-300 rounded-lg">
              <div class="text-center">
                <ChartPieIcon class="w-12 h-12 text-purple-400 mx-auto mb-3" />
                <p class="text-purple-700 font-medium">Sin datos disponibles</p>
                <p class="text-sm text-purple-600 mt-1">No se han encontrado evaluaciones de Referencia III para analizar</p>
              </div>
            </div>
          </div>

          <AnalysisWysiwygBlocks
            v-if="organizationId && (canManageAnalysisBlocks || analysisBlocks.referencia_iii.length > 0)"
            :organization-id="organizationId"
            instrument-type="referencia_iii"
            :blocks="analysisBlocks.referencia_iii"
            :can-manage="canManageAnalysisBlocks"
          />
        </div>
      </div>

      <!-- Modal de Detalles por Nivel de Riesgo -->
      <div v-if="showRiskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
          <div class="p-6 border-b border-slate-200 flex justify-between items-center">
            <div>
              <h3 class="text-lg font-semibold text-slate-900">
                Personal por Nivel de Riesgo
              </h3>
              <p class="text-sm text-slate-600 mt-1">
                <span :class="getRiskLevelColorClass(selectedRiskLevel)">
                  {{ selectedRiskLevel }}
                </span>
                <span v-if="selectedDomain" class="text-slate-500 ml-2">en {{ selectedDomain }}</span>
                <span v-else-if="selectedCategory" class="text-slate-500 ml-2">en {{ selectedCategory }}</span>
                <span v-else class="text-slate-500 ml-2">(Global)</span>
              </p>
            </div>
            <button
              @click="closeRiskModal"
              class="text-slate-500 hover:text-slate-700 transition-colors"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto flex-grow">
            <div v-if="filteredRiskPersonal.length > 0" class="space-y-3">
              <div class="mb-4">
                <p class="text-sm text-slate-600">
                  Total: <span class="font-semibold text-slate-900">{{ filteredRiskPersonal.length }}</span> personas
                </p>
              </div>
              <div class="border rounded-lg overflow-hidden">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
                  <div class="grid grid-cols-3 gap-4">
                    <div class="text-sm font-semibold text-slate-700">Folio</div>
                    <div class="text-sm font-semibold text-slate-700">Puntaje</div>
                    <div class="text-sm font-semibold text-slate-700">Acciones</div>
                  </div>
                </div>
                <div class="divide-y divide-slate-200 max-h-96 overflow-y-auto">
                  <div
                    v-for="item in filteredRiskPersonal"
                    :key="item.personal_folio"
                    class="px-4 py-3 hover:bg-slate-50 transition-colors"
                  >
                    <div class="grid grid-cols-3 gap-4 items-center">
                      <div class="text-sm font-medium text-slate-900">
                        {{ item.personal_folio }}
                      </div>
                      <div class="text-sm text-slate-600">
                        {{ item.score }} pts
                      </div>
                      <div>
                        <Link
                          :href="route('organization.results.detail', {
                            organization: organizationId,
                            personalFolio: item.personal_folio
                          })"
                          class="text-indigo-600 hover:text-indigo-800 text-sm font-medium hover:underline"
                        >
                          Ver detalles →
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-slate-500">No hay personal en este nivel de riesgo</p>
            </div>
          </div>
          
          <div class="p-6 border-t border-slate-200 flex justify-end">
            <button
              @click="closeRiskModal"
              class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg transition-colors font-medium"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>

      <!-- Participantes Tab -->
      <div v-if="activeSubTab === 'participantes'" class="space-y-6">
        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-8 border border-teal-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-teal-100 rounded-lg">
              <UserGroupIcon class="w-6 h-6 text-teal-600" />
            </div>
            <h3 class="text-2xl font-bold text-teal-900">Informe de Participantes</h3>
          </div>

          <div v-if="props.analysisData && props.analysisData.evaluations.length > 0" class="space-y-6">
            <!-- Conteo de participantes -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="text-sm text-slate-700">
                <span class="font-medium">Total de participantes:</span>
                <span class="font-bold text-teal-600 ml-2">{{ filteredParticipants.length }}</span>
              </div>
            </div>

            <!-- Filtros de participantes -->
            <div class="bg-white rounded-lg p-4 border border-slate-200">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="participants_sort" class="block text-sm font-medium text-slate-700 mb-1">
                    Ordenar por
                  </label>
                  <select
                    id="participants_sort"
                    v-model="participantsSortBy"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  >
                    <option value="folio">Por folio</option>
                    <option value="risk">Por nivel de riesgo</option>
                  </select>
                </div>

                <div>
                  <label for="participants_risk" class="block text-sm font-medium text-slate-700 mb-1">
                    Filtrar nivel de riesgo
                  </label>
                  <select
                    id="participants_risk"
                    v-model="participantsRiskFilter"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  >
                    <option value="">Todos</option>
                    <option value="nulo">Nulo</option>
                    <option value="bajo">Bajo</option>
                    <option value="medio">Medio</option>
                    <option value="alto">Alto</option>
                    <option value="muy_alto">Muy Alto</option>
                  </select>
                </div>

                <div>
                  <label for="participants_violence" class="block text-sm font-medium text-slate-700 mb-1">
                    Filtro violencia laboral
                  </label>
                  <select
                    id="participants_violence"
                    v-model="participantsViolenceFilter"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                  >
                    <option value="">Todos</option>
                    <option value="muy_alto">Solo violencia laboral muy alta</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Lista de participantes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
              <div v-if="filteredParticipants.length === 0" class="p-6 text-center text-slate-500">
                No se encontraron datos de participantes
              </div>

              <ul v-else class="divide-y divide-slate-200">
                <li
                  v-for="(participant, index) in filteredParticipants"
                  :key="participant.personal_folio"
                  class="hover:bg-slate-50 transition-colors duration-150 p-0"
                >
                  <Link
                    :href="route('organization.results.detail', {
                      organization: organizationId,
                      personalFolio: participant.personal_folio
                    })"
                    target="_blank"
                    class="flex justify-between items-center p-4 w-full h-full no-underline text-inherit hover:no-underline"
                  >
                    <div class="flex items-center space-x-3">
                      <div class="bg-teal-100 text-teal-800 font-bold rounded-full h-8 w-8 flex items-center justify-center">
                        {{ index + 1 }}
                      </div>
                      <span class="font-medium text-slate-900">Folio {{ participant.personal_folio }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                      <span
                        :class="getRiskLevelPillClass(participant.risk_level)"
                        class="px-2 py-1 rounded-full text-xs font-semibold uppercase tracking-wide"
                      >
                        {{ getRiskLevelLabel(participant.risk_level) }}
                      </span>
                      <div class="hidden sm:block w-32 border-b border-dotted border-slate-300"></div>
                      <div :class="getScoreClass(participant.score)" class="px-3 py-1 rounded-full text-white font-medium min-w-[80px] text-center">
                        {{ participant.score }} pts
                      </div>
                    </div>
                  </Link>
                </li>
              </ul>
            </div>

            <!-- Leyenda de colores para niveles de riesgo -->
            <div class="bg-white rounded-lg shadow-sm p-6">
              <h4 class="font-medium text-slate-900 mb-4">Niveles de Riesgo</h4>
              <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="flex items-center gap-2">
                  <span class="inline-block w-4 h-4 bg-blue-500 rounded-full"></span>
                  <span class="text-sm text-slate-600">Nulo (0-49)</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block w-4 h-4 bg-green-500 rounded-full"></span>
                  <span class="text-sm text-slate-600">Bajo (50-74)</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block w-4 h-4 bg-amber-500 rounded-full"></span>
                  <span class="text-sm text-slate-600">Medio (75-98)</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block w-4 h-4 bg-orange-500 rounded-full"></span>
                  <span class="text-sm text-slate-600">Alto (99-139)</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block w-4 h-4 bg-red-500 rounded-full"></span>
                  <span class="text-sm text-slate-600">Muy Alto (140+)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Sin datos -->
          <div v-else class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-teal-300 rounded-lg">
              <div class="text-center">
                <UserGroupIcon class="w-12 h-12 text-teal-400 mx-auto mb-3" />
                <p class="text-teal-700 font-medium">Sin datos disponibles</p>
                <p class="text-sm text-teal-600 mt-1">No se han encontrado evaluaciones de participantes para mostrar</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Prevenir Tab -->
      <div v-if="activeSubTab === 'prevenir'" class="space-y-6">

        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-8 border border-emerald-200 hover:shadow-lg transition-shadow">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-emerald-100 rounded-lg">
              <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
            </div>
            <h3 class="text-2xl font-bold text-emerald-900">Prevenir y Controlar Riesgos</h3>
          </div>
          <div class="bg-white rounded-lg p-6 space-y-4">
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Conexión Violencia Laboral</p>
                  <p class="text-sm text-red-900 mt-1">
                    Casos detectados con violencia laboral muy alta: <span class="font-bold">{{ props.violenceLaborStatistics.total_by_level.muy_alto ?? 0 }}</span>
                  </p>
                </div>
                <button
                  type="button"
                  @click="openViolenceAnalysisView"
                  class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
                >
                  Revisar en Analizar
                </button>
              </div>
            </div>

            <form
              v-if="canManagePreventionActions && workCenterId"
              @submit.prevent="submitPreventionAction"
              class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 space-y-4"
            >
              <h4 class="text-sm font-semibold text-emerald-900">Agregar acción preventiva</h4>

              <div>
                <label for="ref3_prevent_title" class="block text-sm font-medium text-slate-700">Título</label>
                <input
                  id="ref3_prevent_title"
                  v-model="preventionForm.title"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
                <p v-if="preventionForm.errors.title" class="mt-1 text-xs text-red-500">{{ preventionForm.errors.title }}</p>
              </div>

              <div>
                <label for="ref3_prevent_desc" class="block text-sm font-medium text-slate-700">Descripción</label>
                <textarea
                  id="ref3_prevent_desc"
                  v-model="preventionForm.description"
                  rows="4"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                />
                <p v-if="preventionForm.errors.description" class="mt-1 text-xs text-red-500">{{ preventionForm.errors.description }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="ref3_prevent_responsible" class="block text-sm font-medium text-slate-700">Responsable</label>
                  <input
                    id="ref3_prevent_responsible"
                    v-model="preventionForm.responsible"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label for="ref3_prevent_due" class="block text-sm font-medium text-slate-700">Fecha objetivo</label>
                  <input
                    id="ref3_prevent_due"
                    v-model="preventionForm.due_date"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label for="ref3_prevent_status" class="block text-sm font-medium text-slate-700">Estatus</label>
                  <select
                    id="ref3_prevent_status"
                    v-model="preventionForm.status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                  >
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="completada">Completada</option>
                  </select>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="preventionForm.processing"
                  class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                >
                  {{ preventionForm.processing ? 'Guardando...' : 'Guardar acción' }}
                </button>
              </div>
            </form>

            <div v-if="preventionActions.length === 0" class="flex items-center justify-center p-8 border-2 border-dashed border-emerald-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-emerald-400 mx-auto mb-3 animate-spin" />
                <p class="text-emerald-700 font-medium">Sin acciones registradas</p>
                <p class="text-sm text-emerald-600 mt-1">Acciones preventivas y planes de mejora continua</p>
              </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <article
                v-for="action in preventionActions"
                :key="action.id"
                class="rounded-lg border border-slate-200 p-4 space-y-2"
              >
                <div class="flex items-start justify-between gap-3">
                  <h4 class="font-semibold text-slate-900">{{ action.title }}</h4>
                  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClasses[action.status]">
                    {{ statusLabels[action.status] ?? action.status }}
                  </span>
                </div>

                <p v-if="action.description" class="text-sm text-slate-600">{{ action.description }}</p>

                <div class="text-xs text-slate-500 flex flex-wrap gap-4">
                  <span v-if="action.responsible">Responsable: {{ action.responsible }}</span>
                  <span v-if="action.due_date">Fecha: {{ action.due_date }}</span>
                </div>

                <div v-if="canManagePreventionActions && workCenterId" class="flex justify-end">
                  <button
                    type="button"
                    @click="deletePreventionAction(action.id)"
                    class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
                  >
                    Eliminar
                  </button>
                </div>
              </article>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <LightBulbIcon class="w-6 h-6 text-emerald-600" />
              <h4 class="font-bold text-slate-900">Medidas Preventivas</h4>
            </div>
            <p class="text-sm text-slate-600">Implementación de acciones para reducir riesgos identificados</p>
          </div>
          <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-3 mb-4">
              <ArrowPathIcon class="w-6 h-6 text-emerald-600" />
              <h4 class="font-bold text-slate-900">Seguimiento</h4>
            </div>
            <p class="text-sm text-slate-600">Monitoreo continuo de la efectividad de las medidas implementadas</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import DomainCharts from './Charts/DomainCharts.vue';
import CategoryCharts from './Charts/CategoryCharts.vue';
import DimensionCharts from './Charts/DimensionCharts.vue';
import QuestionsCharts from './Charts/QuestionsCharts.vue';
import BlocksCharts from './Charts/BlocksCharts.vue';
import GlobalCharts from './Charts/GlobalCharts.vue';
import AnalysisFilters from './Charts/AnalysisFilters.vue';
import RiskDistributionCards from './Charts/RiskDistributionCards.vue';
import RiskPieChart from './Charts/RiskPieChart.vue';
import RiskBarChart from './Charts/RiskBarChart.vue';
import AnalysisWysiwygBlocks from './AnalysisWysiwygBlocks.vue';
import {
  ChartBarIcon,
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  ClipboardDocumentListIcon,
  UserGroupIcon,
  ChartPieIcon,
  DocumentChartBarIcon,
  LightBulbIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

interface DomainStatistics {
  domains: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface CategoryStatistics {
  categories: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface DimensionStatistics {
  dimensions: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface QuestionStatistics {
  questions: Record<string, unknown>;
  total_evaluations: number;
}

interface BlockStatistics {
  blocks: Record<string, unknown>;
  total_evaluations: number;
}

interface GlobalStatistics {
  global: Record<string, unknown>;
  total_evaluations: number;
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface Evaluation {
  id: string;
  folio: string;
  personal_folio: string;
  evaluee_name: string;
  demographics: {
    genero: string;
    puesto: string;
    area: string;
    turno: string;
  };
  domain_scores: Record<string, { score: number; risk_level: string }>;
  category_scores: Record<string, { score: number; risk_level: string; domain: string }>;
}

interface AnalysisData {
  evaluations: Evaluation[];
  demographics: {
    generos: string[];
    puestos: string[];
    areas: string[];
    turnos: string[];
  };
  colors: Record<string, string>;
  labels: Record<string, string>;
}

interface GeneralReportRow {
  categoria: {
    nombre: string;
    score: number;
    nivel_riesgo: string;
  };
  dominio: {
    nombre: string;
    score: number;
    nivel_riesgo: string;
  };
  dimension: {
    nombre: string;
    score: number;
    nivel_riesgo: string;
  };
  item: string;
  item_numero: number;
  puntaje: number;
}

interface GeneralReport {
  total_evaluations: number;
  average_total_score: number;
  total_score: number;
  max_score: number;
  percentage: number;
  final_average_score?: number;
  final_max_score?: number;
  final_percentage?: number;
  final_risk_level?: string;
  final_risk_label?: string;
  rows: GeneralReportRow[];
}

interface ViolenceLaborQuestion {
  number: number;
  text: string;
  distribution: Record<string, number>;
  total_responses: number;
  high_risk_total: number;
}

interface ViolenceLaborParticipant {
  personal_folio: string;
  demographics: {
    genero: string;
    puesto: string;
    area: string;
    turno: string;
  };
  violence_score: number;
  risk_level: string;
  question_levels: Record<string, string>;
}

interface ViolenceLaborStatistics {
  question_numbers: number[];
  labels: Record<string, string>;
  colors: Record<string, string>;
  domain_levels: Record<string, { min: number; max: number }>;
  total_evaluated: number;
  total_by_level: Record<string, number>;
  high_risk_total: number;
  questions: ViolenceLaborQuestion[];
  participants: ViolenceLaborParticipant[];
}

interface Props {
  domainStatistics?: DomainStatistics;
  categoryStatistics?: CategoryStatistics;
  dimensionStatistics?: DimensionStatistics;
  questionStatistics?: QuestionStatistics;
  blockStatistics?: BlockStatistics;
  globalStatistics?: GlobalStatistics;
  analysisData?: AnalysisData;
  generalReport?: GeneralReport;
  violenceLaborStatistics?: ViolenceLaborStatistics;
  organizationId?: string | number;
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
  analysisBlocks?: {
    referencia_i: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
    referencia_iii: Array<{ id: number; title: string | null; content_html: string; sort_order: number }>;
  };
  canManageAnalysisBlocks?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  domainStatistics: () => ({ domains: {}, total_evaluations: 0, colors: {}, labels: {} }),
  categoryStatistics: () => ({ categories: {}, total_evaluations: 0, colors: {}, labels: {} }),
  dimensionStatistics: () => ({ dimensions: {}, total_evaluations: 0, colors: {}, labels: {} }),
  globalStatistics: () => ({ global: {}, total_evaluations: 0, colors: {}, labels: {} }),
  analysisData: () => ({ evaluations: [], demographics: { generos: [], puestos: [], areas: [], turnos: [] }, colors: {}, labels: {} }),
  generalReport: () => ({ total_evaluations: 0, average_total_score: 0, total_score: 0, max_score: 0, percentage: 0, rows: [] }),
  violenceLaborStatistics: () => ({
    question_numbers: [],
    labels: {},
    colors: {},
    domain_levels: {},
    total_evaluated: 0,
    total_by_level: {
      nulo: 0,
      bajo: 0,
      medio: 0,
      alto: 0,
      muy_alto: 0,
    },
    high_risk_total: 0,
    questions: [],
    participants: [],
  }),
  organizationId: () => '',
  preventionActions: () => [],
  canManagePreventionActions: false,
  workCenterId: undefined,
  analysisBlocks: () => ({ referencia_i: [], referencia_iii: [] }),
  canManageAnalysisBlocks: false,
});

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const preventionForm = useForm({
  instrument_type: 'referencia_iii',
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
      preventionForm.instrument_type = 'referencia_iii';
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

const activeSubTab = ref('identificar');

// Identificar state
const identificarViewMode = ref<'global' | 'domains' | 'categories' | 'dimensions' | 'questions' | 'blocks'>('global');

// Analysis state
const analysisViewMode = ref<'general_report' | 'domains' | 'categories' | 'violencia'>('general_report');
const selectedDomain = ref<string>(''); // Empty string means "Global" (all domains)
const selectedCategory = ref<string>(''); // Empty string means "Global" (all categories)
const chartType = ref<'pie' | 'bar'>('pie'); // Chart type toggle
const analysisFilters = ref({
  genero: '',
  puesto: '',
  area: '',
  turno: '',
});

// Modal state for risk details
const showRiskModal = ref(false);
const selectedRiskLevel = ref<string>('');
const filteredRiskPersonal = ref<any[]>([]);
const participantsSortBy = ref<'folio' | 'risk'>('folio');
const participantsRiskFilter = ref<string>('');
const participantsViolenceFilter = ref<string>('');

const violenceLabels = computed(() => {
  return Object.keys(props.violenceLaborStatistics.labels || {}).length > 0
    ? props.violenceLaborStatistics.labels
    : props.analysisData.labels;
});

const violenceColors = computed(() => {
  return Object.keys(props.violenceLaborStatistics.colors || {}).length > 0
    ? props.violenceLaborStatistics.colors
    : props.analysisData.colors;
});

const filteredViolenceParticipants = computed(() => {
  const participants = props.violenceLaborStatistics.participants ?? [];

  return participants.filter((participant) => {
    if (analysisFilters.value.genero && participant.demographics.genero !== analysisFilters.value.genero) {
      return false;
    }
    if (analysisFilters.value.puesto && participant.demographics.puesto !== analysisFilters.value.puesto) {
      return false;
    }
    if (analysisFilters.value.area && participant.demographics.area !== analysisFilters.value.area) {
      return false;
    }
    if (analysisFilters.value.turno && participant.demographics.turno !== analysisFilters.value.turno) {
      return false;
    }

    return true;
  });
});

const filteredViolenceDistribution = computed(() => {
  const distribution: Record<string, number> = {
    nulo: 0,
    bajo: 0,
    medio: 0,
    alto: 0,
    muy_alto: 0,
  };

  filteredViolenceParticipants.value.forEach((participant) => {
    if (Object.prototype.hasOwnProperty.call(distribution, participant.risk_level)) {
      distribution[participant.risk_level]++;
    }
  });

  return distribution;
});

const filteredViolenceVeryHighCount = computed(() => {
  return filteredViolenceDistribution.value.muy_alto ?? 0;
});

const filteredViolenceQuestions = computed(() => {
  const baseQuestions = props.violenceLaborStatistics.questions ?? [];

  return baseQuestions.map((question) => {
    const distribution: Record<string, number> = {
      nulo: 0,
      bajo: 0,
      medio: 0,
      alto: 0,
      muy_alto: 0,
    };

    filteredViolenceParticipants.value.forEach((participant) => {
      const level = participant.question_levels?.[String(question.number)];
      if (level && Object.prototype.hasOwnProperty.call(distribution, level)) {
        distribution[level]++;
      }
    });

    return {
      ...question,
      distribution,
      total_responses: Object.values(distribution).reduce((sum, current) => sum + current, 0),
      high_risk_total: (distribution.alto ?? 0) + (distribution.muy_alto ?? 0),
    };
  });
});

const groupedGeneralReport = computed(() => {
  type GroupedDimension = { nombre: string; score: number; nivel_riesgo: string; rowspan: number; items: Array<{ nombre: string; item_numero: number; puntaje: number }> };
  type GroupedDomain = { nombre: string; score: number; nivel_riesgo: string; rowspan: number; dimensiones: GroupedDimension[] };
  type GroupedCategory = { nombre: string; score: number; nivel_riesgo: string; rowspan: number; dominios: GroupedDomain[] };

  const grouped: GroupedCategory[] = [];
  const rows = props.generalReport?.rows ?? [];

  const toSafeNumber = (value: unknown): number => {
    const numericValue = Number(value);
    return Number.isFinite(numericValue) ? numericValue : 0;
  };

  const normalizeRisk = (value: unknown): string => {
    return typeof value === 'string' && value.trim() !== '' ? value : 'nulo';
  };

  if (typeof window !== 'undefined' && /localhost|127\.0\.0\.1/.test(window.location.hostname) && rows.length > 0) {
    console.log(rows[0]);
  }

  rows.forEach((row) => {
    const normalizedCategory = {
      nombre: row.categoria?.nombre ?? 'Sin categoría',
      score: Math.max(0, Math.round(toSafeNumber(row.categoria?.score))),
      nivel_riesgo: normalizeRisk(row.categoria?.nivel_riesgo),
    };

    const normalizedDomain = {
      nombre: row.dominio?.nombre ?? 'Sin dominio',
      score: Math.max(0, Math.round(toSafeNumber(row.dominio?.score))),
      nivel_riesgo: normalizeRisk(row.dominio?.nivel_riesgo),
    };

    const dimensionPayload = typeof row.dimension === 'string'
      ? { nombre: row.dimension, score: 0, nivel_riesgo: 'nulo' }
      : {
          nombre: row.dimension?.nombre ?? 'Sin dimensión',
          score: Math.max(0, Math.round(toSafeNumber(row.dimension?.score))),
          nivel_riesgo: normalizeRisk(row.dimension?.nivel_riesgo),
        };

    const itemAverage = toSafeNumber(row.puntaje);

    if (typeof window !== 'undefined' && /localhost|127\.0\.0\.1/.test(window.location.hostname)) {
      console.log({
        dimension: row.dimension,
        score: row.dimension?.score,
      });
    }

    let category = grouped.find((item) => item.nombre === normalizedCategory.nombre);
    if (!category) {
      category = {
        nombre: normalizedCategory.nombre,
        score: normalizedCategory.score,
        nivel_riesgo: normalizedCategory.nivel_riesgo,
        rowspan: 0,
        dominios: [],
      };
      grouped.push(category);
    } else {
      category.score = Math.max(category.score, normalizedCategory.score);
      if (category.nivel_riesgo === 'nulo' && normalizedCategory.nivel_riesgo !== 'nulo') {
        category.nivel_riesgo = normalizedCategory.nivel_riesgo;
      }
    }

    let domain = category.dominios.find((item) => item.nombre === normalizedDomain.nombre);
    if (!domain) {
      domain = {
        nombre: normalizedDomain.nombre,
        score: normalizedDomain.score,
        nivel_riesgo: normalizedDomain.nivel_riesgo,
        rowspan: 0,
        dimensiones: [],
      };
      category.dominios.push(domain);
    } else {
      domain.score = Math.max(domain.score, normalizedDomain.score);
      if (domain.nivel_riesgo === 'nulo' && normalizedDomain.nivel_riesgo !== 'nulo') {
        domain.nivel_riesgo = normalizedDomain.nivel_riesgo;
      }
    }

    let dimension = domain.dimensiones.find((item) => item.nombre === dimensionPayload.nombre);
    if (!dimension) {
      dimension = {
        nombre: dimensionPayload.nombre,
        score: dimensionPayload.score,
        nivel_riesgo: dimensionPayload.nivel_riesgo,
        rowspan: 0,
        items: [],
      };
      domain.dimensiones.push(dimension);
    } else {
      dimension.score = Math.max(dimension.score, dimensionPayload.score);
      if (dimension.nivel_riesgo === 'nulo' && dimensionPayload.nivel_riesgo !== 'nulo') {
        dimension.nivel_riesgo = dimensionPayload.nivel_riesgo;
      }
    }

    dimension.items.push({
      nombre: row.item,
      item_numero: row.item_numero,
      puntaje: itemAverage,
    });
  });

  grouped.forEach((category) => {
    category.rowspan = 0;
    category.dominios.forEach((domain) => {
      domain.rowspan = 0;
      domain.dimensiones.forEach((dimension) => {
        dimension.rowspan = dimension.items.length;
        domain.rowspan += dimension.rowspan;
      });
      category.rowspan += domain.rowspan;
    });
  });

  return grouped;
});

// Filtered evaluations based on demographic filters
const filteredEvaluations = computed(() => {
  if (!props.analysisData) return [];
  
  return props.analysisData.evaluations.filter(evaluation => {
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

// Recalculate distribution based on filtered evaluations
const filteredDistribution = computed(() => {
  const distribution: Record<string, number> = {
    nulo: 0,
    bajo: 0,
    medio: 0,
    alto: 0,
    muy_alto: 0,
  };

  if (analysisViewMode.value === 'domains') {
    // If a specific domain is selected, show only that domain's distribution
    if (selectedDomain.value) {
      filteredEvaluations.value.forEach(evaluation => {
        if (evaluation.domain_scores && evaluation.domain_scores[selectedDomain.value]) {
          const riskLevel = evaluation.domain_scores[selectedDomain.value].risk_level;
          distribution[riskLevel]++;
        }
      });
    } else {
      // Count evaluations by their highest risk level across all domains
      filteredEvaluations.value.forEach(evaluation => {
        const riskLevels = Object.values(evaluation.domain_scores).map((score: any) => score.risk_level);
        const highestRisk = getHighestRiskLevel(riskLevels);
        distribution[highestRisk]++;
      });
    }
  } else {
    // If a specific category is selected, show only that category's distribution
    if (selectedCategory.value) {
      filteredEvaluations.value.forEach(evaluation => {
        if (evaluation.category_scores && evaluation.category_scores[selectedCategory.value]) {
          const riskLevel = evaluation.category_scores[selectedCategory.value].risk_level;
          distribution[riskLevel]++;
        }
      });
    } else {
      // Count evaluations by their highest risk level across all categories
      filteredEvaluations.value.forEach(evaluation => {
        const riskLevels = Object.values(evaluation.category_scores).map((score: any) => score.risk_level);
        const highestRisk = getHighestRiskLevel(riskLevels);
        distribution[highestRisk]++;
      });
    }
  }

  return distribution;
});

// Calculate risk distribution for each individual domain
const perDomainDistributions = computed(() => {
  const distributions: Record<string, Record<string, number>> = {};
  
  // Initialize distribution structure for each domain
  const initializeDistribution = () => ({
    nulo: 0,
    bajo: 0,
    medio: 0,
    alto: 0,
    muy_alto: 0,
  });
  
  // Count risk levels per domain from filtered evaluations
  filteredEvaluations.value.forEach(evaluation => {
    if (evaluation.domain_scores) {
      Object.entries(evaluation.domain_scores).forEach(([domainName, scoreData]: [string, any]) => {
        if (!distributions[domainName]) {
          distributions[domainName] = initializeDistribution();
        }
        distributions[domainName][scoreData.risk_level]++;
      });
    }
  });
  
  return distributions;
});

// Calculate risk distribution for each individual category
const perCategoryDistributions = computed(() => {
  const distributions: Record<string, Record<string, number>> = {};
  
  // Initialize distribution structure for each category
  const initializeDistribution = () => ({
    nulo: 0,
    bajo: 0,
    medio: 0,
    alto: 0,
    muy_alto: 0,
  });
  
  // Count risk levels per category from filtered evaluations
  filteredEvaluations.value.forEach(evaluation => {
    if (evaluation.category_scores) {
      Object.entries(evaluation.category_scores).forEach(([categoryName, scoreData]: [string, any]) => {
        if (!distributions[categoryName]) {
          distributions[categoryName] = initializeDistribution();
        }
        distributions[categoryName][scoreData.risk_level]++;
      });
    }
  });
  
  return distributions;
});

// Sort domains by highest risk detected (muy_alto first, then alto, etc.)
const sortedDomainsByRisk = computed(() => {
  const hierarchy = ['muy_alto', 'alto', 'medio', 'bajo', 'nulo'];
  
  return Object.entries(perDomainDistributions.value)
    .map(([domainName, distribution]) => {
      // Calculate a risk score for sorting
      let riskScore = 0;
      hierarchy.forEach((level, index) => {
        riskScore += distribution[level] * (hierarchy.length - index);
      });
      
      return { domainName, distribution, riskScore };
    })
    .sort((a, b) => b.riskScore - a.riskScore);
});

// Sort categories by highest risk detected (muy_alto first, then alto, etc.)
const sortedCategoriesByRisk = computed(() => {
  const hierarchy = ['muy_alto', 'alto', 'medio', 'bajo', 'nulo'];
  
  return Object.entries(perCategoryDistributions.value)
    .map(([categoryName, distribution]) => {
      // Calculate a risk score for sorting
      let riskScore = 0;
      hierarchy.forEach((level, index) => {
        riskScore += distribution[level] * (hierarchy.length - index);
      });
      
      return { categoryName, distribution, riskScore };
    })
    .sort((a, b) => b.riskScore - a.riskScore);
});

// Helper function to get the highest risk level from an array
const getHighestRiskLevel = (levels: string[]): string => {
  const hierarchy = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];
  let maxIndex = 0;
  
  levels.forEach(level => {
    const index = hierarchy.indexOf(level);
    if (index > maxIndex) {
      maxIndex = index;
    }
  });
  
  return hierarchy[maxIndex];
};

// Function to show risk details modal
const showRiskDetailsModal = (level: string) => {
  selectedRiskLevel.value = level;
  
  // Get all evaluations that match the selected risk level
  const matchingPersonal: any[] = [];
  
  filteredEvaluations.value.forEach(evaluation => {
    let evaluationRiskLevel: string;
    let score = 0;
    
    if (analysisViewMode.value === 'domains') {
      // If a specific domain is selected, check only that domain
      if (selectedDomain.value) {
        if (evaluation.domain_scores && evaluation.domain_scores[selectedDomain.value]) {
          evaluationRiskLevel = evaluation.domain_scores[selectedDomain.value].risk_level;
          score = evaluation.domain_scores[selectedDomain.value].score;
        } else {
          return; // Skip if domain not found
        }
      } else {
        // Global: check highest risk across all domains
        const riskLevels = Object.values(evaluation.domain_scores).map((score: any) => score.risk_level);
        evaluationRiskLevel = getHighestRiskLevel(riskLevels);
        const scores = Object.values(evaluation.domain_scores).map((s: any) => s.score);
        score = Math.max(...scores);
      }
    } else {
      // If a specific category is selected, check only that category
      if (selectedCategory.value) {
        if (evaluation.category_scores && evaluation.category_scores[selectedCategory.value]) {
          evaluationRiskLevel = evaluation.category_scores[selectedCategory.value].risk_level;
          score = evaluation.category_scores[selectedCategory.value].score;
        } else {
          return; // Skip if category not found
        }
      } else {
        // Global: check highest risk across all categories
        const riskLevels = Object.values(evaluation.category_scores).map((score: any) => score.risk_level);
        evaluationRiskLevel = getHighestRiskLevel(riskLevels);
        const scores = Object.values(evaluation.category_scores).map((s: any) => s.score);
        score = Math.max(...scores);
      }
    }
    
    if (evaluationRiskLevel === level) {
      matchingPersonal.push({
        personal_folio: evaluation.personal_folio,
        score: score,
      });
    }
  });
  
  filteredRiskPersonal.value = matchingPersonal;
  showRiskModal.value = true;
};

const showViolenceRiskDetailsModal = (level: string): void => {
  selectedRiskLevel.value = level;

  filteredRiskPersonal.value = filteredViolenceParticipants.value
    .filter((participant) => participant.risk_level === level)
    .map((participant) => ({
      personal_folio: participant.personal_folio,
      score: participant.violence_score,
    }));

  showRiskModal.value = true;
};

const openViolenceAnalysisView = (): void => {
  activeSubTab.value = 'analizar';
  analysisViewMode.value = 'violencia';
  chartType.value = 'pie';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openParticipantsWithViolenceRisk = (): void => {
  activeSubTab.value = 'participantes';
  participantsViolenceFilter.value = 'muy_alto';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Function to close risk details modal
const closeRiskModal = () => {
  showRiskModal.value = false;
  selectedRiskLevel.value = '';
  filteredRiskPersonal.value = [];
};

// Computed property for participants with their scores
const participantsWithScores = computed(() => {
  if (!props.analysisData || props.analysisData.evaluations.length === 0) {
    return [];
  }

  const violenceByFolio = new Map(
    (props.violenceLaborStatistics.participants ?? []).map((participant) => [participant.personal_folio, participant.risk_level])
  );

  const participants = props.analysisData.evaluations.map((evaluation: any) => {
    // Use the total_score from backend calculation
    const score = evaluation.total_score ?? 0;

    // Keep risk level consistent with displayed total score in participants tab
    const riskLevel = getTotalScoreRiskLevel(score);

    return {
      personal_folio: evaluation.personal_folio,
      score: score,
      risk_level: riskLevel,
      violence_risk_level: violenceByFolio.get(evaluation.personal_folio) ?? 'nulo',
    };
  });

  return participants;
});

const filteredParticipants = computed(() => {
  const riskWeight: Record<string, number> = {
    nulo: 0,
    bajo: 1,
    medio: 2,
    alto: 3,
    muy_alto: 4,
  };

  let participants = [...participantsWithScores.value];

  if (participantsViolenceFilter.value) {
    participants = participants.filter((participant: any) => {
      if (participantsViolenceFilter.value === 'muy_alto') {
        return participant.violence_risk_level === 'muy_alto';
      }

      return true;
    });
  }

  if (participantsRiskFilter.value) {
    participants = participants.filter((participant) => participant.risk_level === participantsRiskFilter.value);
  }

  if (participantsSortBy.value === 'risk') {
    participants.sort((a, b) => {
      const left = riskWeight[a.risk_level] ?? -1;
      const right = riskWeight[b.risk_level] ?? -1;

      if (right !== left) {
        return right - left;
      }

      return b.score - a.score;
    });

    return participants;
  }

  participants.sort((a, b) => Number(a.personal_folio) - Number(b.personal_folio));

  return participants;
});

// Function to get score color class - aligned with nom035_risk_levels.php config
const getScoreClass = (score: number): string => {
  if (score <= 49) return 'bg-blue-500';        // Nulo
  if (score <= 74) return 'bg-green-500';       // Bajo
  if (score <= 98) return 'bg-amber-500';       // Medio
  if (score <= 139) return 'bg-orange-500';     // Alto
  return 'bg-red-500';                          // Muy Alto
};

const getTotalScoreRiskLevel = (score: number): string => {
  if (score <= 49) return 'nulo';
  if (score <= 74) return 'bajo';
  if (score <= 98) return 'medio';
  if (score <= 139) return 'alto';
  return 'muy_alto';
};

// Map risk level to appropriate color class
const getRiskLevelColorClass = (level: string): string => {
  const classMap: Record<string, string> = {
    'nulo': 'text-blue-700 font-semibold',
    'bajo': 'text-green-700 font-semibold',
    'medio': 'text-amber-700 font-semibold',
    'alto': 'text-orange-700 font-semibold',
    'muy_alto': 'text-red-700 font-semibold',
  };
  return classMap[level.toLowerCase()] || 'font-semibold';
};

const getRiskLevelLabel = (level: string): string => {
  const labels: Record<string, string> = {
    nulo: 'Nulo',
    bajo: 'Bajo',
    medio: 'Medio',
    alto: 'Alto',
    muy_alto: 'Muy Alto',
  };

  return labels[level] ?? level;
};

const getRiskLevelPillClass = (level: string): string => {
  const classes: Record<string, string> = {
    nulo: 'bg-blue-100 text-blue-700',
    bajo: 'bg-green-100 text-green-700',
    medio: 'bg-amber-100 text-amber-700',
    alto: 'bg-orange-100 text-orange-700',
    muy_alto: 'bg-red-100 text-red-700',
  };

  return classes[level] ?? 'bg-slate-100 text-slate-700';
};

const formatScore = (score: number): string => {
  const numericScore = Number(score);
  return Number.isFinite(numericScore) ? numericScore.toFixed(2) : '0.00';
};

const formatIntegerScore = (score: number): string => {
  const numericScore = Number(score);
  return Number.isFinite(numericScore) ? String(Math.max(0, Math.round(numericScore))) : '0';
};

const totalEvaluationsForTooltips = computed(() => {
  const totalEvaluations = Number(props.generalReport?.total_evaluations ?? 0);
  return Number.isFinite(totalEvaluations) ? Math.max(0, totalEvaluations) : 0;
});

const globalLevelsByRisk = computed<Record<string, { min: number; max: number }>>(() => {
  const globalPayload = props.globalStatistics?.global as Record<string, unknown> | undefined;
  const levels = globalPayload?.levels;

  if (!levels || typeof levels !== 'object') {
    return {};
  }

  return levels as Record<string, { min: number; max: number }>;
});

const finalGlobalSummary = computed(() => {
  const totalEvaluations = Number(props.generalReport?.total_evaluations ?? 0);
  const normalizedEvaluations = Number.isFinite(totalEvaluations) ? Math.max(0, totalEvaluations) : 0;

  const fallbackAverage = normalizedEvaluations > 0
    ? Number(props.generalReport?.total_score ?? 0) / normalizedEvaluations
    : 0;

  const averageScore = Number(props.generalReport?.final_average_score ?? fallbackAverage);

  const maxScoreFromConfig = Number((props.globalStatistics?.global as Record<string, unknown> | undefined)?.max_score ?? 288);
  const maxScore = Number(props.generalReport?.final_max_score ?? maxScoreFromConfig);

  const percentageFallback = maxScore > 0 ? (averageScore / maxScore) * 100 : 0;
  const percentage = Number(props.generalReport?.final_percentage ?? percentageFallback);

  const riskLevelRaw = props.generalReport?.final_risk_level;
  const riskLevel = normalizeRiskLevel(typeof riskLevelRaw === 'string' ? riskLevelRaw : getTotalScoreRiskLevel(averageScore));

  const riskLabel = props.generalReport?.final_risk_label ?? getRiskLevelLabel(riskLevel);

  return {
    averageScore: Number.isFinite(averageScore) ? averageScore : 0,
    maxScore: Number.isFinite(maxScore) ? maxScore : 288,
    percentage: Number.isFinite(percentage) ? percentage : 0,
    riskLevel,
    riskLabel,
    totalEvaluations: normalizedEvaluations,
  };
});

const getGlobalRiskRangeText = (riskLevel: string): string => {
  const normalizedRiskLevel = normalizeRiskLevel(riskLevel);
  const range = globalLevelsByRisk.value[normalizedRiskLevel];

  if (!range) {
    return 'N/A';
  }

  return `${range.min} - ${range.max}`;
};

const getAverageByEvaluations = (totalScore: number): string => {
  const evaluations = totalEvaluationsForTooltips.value;
  if (evaluations <= 0) {
    return '0';
  }

  return formatIntegerScore(totalScore / evaluations);
};

const normalizeRiskLevel = (riskLevel?: string | null): string => {
  if (!riskLevel || typeof riskLevel !== 'string') {
    return 'nulo';
  }

  return riskLevel.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').replace(/\s+/g, '_');
};

const nomRiskColors: Record<string, string> = {
  nulo: '#3B82F6',
  bajo: '#10B981',
  medio: '#F59E0B',
  alto: '#F97316',
  muy_alto: '#EF4444',
};

const getRiskColorHex = (riskLevel?: string | null): string => {
  const normalizedRiskLevel = normalizeRiskLevel(riskLevel);
  return nomRiskColors[normalizedRiskLevel] ?? '#64748B';
};

const hexToRgba = (hex: string, alpha: number): string => {
  const normalizedHex = hex.replace('#', '');
  const validHex = normalizedHex.length === 3
    ? normalizedHex.split('').map((char) => char + char).join('')
    : normalizedHex;

  const red = parseInt(validHex.substring(0, 2), 16);
  const green = parseInt(validHex.substring(2, 4), 16);
  const blue = parseInt(validHex.substring(4, 6), 16);

  if (Number.isNaN(red) || Number.isNaN(green) || Number.isNaN(blue)) {
    return `rgba(100, 116, 139, ${alpha})`;
  }

  return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

const getRiskForegroundColor = (riskLevel?: string | null): string => {
  const normalizedRiskLevel = normalizeRiskLevel(riskLevel);

  if (normalizedRiskLevel === 'medio') {
    return '#111827';
  }

  return '#FFFFFF';
};

const getRiskBadgeSolidStyle = (riskLevel?: string | null): Record<string, string> => {
  const color = getRiskColorHex(riskLevel);

  return {
    backgroundColor: color,
    borderColor: color,
    color: getRiskForegroundColor(riskLevel),
  };
};

const getRiskContainerStyle = (riskLevel?: string | null): Record<string, string> => {
  const color = getRiskColorHex(riskLevel);

  return {
    backgroundColor: '#FFFFFF',
    borderColor: '#CBD5E1',
    borderLeftWidth: '5px',
    borderLeftColor: color,
  };
};

const getItemAverageRiskLevel = (score: number): string => {
  if (score <= 0.8) {
    return 'nulo';
  }

  if (score <= 1.6) {
    return 'bajo';
  }

  if (score <= 2.4) {
    return 'medio';
  }

  if (score <= 3.2) {
    return 'alto';
  }

  return 'muy_alto';
};

const getItemTotalScore = (average: number): number => {
  return average * totalEvaluationsForTooltips.value;
};

const subTabs = [
  { key: 'identificar', label: 'Identificar', icon: MagnifyingGlassIcon },
  { key: 'analizar', label: 'Analizar', icon: ChartBarIcon },
  { key: 'participantes', label: 'Participantes', icon: UserGroupIcon },
  { key: 'prevenir', label: 'Prevenir', icon: ShieldCheckIcon },
];
</script>