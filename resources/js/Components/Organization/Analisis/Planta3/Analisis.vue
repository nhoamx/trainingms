<template>
    <div class="space-y-8">
        <!-- Analysis by Department -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ t('Analysis by Department') }}</h2>
        </div>
        <div class="overflow-x-auto shadow-md rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead class="bg-slate-800 text-white sticky top-0 z-10">
                    <tr>
                        <th scope="col" rowspan="2"
                            class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider min-w-[150px] border-r border-slate-700">
                            {{ t('Department') }}
                        </th>
                        <th scope="col" rowspan="2"
                            class="px-3 py-4 text-center text-xs font-bold uppercase tracking-wider border-r border-slate-700">
                            {{ t('Total Workers') }}
                        </th>
                        <th colspan="4"
                            class="px-3 py-2 text-center text-xs font-bold uppercase tracking-wider bg-slate-800 border-b border-slate-700">
                            {{ t('Satisfaction Level') }}
                        </th>
                        <th scope="col" rowspan="2"
                            class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider bg-red-600 text-white border-l border-r border-red-500">
                            {{ t('Unsatisfactory Level (Total)') }}
                        </th>
                        <th colspan="3"
                            class="px-6 py-2 text-center text-xs font-bold uppercase tracking-wider bg-slate-800 border-b border-slate-700 min-w-[450px]">
                            {{ t('Most Critical Factors') }}
                        </th>
                    </tr>
                    <tr class="bg-slate-600 text-white">
                        <th scope="col"
                            class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                            {{ t('Strongly Agree') }}</th>
                        <th scope="col"
                            class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                            {{ t('Agree') }}</th>
                        <th scope="col"
                            class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                            {{ t('Disagree') }}</th>
                        <th scope="col"
                            class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                            {{ t('Strongly Disagree') }}</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold border-r border-slate-500">1</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold border-r border-slate-500">2</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold">3</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200 text-gray-800">
                    <tr class="bg-yellow-50 hover:bg-yellow-100 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold border-r">{{ t('Production') }}</td>
                        <td class="px-3 py-4 text-center text-sm font-semibold border-r">2149</td>
                        <td class="px-3 py-4 text-center text-sm border-r">276</td>
                        <td class="px-3 py-4 text-center text-sm border-r">1347</td>
                        <td class="px-3 py-4 text-center text-sm border-r">497</td>
                        <td class="px-3 py-4 text-center text-sm border-r">29</td>
                        <td class="px-3 py-4 text-center text-sm font-black text-red-700 bg-red-50 border-r">526</td>
                        <td class="px-6 py-4 text-xs border-r">{{ t('Work-Life Balance') }}</td>
                        <td class="px-6 py-4 text-xs border-r">{{ t('Recognition and Rewards') }}</td>
                        <td class="px-6 py-4 text-xs">{{ t('Professional Development') }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">{{ t('Materials') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">54</td>
                        <td class="px-3 py-3 text-center text-sm border-r">19</td>
                        <td class="px-3 py-3 text-center text-sm border-r">25</td>
                        <td class="px-3 py-3 text-center text-sm border-r">8</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">10</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Employee Support') }}</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Work-Life Balance') }}</td>
                        <td class="px-6 py-3 text-xs">{{ t('Training and Development') }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">{{ t('Maintenance') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">57</td>
                        <td class="px-3 py-3 text-center text-sm border-r">13</td>
                        <td class="px-3 py-3 text-center text-sm border-r">35</td>
                        <td class="px-3 py-3 text-center text-sm border-r">8</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">9</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Professional Development') }}</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Employee Support') }}</td>
                        <td class="px-6 py-3 text-xs">{{ t('Work-Life Balance') }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">{{ t('Quality') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">37</td>
                        <td class="px-3 py-3 text-center text-sm border-r">12</td>
                        <td class="px-3 py-3 text-center text-sm border-r">20</td>
                        <td class="px-3 py-3 text-center text-sm border-r">5</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">5</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Recognition and Rewards') }}</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Professional Development') }}</td>
                        <td class="px-6 py-3 text-xs">{{ t('Training and Development') }}</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">{{ t('Engineering') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">2</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Recognition and Rewards') }}</td>
                        <td class="px-6 py-3 text-xs border-r">{{ t('Professional Development') }}</td>
                        <td class="px-6 py-3 text-xs">{{ t('Employee Support') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Operations') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm font-bold border-r">1</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Human Resources') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">20</td>
                        <td class="px-3 py-3 text-center text-sm border-r">15</td>
                        <td class="px-3 py-3 text-center text-sm border-r">5</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Materials') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Manufacturing') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">6</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">4</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Finance') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">4</td>
                        <td class="px-3 py-3 text-center text-sm border-r">3</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Administration') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Continuous Improvement') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Programs') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">{{ t('Systems') }}</td>
                        <td class="px-3 py-3 text-center text-sm border-r">3</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">{{ t('No critical factors were listed') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<!-- Analysis by Position -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ t('Analysis by Position') }}</h2>
            <div class="overflow-x-auto shadow-md rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 border-collapse">
                    <thead class="bg-slate-800 text-white sticky top-0 z-10">
                        <tr>
                            <th scope="col" rowspan="2"
                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider min-w-[150px] border-r border-slate-700">
                                {{ t('Position') }}
                            </th>
                            <th scope="col" rowspan="2"
                                class="px-3 py-4 text-center text-xs font-bold uppercase tracking-wider border-r border-slate-700">
                                {{ t('Total Workers') }}
                            </th>
                            <th colspan="4"
                                class="px-3 py-2 text-center text-xs font-bold uppercase tracking-wider bg-slate-800 border-b border-slate-700">
                                {{ t('Satisfaction Level') }}
                            </th>
                            <th scope="col" rowspan="2"
                                class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider bg-red-600 text-white border-l border-r border-red-500">
                                {{ t('Unsatisfactory Level (Total)') }}
                            </th>
                            <th colspan="3"
                                class="px-6 py-2 text-center text-xs font-bold uppercase tracking-wider bg-slate-800 border-b border-slate-700 min-w-[450px]">
                                {{ t('Most Critical Factors') }}
                            </th>
                        </tr>
                        <tr class="bg-slate-600 text-white">
                            <th scope="col"
                                class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                                {{ t('Strongly Agree') }}</th>
                            <th scope="col"
                                class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                                {{ t('Agree') }}</th>
                            <th scope="col"
                                class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                                {{ t('Disagree') }}</th>
                            <th scope="col"
                                class="px-3 py-2 text-center text-[10px] font-semibold border-r border-slate-500 uppercase">
                                {{ t('Strongly Disagree') }}</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold border-r border-slate-500">1</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold border-r border-slate-500">2</th>
                        <th scope="col" class="px-3 py-2 text-center text-xs font-bold">3</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200 text-gray-800">
                    <tr class="bg-yellow-50 hover:bg-yellow-100 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold border-r">Producción</td>
                        <td class="px-3 py-4 text-center text-sm font-semibold border-r">2149</td>
                        <td class="px-3 py-4 text-center text-sm border-r">276</td>
                        <td class="px-3 py-4 text-center text-sm border-r">1347</td>
                        <td class="px-3 py-4 text-center text-sm border-r">497</td>
                        <td class="px-3 py-4 text-center text-sm border-r">29</td>
                        <td class="px-3 py-4 text-center text-sm font-black text-red-700 bg-red-50 border-r">526</td>
                        <td class="px-6 py-4 text-xs border-r">Equilibrio entre vida laboral y personal</td>
                        <td class="px-6 py-4 text-xs border-r">Reconocimiento y recompensa</td>
                        <td class="px-6 py-4 text-xs">Avance profesional</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">Materiales</td>
                        <td class="px-3 py-3 text-center text-sm border-r">54</td>
                        <td class="px-3 py-3 text-center text-sm border-r">19</td>
                        <td class="px-3 py-3 text-center text-sm border-r">25</td>
                        <td class="px-3 py-3 text-center text-sm border-r">8</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">10</td>
                        <td class="px-6 py-3 text-xs border-r">Apoyo al empleado</td>
                        <td class="px-6 py-3 text-xs border-r">Equilibrio entre vida laboral y personal</td>
                        <td class="px-6 py-3 text-xs">Capacitación y desarrollo</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">Mantenimiento</td>
                        <td class="px-3 py-3 text-center text-sm border-r">57</td>
                        <td class="px-3 py-3 text-center text-sm border-r">13</td>
                        <td class="px-3 py-3 text-center text-sm border-r">35</td>
                        <td class="px-3 py-3 text-center text-sm border-r">8</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">9</td>
                        <td class="px-6 py-3 text-xs border-r">Avance profesional</td>
                        <td class="px-6 py-3 text-xs border-r">Apoyo al empleado</td>
                        <td class="px-6 py-3 text-xs">Equilibrio entre vida laboral y personal</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">Calidad</td>
                        <td class="px-3 py-3 text-center text-sm border-r">37</td>
                        <td class="px-3 py-3 text-center text-sm border-r">12</td>
                        <td class="px-3 py-3 text-center text-sm border-r">20</td>
                        <td class="px-3 py-3 text-center text-sm border-r">5</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">5</td>
                        <td class="px-6 py-3 text-xs border-r">Reconocimiento y recompensa</td>
                        <td class="px-6 py-3 text-xs border-r">Avance profesional</td>
                        <td class="px-6 py-3 text-xs">Capacitación y desarrollo</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-medium border-r">Ingeniería</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-red-600 border-r">2</td>
                        <td class="px-6 py-3 text-xs border-r">Reconocimiento y recompensa</td>
                        <td class="px-6 py-3 text-xs border-r">Avance profesional</td>
                        <td class="px-6 py-3 text-xs">Apoyo al empleado</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Operaciones</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm font-bold border-r">1</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Recursos Humanos</td>
                        <td class="px-3 py-3 text-center text-sm border-r">20</td>
                        <td class="px-3 py-3 text-center text-sm border-r">15</td>
                        <td class="px-3 py-3 text-center text-sm border-r">5</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Materiales</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Manufactura</td>
                        <td class="px-3 py-3 text-center text-sm border-r">6</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">4</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Finanzas</td>
                        <td class="px-3 py-3 text-center text-sm border-r">4</td>
                        <td class="px-3 py-3 text-center text-sm border-r">3</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Administración</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Mejora continua</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Programas</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm border-r">Sistemas</td>
                        <td class="px-3 py-3 text-center text-sm border-r">3</td>
                        <td class="px-3 py-3 text-center text-sm border-r">2</td>
                        <td class="px-3 py-3 text-center text-sm border-r">1</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm border-r">0</td>
                        <td class="px-3 py-3 text-center text-sm font-bold text-gray-400 border-r">0</td>
                        <td class="px-6 py-3 text-xs text-center text-gray-400 bg-gray-100" colspan="3">No se listaron
                            factores críticos</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useTranslations } from '@/Composables/useTranslations';
const { t } = useTranslations();
</script>