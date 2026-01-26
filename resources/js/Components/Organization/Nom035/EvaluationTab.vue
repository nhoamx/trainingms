<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-blue-100 rounded-lg">
          <ClipboardDocumentListIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Evaluación de Riesgos Psicosociales</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Resultados e interpretación de instrumentos aplicados</p>
    </div>

    <!-- Resumen de Evaluaciones -->
    <div :class="['grid gap-4', gridColsClass]">
      <StatCard 
        v-for="type in availableEvaluationTypes"
        :key="type.key"
        :label="type.label"
        :value="evaluationsByType[type.key] || 0"
        :icon="type.icon"
        :color="type.color"
      />
    </div>

    <!-- Instrumentos Aplicados -->
    <div v-if="availableEvaluationTypes.length > 0" class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-blue-100 rounded-lg">
          <SparklesIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="text-2xl font-bold text-blue-900">Instrumentos de Evaluación Utilizados</h3>
      </div>
      <div :class="['grid gap-6', instrumentGridClass]">
        <div 
          v-for="type in availableEvaluationTypes"
          :key="type.key"
          :class="['bg-white rounded-lg p-6 border-l-4 hover:shadow-md transition-shadow', borderColorClass(type.color)]"
        >
          <div class="flex items-center gap-2 mb-3">
            <component :is="typeIcon(type.icon)" :class="['w-5 h-5', iconColorClass(type.color)]" />
            <h4 class="font-bold text-slate-900">{{ type.label }}</h4>
          </div>
          <p class="text-sm text-slate-600 mb-4">{{ type.description }}</p>
          <div class="flex items-center justify-between">
            <span :class="['text-xs font-medium px-3 py-1.5 rounded-full', badgeClass(type.color)]">{{ type.badge }}</span>
            <span :class="['text-lg font-bold', textColorClass(type.color)]">{{ evaluationsByType[type.key] || 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs de Análisis -->
    <div v-if="availableEvaluationTypes.length > 0" class="bg-white rounded-xl border border-slate-200 shadow-sm">
      <!-- Navegación de Tabs -->
      <div class="border-b border-slate-200">
        <nav class="flex flex-wrap -mb-px" aria-label="Tabs">
          <button
            v-for="tab in analysisTabs"
            :key="tab.key"
            @click="activeAnalysisTab = tab.key"
            :class="[
              'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
              activeAnalysisTab === tab.key
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300'
            ]"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Contenido de Tabs -->
      <div class="p-8">
        <!-- Tab: Calificación final -->
        <div v-show="activeAnalysisTab === 'calificacion'" class="space-y-8">
          <!-- Valor de las opciones de respuesta -->
          <div>
            <h4 class="text-lg font-bold text-slate-900 mb-4">Valor de las opciones de respuesta</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 border border-slate-200">
                <thead class="bg-slate-50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      N° de Pregunta
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Siempre
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Casi siempre
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Algunas veces
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Casi nunca
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Nunca
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                  <tr>
                    <td class="px-4 py-3 text-sm text-slate-600">
                      1, 4, 23, 24, 25, 26, 27, 28, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 55, 56, 57
                    </td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">0</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">1</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">2</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">3</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">4</td>
                  </tr>
                  <tr>
                    <td class="px-4 py-3 text-sm text-slate-600">
                      2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 29, 54, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72
                    </td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">4</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">3</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">2</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">1</td>
                    <td class="px-4 py-3 text-center text-sm font-medium text-slate-900">0</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Resultado del puntaje -->
          <div>
            <h4 class="text-lg font-bold text-slate-900 mb-4">Resultado del puntaje</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 border border-slate-200 rounded-lg">
                <thead class="bg-slate-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Resultado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                      Rango de calificación
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                  <tr class="hover:bg-blue-50 transition-colors border-l-4 border-blue-500">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm font-medium text-slate-900">Nulo o despreciable</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      Calificación final &lt; 50
                    </td>
                  </tr>
                  <tr class="hover:bg-emerald-50 transition-colors border-l-4 border-emerald-500">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-sm font-medium text-slate-900">Bajo</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      49 &lt; Calificación final &lt; 75
                    </td>
                  </tr>
                  <tr class="hover:bg-amber-50 transition-colors border-l-4 border-amber-500">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <span class="text-sm font-medium text-slate-900">Medio</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      74 &lt; Calificación final &lt; 99
                    </td>
                  </tr>
                  <tr class="hover:bg-orange-50 transition-colors border-l-4 border-orange-500">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        <span class="text-sm font-medium text-slate-900">Alto</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      98 &lt; Calificación final &lt; 140
                    </td>
                  </tr>
                  <tr class="hover:bg-red-50 transition-colors border-l-4 border-red-500">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-sm font-medium text-slate-900">Muy Alto</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                      Calificación final &gt; 139
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab: Categoría -->
        <div v-show="activeAnalysisTab === 'categoria'" class="space-y-6">
          <h4 class="text-lg font-bold text-slate-900 mb-4">Categorías de calificación</h4>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 border border-slate-200 rounded-lg">
              <thead class="bg-slate-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Categoría
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-blue-700 uppercase tracking-wider bg-blue-50">
                    Nulo o despreciable
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-green-700 uppercase tracking-wider bg-green-50">
                    Bajo
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-amber-700 uppercase tracking-wider bg-amber-50">
                    Medio
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-orange-700 uppercase tracking-wider bg-orange-50">
                    Alto
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-red-700 uppercase tracking-wider bg-red-50">
                    Muy Alto
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Ambiente de trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>cat</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">4 &lt; C<sub>cat</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">8 &lt; C<sub>cat</sub> &lt; 11</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">10 &lt; C<sub>cat</sub> &lt; 14</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>cat</sub> &gt; 13</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Factores propios de la actividad</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>cat</sub> &lt; 15</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">14 &lt; C<sub>cat</sub> &lt; 30</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">29 &lt; C<sub>cat</sub> &lt; 45</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">44 &lt; C<sub>cat</sub> &lt; 60</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>cat</sub> &gt; 59</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Organización del tiempo de trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>cat</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">4 &lt; C<sub>cat</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">6 &lt; C<sub>cat</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">9 &lt; C<sub>cat</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>cat</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Liderazgo y relaciones en el trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>cat</sub> &lt; 14</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">13 &lt; C<sub>cat</sub> &lt; 29</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">28 &lt; C<sub>cat</sub> &lt; 42</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">41 &lt; C<sub>cat</sub> &lt; 58</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>cat</sub> &gt; 57</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Entorno organizacional</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>cat</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">9 &lt; C<sub>cat</sub> &lt; 14</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">13 &lt; C<sub>cat</sub> &lt; 18</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">17 &lt; C<sub>cat</sub> &lt; 23</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>cat</sub> &gt; 22</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab: Dominio -->
        <div v-show="activeAnalysisTab === 'dominio'" class="space-y-6">
          <h4 class="text-lg font-bold text-slate-900 mb-4">Dominios – Rangos de calificación</h4>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 border border-slate-200 rounded-lg">
              <thead class="bg-slate-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Dominio
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-blue-700 uppercase tracking-wider bg-blue-50">
                    Nulo o despreciable
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-green-700 uppercase tracking-wider bg-green-50">
                    Bajo
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-amber-700 uppercase tracking-wider bg-amber-50">
                    Medio
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-orange-700 uppercase tracking-wider bg-orange-50">
                    Alto
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-red-700 uppercase tracking-wider bg-red-50">
                    Muy Alto
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Condiciones en el ambiente de trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">4 &lt; C<sub>dom</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">8 &lt; C<sub>dom</sub> &lt; 11</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">10 &lt; C<sub>dom</sub> &lt; 14</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 13</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Carga de trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 15</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">14 &lt; C<sub>dom</sub> &lt; 21</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">20 &lt; C<sub>dom</sub> &lt; 27</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">26 &lt; C<sub>dom</sub> &lt; 37</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 36</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Falta de control sobre el trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 11</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">10 &lt; C<sub>dom</sub> &lt; 16</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">15 &lt; C<sub>dom</sub> &lt; 21</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">20 &lt; C<sub>dom</sub> &lt; 25</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 24</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Jornada de trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 1</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dom</sub> &lt; 2</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">1 &lt; C<sub>dom</sub> &lt; 4</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">3 &lt; C<sub>dom</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 5</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Interferencia en la relación trabajo-familia</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 4</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">3 &lt; C<sub>dom</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">5 &lt; C<sub>dom</sub> &lt; 8</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">7 &lt; C<sub>dom</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 9</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Liderazgo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">8 &lt; C<sub>dom</sub> &lt; 12</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">11 &lt; C<sub>dom</sub> &lt; 16</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">15 &lt; C<sub>dom</sub> &lt; 20</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 19</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Relaciones en el trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">9 &lt; C<sub>dom</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">12 &lt; C<sub>dom</sub> &lt; 17</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">16 &lt; C<sub>dom</sub> &lt; 21</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 20</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Violencia</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">6 &lt; C<sub>dom</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">9 &lt; C<sub>dom</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">12 &lt; C<sub>dom</sub> &lt; 16</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 15</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Reconocimiento del desempeño</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">5 &lt; C<sub>dom</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">8 &lt; C<sub>dom</sub> &lt; 14</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">13 &lt; C<sub>dom</sub> &lt; 18</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 17</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Insuficiente sentido de pertenencia e inestabilidad</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dom</sub> &lt; 4</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">3 &lt; C<sub>dom</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">5 &lt; C<sub>dom</sub> &lt; 8</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">7 &lt; C<sub>dom</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dom</sub> &gt; 9</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab: Dimensión -->
        <div v-show="activeAnalysisTab === 'dimension'" class="space-y-6">
          <h4 class="text-lg font-bold text-slate-900 mb-4">Dimensiones – Rangos de calificación</h4>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 border border-slate-200 rounded-lg">
              <thead class="bg-slate-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Dimensión
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-blue-700 uppercase tracking-wider bg-blue-50">
                    Nulo o despreciable
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-green-700 uppercase tracking-wider bg-green-50">
                    Bajo
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-amber-700 uppercase tracking-wider bg-amber-50">
                    Medio
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-orange-700 uppercase tracking-wider bg-orange-50">
                    Alto
                  </th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-red-700 uppercase tracking-wider bg-red-50">
                    Muy Alto
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Trabajos peligrosos</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">C<sub>dim</sub> = 1</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">C<sub>dim</sub> = 2</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">C<sub>dim</sub> = 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> = 4</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Condiciones peligrosas e inseguras</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Cargas contradictorias o inconsistentes</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Cargas cuantitativas</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Cargas de alta responsabilidad</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Condiciones deficientes e insalubres</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Escasa o nula retroalimentación del desempeño</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Inestabilidad laboral</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Influencia de las responsabilidades familiares</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Influencia del trabajo fuera del centro laboral</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Insuficiente participación y manejo del cambio</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Jornadas de trabajo extensas</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Limitada o inexistente capacitación</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Limitada o nula posibilidad de desarrollo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Limitado sentido de pertenencia</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Ritmos de trabajo acelerado</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 3</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">2 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">4 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 6</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Carga mental</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 4</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">3 &lt; C<sub>dim</sub> &lt; 7</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">6 &lt; C<sub>dim</sub> &lt; 10</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 9</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Cargas psicológicas emocionales</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">4 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">8 &lt; C<sub>dim</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Deficiente relación con los colaboradores que supervisa</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">4 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">8 &lt; C<sub>dim</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Escaso o nulo reconocimiento y compensación</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">4 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">8 &lt; C<sub>dim</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Escaza claridad de funciones</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">4 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">8 &lt; C<sub>dim</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Falta de control y autonomía sobre el trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 5</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">4 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">8 &lt; C<sub>dim</sub> &lt; 13</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 12</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Características del liderazgo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">5 &lt; C<sub>dim</sub> &lt; 11</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">10 &lt; C<sub>dim</sub> &lt; 16</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 15</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Relaciones sociales en el trabajo</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 6</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">5 &lt; C<sub>dim</sub> &lt; 11</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">10 &lt; C<sub>dim</sub> &lt; 16</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 15</td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 text-sm font-medium text-slate-900">Violencia laboral</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-blue-50/30">C<sub>dim</sub> = 0</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-green-50/30">0 &lt; C<sub>dim</sub> &lt; 9</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-amber-50/30">8 &lt; C<sub>dim</sub> &lt; 17</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-orange-50/30">16 &lt; C<sub>dim</sub> &lt; 25</td>
                  <td class="px-4 py-3 text-center text-sm text-slate-600 bg-red-50/30">C<sub>dim</sub> &gt; 24</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import StatCard from './StatCard.vue';
import {
  ClipboardDocumentListIcon,
  DocumentIcon,
  AdjustmentsHorizontalIcon,
  ShieldCheckIcon,
  SparklesIcon,
  DocumentTextIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';

interface Evaluation {
  id: string;
  evaluation_type?: string;
  personal_folio?: string;
}

interface EvaluationType {
  key: string;
  label: string;
  description: string;
  badge: string;
  color: string;
  icon: string;
}

interface Props {
  evaluations?: Evaluation[];
  availableEvaluationTypes?: EvaluationType[];
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
  availableEvaluationTypes: () => [],
});

// Estado para las tabs de análisis
const activeAnalysisTab = ref('calificacion');

const analysisTabs = [
  { key: 'calificacion', label: 'Calificación final' },
  { key: 'categoria', label: 'Categoría' },
  { key: 'dominio', label: 'Dominio' },
  //{ key: 'dimension', label: 'Dimensión' },
];

const totalEvaluations = computed(() => props.evaluations?.length || 0);

const evaluationsByType = computed(() => {
  const counts: Record<string, number> = {};
  
  // Initialize counts for all available types
  props.availableEvaluationTypes?.forEach((type) => {
    counts[type.key] = 0;
  });

  // Count evaluations by type
  props.evaluations?.forEach((evaluation) => {
    if (evaluation.evaluation_type && counts.hasOwnProperty(evaluation.evaluation_type)) {
      counts[evaluation.evaluation_type]++;
    }
  });

  return counts;
});

const gridColsClass = computed(() => {
  const count = props.availableEvaluationTypes?.length || 0;
  if (count === 0) return 'grid-cols-1';
  if (count === 1) return 'grid-cols-1';
  if (count === 2) return 'grid-cols-1 md:grid-cols-2';
  if (count === 3) return 'grid-cols-1 md:grid-cols-3';
  return 'grid-cols-1 md:grid-cols-4';
});

const instrumentGridClass = computed(() => {
  const count = props.availableEvaluationTypes?.length || 0;
  if (count === 0) return 'grid-cols-1';
  if (count === 1) return 'grid-cols-1';
  if (count === 2) return 'grid-cols-1 md:grid-cols-2';
  return 'grid-cols-1 md:grid-cols-3';
});

const borderColorClass = (color: string): string => {
  const colorMap: Record<string, string> = {
    red: 'border-red-500',
    amber: 'border-amber-500',
    orange: 'border-orange-500',
    green: 'border-green-500',
  };
  return colorMap[color] || 'border-blue-500';
};

const iconColorClass = (color: string): string => {
  const colorMap: Record<string, string> = {
    red: 'text-red-600',
    amber: 'text-amber-600',
    orange: 'text-orange-600',
    green: 'text-green-600',
  };
  return colorMap[color] || 'text-blue-600';
};

const textColorClass = (color: string): string => {
  const colorMap: Record<string, string> = {
    red: 'text-red-600',
    amber: 'text-amber-600',
    orange: 'text-orange-600',
    green: 'text-green-600',
  };
  return colorMap[color] || 'text-blue-600';
};

const badgeClass = (color: string): string => {
  const colorMap: Record<string, string> = {
    red: 'text-red-600 bg-red-50',
    amber: 'text-amber-600 bg-amber-50',
    orange: 'text-orange-600 bg-orange-50',
    green: 'text-green-600 bg-green-50',
  };
  return colorMap[color] || 'text-blue-600 bg-blue-50';
};

const typeIcon = (iconName: string) => {
  const iconMap: Record<string, any> = {
    DocumentTextIcon,
    AdjustmentsHorizontalIcon,
    ShieldCheckIcon,
    UserGroupIcon,
  };
  return iconMap[iconName] || DocumentTextIcon;
};
</script>
