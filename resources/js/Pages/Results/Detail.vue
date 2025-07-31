<template>
    <Dashboard>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Botón de navegación -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('dashboard')"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Volver al Dashboard
                        </Link>
                    </div>
                </div>

                <div class="mt-4 text-gray-600">
                    <p>Folio: {{ evaluation.folio }}</p>
                    <p>Fecha: {{ evaluation.created_at }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="currentTab = tab.key"
                            :class="[
                                currentTab === tab.key
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Contenido de los tabs -->
                <div class="p-6">
                    <!-- Tab de Resumen -->
                    <div v-if="currentTab === 'summary'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Calificación Final -->
                <div class="bg-white p-6 rounded-lg shadow flex flex-col justify-center items-center h-full">
                    <h3 class="text-4xl text-center font-semibold text-gray-900 mb-4">Calificación Final</h3>
                    <div class="text-5xl font-bold text-blue-600">
                        {{ totalScore }}
                    </div>
                </div>

                <!-- Categorías -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Categorías</h3>
                    <div class="space-y-2">
                        <div v-for="category in categoryScores" :key="category.name"
                             class="flex justify-between items-center">
                            <span class="text-gray-700">{{ category.name }}:</span>
                            <span class="font-semibold">{{ category.score }}</span>
                        </div>
                    </div>
                </div>

                <!-- Dominios -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Dominios</h3>
                    <div class="space-y-2">
                        <div v-for="domain in domainScores" :key="domain.name"
                             class="flex justify-between items-center">
                                        <span class="text-gray-700 pr-2">{{ domain.name }}:</span>
                            <span class="font-semibold">{{ domain.score }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla general de resultados -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Categoría
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Dominio
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Dimensión
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-center border border-gray-200 text-xs font-medium text-gray-500 uppercase tracking-wider align-middle">
                                        Puntaje
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template v-for="(category, categoryIndex) in groupedResults" :key="categoryIndex">
                                    <template v-for="(domain, domainIndex) in category.domains" :key="domainIndex">
                                        <template v-for="(dimension, dimensionIndex) in domain.dimensions" :key="dimensionIndex">
                                            <tr>
                                                <!-- Categoría -->
                                                <td v-if="domainIndex === 0 && dimensionIndex === 0"
                                                    :rowspan="category.rowspan"
                                                    class="px-6 py-4 align-middle border border-gray-200">
                                                    <div class="flex flex-col items-center justify-center h-full">
                                                        <div class="font-medium text-center">{{ category.name }}</div>
                                                        <div class="text-sm text-gray-500 text-center">
                                                            Puntaje: {{ category.score }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Dominio -->
                                                <td v-if="dimensionIndex === 0"
                                                    :rowspan="domain.rowspan"
                                                    class="px-6 py-4 align-middle border border-gray-200">
                                                    <div class="flex flex-col items-center justify-center h-full">
                                                        <div class="font-medium text-center">{{ domain.name }}</div>
                                                        <div class="text-sm text-gray-500 text-center">
                                                            Puntaje: {{ domain.score }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Dimensión -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    {{ dimension.name }}
                                                </td>

                                                <!-- Items -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    <div class="flex flex-wrap justify-center gap-2">
                                                        <span
                                                            v-for="(item, index) in dimension.items"
                                                            :key="index"
                                                            class="cursor-help relative group"
                                                        >
                                                            {{ item }}
                                                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-sm rounded-lg p-2 min-w-[200px] z-50 left-1/2 transform -translate-x-1/2">
                                                                <p class="font-bold mb-1">Pregunta:</p>
                                                                <p class="mb-2">{{ item }}</p>
                                                                <p class="font-bold mb-1">Respuesta:</p>
                                                                <p class="mb-2">{{ dimension.respuestas[index] }}</p>
                                                                <p class="font-bold mb-1">Puntaje:</p>
                                                                <p>{{ dimension.itemScores[index] }}</p>
                                                                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                                                                    <div class="border-8 border-transparent border-t-gray-800"></div>
                                                                </div>
                                                            </div>
                                                            {{ index < dimension.items.length - 1 ? ',' : '' }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- Puntaje -->
                                                <td class="px-6 py-4 align-middle border border-gray-200 text-center">
                                                    {{ dimension.score }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </template>
                            </tbody>
                    </table>
                </div>
            </div>

                    <!-- Tab de Interpretaciones -->
                    <div v-if="currentTab === 'interpretations'" class="space-y-6">
            <!-- Gráficas de resultados -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Visualización de Resultados</h3>
                <div class="grid grid-cols-1 gap-6">
                    <!-- Gráfica de calificación final -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Calificación Final</h4>
                        <div class="relative pt-2">
                            <div class="flex items-center mb-6">
                                <div class="w-32 text-sm font-medium text-gray-700">Total</div>
                                <div class="flex-1 h-8">
                                    <div class="h-full flex items-center">
                                        <div
                                            class="h-full transition-all duration-500 ease-in-out rounded-r-sm flex items-center"
                                            :class="getScoreColorClass(totalScore)"
                                            :style="{ width: `${(totalScore / 200) * 100}%` }"
                                        >
                                            <span class="px-2 text-white font-bold">{{ totalScore }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Escala de interpretación -->
                            <div class="mt-4 flex justify-between text-xs text-gray-500">
                                <div class="text-center">
                                    <div class="h-3 w-3 bg-green-500 inline-block"></div>
                                    <div>Nulo (&lt;50)</div>
                                </div>
                                <div class="text-center">
                                    <div class="h-3 w-3 bg-yellow-500 inline-block"></div>
                                    <div>Bajo (50-74)</div>
                                </div>
                                <div class="text-center">
                                    <div class="h-3 w-3 bg-orange-500 inline-block"></div>
                                    <div>Medio (75-98)</div>
                                </div>
                                <div class="text-center">
                                    <div class="h-3 w-3 bg-red-500 inline-block"></div>
                                    <div>Alto (99-139)</div>
                                </div>
                                <div class="text-center">
                                    <div class="h-3 w-3 bg-red-600 inline-block"></div>
                                    <div>Muy alto (≥140)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de categorías -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Puntaje por Categoría</h4>
                        <div class="space-y-6">
                            <div v-for="category in categoryScores" :key="category.name" class="flex items-center">
                                <div class="w-56 pr-4 text-sm font-medium text-gray-700 truncate">{{ category.name }}</div>
                                <div class="flex-1 h-6">
                                    <div class="h-full flex items-center">
                                        <div
                                            class="h-full transition-all duration-500 ease-in-out rounded-r-sm flex items-center"
                                            :class="getCategoryBarClass(category.name, category.score)"
                                            :style="{ width: `${(category.score / getCategoryMaxScore(category.name)) * 100}%` }"
                                        >
                                            <span class="px-2 text-white text-xs font-bold">{{ category.score }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de dominios -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Puntaje por Dominio</h4>
                        <div class="space-y-4">
                            <div v-for="domain in domainScores" :key="domain.name" class="flex items-center">
                                <div class="w-56 pr-4 text-sm font-medium text-gray-700 truncate">{{ domain.name }}</div>
                                <div class="flex-1 h-6">
                                    <div class="h-full flex items-center">
                                        <div
                                            class="h-full transition-all duration-500 ease-in-out rounded-r-sm flex items-center"
                                            :class="getDomainBarClass(domain.name, domain.score)"
                                            :style="{ width: `${(domain.score / getDomainMaxScore(domain.name)) * 100}%` }"
                                        >
                                            <span class="px-2 text-white text-xs font-bold">{{ domain.score }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de interpretación final -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Interpretación de Resultados Finales</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Resultado del cuestionario
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            Calificación final
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-green-100': totalScore < 50}">
                                            C_final < 50
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-yellow-100': totalScore >= 50 && totalScore < 75}">
                                            50 ≤ C_final < 75
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-orange-100': totalScore >= 75 && totalScore < 99}">
                                            75 ≤ C_final < 99
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-red-100': totalScore >= 99 && totalScore < 140}">
                                            99 ≤ C_final < 140
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="{'bg-red-200': totalScore >= 140}">
                                            C_final ≥ 140
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de interpretación por categorías -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Interpretación por Categorías</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Categoría
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="category in categoryScores" :key="category.name">
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            {{ category.name }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'nulo')">
                                            {{ getCategoryRangeText(category.name, 'nulo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'bajo')">
                                            {{ getCategoryRangeText(category.name, 'bajo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'medio')">
                                            {{ getCategoryRangeText(category.name, 'medio') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'alto')">
                                            {{ getCategoryRangeText(category.name, 'alto') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getCategoryColorClass(category.name, category.score, 'muy_alto')">
                                            {{ getCategoryRangeText(category.name, 'muy_alto') }}
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de interpretación por dominios -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Interpretación por Dominios</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Dominio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Nulo o despreciable
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Bajo
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Medio
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Alto
                                        </th>
                                        <th class="px-6 py-3 text-center border border-gray-200 bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                                            Muy alto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="domain in domainScores" :key="domain.name">
                                        <td class="px-6 py-4 text-center border border-gray-200 font-medium">
                                            {{ domain.name }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'nulo')">
                                            {{ getDomainRangeText(domain.name, 'nulo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'bajo')">
                                            {{ getDomainRangeText(domain.name, 'bajo') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'medio')">
                                            {{ getDomainRangeText(domain.name, 'medio') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'alto')">
                                            {{ getDomainRangeText(domain.name, 'alto') }}
                                        </td>
                                        <td class="px-6 py-4 text-center border border-gray-200"
                                            :class="getDomainColorClass(domain.name, domain.score, 'muy_alto')">
                                            {{ getDomainRangeText(domain.name, 'muy_alto') }}
                                        </td>
                                    </tr>
                                </tbody>
                    </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab de Guía I -->
                    <div v-if="currentTab === 'guide_i'" class="space-y-4">
                        <div v-if="guideIResults" class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Fecha:</span>
                                <span class="font-medium">{{ guideIResults.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-gray-600">Folio:</span>
                                <span class="font-medium">{{ guideIResults.folio }}</span>
                            </div>

                            <!-- Iteración por categorías -->
                            <div v-for="(category, categoryKey) in guideICategories" :key="categoryKey" class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ category.title }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="questionKey in category.questions" :key="questionKey" class="hover:bg-gray-50">
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ guideIQuestions[questionKey] }}</td>
                                                <td class="px-4 py-2 text-sm text-center font-medium"
                                                    :class="guideIResults.answers[questionKey] === 'si' ? 'text-green-600' : 'text-red-600'">
                                                    {{ guideIResults.answers[questionKey]?.toUpperCase() }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            No hay resultados disponibles para la Guía I
                        </div>
                    </div>

                    <!-- Tab de Guía III -->
                    <div v-if="currentTab === 'guide_iii'" class="space-y-4">
                        <div v-if="guideIIIResults" class="space-y-4">
                            <!-- Información del encabezado -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Fecha:</span>
                                <span class="font-medium">{{ guideIIIResults.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-gray-600">Folio:</span>
                                <span class="font-medium">{{ guideIIIResults.folio }}</span>
                            </div>
                            
                            <!-- Preguntas y respuestas -->
                            <div v-if="guideIIIResults.questions && guideIIIResults.questions.length" class="mb-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900">Preguntas y respuestas de la Guía de Referencia III</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Respuesta</th>
                                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(q, idx) in guideIIIResults.questions" :key="idx" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-pre-line text-sm text-gray-900">{{ q.question }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <div v-if="editingQuestionIdx === idx && currentEditingGuide === 'III'" class="flex items-center space-x-2">
                                                        <textarea 
                                                            v-model="editingAnswer" 
                                                            class="border border-gray-300 rounded px-3 py-2 w-full"
                                                            rows="3"
                                                        ></textarea>
                                                    </div>
                                                    <div v-else>{{ q.answer || 'Sin Respuesta' }}</div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-right space-x-2 text-center">
                                                    <div v-if="editingQuestionIdx === idx && currentEditingGuide === 'III'" class="flex justify-center space-x-2">
                                                        <button 
                                                            @click="saveGuideIIIQuestionEdit(q.id || idx)" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs"
                                                        >
                                                            Guardar
                                                        </button>
                                                        <button 
                                                            @click="cancelQuestionEdit" 
                                                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-2 py-1 rounded text-xs"
                                                        >
                                                            Cancelar
                                                        </button>
                                                    </div>
                                                    <button 
                                                        v-if="canEdit"
                                                        @click="startGuideIIIQuestionEdit(idx, q.answer)" 
                                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs"
                                                    >
                                                        Editar
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            No hay resultados disponibles para la Guía III
                        </div>
                    </div>

                    <!-- Tab de Guía V -->
                    <div v-if="currentTab === 'guide_v'" class="space-y-4">
                        <div v-if="guideVResults" class="space-y-4">
                            <!-- Información del encabezado -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Fecha:</span>
                                <span class="font-medium">{{ guideVResults.created_at }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-gray-600">Folio:</span>
                                <span class="font-medium">{{ guideVResults.folio }}</span>
                            </div>
                            
                            <!-- Preguntas y respuestas (formato antiguo) -->
                            <div v-if="guideVResults.questions && guideVResults.questions.length" class="mb-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900">Preguntas y respuestas de la Guía de Referencia V</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pregunta</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Respuesta</th>
                                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <!-- Ocultar los campos individuales de edad y solo mostrar la edad combinada una vez -->
                                            <template v-for="(q, idx) in guideVResults?.questions || []" :key="idx">
                                                <tr v-if="q && q.question !== 'edad_d2'" class="hover:bg-gray-50">
                                                    <!-- Título especial para edad_d1 (mostramos solo "Edad") -->
                                                    <td class="px-6 py-4 whitespace-pre-line text-sm text-gray-900">
                                                        {{ q.question === 'edad_d1' ? 'Edad' : (guideVQuestions[q.question] || q.question) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        <!-- Caso especial para campos de edad -->
                                                        <div v-if="q.question === 'edad_d1'">
                                                            <div v-if="editingQuestionIdx === idx && currentEditingGuide === 'V'" class="flex items-center space-x-4">
                                                                <!-- Mostrar dos inputs para editar la edad -->
                                                                <div>
                                                                    <label class="block text-sm text-gray-600 mb-1">Decenas:</label>
                                                                    <input 
                                                                        v-model="editingAnswer"
                                                                        type="number" 
                                                                        min="0" 
                                                                        max="9" 
                                                                        class="border border-gray-300 rounded px-3 py-2 w-20"
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <label class="block text-sm text-gray-600 mb-1">Unidades:</label>
                                                                    <input 
                                                                        v-model="editingAnswerUnits"
                                                                        type="number" 
                                                                        min="0" 
                                                                        max="9" 
                                                                        class="border border-gray-300 rounded px-3 py-2 w-20"
                                                                    />
                                                                </div>
                                                            </div>
                                                            <div v-else>
                                                                <!-- Mostrar edad combinada -->
                                                                {{ getCombinedAge(q.answer, getEdad_d2(idx)) }}
                                                            </div>
                                                        </div>
                                                        <!-- Para el resto de preguntas -->
                                                        <div v-else>
                                                            <div v-if="editingQuestionIdx === idx && currentEditingGuide === 'V'" class="flex items-center space-x-2">
                                                                <textarea 
                                                                    v-model="editingAnswer" 
                                                                    class="border border-gray-300 rounded px-3 py-2 w-full"
                                                                    rows="3"
                                                                ></textarea>
                                                            </div>
                                                            <div v-else>{{ guideVAnswers[q.question]?.[q.answer] || q.answer }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-right space-x-2 text-center">
                                                        <div v-if="editingQuestionIdx === idx && currentEditingGuide === 'V'" class="flex justify-center space-x-2">
                                                            <button 
                                                                @click="saveGuideVQuestionEdit(q.id || idx)" 
                                                                class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs"
                                                            >
                                                                Guardar
                                                            </button>
                                                            <button 
                                                                @click="cancelQuestionEdit" 
                                                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-2 py-1 rounded text-xs"
                                                            >
                                                                Cancelar
                                                            </button>
                                                        </div>
                                                        <button 
                                                            v-if="canEdit"
                                                            @click="startGuideVQuestionEdit(idx, q.answer)" 
                                                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs"
                                                        >
                                                            Editar
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 text-center py-4">
                            No hay resultados disponibles para la Guía V
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import Dashboard from "../../Layouts/Dashboard.vue";
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

// Get page props for user access
const page = usePage();
const user = computed(() => page.props.auth.user);

// Check if user has admin or super-admin role
const canEdit = computed(() => {
    return user.value?.roles?.some(role => role.name === 'admin' || role.name === 'super-admin') || false;
});

// Variables reactivas para la edición de preguntas
const editingQuestionIdx = ref(null);
const editingAnswer = ref('');
const editingAnswerUnits = ref(''); // Para el segundo dígito de la edad (unidades)
const currentEditingGuide = ref(null); // 'III' o 'V' dependiendo de qué guía se está editando
const showCombinedAge = ref(true); // Controla si mostrar la edad combinada o los dígitos separados

// Método para iniciar la edición de una pregunta de la Guía V
function startGuideVQuestionEdit(index, answer) {
    editingQuestionIdx.value = index;
    editingAnswer.value = answer || '';
    currentEditingGuide.value = 'V';
}

// Método para iniciar la edición de una pregunta de la Guía III
function startGuideIIIQuestionEdit(index, answer) {
    editingQuestionIdx.value = index;
    editingAnswer.value = answer || '';
    currentEditingGuide.value = 'III';
}

// Método para cancelar la edición
function cancelQuestionEdit() {
    editingQuestionIdx.value = null;
    editingAnswer.value = '';
    currentEditingGuide.value = null;
}

// Método para guardar los cambios a una pregunta de la Guía V
function saveGuideVQuestionEdit(questionId) {
    // Crear un formulario con useForm (siguiendo la documentación de Inertia)
    const form = useForm({
        answer: editingAnswer.value
    });

    // Enviar el formulario con put
    form.put(route('results.guide-v.question.update', {
        evaluation: props.guideVResults?.id,
        question: questionId
    }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Actualizar la respuesta en el estado local
            if (props.guideVResults && props.guideVResults.questions) {
                props.guideVResults.questions[editingQuestionIdx.value].answer = editingAnswer.value;
            }
            
            // Mostrar mensaje de éxito
            alert('Respuesta actualizada correctamente');
            
            // Salir del modo edición
            cancelQuestionEdit();
        },
        onError: (errors) => {
            console.error('Error al actualizar la respuesta:', errors);
            alert('Error al actualizar la respuesta: ' + 
                  (Object.values(errors)[0] || 'Por favor, inténtelo de nuevo.'));
        }
    });
}

// Método para guardar los cambios a una pregunta de la Guía III
function saveGuideIIIQuestionEdit(questionId) {
    // Crear un formulario con useForm (siguiendo la documentación de Inertia)
    const form = useForm({
        answer: editingAnswer.value
    });

    // Enviar el formulario con put
    form.put(route('results.guide-iii.question.update', {
        evaluation: props.guideIIIResults?.id,
        question: questionId
    }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Actualizar la respuesta en el estado local
            if (props.guideIIIResults && props.guideIIIResults.questions) {
                props.guideIIIResults.questions[editingQuestionIdx.value].answer = editingAnswer.value;
            }
            
            // Mostrar mensaje de éxito
            alert('Respuesta actualizada correctamente');
            
            // Salir del modo edición
            cancelQuestionEdit();
        },
        onError: (errors) => {
            console.error('Error al actualizar la respuesta:', errors);
            alert('Error al actualizar la respuesta: ' + 
                  (Object.values(errors)[0] || 'Por favor, inténtelo de nuevo.'));
        }
    });
}

const props = defineProps({
    organization: {
        type: Object,
        required: true
    },
    evaluation: {
        type: Object,
        required: true
    },
    results: {
        type: Array,
        required: true
    },
    guideIResults: {
        type: Object,
        default: null
    },
    guideVResults: {
        type: Object,
        default: null
    },
    guideIIIResults: {
        type: Object,
        default: null
    }
});

const groupedResults = computed(() => {
    const categoriesMap = new Map();

    props.results.forEach(result => {
        const categoryKey = result.categoria.nombre;
        const domainKey = result.dominio.nombre;

        if (!categoriesMap.has(categoryKey)) {
            categoriesMap.set(categoryKey, {
                name: result.categoria.nombre,
                score: result.categoria.puntaje,
                domains: new Map(),
                rowspan: 0
            });
        }

        const category = categoriesMap.get(categoryKey);
        if (!category.domains.has(domainKey)) {
            category.domains.set(domainKey, {
                name: result.dominio.nombre,
                score: result.dominio.puntaje,
                dimensions: new Map(),
                rowspan: 0
            });
        }

        const domain = category.domains.get(domainKey);
        const dimensionKey = result.dimension;

        if (!domain.dimensions.has(dimensionKey)) {
            domain.dimensions.set(dimensionKey, {
                name: result.dimension,
                items: [],
                respuestas: [],
                itemScores: [],
                score: 0
            });
        }

        const dimension = domain.dimensions.get(dimensionKey);
        dimension.items.push(result.item);
        dimension.respuestas.push(result.respuesta);
        dimension.itemScores.push(result.puntaje);
    });

    // Calcular scores y convertir Maps a arrays
    return Array.from(categoriesMap.values()).map(category => {
        const domains = Array.from(category.domains.values()).map(domain => {
            const dimensions = Array.from(domain.dimensions.values()).map(dimension => {
                // Calcular score de dimensión
                dimension.score = dimension.itemScores.reduce((sum, score) => sum + score, 0);
                return dimension;
            });

            // Calcular rowspan y score del dominio
            domain.rowspan = dimensions.length;
            domain.score = dimensions.reduce((sum, dim) => sum + dim.score, 0);

            return {
                ...domain,
                dimensions
            };
        });

        // Calcular rowspan y score de la categoría
        category.rowspan = domains.reduce((sum, domain) => sum + domain.rowspan, 0);
        category.score = domains.reduce((sum, domain) => sum + domain.score, 0);

        return {
            ...category,
            domains
        };
    });
});

const categoryScores = computed(() => {
    return groupedResults.value.map(category => ({
        name: category.name,
        score: category.score
    }));
});

const domainScores = computed(() => {
    return groupedResults.value.flatMap(category =>
        category.domains.map(domain => ({
            name: domain.name,
            score: domain.score
        }))
    );
});

const totalScore = computed(() => {
    return groupedResults.value.reduce((total, category) => total + category.score, 0);
});

const categoryRanges = {
    'Ambiente de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 9 },
        medio: { min: 9, max: 11 },
        alto: { min: 11, max: 14 },
        muy_alto: { min: 14 }
    },
    'Factores propios de la actividad': {
        nulo: { max: 15 },
        bajo: { min: 15, max: 30 },
        medio: { min: 30, max: 45 },
        alto: { min: 45, max: 60 },
        muy_alto: { min: 60 }
    },
    'Organización del tiempo de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 7 },
        medio: { min: 7, max: 10 },
        alto: { min: 10, max: 13 },
        muy_alto: { min: 13 }
    },
    'Liderazgo y relaciones en el trabajo': {
        nulo: { max: 14 },
        bajo: { min: 14, max: 29 },
        medio: { min: 29, max: 42 },
        alto: { min: 42, max: 58 },
        muy_alto: { min: 58 }
    },
    'Entorno organizacional': {
        nulo: { max: 10 },
        bajo: { min: 10, max: 14 },
        medio: { min: 14, max: 18 },
        alto: { min: 18, max: 23 },
        muy_alto: { min: 23 }
    }
};

const getCategoryRangeText = (categoryName, level) => {
    const ranges = categoryRanges[categoryName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    if (level === 'nulo') {
        return `C_cat < ${range.max}`;
    } else if (level === 'muy_alto') {
        return `C_cat ≥ ${range.min}`;
    } else {
        return `${range.min} ≤ C_cat < ${range.max}`;
    }
};

const getCategoryColorClass = (categoryName, score, level) => {
    const ranges = categoryRanges[categoryName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    const isInRange = (score) => {
        if (level === 'nulo') {
            return score < range.max;
        } else if (level === 'muy_alto') {
            return score >= range.min;
        } else {
            return score >= range.min && score < range.max;
        }
    };

    return {
        'bg-green-100': level === 'nulo' && isInRange(score),
        'bg-yellow-100': level === 'bajo' && isInRange(score),
        'bg-orange-100': level === 'medio' && isInRange(score),
        'bg-red-100': level === 'alto' && isInRange(score),
        'bg-red-200': level === 'muy_alto' && isInRange(score)
    };
};

const domainRanges = {
    'Condiciones en el ambiente de trabajo': {
        nulo: { max: 5 },
        bajo: { min: 5, max: 9 },
        medio: { min: 9, max: 11 },
        alto: { min: 11, max: 14 },
        muy_alto: { min: 14 }
    },
    'Carga de trabajo': {
        nulo: { max: 15 },
        bajo: { min: 15, max: 21 },
        medio: { min: 21, max: 27 },
        alto: { min: 27, max: 37 },
        muy_alto: { min: 37 }
    },
    'Falta de control sobre el trabajo': {
        nulo: { max: 11 },
        bajo: { min: 11, max: 16 },
        medio: { min: 16, max: 21 },
        alto: { min: 21, max: 25 },
        muy_alto: { min: 25 }
    },
    'Jornada de trabajo': {
        nulo: { max: 1 },
        bajo: { min: 1, max: 2 },
        medio: { min: 2, max: 4 },
        alto: { min: 4, max: 6 },
        muy_alto: { min: 6 }
    },
    'Interferencia en la relación trabajo-familia': {
        nulo: { max: 4 },
        bajo: { min: 4, max: 6 },
        medio: { min: 6, max: 8 },
        alto: { min: 8, max: 10 },
        muy_alto: { min: 10 }
    },
    'Liderazgo': {
        nulo: { max: 9 },
        bajo: { min: 9, max: 12 },
        medio: { min: 12, max: 16 },
        alto: { min: 16, max: 20 },
        muy_alto: { min: 20 }
    },
    'Relaciones en el trabajo': {
        nulo: { max: 10 },
        bajo: { min: 10, max: 13 },
        medio: { min: 13, max: 17 },
        alto: { min: 17, max: 21 },
        muy_alto: { min: 21 }
    },
    'Violencia': {
        nulo: { max: 7 },
        bajo: { min: 7, max: 10 },
        medio: { min: 10, max: 13 },
        alto: { min: 13, max: 16 },
        muy_alto: { min: 16 }
    },
    'Reconocimiento del desempeño': {
        nulo: { max: 6 },
        bajo: { min: 6, max: 10 },
        medio: { min: 10, max: 14 },
        alto: { min: 14, max: 18 },
        muy_alto: { min: 18 }
    },
    'Insuficiente sentido de pertenencia e inestabilidad': {
        nulo: { max: 4 },
        bajo: { min: 4, max: 6 },
        medio: { min: 6, max: 8 },
        alto: { min: 8, max: 10 },
        muy_alto: { min: 10 }
    }
};

const getDomainRangeText = (domainName, level) => {
    const ranges = domainRanges[domainName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    if (level === 'nulo') {
        return `C_dom < ${range.max}`;
    } else if (level === 'muy_alto') {
        return `C_dom ≥ ${range.min}`;
    } else {
        return `${range.min} ≤ C_dom < ${range.max}`;
    }
};

const getDomainColorClass = (domainName, score, level) => {
    const ranges = domainRanges[domainName];
    if (!ranges) return '';

    const range = ranges[level];
    if (!range) return '';

    const isInRange = (score) => {
        if (level === 'nulo') {
            return score < range.max;
        } else if (level === 'muy_alto') {
            return score >= range.min;
        } else {
            return score >= range.min && score < range.max;
        }
    };

    return {
        'bg-green-100': level === 'nulo' && isInRange(score),
        'bg-yellow-100': level === 'bajo' && isInRange(score),
        'bg-orange-100': level === 'medio' && isInRange(score),
        'bg-red-100': level === 'alto' && isInRange(score),
        'bg-red-200': level === 'muy_alto' && isInRange(score)
    };
};

// Mapeo de valores para la Guía V
const guideVLabels = {
    sexo: {
        label: 'Sexo',
        values: {
            masculino: 'Masculino',
            femenino: 'Femenino'
        }
    },
    estado_civil: {
        label: 'Estado Civil',
        values: {
            soltero: 'Soltero(a)',
            casado: 'Casado(a)',
            union_libre: 'Unión libre',
            divorciado: 'Divorciado(a)',
            viudo: 'Viudo(a)'
        }
    },
    tipo_puesto: {
        label: 'Tipo de Puesto',
        values: {
            operativo: 'Operativo',
            profesional: 'Profesional/Técnico',
            supervisor: 'Supervisor',
            gerente: 'Gerente'
        }
    },
    tipo_contratacion: {
        label: 'Tipo de Contratación',
        values: {
            indeterminado: 'Por tiempo indeterminado',
            determinado: 'Por tiempo determinado',
            honorarios: 'Por honorarios',
            subcontratacion: 'Por subcontratación'
        }
    },
    tipo_personal: {
        label: 'Tipo de Personal',
        values: {
            sindicalizado: 'Sindicalizado',
            confianza: 'Confianza',
            ninguno: 'Ninguno'
        }
    },
    tipo_jornada: {
        label: 'Tipo de Jornada',
        values: {
            fijo_diurno: 'Fijo Diurno',
            fijo_nocturno: 'Fijo Nocturno',
            fijo_mixto: 'Fijo Mixto'
        }
    },
    rotacion_turnos: {
        label: 'Rotación de Turnos',
        values: {
            si: 'Sí',
            no: 'No'
        }
    },
    tiempo_puesto_actual: {
        label: 'Tiempo en el Puesto Actual',
        values: {
            'menos-6-meses': 'Menos de 6 meses',
            '6-12_meses': '6 meses a 1 año',
            '1-4-anos': '1 a 4 años',
            '5-9-anos': '5 a 9 años',
            '10-14-anos': '10 a 14 años',
            '15-19-anos': '15 a 19 años',
            '20-24-anos': '20 a 24 años',
            '25-o-mas': '25 años o más'
        }
    },
    ultimo_nivel_estudio: {
        label: 'Último Nivel de Estudios',
        values: {
            primaria_incompleta: 'Primaria incompleta',
            primaria_completa: 'Primaria completa',
            secundaria_incompleta: 'Secundaria incompleta',
            secundaria_completa: 'Secundaria completa',
            preparatoria_incompleta: 'Preparatoria o Bachillerato incompleto',
            preparatoria_completa: 'Preparatoria o Bachillerato completo',
            tecnico_superior_incompleto: 'Técnico Superior incompleto',
            tecnico_superior_completo: 'Técnico Superior completo',
            licenciatura_incompleta: 'Licenciatura incompleta',
            licenciatura_completa: 'Licenciatura completa',
            maestria_incompleta: 'Maestría incompleta',
            maestria_completa: 'Maestría completa',
            doctorado_incompleto: 'Doctorado incompleto',
            doctorado_terminado: 'Doctorado completo'
        }
    },
    experiencia_vida_laboral: {
        label: 'Experiencia Laboral',
        values: {
            'menos-6-meses': 'Menos de 6 meses',
            '6-12_meses': '6 meses a 1 año',
            '1-4-anos': '1 a 4 años',
            '5-9-anos': '5 a 9 años',
            '10-14-anos': '10 a 14 años',
            '15-19-anos': '15 a 19 años',
            '20-24-anos': '20 a 24 años',
            '25-o-mas': '25 años o más'
        }
    }
};

// Función para obtener la edad completa
const getFullAge = (d1, d2) => {
    if (!d1 || !d2) return 'No especificada';
    return `${d1}${d2}`;
};

// Función para obtener la edad combinada para mostrar en la tabla
const getCombinedAge = (d1, d2) => {
    if (!d1 || !d2) return 'No especificada';
    return `${d1}${d2} años`;
};

// Función para encontrar el otro dígito de la edad
const getEdad_d2 = (currentIdx) => {
    if (!props.guideVResults || !props.guideVResults.questions) return '';
    
    // Si el actual es edad_d1, buscar edad_d2 y viceversa
    const currentQuestion = props.guideVResults.questions[currentIdx].question;
    const lookFor = currentQuestion === 'edad_d1' ? 'edad_d2' : 'edad_d1';
    
    // Buscar la pregunta correspondiente al otro dígito
    const otherDigit = props.guideVResults.questions.find(q => q.question === lookFor);
    return otherDigit ? otherDigit.answer : '';
};

// Función para obtener el valor traducido de la Guía V
const getGuideVTranslatedValue = (key, value) => {
    if (key === 'edad_d1' || key === 'edad_d2') return value;
    if (!value) return 'No especificado';
    return guideVLabels[key]?.values[value] || value;
};

// Funciones para las gráficas
const getScoreColorClass = (score) => {
    if (score < 50) return 'bg-green-500';
    if (score < 75) return 'bg-yellow-500';
    if (score < 99) return 'bg-orange-500';
    if (score < 140) return 'bg-red-500';
    return 'bg-red-600';
};

const getCategoryMaxScore = (categoryName) => {
    const range = categoryRanges[categoryName]?.muy_alto?.min;
    // Multiplicamos por 1.2 para dar espacio visual en la gráfica
    return range ? range * 1.2 : 100;
};

const getCategoryBarClass = (categoryName, score) => {
    const ranges = categoryRanges[categoryName];
    if (!ranges) return 'bg-gray-500';

    if (score < ranges.nulo.max) return 'bg-green-500';
    if (score < ranges.bajo.max) return 'bg-yellow-500';
    if (score < ranges.medio.max) return 'bg-orange-500';
    if (score < ranges.alto.max) return 'bg-red-500';
    return 'bg-red-600';
};

const getDomainMaxScore = (domainName) => {
    const range = domainRanges[domainName]?.muy_alto?.min;
    // Multiplicamos por 1.2 para dar espacio visual en la gráfica
    return range ? range * 1.2 : 100;
};

const getDomainBarClass = (domainName, score) => {
    const ranges = domainRanges[domainName];
    if (!ranges) return 'bg-gray-500';

    if (score < ranges.nulo.max) return 'bg-green-500';
    if (score < ranges.bajo.max) return 'bg-yellow-500';
    if (score < ranges.medio.max) return 'bg-orange-500';
    if (score < ranges.alto.max) return 'bg-red-500';
    return 'bg-red-600';
};

// Definición de tabs
const tabs = [
    { key: 'summary', label: 'Resumen' },
    { key: 'interpretations', label: 'Interpretaciones' },
    { key: 'guide_i', label: 'Guía de Referencia I' },
    { key: 'guide_iii', label: 'Guía de Referencia III' },
    { key: 'guide_v', label: 'Guía de Referencia V' }
];

// Tab activo
const currentTab = ref('summary');

// Configuración de categorías para Guía I
const guideICategories = {
    'recuerdos_persistentes': {
        title: 'Recuerdos persistentes sobre el acontecimiento (durante el ultimo mes)',
        questions: ['pregunta_1', 'pregunta_2']
    },
    'esfuerzo_evitar': {
        title: 'Esfuerzo por evitar circunstancias parecidas o asociadas al acontecimiento (durante el ultimo mes)',
        questions: ['pregunta_3', 'pregunta_4', 'pregunta_5', 'pregunta_6', 'pregunta_7', 'pregunta_8', 'pregunta_9']
    },
    'afectacion': {
        title: 'Afectación (Durante el ultimo mes)',
        questions: ['pregunta_10', 'pregunta_11', 'pregunta_12', 'pregunta_13', 'pregunta_14']
    }
};

// Importar las preguntas de la configuración
const guideIQuestions = {
    pregunta_1: '¿Ha tenido recuerdos recurrentes sobre el acontecimiento que le provocaron malestares?',
    pregunta_2: '¿Ha tenido sueños de carácter recurrente sobre el acontecimiento, que le producen malestar?',
    pregunta_3: '¿Se ha esforzado por evitar todo tipo de sentimientos, conversaciones o situaciones que le puedan recordar el acontecimiento?',
    pregunta_4: '¿Se ha esforzado por evitar todo tipo de actividades, lugares o personas que motivan recuerdos del acontecimiento?',
    pregunta_5: '¿Ha tenido dificultad para recordar alguna parte importante del evento?',
    pregunta_6: '¿Ha disminuido su interés en sus actividades cotidianas?',
    pregunta_7: '¿Se ha sentido usted alejado o distante de los demás?',
    pregunta_8: '¿Ha notado que tiene dificultad para expresar sus sentimientos?',
    pregunta_9: '¿Ha tenido la impresión de que su vida se va a acortar, que va a morir antes que otras personas o que tiene un futuro limitado?',
    pregunta_10: '¿Ha tenido usted dificultades para dormir?',
    pregunta_11: '¿Ha estado particularmente irritable o le han dado arranques de coraje?',
    pregunta_12: '¿Ha tenido dificultad para concentrarse?',
    pregunta_13: '¿Ha estado nervioso o constantemente en alerta?',
    pregunta_14: '¿Se ha sobresaltado fácilmente por cualquier cosa?',
};

// Preguntas de la Guía de Referencia V
const guideVQuestions = {
    sexo: 'Sexo',
    edad_d1: 'Edad (Decenas)',
    edad_d2: 'Edad (Unidades)',
    tipo_puesto: 'Tipo de Puesto',
    estado_civil: 'Estado Civil',
    tipo_jornada: 'Tipo de Jornada',
    tipo_personal: 'Tipo de Personal',
    rotacion_turnos: 'Rotación de Turnos',
    tipo_contratacion: 'Tipo de Contratación',
    tiempo_puesto_actual: 'Tiempo en el Puesto Actual',
    ultimo_nivel_estudio: 'Último Nivel de Estudios',
    experiencia_vida_laboral: 'Experiencia en Vida Laboral',
    departamento_seccion_area: 'Departamento/Sección/Área',
    ocupacion_profesion_puesto: 'Ocupación/Profesión/Puesto'
};

// Respuestas posibles para la Guía de Referencia V
const guideVAnswers = {
    sexo: {
        masculino: 'Masculino',
        femenino: 'Femenino'
    },
    edad_d1: {
        '0': '0', '1': '1', '2': '2', '3': '3', '4': '4', 
        '5': '5', '6': '6', '7': '7', '8': '8', '9': '9'
    },
    edad_d2: {
        '0': '0', '1': '1', '2': '2', '3': '3', '4': '4', 
        '5': '5', '6': '6', '7': '7', '8': '8', '9': '9'
    },
    tipo_puesto: {
        operativo: 'Operativo',
        profesional: 'Profesional/Técnico',
        supervisor: 'Supervisor',
        gerente: 'Gerente'
    },
    estado_civil: {
        soltero: 'Soltero(a)',
        casado: 'Casado(a)',
        union_libre: 'Unión libre',
        divorciado: 'Divorciado(a)',
        viudo: 'Viudo(a)'
    },
    tipo_jornada: {
        fijo_diurno: 'Fijo Diurno',
        fijo_nocturno: 'Fijo Nocturno',
        fijo_mixto: 'Fijo Mixto',
        fijo_6_20: 'Fijo de 6:00 a 20:00 hrs',
        fijo_20_6: 'Fijo de 20:00 a 6:00 hrs'
    },
    tipo_personal: {
        sindicalizado: 'Sindicalizado',
        confianza: 'Confianza',
        ninguno: 'Ninguno'
    },
    rotacion_turnos: {
        si: 'Sí',
        no: 'No'
    },
    tipo_contratacion: {
        indeterminado: 'Por tiempo indeterminado',
        temporal: 'Temporal',
        honorarios: 'Por honorarios',
        subcontratacion: 'Por subcontratación'
    },
    tiempo_puesto_actual: {
        '0-6_meses': 'Menos de 6 meses',
        '6-12_meses': '6 meses a 1 año',
        '1-4-anos': '1 a 4 años',
        '5-9-anos': '5 a 9 años',
        '10-14-anos': '10 a 14 años',
        '15-19-anos': '15 a 19 años',
        '20-24-anos': '20 a 24 años',
        '25-anos_o_mas': '25 años o más'
    },
    ultimo_nivel_estudio: {
        ninguno: 'Ninguno',
        primaria_terminado: 'Primaria terminada',
        secundaria_inconcluso: 'Secundaria sin terminar',
        secundaria_terminado: 'Secundaria terminada',
        preparatoria_terminado: 'Preparatoria o bachillerato terminado',
        tecnico_superior_inconcluso: 'Técnico superior sin terminar',
        tecnico_superior_terminado: 'Técnico superior terminado',
        licenciatura_terminado: 'Licenciatura terminada',
        maestria_inconcluso: 'Maestría sin terminar',
        maestria_terminado: 'Maestría terminada',
        doctorado_inconcluso: 'Doctorado sin terminar',
        doctorado_terminado: 'Doctorado terminado'
    },
    experiencia_vida_laboral: {
        '1-4-anos': '1 a 4 años',
        '5-9-anos': '5 a 9 años',
        '10-14-anos': '10 a 14 años',
        '15-19-anos': '15 a 19 años',
        '20-24-anos': '20 a 24 años',
        '25-anos_o_mas': '25 años o más'
    },
    departamento_seccion_area: {
        '1_a': 'Opción 1A',
        '1_b': 'Opción 1B',
        '1_c': 'Opción 1C',
        '1_d': 'Opción 1D',
        '1_e': 'Opción 1E',
        '2_a': 'Opción 2A',
        '2_b': 'Opción 2B',
        '2_c': 'Opción 2C',
        '2_d': 'Opción 2D',
        '2_e': 'Opción 2E'
    },
    ocupacion_profesion_puesto: {
        '1': 'Opción 1',
        '2': 'Opción 2',
        '3': 'Opción 3',
        '4': 'Opción 4',
        '5': 'Opción 5',
        '6': 'Opción 6',
        '7': 'Opción 7',
        '8': 'Opción 8',
        '9': 'Opción 9'
    }
};
</script>

<style scoped>
.border-b {
    border-bottom-width: 1px;
}

td[rowspan] {
    vertical-align: middle !important;
}

.overflow-x-auto {
    overflow: visible;
}

/* Estilos para las gráficas */
.transition-all {
    transition-property: all;
    transition-duration: 0.5s;
}

/* Efecto de hover para las barras de las gráficas */
[class*="bg-"].transition-all:hover {
    filter: brightness(110%);
    transform: scaleX(1.01);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Bordes redondeados para las barras */
.rounded-r-sm {
    border-radius: 0 0.125rem 0.125rem 0;
}
</style>
