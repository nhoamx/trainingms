<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ t('Management of Unsatisfactory Levels') }}</h2>
        </div>

        <!-- Risk Factors and Recommendations -->
        <div class="space-y-6">
            <table class="w-full table-fixed border-collapse">
                <thead>
                    <tr>
                        <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                            {{ t('Factor') }}
                        </th>
                        <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                            {{ t('Action') }}
                        </th>
                        <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                            {{ t('Department') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white text-justify justify-center">
                    <!-- Entorno Laboral Seguro -->
                    <tr class="">
                        <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                            <span class="font-bold text-lg">{{ t('Safe Work Environment') }}</span>
                            <p>
                                {{ t('Safe Work Environment Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Safe Work Environment Action 1') }}</li>
                                <li>{{ t('Safe Work Environment Action 2') }}</li>
                                <li>{{ t('Safe Work Environment Action 3') }}</li>
                                <li>{{ t('Safe Work Environment Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Safe Work Environment HR')"></span>
                        </td>
                    </tr>

                    <!-- Seguridad Laboral -->
                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Job Security') }}</span>
                            <p>
                                {{ t('Job Security Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Job Security Action 1') }}</li>
                                <li>{{ t('Job Security Action 2') }}</li>
                                <li>{{ t('Job Security Action 3') }}</li>
                                <li>{{ t('Job Security Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Job Security Department')"></span>
                        </td>
                    </tr>

                    <!-- Compensación Justa -->
                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Fair Compensation') }}</span>
                            <p>
                                {{ t('Fair Compensation Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Fair Compensation Action 1') }}</li>
                                <li>{{ t('Fair Compensation Action 2') }}</li>
                                <li>{{ t('Fair Compensation Action 3') }}</li>
                                <li>{{ t('Fair Compensation Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Fair Compensation Department')"></span>
                        </td>
                    </tr>

                    <!-- Comunicación Abierta -->
                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Open Communication') }}</span>
                            <p>
                                {{ t('Open Communication Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Open Communication Action 1') }}</li>
                                <li>{{ t('Open Communication Action 2') }}</li>
                                <li>{{ t('Open Communication Action 3') }}</li>
                                <li>{{ t('Open Communication Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Open Communication Department')"></span>
                        </td>
                    </tr>

                    <!-- Participación de los Empleados -->
                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Employee Participation') }}</span>
                            <p>
                                {{ t('Employee Participation Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Employee Participation Action 1') }}</li>
                                <li>{{ t('Employee Participation Action 2') }}</li>
                                <li>{{ t('Employee Participation Action 3') }}</li>
                                <li>{{ t('Employee Participation Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Employee Participation Department')"></span>
                        </td>
                    </tr>

                    <!-- Reconocimiento y Recompensa -->
                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Recognition and Reward') }}</span>
                            <p>
                                {{ t('Recognition and Reward Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>{{ t('Recognition and Reward Action 1') }}</li>
                                <li>{{ t('Recognition and Reward Action 2') }}</li>
                                <li>{{ t('Recognition and Reward Action 3') }}</li>
                                <li>{{ t('Recognition and Reward Action 4') }}</li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Recognition and Reward Department')"></span>
                        </td>
                    </tr>


                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Training and Development') }}</span>
                            <p>
                                {{ t('Training and Development Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>
                                    {{ t('Training and Development Action 1') }}
                                </li>
                                <li>
                                    {{ t('Training and Development Action 2') }}
                                </li>
                                <li>
                                    {{ t('Training and Development Action 3') }}
                                </li>
                                <li>
                                    {{ t('Training and Development Action 4') }}
                                </li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Training and Development Department')"></span>
                        </td>
                    </tr>

                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Work-Life Balance') }}</span>
                            <p>
                                {{ t('Work-Life Balance Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>
                                    {{ t('Work-Life Balance Action 1') }}
                                </li>
                                <li>
                                    {{ t('Work-Life Balance Action 2') }}
                                </li>
                                <li>
                                    {{ t('Work-Life Balance Action 3') }}
                                </li>
                                <li>
                                    {{ t('Work-Life Balance Action 4') }}
                                </li>
                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Work-Life Balance Department')"></span>
                        </td>
                    </tr>

                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Professional Advancement') }}</span>
                            <p>
                                {{ t('Professional Advancement Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>
                                    {{ t('Professional Advancement Action 1') }}
                                </li>
                                <li>
                                    {{ t('Professional Advancement Action 2') }}
                                </li>
                                <li>
                                    {{ t('Professional Advancement Action 3') }}
                                </li>
                                <li>
                                    {{ t('Professional Advancement Action 4') }}
                                </li>

                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Professional Advancement Department')"></span>
                        </td>
                    </tr>


                    <tr>
                        <td class="border-b border-gray-100 p-4 text-gray-800 text-justify">
                            <span class="font-bold text-lg">{{ t('Employee Support') }}</span>
                            <p>
                                {{ t('Employee Support Description') }}
                            </p>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-800 ">
                            <ul class="list-decimal list-inside space-y-2 marker:font-bold">
                                <li>
                                    {{ t('Employee Support Action 1') }}
                                </li>
                                <li>
                                    {{ t('Employee Support Action 2') }}
                                </li>
                                <li>
                                    {{ t('Employee Support Action 3') }}
                                </li>
                                <li>
                                    {{ t('Employee Support Action 4') }}
                                </li>

                            </ul>
                        </td>
                        <td class="border-b border-gray-100 p-4 text-gray-500 ">
                            <span v-html="t('Employee Support Department')"></span>
                        </td>
                    </tr>
                </tbody>

            </table>
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

        <div class="space-y-6">
            <div class="max-w-7xl mx-auto shadow-lg rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-800 text-white sticky top-0">
                            <tr>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[200px]">
                                    {{ t('Factors').toUpperCase() }}
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">
                                    {{ t('Workers').toUpperCase() }}
                                </th>
                                <th colspan="3"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider bg-green-700">
                                    {{ t('Satisfactory Level').toUpperCase() }}
                                </th>
                                <th colspan="2"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider bg-red-600">
                                    {{ t('Unsatisfactory Level').toUpperCase() }}
                                </th>
                                <th colspan="4"
                                    class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider min-w-[500px] bg-slate-800">
                                    {{ t('Recommended Actions').toUpperCase() }}
                                </th>
                            </tr>
                            <tr class="bg-slate-500 text-white">
                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium"></th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium"></th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Totally Agree') }}
                                </th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Agree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Total') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Disagree') }}</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Totally Disagree') }}
                                </th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">1</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">2</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">3</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-medium">4</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-gray-800">

                            <tr class="bg-yellow-50">
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Recognition and Reward') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">523</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">620</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1143</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1549</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">810
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">{{ t('Recognition and Reward Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">{{ t('Recognition and Reward Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">{{ t('Recognition and Reward Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">{{ t('Recognition and Reward Action 4') }}</td>
                            </tr>

                            <tr class="bg-yellow-50">
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Training and Development')
                                }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">592</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">777</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1369</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1445</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">588
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Training and Development Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Training and Development Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Training and Development Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Training and Development Action 4') }}</td>
                            </tr>

                            <tr class="bg-yellow-50">
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Work-Life Balance') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">628</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">777</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1405</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1370</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">727
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Work-Life Balance Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Work-Life Balance Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Work-Life Balance Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Work-Life Balance Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Employee Support') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">560</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">984</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1544</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1171</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">767
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Support Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Support Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Support Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Support Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Professional Advancement') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">705</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">910</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1615</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1380</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">507
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Professional Advancement Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Professional Advancement Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Professional Advancement Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Professional Advancement Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Employee Participation') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">715</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1185</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1900</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1013</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">589
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Participation Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Participation Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Employee Participation Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm"></td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Safe Work Environment') }}.</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1048</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">943</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    1991</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1296</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">215
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Safe Work Environment Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Safe Work Environment Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Safe Work Environment Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Safe Work Environment Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Fair Compensation') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">756</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1350</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    2106</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">935</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">461
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Fair Compensation Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Fair Compensation Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Fair Compensation Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Fair Compensation Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Job Security') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1308</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1045</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    2353</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">979</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">170
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Job Security Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Job Security Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Job Security Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Job Security Action 4') }}</td>
                            </tr>

                            <tr>
                                <td class="px-3 py-3 whitespace-normal text-sm font-medium">{{ t('Open Communication') }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">3502</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1841</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1246</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                    3087</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center">380</td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">35
                                </td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Open Communication Action 1') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Open Communication Action 2') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Open Communication Action 3') }}</td>
                                <td class="px-3 py-3 whitespace-normal text-xs">{{ t('Open Communication Action 4') }}</td>
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
