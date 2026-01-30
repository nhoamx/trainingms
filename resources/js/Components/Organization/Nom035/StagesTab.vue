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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                  </div>
                  <div class="ml-auto text-sm text-slate-600">
                    <span class="font-semibold">{{ filteredEvaluations.length }}</span> evaluaciones filtradas
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
                <div class="flex items-center gap-4">
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
                      Pastel
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

            <!-- Distribución y Gráfica -->
            <div class="bg-white rounded-lg p-6 border border-slate-200">
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
                <span class="font-bold text-teal-600 ml-2">{{ participantsWithScores.length }}</span>
              </div>
            </div>

            <!-- Lista de participantes -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
              <div v-if="participantsWithScores.length === 0" class="p-6 text-center text-slate-500">
                No se encontraron datos de participantes
              </div>

              <ul v-else class="divide-y divide-slate-200">
                <li
                  v-for="(participant, index) in participantsWithScores"
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
          <div class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-center p-8 border-2 border-dashed border-emerald-300 rounded-lg">
              <div class="text-center">
                <Cog6ToothIcon class="w-12 h-12 text-emerald-400 mx-auto mb-3 animate-spin" />
                <p class="text-emerald-700 font-medium">En desarrollo</p>
                <p class="text-sm text-emerald-600 mt-1">Acciones preventivas y planes de mejora continua</p>
              </div>
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
import { Link } from '@inertiajs/vue3';
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

interface Props {
  domainStatistics?: DomainStatistics;
  categoryStatistics?: CategoryStatistics;
  dimensionStatistics?: DimensionStatistics;
  questionStatistics?: QuestionStatistics;
  blockStatistics?: BlockStatistics;
  globalStatistics?: GlobalStatistics;
  analysisData?: AnalysisData;
  organizationId?: string | number;
}

const props = withDefaults(defineProps<Props>(), {
  domainStatistics: () => ({ domains: {}, total_evaluations: 0, colors: {}, labels: {} }),
  categoryStatistics: () => ({ categories: {}, total_evaluations: 0, colors: {}, labels: {} }),
  dimensionStatistics: () => ({ dimensions: {}, total_evaluations: 0, colors: {}, labels: {} }),
  globalStatistics: () => ({ global: {}, total_evaluations: 0, colors: {}, labels: {} }),
  analysisData: () => ({ evaluations: [], demographics: { generos: [], puestos: [], areas: [], turnos: [] }, colors: {}, labels: {} }),
  organizationId: () => '',
});

const activeSubTab = ref('identificar');

// Identificar state
const identificarViewMode = ref<'global' | 'domains' | 'categories' | 'dimensions' | 'questions' | 'blocks'>('global');

// Analysis state
const analysisViewMode = ref<'domains' | 'categories'>('domains');
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

  const participants = props.analysisData.evaluations.map((evaluation: any) => {
    // Use the total_score from backend calculation
    const score = evaluation.total_score ?? 0;
    
    // Get highest risk level across all domains
    const riskLevels = Object.values(evaluation.domain_scores).map((s: any) => s.risk_level);
    const riskLevel = getHighestRiskLevel(riskLevels);

    return {
      personal_folio: evaluation.personal_folio,
      score: score,
      risk_level: riskLevel,
    };
  });

  // Sort by score in descending order (highest to lowest)
  return participants.sort((a, b) => b.score - a.score);
});

// Function to get score color class - aligned with nom035_risk_levels.php config
const getScoreClass = (score: number): string => {
  if (score <= 49) return 'bg-blue-500';        // Nulo
  if (score <= 74) return 'bg-green-500';       // Bajo
  if (score <= 98) return 'bg-amber-500';       // Medio
  if (score <= 139) return 'bg-orange-500';     // Alto
  return 'bg-red-500';                          // Muy Alto
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

const subTabs = [
  { key: 'identificar', label: 'Identificar', icon: MagnifyingGlassIcon },
  { key: 'analizar', label: 'Analizar', icon: ChartBarIcon },
  { key: 'participantes', label: 'Participantes', icon: UserGroupIcon },
  { key: 'prevenir', label: 'Prevenir', icon: ShieldCheckIcon },
];
</script>