<template>
  <Dashboard :title="title || 'Reporte Clima Laboral'">
    <div class="max-w-7xl mx-auto p-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">
              Reporte Clima Laboral - {{ organizationName }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
              {{ evaluations.length }} evaluaciones completadas
            </p>
          </div>
          <div v-if="evaluations.length > 0 && (isAdmin || isSuperAdmin)" class="flex items-center gap-3">
            <button
              @click="downloadWordReport"
              :disabled="isDownloading"
              class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg v-if="isDownloading" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {{ isDownloading ? 'Generando...' : 'Descargar Word' }}
            </button>
          </div>
        </div>
        <!-- Download Status Message -->
        <div v-if="downloadMessage" class="mt-4 p-3 rounded-lg" :class="downloadMessageClass">
          {{ downloadMessage }}
        </div>
      </div>

      <div v-if="evaluations.length === 0" class="bg-white rounded-lg shadow p-8">
        <div class="text-center text-gray-500">
          <p class="text-lg">No hay evaluaciones Clima Laboral completadas para esta organización.</p>
        </div>
      </div>

      <div v-else>
        <!-- Filtros Demográficos -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Género -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
              <select 
                v-model="filters.genero"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="g in demographics.generos" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>

            <!-- Tipo de Contrato -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato</label>
              <select 
                v-model="filters.tipo_contrato"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="tc in demographics.tipos_contrato" :key="tc" :value="tc">{{ tc }}</option>
              </select>
            </div>

            <!-- Puesto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Puesto</label>
              <select 
                v-model="filters.puesto"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="p in demographics.puestos" :key="p" :value="p">{{ getPuestoName(p) }}</option>
              </select>
            </div>

            <!-- Área -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
              <select 
                v-model="filters.area"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todas</option>
                <option v-for="a in demographics.areas" :key="a" :value="a">{{ getAreaName(a) }}</option>
              </select>
            </div>

            <!-- Turno -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
              <select 
                v-model="filters.turno"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="t in demographics.turnos" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>

            <!-- Factor (for comments) -->
            <div v-if="factors.length > 0">
              <label class="block text-sm font-medium text-gray-700 mb-2">Factor (Comentarios)</label>
              <select 
                v-model="filters.factor"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Todos</option>
                <option v-for="f in factors" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
          </div>

          <!-- Custom Field Filters -->
          <div v-if="Object.keys(customFieldFilters).length > 0" class="mt-4 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Campos Adicionales</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div v-for="(fieldData, fieldKey) in customFieldFilters" :key="fieldKey">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ fieldData.label }}</label>
                <select 
                  v-model="customFilters[fieldKey]"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">Todos</option>
                  <option v-for="val in fieldData.values" :key="val" :value="val">{{ val }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Reset Filters Button -->
          <div class="mt-4">
            <button 
              @click="resetFilters"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm"
            >
              Limpiar Filtros
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
          <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px">
              <button
                @click="activeTab = 'Total'"
                :class="[
                  'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                  activeTab === 'Total'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Total
              </button>
              <button
                v-for="dimensionName in Object.keys(dimensions)"
                :key="dimensionName"
                @click="activeTab = dimensionName"
                :class="[
                  'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                  activeTab === dimensionName
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                {{ dimensionName }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Total Tab -->
            <div v-if="activeTab === 'Total'">
              <!-- Clima Laboral -->
              <div class="mb-6 rounded-lg p-6" :class="getLevelColor(getMostCommonInterpretation).bgSolid">
                <h3 class="text-lg font-semibold mb-2" :class="getLevelColor(getMostCommonInterpretation).text">Clima Laboral</h3>
                <div class="flex items-baseline gap-3">
                  <span class="text-3xl font-bold" :class="getLevelColor(getMostCommonInterpretation).text">{{ getMostCommonInterpretation }}</span>
                  <span class="text-lg opacity-90" :class="getLevelColor(getMostCommonInterpretation).text">/ {{ filteredTotalPeople }} {{ filteredTotalPeople === 1 ? 'persona' : 'personas' }}</span>
                </div>
                <div class="text-sm mt-2 opacity-90" :class="getLevelColor(getMostCommonInterpretation).text">
                  Nivel más frecuente en la organización
                </div>
              </div>

              <!-- Distribución + Chart en 2 columnas (Total) -->
              <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                  <!-- Bloques de niveles (izquierda) -->
                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                      <div>
                        <h4 class="text-md font-semibold text-gray-900">Distribución por nivel</h4>
                        <div class="text-xs text-gray-500">{{ filteredTotalPeople }} {{ filteredTotalPeople === 1 ? 'persona' : 'personas' }}</div>
                      </div>
                    </div>
                    <div class="flex flex-col gap-3">
                      <button
                        v-for="(count, level) in filteredClimaLaboralDistribution"
                        :key="level"
                        type="button"
                        class="text-left px-4 py-3 rounded-lg shadow-sm transition-transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="getLevelColor(level).bgSolid + ' ' + getLevelColor(level).text"
                        @click="openFoliosModal(`Folios en ${level} (Clima Laboral)`, getFoliosForClimaLevel(level))"
                      >
                        <div class="text-2xl font-bold">{{ count }}</div>
                        <div class="text-xs mt-1">{{ level }}</div>
                      </button>
                    </div>
                  </div>

                  <!-- Gráfica (derecha) -->
                  <div class="bg-gray-50 rounded-lg p-4 md:ml-auto w-full">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Nivel de Satisfacción</h4>
                    <canvas ref="pieChartTotal"></canvas>
                  </div>
                </div>
              </div>

              <!-- Lista de Dimensiones con Distribución de Personas -->
              <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Distribución de Personas por Dimensión</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div 
                    v-for="(dim, dimName) in filteredDimensions" 
                    :key="dimName"
                    class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500"
                  >
                    <div class="mb-3">
                      <span class="font-medium text-gray-900">{{ dimName }}</span>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ dim.questionCount }} preguntas
                      </div>
                    </div>
                    <div class="space-y-2 text-sm">
                      <div v-for="(count, level) in dim.distribution" :key="level" class="flex items-center justify-between gap-2">
                        <span class="px-2 py-1 rounded text-xs font-medium flex-shrink-0" :class="getLevelColor(level).badge">
                          {{ level }}
                        </span>
                        <span class="font-medium text-gray-900">{{ count }} {{ count === 1 ? 'persona' : 'personas' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sub-tabs for Total: Mapa de Calor / Comentarios -->
              <div class="mb-4 border-b border-gray-200">
                <nav class="flex -mb-px">
                  <button
                    @click="totalSubTab = 'mapa'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                      totalSubTab === 'mapa'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Mapa de Calor
                  </button>
                  <button
                    @click="totalSubTab = 'comentarios'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                      totalSubTab === 'comentarios'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Comentarios
                  </button>
                </nav>
              </div>

              <!-- Mapa de Calor Sub-tab -->
              <div v-if="totalSubTab === 'mapa'">
              <!-- Mapa de Calor - Todas las Preguntas -->
              <div>
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-md font-semibold text-gray-900">Mapa de Calor - Todas las Preguntas</h4>
                  <p class="text-xs text-gray-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Clic en el número de pregunta para ordenar
                  </p>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                      <!-- Dimension headers row -->
                      <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-100 z-10">
                          Folio
                        </th>
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 text-center bg-gray-100">
                          Clima Laboral
                        </th>
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`dim-header-${dimName}`">
                          <th 
                            :colspan="dim.questionCount" 
                            class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 text-center"
                          >
                            {{ dimName }}
                          </th>
                        </template>
                      </tr>
                      <!-- Question numbers row -->
                      <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10">
                          #
                        </th>
                        <th 
                          class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 text-center bg-gray-50 cursor-pointer hover:bg-gray-200 select-none"
                          @click="toggleSort('clima_laboral')"
                          title="Ordenar por Clima Laboral"
                        >
                          <div class="flex items-center justify-center gap-1">
                            Total
                            <span v-if="sortColumn === 'clima_laboral'" class="text-blue-600">
                              {{ sortDirection === 'desc' ? '▼' : '▲' }}
                            </span>
                          </div>
                        </th>
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`questions-${dimName}`">
                          <th 
                            v-for="qNum in Object.keys(dim.questions)" 
                            :key="`q-header-${qNum}`"
                            class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 text-center cursor-pointer hover:bg-gray-200 select-none"
                            @click="toggleSort(qNum)"
                            :title="`Ordenar por pregunta ${qNum}`"
                          >
                            <div class="flex items-center justify-center gap-1">
                              {{ qNum }}
                              <span v-if="sortColumn === qNum" class="text-blue-600">
                                {{ sortDirection === 'desc' ? '▼' : '▲' }}
                              </span>
                            </div>
                          </th>
                        </template>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="evaluation in sortedFilteredEvaluations" :key="`eval-${evaluation.folio}`">
                        <!-- Personal folio column -->
                        <td class="border border-gray-300 px-2 py-2 text-xs font-semibold sticky left-0 bg-white z-10">
                          <a
                            :href="route('organization.results.likert', { organization: organizationId, personalFolio: evaluation.personal_folio })"
                            target="_blank"
                            class="text-blue-600 hover:text-blue-800 hover:underline"
                          >
                            {{ evaluation.personal_folio }}
                          </a>
                        </td>
                        <!-- Clima Laboral (Total Score) column -->
                        <td 
                          class="border border-gray-300 px-2 py-2 text-center text-xs font-bold"
                          :class="getClimaLaboralColorClass(evaluation.scores?.total_score)"
                        >
                          {{ evaluation.scores?.total_score ?? '-' }}
                        </td>
                        <!-- Answer cells for each question -->
                        <template v-for="(dim, dimName) in filteredDimensions" :key="`eval-dim-${evaluation.folio}-${dimName}`">
                          <td 
                            v-for="qNum in Object.keys(dim.questions)" 
                            :key="`eval-q-${evaluation.folio}-${qNum}`"
                            class="border border-gray-300 px-2 py-2 text-center text-xs font-bold"
                            :class="getAnswerColorClass(evaluation.answers[qNum])"
                          >
                            {{ getAnswerNumericValue(evaluation.answers[qNum]) }}
                          </td>
                        </template>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              </div>

              <!-- Comentarios Sub-tab -->
              <div v-else-if="totalSubTab === 'comentarios'">
                <div v-if="filteredComments.length === 0" class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
                  No hay comentarios disponibles con los filtros actuales.
                </div>
                <div v-else>
                  <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Comentarios por Factor
                    <span class="text-sm font-normal text-gray-600 ml-2">({{ filteredComments.length }} comentarios)</span>
                  </h3>
                  
                  <div class="space-y-4">
                    <div v-for="(commentsGroup, factor) in groupedComments" :key="factor" class="border-l-4 border-blue-500 pl-4">
                      <h4 class="font-medium text-gray-900 mb-2">{{ factor }}</h4>
                      <div class="space-y-2">
                        <div v-for="(comment, index) in commentsGroup" :key="`${factor}-${index}`" class="bg-gray-50 rounded p-3">
                          <p class="text-sm text-gray-700">{{ comment.comment }}</p>
                          <p class="text-xs text-gray-500 mt-1">
                            Folio: {{ comment.folio }} 
                            <span v-if="comment.name" class="ml-2">- {{ comment.name }}</span>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dimension Tabs -->
            <div v-else-if="filteredDimensions[activeTab]">
              <!-- Clima Laboral de la Dimensión (encabezado como en Total) -->
              <div class="mb-6 rounded-lg p-6" :class="getLevelColor(activeDimensionMostCommon).bgSolid">
                <h3 class="text-lg font-semibold mb-2" :class="getLevelColor(activeDimensionMostCommon).text">Clima Laboral</h3>
                <div class="flex items-baseline gap-3">
                  <span class="text-3xl font-bold" :class="getLevelColor(activeDimensionMostCommon).text">{{ activeDimensionMostCommon }}</span>
                  <span class="text-lg opacity-90" :class="getLevelColor(activeDimensionMostCommon).text">/ {{ activeDimensionTotalPeople }} {{ activeDimensionTotalPeople === 1 ? 'persona' : 'personas' }}</span>
                </div>
                <div class="text-sm mt-2 opacity-90" :class="getLevelColor(activeDimensionMostCommon).text">
                  Nivel más frecuente en la dimensión
                </div>
              </div>

              <!-- Distribución + Chart en 2 columnas -->
              <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                  <!-- Bloques de niveles (izquierda) -->
                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                      <div>
                        <h4 class="text-md font-semibold text-gray-900">Distribución por nivel</h4>
                        <div class="text-xs text-gray-500">{{ filteredDimensions[activeTab].questionCount }} preguntas evaluadas</div>
                      </div>
                    </div>
                    <div class="flex flex-col gap-3">
                      <button
                        v-for="(count, level) in filteredDimensions[activeTab].distribution"
                        :key="level"
                        type="button"
                        class="text-left px-4 py-3 rounded-lg shadow-sm transition-transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2"
                        :class="getLevelColor(level).bgSolid + ' ' + getLevelColor(level).text"
                        @click="openFoliosModal(`Folios en ${level} - ${activeTab}`, getFoliosForDimensionLevel(activeTab, level))"
                      >
                        <div class="text-2xl font-bold">{{ count }}</div>
                        <div class="text-xs mt-1">{{ level }}</div>
                      </button>
                    </div>
                  </div>

                  <!-- Gráfica (derecha) -->
                  <div class="bg-gray-50 rounded-lg p-4 md:ml-auto w-full">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Nivel de Satisfacción</h4>
                    <canvas ref="dimensionChartCanvas"></canvas>
                  </div>
                </div>
              </div>

              <!-- Sub-tabs for Dimension: Mapa de Calor / Comentarios -->
              <div class="mb-4 border-b border-gray-200">
                <nav class="flex -mb-px">
                  <button
                    @click="dimensionSubTab = 'mapa'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                      dimensionSubTab === 'mapa'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Mapa de Calor
                  </button>
                  <button
                    @click="dimensionSubTab = 'comentarios'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                      dimensionSubTab === 'comentarios'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Comentarios
                  </button>
                </nav>
              </div>

              <!-- Mapa de Calor Sub-tab for Dimension -->
              <div v-if="dimensionSubTab === 'mapa'">
              <!-- Mapa de Calor para esta Dimensión -->
              <div>
                <div class="flex items-center justify-between mb-4">
                  <h4 class="text-md font-semibold text-gray-900">Mapa de Calor</h4>
                  <p class="text-xs text-gray-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Clic en el número de pregunta para ordenar
                  </p>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                      <!-- Question numbers row -->
                      <tr class="bg-gray-50">
                        <th class="border border-gray-300 px-2 py-2 text-xs font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10">
                          Folio
                        </th>
                        <th 
                          v-for="qNum in Object.keys(filteredDimensions[activeTab]?.questions || {})" 
                          :key="`dim-q-header-${qNum}`"
                          class="border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 text-center cursor-pointer hover:bg-gray-200 select-none"
                          @click="toggleSort(qNum)"
                          :title="`Ordenar por pregunta ${qNum}`"
                        >
                          <div class="flex items-center justify-center gap-1">
                            {{ qNum }}
                            <span v-if="sortColumn === qNum" class="text-blue-600">
                              {{ sortDirection === 'desc' ? '▼' : '▲' }}
                            </span>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="evaluation in sortedFilteredEvaluations" :key="`dim-eval-${evaluation.folio}`">
                        <!-- Personal folio column -->
                        <td class="border border-gray-300 px-2 py-2 text-xs font-semibold sticky left-0 bg-white z-10">
                          <a
                            :href="route('organization.results.likert', { organization: organizationId, personalFolio: evaluation.personal_folio })"
                            target="_blank"
                            class="text-blue-600 hover:text-blue-800 hover:underline"
                          >
                            {{ evaluation.personal_folio }}
                          </a>
                        </td>
                        <!-- Answer cells for this dimension's questions -->
                        <td 
                          v-for="qNum in Object.keys(filteredDimensions[activeTab]?.questions || {})" 
                          :key="`dim-eval-q-${evaluation.folio}-${qNum}`"
                          class="border border-gray-300 px-2 py-2 text-center text-xs font-bold"
                          :class="getAnswerColorClass(evaluation.answers[qNum])"
                        >
                          {{ getAnswerNumericValue(evaluation.answers[qNum]) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              </div>

              <!-- Comentarios Sub-tab for Dimension -->
              <div v-else-if="dimensionSubTab === 'comentarios'">
                <div v-if="filteredComments.length === 0" class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
                  No hay comentarios disponibles con los filtros actuales.
                </div>
                <div v-else>
                  <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Comentarios por Factor
                    <span class="text-sm font-normal text-gray-600 ml-2">({{ filteredComments.length }} comentarios)</span>
                  </h3>
                  
                  <div class="space-y-4">
                    <div v-for="(commentsGroup, factor) in groupedComments" :key="factor" class="border-l-4 border-blue-500 pl-4">
                      <h4 class="font-medium text-gray-900 mb-2">{{ factor }}</h4>
                      <div class="space-y-2">
                        <div v-for="(comment, index) in commentsGroup" :key="`${factor}-${index}`" class="bg-gray-50 rounded p-3">
                          <p class="text-sm text-gray-700">{{ comment.comment }}</p>
                          <p class="text-xs text-gray-500 mt-1">
                            Folio: {{ comment.folio }} 
                            <span v-if="comment.name" class="ml-2">- {{ comment.name }}</span>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
  
  <!-- Modal de Folios por nivel -->
  <div v-if="showFoliosModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click="closeFoliosModal">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-xl max-h-[80vh] flex flex-col" @click.stop>
      <div class="flex items-center justify-between p-4 border-b flex-shrink-0">
        <h3 class="text-lg font-semibold">{{ foliosModalTitle }}</h3>
        <button @click="closeFoliosModal" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div class="p-4 overflow-y-auto flex-1">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="py-2 pr-4">Folio</th>
              <th class="py-2 pr-4">Nombre</th>
              <th class="py-2 pr-4 text-center">Clima Laboral</th>
              <th class="py-2"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="foliosModalItems.length === 0">
              <td colspan="4" class="py-4 text-gray-500">No hay folios para este nivel.</td>
            </tr>
            <tr v-for="item in foliosModalItems" :key="item.folio" class="border-t">
              <td class="py-2 pr-4 font-medium">{{ item.folio }}</td>
              <td class="py-2 pr-4">{{ item.name || 'Sin nombre' }}</td>
              <td class="py-2 pr-4 text-center">
                <span 
                  class="inline-block px-2 py-1 rounded text-xs font-bold"
                  :class="getClimaLaboralColorClass(item.totalScore)"
                >
                  {{ item.totalScore ?? '-' }}
                </span>
              </td>
              <td class="py-2">
                <a
                  :href="route('organization.results.likert', { organization: organizationId, personalFolio: item.folio })"
                  target="_blank"
                  class="inline-flex items-center gap-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Ver datos
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10v10a1 1 0 001 1h10" />
                  </svg>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t flex justify-end flex-shrink-0">
        <button @click="closeFoliosModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue'
import Dashboard from '@/Layouts/Dashboard.vue'
import { Chart, registerables } from 'chart.js'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

Chart.register(...registerables)

const props = defineProps({
  organizationId: {
    type: String,
    required: true,
  },
  organizationName: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: 'Reporte Clima Laboral',
  },
  evaluations: {
    type: Array,
    default: () => [],
  },
  demographics: {
    type: Object,
    default: () => ({
      generos: [],
      tipos_contrato: [],
      puestos: [],
      areas: [],
      turnos: [],
    }),
  },
  dimensions: {
    type: Object,
    default: () => ({}),
  },
  climaLaboralDistribution: {
    type: Object,
    default: () => ({
      'Totalmente de Acuerdo': 0,
      'De Acuerdo': 0,
      'Desacuerdo': 0,
      'Totalmente Desacuerdo': 0,
    }),
  },
  totalPeople: {
    type: Number,
    default: 0,
  },
  puestosMap: {
    type: Object,
    default: () => ({}),
  },
  areasMap: {
    type: Object,
    default: () => ({}),
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
  isSuperAdmin: {
    type: Boolean,
    default: false,
  },
  customFieldFilters: {
    type: Object,
    default: () => ({}),
  },
  factors: {
    type: Array,
    default: () => [],
  },
})

const activeTab = ref('Total')
const totalSubTab = ref('mapa')
const dimensionSubTab = ref('mapa')
const filters = ref({
  genero: '',
  tipo_contrato: '',
  puesto: '',
  area: '',
  turno: '',
  factor: '',
})

// Initialize custom field filters based on available custom fields
const customFilters = ref(
  Object.keys(props.customFieldFilters || {}).reduce((acc, key) => {
    acc[key] = ''
    return acc
  }, {})
)

const pieChartTotal = ref(null)
const dimensionChartCanvas = ref(null)
const chartInstances = ref({}) // keyed instances; we'll use 'Total' and 'Dimension'
const TOTAL_CHART_KEY = 'Total'
const DIMENSION_CHART_KEY = 'Dimension'

// Sorting state for heatmap
const sortColumn = ref(null) // Question number to sort by, or null for default (folio asc)
const sortDirection = ref('desc') // 'asc' or 'desc'

// Word Report Download State
const isDownloading = ref(false)
const downloadMessage = ref('')
const downloadMessageClass = ref('')
let pollingInterval = null

const downloadWordReport = async () => {
  isDownloading.value = true
  downloadMessage.value = 'Iniciando generación del reporte...'
  downloadMessageClass.value = 'bg-blue-100 text-blue-800'

  try {
    const response = await axios.get(`/reportes/word/likert/${props.organizationId}`)
    
    if (response.data.success && response.data.report_id) {
      downloadMessage.value = 'El reporte se está generando. Por favor espere...'
      pollReportStatus(response.data.report_id)
    } else {
      throw new Error(response.data.error || 'Error al iniciar la generación')
    }
  } catch (error) {
    console.error('Error downloading Word report:', error)
    downloadMessage.value = error.response?.data?.error || error.message || 'Error al generar el reporte'
    downloadMessageClass.value = 'bg-red-100 text-red-800'
    isDownloading.value = false
  }
}

const pollReportStatus = (reportId) => {
  pollingInterval = setInterval(async () => {
    try {
      const response = await axios.get(`/reportes/word/status/${reportId}`)
      const { completed, failed, error_message } = response.data

      if (completed) {
        clearInterval(pollingInterval)
        pollingInterval = null
        downloadMessage.value = 'Reporte generado exitosamente. Descargando...'
        downloadMessageClass.value = 'bg-green-100 text-green-800'
        
        // Trigger download
        window.location.href = `/reportes/word/download/${reportId}`
        
        setTimeout(() => {
          isDownloading.value = false
          downloadMessage.value = ''
        }, 3000)
      } else if (failed) {
        clearInterval(pollingInterval)
        pollingInterval = null
        downloadMessage.value = error_message || 'Error al generar el reporte'
        downloadMessageClass.value = 'bg-red-100 text-red-800'
        isDownloading.value = false
      }
    } catch (error) {
      console.error('Error polling report status:', error)
      clearInterval(pollingInterval)
      pollingInterval = null
      downloadMessage.value = 'Error al verificar el estado del reporte'
      downloadMessageClass.value = 'bg-red-100 text-red-800'
      isDownloading.value = false
    }
  }, 2000)
}

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
})

// Helper functions to get names
const getPuestoName = (puestoId) => {
  if (!puestoId) return 'Sin Puesto'
  return props.puestosMap[puestoId] || puestoId
}

const getAreaName = (areaId) => {
  if (!areaId) return 'Sin Área'
  return props.areasMap[areaId] || areaId
}

// Filter evaluations based on demographic filters
const filteredEvaluations = computed(() => {
  return props.evaluations.filter(evaluation => {
    if (filters.value.genero && evaluation.demographics.genero !== filters.value.genero) return false
    if (filters.value.tipo_contrato && evaluation.demographics.tipo_contrato !== filters.value.tipo_contrato) return false
    if (filters.value.puesto && evaluation.demographics.puesto !== filters.value.puesto) return false
    if (filters.value.area && evaluation.demographics.area !== filters.value.area) return false
    if (filters.value.turno && evaluation.demographics.turno !== filters.value.turno) return false
    
    // Factor filter (filter evaluations that have a comment for the selected factor)
    if (filters.value.factor) {
      const hasFactorComment = evaluation.comments?.some(comment => comment.factor === filters.value.factor)
      if (!hasFactorComment) return false
    }
    
    // Custom field filters
    for (const [key, value] of Object.entries(customFilters.value)) {
      if (value && evaluation.customFields?.[key]?.value !== value) return false
    }
    
    return true
  })
})

// Sorted and filtered evaluations for heatmap display
const sortedFilteredEvaluations = computed(() => {
  const evaluations = [...filteredEvaluations.value]
  
  if (sortColumn.value === 'clima_laboral') {
    // Sort by Clima Laboral total score
    evaluations.sort((a, b) => {
      const aVal = a.scores?.total_score ?? 0
      const bVal = b.scores?.total_score ?? 0
      return sortDirection.value === 'desc' ? bVal - aVal : aVal - bVal
    })
  } else if (sortColumn.value !== null) {
    // Sort by specific question answer value
    const valorOpciones = { A: 4, B: 3, C: 2, D: 1 }
    evaluations.sort((a, b) => {
      const aVal = valorOpciones[a.answers[sortColumn.value]] || 0
      const bVal = valorOpciones[b.answers[sortColumn.value]] || 0
      return sortDirection.value === 'desc' ? bVal - aVal : aVal - bVal
    })
  } else {
    // Default sort: by personal_folio ascending (0001 first)
    evaluations.sort((a, b) => {
      const folioA = String(a.personal_folio || '')
      const folioB = String(b.personal_folio || '')
      return folioA.localeCompare(folioB, undefined, { numeric: true })
    })
  }
  
  return evaluations
})

// Get all comments from filtered evaluations
const filteredComments = computed(() => {
  const comments = []
  filteredEvaluations.value.forEach(evaluation => {
    if (evaluation.comments && evaluation.comments.length > 0) {
      evaluation.comments.forEach(comment => {
        comments.push({
          folio: evaluation.personal_folio,
          name: evaluation.evaluee_name,
          factor: comment.factor,
          comment: comment.comment,
        })
      })
    }
  })
  return comments
})

// Group comments by factor
const groupedComments = computed(() => {
  const groups = {}
  filteredComments.value.forEach(comment => {
    if (!groups[comment.factor]) {
      groups[comment.factor] = []
    }
    groups[comment.factor].push(comment)
  })
  return groups
})

// Toggle sort by column
const toggleSort = (questionNumber) => {
  const qNum = String(questionNumber)
  if (sortColumn.value === qNum) {
    // Toggle direction or reset
    if (sortDirection.value === 'desc') {
      sortDirection.value = 'asc'
    } else {
      // Reset to default (folio order)
      sortColumn.value = null
      sortDirection.value = 'desc'
    }
  } else {
    // New column, start with desc (highest first)
    sortColumn.value = qNum
    sortDirection.value = 'desc'
  }
}

// Recompute dimensions distribution based on filtered evaluations
const filteredDimensions = computed(() => {
  if (filteredEvaluations.value.length === 0 || !props.dimensions || Object.keys(props.dimensions).length === 0) {
    return props.dimensions || {}
  }

  const valorOpciones = { A: 4, B: 3, C: 2, D: 1 }
  const dimensionSummaries = {}

  Object.keys(props.dimensions).forEach(dimensionName => {
    const dimension = props.dimensions[dimensionName]
    if (!dimension || !dimension.questions) return

    const questionNumbers = Object.keys(dimension.questions).map(Number)
    const questionScores = {}

    // Distribution of people by level for this dimension (filtered)
    const dimensionDistribution = {
      'Totalmente de Acuerdo': 0,
      'De Acuerdo': 0,
      'Desacuerdo': 0,
      'Totalmente Desacuerdo': 0,
    }

    // Get dimension level ranges from config
    const dimensionRanges = getLevelRanges(dimensionName)

    // Calculate score for each person in this dimension
    filteredEvaluations.value.forEach(evalData => {
      let personScore = 0
      questionNumbers.forEach(qNum => {
        const answer = evalData.answers[qNum]
        if (answer) {
          personScore += valorOpciones[answer] || 0
        }
      })

      // Get interpretation for this person's dimension score
      const interpretation = getScoreInterpretation(personScore, dimensionRanges)
      if (interpretation) {
        dimensionDistribution[interpretation] = (dimensionDistribution[interpretation] || 0) + 1
      }
    })

    // Calculate average scores per question for display
    questionNumbers.forEach(qNum => {
      let qScore = 0
      let qCount = 0
      filteredEvaluations.value.forEach(evalData => {
        const answer = evalData.answers[qNum]
        if (answer) {
          qScore += valorOpciones[answer] || 0
          qCount++
        }
      })
      const avgScore = qCount > 0 ? qScore / qCount : 0
      const origQuestion = dimension.questions[qNum]
      questionScores[qNum] = {
        question: (typeof origQuestion === 'object' ? origQuestion.question : origQuestion) || `Pregunta ${qNum}`,
        score: avgScore,
      }
    })

    dimensionSummaries[dimensionName] = {
      name: dimensionName,
      distribution: dimensionDistribution,
      questionCount: questionNumbers.length,
      questions: questionScores,
    }
  })

  return dimensionSummaries
})

// Calculate Clima Laboral distribution for filtered evaluations
const filteredClimaLaboralDistribution = computed(() => {
  const distribution = {
    'Totalmente de Acuerdo': 0,
    'De Acuerdo': 0,
    'Desacuerdo': 0,
    'Totalmente Desacuerdo': 0,
  }

  filteredEvaluations.value.forEach(evalData => {
    const interpretation = evalData.scores?.interpretation
    if (interpretation) {
      distribution[interpretation] = (distribution[interpretation] || 0) + 1
    }
  })

  return distribution
})

const filteredTotalPeople = computed(() => {
  return filteredEvaluations.value.length
})

// Dimension-level helpers for header (similar to Total)
const activeDimensionDistribution = computed(() => {
  if (activeTab.value === 'Total') return null
  return filteredDimensions.value[activeTab.value]?.distribution || null
})

const activeDimensionMostCommon = computed(() => {
  const dist = activeDimensionDistribution.value
  if (!dist) return 'Sin datos'
  let maxCount = -1
  let mostCommon = 'Sin datos'
  Object.entries(dist).forEach(([level, count]) => {
    if ((count || 0) > maxCount) {
      maxCount = count || 0
      mostCommon = level
    }
  })
  return mostCommon
})

const activeDimensionTotalPeople = computed(() => {
  const dist = activeDimensionDistribution.value
  if (!dist) return 0
  return Object.values(dist).reduce((sum, n) => sum + (n || 0), 0)
})

// Get most common interpretation (modal)
const getMostCommonInterpretation = computed(() => {
  const dist = filteredClimaLaboralDistribution.value
  let maxCount = 0
  let mostCommon = 'Sin datos'

  Object.entries(dist).forEach(([level, count]) => {
    if (count > maxCount) {
      maxCount = count
      mostCommon = level
    }
  })

  return mostCommon
})

// Helper function to get level ranges for a dimension
const getLevelRanges = (dimensionName) => {
  // These ranges should match the config in likert-value.php
  const ranges = {
    'Entorno Laboral Seguro': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Seguridad Laboral': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Compensación Justa': [
      { min: 3.26, max: 4, level: 'Totalmente de Acuerdo' },
      { min: 2.6, max: 3.25, level: 'De Acuerdo' },
      { min: 1.76, max: 2.5, level: 'Desacuerdo' },
      { min: 1, max: 1.75, level: 'Totalmente Desacuerdo' },
    ],
    'Comunicación Abierta': [
      { min: 19.6, max: 24, level: 'Totalmente de Acuerdo' },
      { min: 15.1, max: 19.5, level: 'De Acuerdo' },
      { min: 10.6, max: 15, level: 'Desacuerdo' },
      { min: 6, max: 10.5, level: 'Totalmente Desacuerdo' },
    ],
    'Participación de los Empleados': [
      { min: 9.76, max: 12, level: 'Totalmente de Acuerdo' },
      { min: 7.6, max: 9.75, level: 'De Acuerdo' },
      { min: 5.26, max: 7.5, level: 'Desacuerdo' },
      { min: 3, max: 5.25, level: 'Totalmente Desacuerdo' },
    ],
    'Reconocimiento y Recompensa': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Capacitación y Desarrollo': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Equilibrio entre Vida Laboral y Personal': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Avance Profesional': [
      { min: 6.6, max: 8, level: 'Totalmente de Acuerdo' },
      { min: 5.1, max: 6.5, level: 'De Acuerdo' },
      { min: 3.6, max: 5, level: 'Desacuerdo' },
      { min: 2, max: 3.5, level: 'Totalmente Desacuerdo' },
    ],
    'Apoyo al Empleado': [
      { min: 3.26, max: 4, level: 'Totalmente de Acuerdo' },
      { min: 2.6, max: 3.25, level: 'De Acuerdo' },
      { min: 1.76, max: 2.5, level: 'Desacuerdo' },
      { min: 1, max: 1.75, level: 'Totalmente Desacuerdo' },
    ],
  }

  return ranges[dimensionName] || []
}

// Helper function to get interpretation from score
const getScoreInterpretation = (score, ranges) => {
  for (const range of ranges) {
    if (score >= range.min && score <= range.max) {
      return range.level
    }
  }
  return null
}

const resetFilters = () => {
  filters.value = {
    genero: '',
    tipo_contrato: '',
    puesto: '',
    area: '',
    turno: '',
  }
  // Reset custom filters
  Object.keys(customFilters.value).forEach(key => {
    customFilters.value[key] = ''
  })
}

// Helper: Convert letter answer to numeric value
const getAnswerNumericValue = (answer) => {
  const valueMap = {
    'A': 4,
    'B': 3,
    'C': 2,
    'D': 1
  }
  return valueMap[answer] || '-'
}

// Helper: Get interpretation level for Clima Laboral total score
const getClimaLaboralLevel = (score) => {
  if (score === null || score === undefined) return null
  if (score >= 75.1) return 'Totalmente de Acuerdo'
  if (score >= 57.76) return 'De Acuerdo'
  if (score >= 40.26) return 'Desacuerdo'
  return 'Totalmente Desacuerdo'
}

// Helper: Get Tailwind color class for Clima Laboral total score
const getClimaLaboralColorClass = (score) => {
  const level = getClimaLaboralLevel(score)
  
  switch(level) {
    case 'Totalmente de Acuerdo':
      return 'bg-blue-400 text-black'  // Azul cielo
    case 'De Acuerdo':
      return 'bg-green-600 text-white'  // Verde mayate
    case 'Desacuerdo':
      return 'bg-yellow-500 text-black'  // Amarillo mostaza
    case 'Totalmente Desacuerdo':
      return 'bg-red-600 text-white'  // Rojo
    default:
      return 'bg-gray-200 text-gray-500'  // Sin respuesta
  }
}

// Helper: Get Tailwind color class for answer value
const getAnswerColorClass = (answer) => {
  const value = getAnswerNumericValue(answer)
  
  // Standardized colors per user specification:
  // 4 (A): Azul cielo (sky blue) - Totalmente de Acuerdo
  // 3 (B): Verde mayate (green) - De Acuerdo
  // 2 (C): Amarillo mostaza (mustard yellow) - Desacuerdo
  // 1 (D): Rojo (red) - Totalmente Desacuerdo
  
  switch(value) {
    case 4:
      return 'bg-blue-400 text-black'  // Azul cielo
    case 3:
      return 'bg-green-600 text-white'  // Verde mayate
    case 2:
      return 'bg-yellow-500 text-black'  // Amarillo mostaza
    case 1:
      return 'bg-red-600 text-white'  // Rojo
    default:
      return 'bg-gray-200 text-gray-500'  // Sin respuesta
  }
}

// Helper: Get standardized color for level (used in charts and badges)
const getLevelColor = (level) => {
  // Same standardized colors as heat map
  const colorMap = {
    'Totalmente de Acuerdo': {
      bg: 'rgba(96, 165, 250, 0.8)',      // Blue-400 with opacity
      bgSolid: 'bg-blue-400',
      text: 'text-black',
      badge: 'bg-blue-400 text-black'
    },
    'De Acuerdo': {
      bg: 'rgba(22, 163, 74, 0.8)',      // Green-600 with opacity
      bgSolid: 'bg-green-600',
      text: 'text-white',
      badge: 'bg-green-600 text-white'
    },
    'Desacuerdo': {
      bg: 'rgba(234, 179, 8, 0.8)',      // Yellow-500 with opacity
      bgSolid: 'bg-yellow-500',
      text: 'text-black',
      badge: 'bg-yellow-500 text-black'
    },
    'Totalmente Desacuerdo': {
      bg: 'rgba(220, 38, 38, 0.8)',      // Red-600 with opacity
      bgSolid: 'bg-red-600',
      text: 'text-white',
      badge: 'bg-red-600 text-white'
    }
  }
  return colorMap[level] || {
    bg: 'rgba(156, 163, 175, 0.8)',
    bgSolid: 'bg-gray-400',
    text: 'text-white',
    badge: 'bg-gray-400 text-white'
  }
}

const getHeatmapColor = (score) => {
  if (score >= 3.5) return 'bg-green-100'
  if (score >= 2.5) return 'bg-yellow-100'
  if (score >= 1.5) return 'bg-orange-100'
  return 'bg-red-100'
}

const getQuestionBorderClass = (score) => {
  if (score >= 3.5) return 'border-green-500'
  if (score >= 2.5) return 'border-yellow-500'
  if (score >= 1.5) return 'border-orange-500'
  return 'border-red-500'
}

const getScoreBadgeClass = (score) => {
  if (score >= 3.5) return 'bg-green-100 text-green-800'
  if (score >= 2.5) return 'bg-yellow-100 text-yellow-800'
  if (score >= 1.5) return 'bg-orange-100 text-orange-800'
  return 'bg-red-100 text-red-800'
}

const getScoreLevel = (score) => {
  if (score >= 3.5) return 'Totalmente de Acuerdo'
  if (score >= 2.5) return 'De Acuerdo'
  if (score >= 1.5) return 'Desacuerdo'
  return 'Totalmente Desacuerdo'
}

const createPieChart = (canvasRef, labels, data, title, legendClickHandler = null, legendPosition = 'right') => {
  if (!canvasRef) return

  const ctx = canvasRef.getContext('2d')
  
  // Destroy existing chart if any
  const existingChart = chartInstances.value[title]
  if (existingChart) {
    existingChart.destroy()
  }

  // Generate colors based on labels using standardized color scheme
  const backgroundColors = labels.map(label => getLevelColor(label).bg)

  const chart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: backgroundColors,
        borderWidth: 2,
        borderColor: '#fff',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          position: legendPosition,
          labels: {
            boxWidth: 12,
            padding: 10,
            // Append counts to legend labels
            generateLabels: (chart) => {
              const data = chart.data
              if (!data.labels) return []
              const counts = (data.datasets?.[0]?.data || [])
              return data.labels.map((label, i) => {
                const meta = chart.getDatasetMeta(0)
                const hidden = meta.data[i]?.hidden === true || meta._hiddenIndices?.[i]
                const color = (data.datasets?.[0]?.backgroundColor || [])[i]
                const value = counts[i] || 0
                const personas = value === 1 ? 'persona' : 'personas'
                return {
                  text: `${label} (${value} ${personas})`,
                  fillStyle: color,
                  strokeStyle: '#ffffff',
                  lineWidth: 2,
                  hidden,
                  index: i,
                }
              })
            },
          },
          // On legend click, optionally show folios modal instead of toggling visibility
          onClick: (evt, legendItem, legend) => {
            if (typeof legendClickHandler === 'function') {
              const label = legendItem.text?.split(' (')?.[0] || legendItem.text || ''
              legendClickHandler(label)
              return
            }
          },
        },
        title: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || ''
              const value = context.parsed || 0
              const total = context.dataset.data.reduce((a, b) => a + b, 0)
              const percentage = ((value / total) * 100).toFixed(1)
              const personas = value === 1 ? 'persona' : 'personas'
              return `${label}: ${value} ${personas} (${percentage}%)`
            }
          }
        }
      }
    }
  })

  chartInstances.value[title] = chart
}

