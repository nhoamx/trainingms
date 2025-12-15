<template>
    <div class="space-y-8">
        <!-- Análisis por Departamento -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ t('Analysis by Department')}}</h2>
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
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[80px]">{{
                                    t('Agree') }}</th>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold">{{ t('Production') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Human Resources') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Maintenance') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Quality') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Materials') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Materials') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Manufacturing') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Finance') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Import / Export') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Engineering') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Administration') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Management') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Continuous Improvement') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Programs') }}</td>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Systems') }}</td>
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

        <!-- Análisis por Puesto -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ t('Analysis by Position')}}</h2>
        </div>

        <div class="space-y-6 ">
            <div class="max-w-7xl mx-auto shadow-xl rounded-lg overflow-hidden mb-24">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-800 text-white sticky top-0">
                            <tr>
                                <th scope="col" rowspan="2"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[150px]">
                                    {{ t('Position').toUpperCase() }}
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
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium min-w-[80px]">{{
                                    t('Agree') }}</th>
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold">{{ t('Sewing Operator') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">2309</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">306</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1300</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">647</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">56</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-bold text-red-700">
                                    703</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Work-Life Balance') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Utility') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">347</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">46</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">191</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">100</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">110</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Work-Life Balance') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Inspector') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">271</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">24</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">167</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">74</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">6</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">80</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Short-Run Operator') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">126</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">21</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">63</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">35</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">42</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Leader') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">130</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">21</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">86</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">22</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">23</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Work-Life Balance') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Mechanic') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">40</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">22</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">13</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">14</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Professional Advancement') }}</td>
                            </tr>

                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Flexible') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">28</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">13</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">11</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Recognition and Reward') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Training and Development') }}</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs">{{ t('Employee Support') }}</td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Materialista') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">23</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">11</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">8</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">8</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Clerk') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">15</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">4</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Engineer') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">34</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">8</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">22</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">4</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Supervisor') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">35</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">7</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">24</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">4</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Forklift Operator') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">20</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">6</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">11</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">3</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Superintendent') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">28</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">17</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">2</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Analyst') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">6</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">1</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Planner') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">1</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Administrative') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Generalist') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Manager') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">19</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">9</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">10</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Others') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('Programmer') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">4</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">2</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 whitespace-nowrap text-sm">{{ t('QC Technician') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">0</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center font-semibold">0</td>
                                <td class="px-6 py-3 whitespace-nowrap text-xs italic text-gray-500" colspan="3">No se listaron factores críticos</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>
<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';
const { t } = useTranslations();
</script>