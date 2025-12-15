<template>
  <div class="space-y-6">
    <!-- Filtros Demográficos -->
    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ t('Demographic Filters') }}</h3>
        <button
          @click="resetFilters"
          class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors"
        >
          ↺ {{ t('Reset Filters') }}
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Genero -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('Gender') }}</label>
          <select
            v-model="filters.gender"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="gender in demographicDetails.genders" :key="gender" :value="gender">
              {{ gender }}
            </option>
          </select>
        </div>

        <!-- Tipo de Contrato -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('Contract Type') }}</label>
          <select
            v-model="filters.contract_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="type in demographicDetails.contract_types" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <!-- Puesto -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('Position') }}</label>
          <select
            v-model="filters.position"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="position in demographicDetails.positions" :key="position" :value="position">
              {{ position }}
            </option>
          </select>
        </div>

        <!-- Área -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('Area') }}</label>
          <select
            v-model="filters.area"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="area in demographicDetails.areas" :key="area" :value="area">
              {{ area }}
            </option>
          </select>
        </div>

        <!-- Turno -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('Shift') }}</label>
          <select
            v-model="filters.shift"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ t('All') }}</option>
            <option v-for="shift in demographicDetails.shifts" :key="shift" :value="shift">
              {{ shift }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabla de Factores de Riesgo -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">{{ t('Top 3 Risk Factors') }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ t('Based on total disagreement responses') }}</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">{{ t('Risk Factor') }}</th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full">
                  {{ t('Strongly Agree') }}
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                  {{ t('Agree') }}
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                  {{ t('Disagree') }}
                </span>
              </th>
              <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 rounded-full">
                  {{ t('Strongly Disagree') }}
                </span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(factor, index) in topThreeFactors"
              :key="factor.name"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white font-semibold text-sm"
                    :class="getSeverityBadgeClass(index)"
                  >
                    {{ index + 1 }}
                  </span>
                  <span class="font-medium text-gray-900">{{ t(factor.name) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-green-50 rounded-lg font-semibold text-green-700">
                  {{ factor.counts['Totally Agree'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-50 rounded-lg font-semibold text-blue-700">
                  {{ factor.counts['Agree'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-yellow-50 rounded-lg font-semibold text-yellow-700">
                  {{ factor.counts['Disagree'] || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center w-10 h-10 bg-red-50 rounded-lg font-semibold text-red-700">
                  {{ factor.counts['Totally Disagree'] || 0 }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="topThreeFactors.length === 0" class="p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-lg font-semibold text-gray-900 mb-2">{{ t('No data available') }}</p>
        <p class="text-gray-600">{{ t('Try changing the filters to see risk factors') }}</p>
      </div>
    </div>

    
        <div class="space-y-6 ">
            <div class="max-w-7xl mx-auto shadow-xl rounded-lg overflow-hidden mb-24">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-800 text-white sticky top-0">
                            <tr>
                                <th scope="col" rowspan="2"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[150px]">
                                    {{ t('Department').toUpperCase() }}
                                </th>
                                <th scope="col" rowspan="2"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">
                                    {{ t('Workers').toUpperCase() }}
                                </th>
                                <th colspan="4"
                                    class="px-3 py-2 text-center text-xs font-medium uppercase tracking-wider bg-slate-800">
                                    {{ t('Satisfaction Level').toUpperCase() }}
                                </th>
                                <th scope="col" rowspan="2"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider bg-red-600">
                                    {{ t('Unsatisfactory Level').toUpperCase() }} ({{ t('Total').toUpperCase() }})
                                </th>
                                <th colspan="3"
                                    class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider bg-slate-800 min-w-[450px]">
                                    {{ t('Most Critical Factors').toUpperCase() }}
                                </th>
                            </tr>
                            <tr class="bg-slate-500 text-white">
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[90px]">
                                    {{ t('Totally Agree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[80px]">{{ t('Agree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[80px]">
                                    {{ t('Disagree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[90px]">
                                    {{ t('Totally Disagree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">1</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">2</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">3</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-gray-800">

                            <tr class="bg-yellow-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold">Producción</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">3221</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">429</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1821</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">888</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">83</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-bold text-red-700">
                                    971</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Work-Life Balance') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Recursos Humanos</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">40</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">6</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">17</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">17</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">17</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Safe Work Environment') }}.</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Mantenimiento</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">61</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">12</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">33</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">15</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">16</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Calidad</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">69</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">13</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">42</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">11</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">14</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Materiales</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">54</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">14</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">30</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">10</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Materiales</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">54</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">14</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">30</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">10</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Manufactura</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">19</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">6</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">8</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Finanzas</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">15</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">4</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Import / Export</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">1</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Fair Compensation') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Ingeniería</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">1</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Administración</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">16</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Gerencia</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">19</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Mejora continua</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Programas</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">5</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">Sistemas</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No
                                    se listaron factores críticos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Gráfica de Comentarios por Factor -->
    <!-- <div v-if="commentFactors.length > 0" class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-6">{{ t('Comments by Factor') }}</h3>
      <p class="text-sm text-gray-600 mb-6">{{ t('Comment distribution based on applied demographic filters') }}</p>
      
      <canvas ref="commentChartCanvas" style="height: 300px"></canvas>
      
      <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-sm">
        <div v-for="(count, label) in commentCounts" :key="label" class="flex justify-between">
          <span class="text-gray-700">{{ translateDimension(label) }}</span>
          <span class="font-semibold text-gray-900">{{ count }}</span>
        </div>
      </div>
    </div> -->

    <!-- Empty State para Comentarios -->
    <!-- <div v-else class="bg-gray-50 rounded-lg p-12 text-center border border-gray-200">
      <div class="text-6xl mb-4">💬</div>
      <p class="text-lg font-semibold text-gray-900 mb-2">{{ t('No comments available') }}</p>
      <p class="text-gray-600">{{ t('No comments found for selected filters') }}</p>
    </div> -->
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useTranslations } from '@/composables/useTranslations';

Chart.register(...registerables);

const { t } = useTranslations();

interface DemographicData {
  gender?: string;
  contract_type?: string;
  position?: string;
  department?: string;
  work_schedule?: string;
}

interface DimensionScore {
  name: string;
  score: number;
  interpretation: string;
}

interface EvaluationComment {
  factor: string;
  comment: string;
}

interface Evaluation {
  id: string;
  demographicData?: DemographicData;
  dimensions?: DimensionScore[];
  comments?: EvaluationComment[];
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface RiskFactor {
  name: string;
  disagreementSum: number;
  counts: Record<string, number>;
}

interface Props {
  evaluations: Evaluation[];
  demographicDetails: DemographicDetails;
}

const props = defineProps<Props>();

// Filters
const filters = ref({
  gender: '',
  contract_type: '',
  position: '',
  area: '',
  shift: '',
});

// Chart refs
const commentChartCanvas = ref<HTMLCanvasElement>();
const chartInstances = ref<Record<string, Chart>>({});

// Map Spanish interpretations to English keys
const interpretationMap = {
  'Totalmente de Acuerdo': 'Totally Agree',
  'De Acuerdo': 'Agree',
  'Desacuerdo': 'Disagree',
  'Totalmente Desacuerdo': 'Totally Disagree',
};

// Map Spanish dimension names to English
const dimensionMap = {
  'Entorno Laboral Seguro': 'Safe Work Environment',
  'Seguridad Laboral': 'Job Security',
  'Compensación Justa': 'Fair Compensation',
  'Comunicación Abierta': 'Open Communication',
  'Participación de los Empleados': 'Employee Participation',
  'Reconocimiento y Recompensa': 'Recognition and Reward',
  'Capacitación y Desarrollo': 'Training and Development',
  'Equilibrio entre Vida Laboral y Personal': 'Work-Life Balance',
  'Avance Profesional': 'Professional Advancement',
  'Apoyo al Empleado': 'Employee Support',
};

// Helper to translate dimension name
const translateDimension = (name) => {
  return dimensionMap[name] || name;
};

// Reset filters function
const resetFilters = (): void => {
  filters.value.gender = '';
  filters.value.contract_type = '';
  filters.value.position = '';
  filters.value.area = '';
  filters.value.shift = '';
};

// Filtered evaluations
const filteredEvaluations = computed(() => {
  return props.evaluations.filter((evaluation: Evaluation) => {
    const demo = evaluation.demographicData || {};

    if (filters.value.gender && demo.gender !== filters.value.gender) {
      return false;
    }
    if (filters.value.contract_type && demo.contract_type !== filters.value.contract_type) {
      return false;
    }
    if (filters.value.position && demo.position !== filters.value.position) {
      return false;
    }
    if (filters.value.area && demo.department !== filters.value.area) {
      return false;
    }
    if (filters.value.shift && demo.work_schedule !== filters.value.shift) {
      return false;
    }

    return true;
  });
});

// Calculate top 3 risk factors
const topThreeFactors = computed(() => {
  const factorMap: Record<string, Record<string, number>> = {};

  // Aggregate dimension scores
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (!evaluation.dimensions || !Array.isArray(evaluation.dimensions)) {
      return;
    }

    evaluation.dimensions.forEach((dimension: DimensionScore) => {
      if (!factorMap[dimension.name]) {
        factorMap[dimension.name] = {
          'Totally Agree': 0,
          'Agree': 0,
          'Disagree': 0,
          'Totally Disagree': 0,
        };
      }

      // Map interpretation to agreement level
      const interpretation = interpretationMap[dimension.interpretation] || dimension.interpretation || '';
      if (factorMap[dimension.name][interpretation] !== undefined) {
        factorMap[dimension.name][interpretation]++;
      }
    });
  });

  // Convert to array and calculate disagreement sum
  const factors: RiskFactor[] = Object.entries(factorMap).map(([name, counts]) => ({
    name,
    counts: counts as Record<string, number>,
    disagreementSum: (counts['Disagree'] || 0) + (counts['Totally Disagree'] || 0),
  }));

  // Sort by disagreement sum (descending) and return top 3
  return factors
    .sort((a, b) => b.disagreementSum - a.disagreementSum)
    //.slice(0, 3)
    .map((factor) => ({
      name: factor.name,
      counts: factor.counts,
      disagreementSum: factor.disagreementSum,
    }));
});

// Get severity badge color based on rank
const getSeverityBadgeClass = (index: number): string => {
  const severities = [
    'bg-red-600',
    'bg-red-600',
    'bg-red-600',      // 1st place - worst
    'bg-orange-600',
    'bg-orange-600',   // 2nd place
    'bg-yellow-600',
    'bg-yellow-600',   // 3rd place
  ];
  return severities[index] || 'bg-green-600';
};

// Extract unique comment factors from filtered evaluations
const commentFactors = computed(() => {
  const factors = new Set<string>();
  
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (evaluation.comments && Array.isArray(evaluation.comments)) {
      evaluation.comments.forEach((comment: EvaluationComment) => {
        if (comment.factor) {
          factors.add(comment.factor);
        }
      });
    }
  });
  
  return Array.from(factors).sort();
});

// Count comments by factor
const commentCounts = computed(() => {
  const counts: Record<string, number> = {};
  
  filteredEvaluations.value.forEach((evaluation: Evaluation) => {
    if (evaluation.comments && Array.isArray(evaluation.comments)) {
      evaluation.comments.forEach((comment: EvaluationComment) => {
        if (comment.factor) {
          counts[comment.factor] = (counts[comment.factor] || 0) + 1;
        }
      });
    }
  });
  
  return counts;
});

// Chart color helper
const getChartColor = (index: number): string => {
  const colors = [
    'rgba(59, 130, 246, 0.8)',    // Blue
    'rgba(34, 197, 94, 0.8)',     // Green
    'rgba(239, 68, 68, 0.8)',     // Red
    'rgba(251, 146, 60, 0.8)',    // Orange
    'rgba(168, 85, 247, 0.8)',    // Purple
    'rgba(14, 165, 233, 0.8)',    // Cyan
    'rgba(236, 72, 153, 0.8)',    // Pink
    'rgba(100, 116, 139, 0.8)',   // Slate
  ];
  return colors[index % colors.length];
};

const getConsistentStepSize = (maxValue: number): number => {
  if (maxValue <= 10) return 1;
  if (maxValue <= 50) return 5;
  if (maxValue <= 100) return 10;
  if (maxValue <= 500) return 50;
  if (maxValue <= 1000) return 100;
  if (maxValue <= 5000) return 500;
  return Math.ceil(maxValue / 5 / 100) * 100;
};

// Create chart for comments
const createCommentChart = (): void => {
  if (!commentChartCanvas.value) return;

  const ctx = commentChartCanvas.value.getContext('2d');
  if (!ctx) return;

  // Destroy existing chart
  const existingChart = chartInstances.value['comments'];
  if (existingChart) {
    existingChart.destroy();
  }

  const labels = Object.keys(commentCounts.value).filter(label => commentCounts.value[label] > 0);
  const data = labels.map(label => commentCounts.value[label]);
  const backgroundColors = labels.map((_, index) => getChartColor(index));

  const maxValue = data.length > 0 ? Math.max(...data) : 0;
  const stepSize = getConsistentStepSize(maxValue);

  const chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: backgroundColors,
          borderColor: backgroundColors.map(c => c.replace('0.8', '1')),
          borderWidth: 2,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      indexAxis: 'y',
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize,
          },
        },
      },
    },
  });

  chartInstances.value['comments'] = chart;
};

// Render comment chart
const renderCommentChart = (): void => {
  nextTick(() => {
    if (commentFactors.value.length > 0) {
      createCommentChart();
    }
  });
};

// Watch for changes in filtered evaluations and re-render chart
watch([filteredEvaluations, commentFactors], () => {
  renderCommentChart();
}, { deep: true });

// Render chart on mount
onMounted(() => {
  renderCommentChart();
});
</script>