const renderCharts = () => {
  nextTick(() => {
    // Total pie chart - Distribution by Clima Laboral level
    if (pieChartTotal.value) {
      const distribution = filteredClimaLaboralDistribution.value
      const labels = Object.keys(distribution).filter(key => distribution[key] > 0)
      const data = labels.map(key => distribution[key])
      
      if (data.length > 0) {
        createPieChart(pieChartTotal.value, labels, data, TOTAL_CHART_KEY, (levelLabel) => {
          // Show folios by overall Clima Laboral interpretation
          const items = getFoliosForClimaLevel(levelLabel)
          openFoliosModal(`Folios en ${levelLabel} (Clima Laboral)`, items)
        }, 'bottom')
      }
    }

    // Dimension-specific pie chart - Only for active tab
    if (activeTab.value !== 'Total' && dimensionChartCanvas.value) {
      const dimension = filteredDimensions.value[activeTab.value]
      if (dimension) {
        const distribution = dimension.distribution
        const labels = Object.keys(distribution).filter(key => distribution[key] > 0)
        const data = labels.map(key => distribution[key])
        if (data.length > 0) {
          createPieChart(
            dimensionChartCanvas.value,
            labels,
            data,
            DIMENSION_CHART_KEY,
            (levelLabel) => {
              const items = getFoliosForDimensionLevel(activeTab.value, levelLabel)
              openFoliosModal(`Folios en ${levelLabel} - ${activeTab.value}`, items)
            },
            'bottom'
          )
        } else {
          // Destroy existing dimension chart if no data
          if (chartInstances.value[DIMENSION_CHART_KEY]) {
            chartInstances.value[DIMENSION_CHART_KEY].destroy()
            delete chartInstances.value[DIMENSION_CHART_KEY]
          }
        }
      }
    }
  })
}

onMounted(() => {
  renderCharts()
})

watch([activeTab, filteredDimensions], () => {
  renderCharts()
}, { deep: true })

// Compute the list of folios for a given level in the current dimension
const valorOpciones = { A: 4, B: 3, C: 2, D: 1 }

const getFoliosForDimensionLevel = (dimensionName, level) => {
  const ranges = getLevelRanges(dimensionName)
  const range = ranges.find(r => r.level === level)
  if (!range) return []

  // Identify question numbers for this dimension from props.dimensions
  const dim = props.dimensions[dimensionName]
  if (!dim || !dim.questions) return []
  const qNums = Object.keys(dim.questions).map(n => parseInt(n, 10))

  // For filtered evaluations, compute person score for this dimension
  const items = []
  filteredEvaluations.value.forEach(evalData => {
    let score = 0
    qNums.forEach(q => {
      const ans = evalData.answers[q]
      if (ans) score += (valorOpciones[ans] || 0)
    })
    if (score >= range.min && score <= range.max) {
      items.push({ 
        folio: evalData.personal_folio, 
        name: evalData.evaluee_name || '',
        totalScore: evalData.scores?.total_score ?? null
      })
    }
  })
  // Sort by totalScore ascending (lowest first)
  items.sort((a, b) => (a.totalScore ?? 0) - (b.totalScore ?? 0))
  return items
}

const getFoliosForClimaLevel = (level) => {
  const items = []
  filteredEvaluations.value.forEach(evalData => {
    const interpretation = evalData.scores?.interpretation
    if (interpretation === level) {
      items.push({ 
        folio: evalData.personal_folio, 
        name: evalData.evaluee_name || '',
        totalScore: evalData.scores?.total_score ?? null
      })
    }
  })
  // Sort by totalScore ascending (lowest first)
  items.sort((a, b) => (a.totalScore ?? 0) - (b.totalScore ?? 0))
  return items
}

// Modal to display folios list
const showFoliosModal = ref(false)
const foliosModalTitle = ref('')
const foliosModalItems = ref([])

const openFoliosModal = (title, items) => {
  foliosModalTitle.value = title
  foliosModalItems.value = items
  showFoliosModal.value = true
}
const closeFoliosModal = () => { showFoliosModal.value = false }
</script>
