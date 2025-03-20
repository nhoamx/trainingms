<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import QuizLayout from '@/Layouts/QuizLayout.vue';

const currentSection = ref('referencia_iii');
const showTraumaticQuestions = ref(false);

const props = defineProps({
    quiz: Object
});

const answers = ref({
    referencia_iii: {
        acontecimientos_traumaticos: {} // Inicializando el objeto para acontecimientos traumáticos
    },
    referencia_i: {},
    referencia_v: {
        sexo: '',
        edad: '',
        estado_civil: '',
        nivel_estudios: '',
        datos_laborales: {
            ocupacion_puesto: '',
            departamento_seccion_area: '',
            tipo_puesto: '',
            tipo_contratacion: '',
            tipo_personal: '',
            tipo_jornada: '',
            rotacion_turnos: '',
            experiencia: {
                tiempo_puesto_actual: '',
                tiempo_experiencia_laboral: ''
            }
        }
    }
});

const answerOptions = {
    general: [
        { label: 'Siempre', value: 'A' },
        { label: 'Casi siempre', value: 'B' },
        { label: 'Algunas Veces', value: 'C' },
        { label: 'Casi nunca', value: 'D' },
        { label: 'Nunca', value: 'E' }
    ],
    yesNo: [
        { label: 'Sí', value: true },
        { label: 'No', value: false }
    ]
};

const checkTraumaticEvents = () => {
    const traumaticAnswers = answers.value.referencia_iii.acontecimientos_traumaticos || {};
    showTraumaticQuestions.value = Object.values(traumaticAnswers).some(answer => answer === true);
};

const nextSection = () => {
    if (currentSection.value === 'referencia_iii') {
        checkTraumaticEvents();
        currentSection.value = showTraumaticQuestions.value ? 'referencia_i' : 'referencia_v';
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_v';
    }
};

const previousSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = showTraumaticQuestions.value ? 'referencia_i' : 'referencia_iii';
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_iii';
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'referencia_v';
});
</script>

<template>
    <QuizLayout :title="quiz.name">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <!-- Encabezado -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">{{ quiz.name }}</h1>
                    <div class="mt-4 flex space-x-4">
                        <div class="px-4 py-2 bg-blue-100 rounded-lg" :class="{ 'bg-blue-500 text-white': currentSection === 'referencia_iii' }">
                            Cuestionario Principal
                        </div>
                        <div v-if="showTraumaticQuestions" class="px-4 py-2 bg-blue-100 rounded-lg" :class="{ 'bg-blue-500 text-white': currentSection === 'referencia_i' }">
                            Preguntas Adicionales
                        </div>
                        <div class="px-4 py-2 bg-blue-100 rounded-lg" :class="{ 'bg-blue-500 text-white': currentSection === 'referencia_v' }">
                            Datos Personales
                        </div>
                    </div>
                </div>

                <!-- Sección Referencia III -->
                <div v-if="currentSection === 'referencia_iii'" class="space-y-8">
                    <!-- Preguntas generales -->
                    <div v-for="(question, index) in quiz.questions.general" :key="index" class="bg-gray-50 p-6 rounded-lg">
                        <p class="mb-4 text-lg">{{ index }}. {{ question }}</p>
                        <div class="grid grid-cols-5 gap-4">
                            <label 
                                v-for="option in answerOptions.general" 
                                :key="option.value"
                                class="flex items-center space-x-2 cursor-pointer"
                            >
                                <input 
                                    type="radio" 
                                    :name="`question_${index}`"
                                    :value="option.value"
                                    v-model="answers.referencia_iii[index]"
                                    class="form-radio h-4 w-4 text-blue-600"
                                >
                                <span>{{ option.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Secciones condicionales -->
                    <div v-for="(section, key) in quiz.questions.conditional_sections" :key="key" class="mt-8 border-t pt-8">
                        <div class="bg-blue-50 p-6 rounded-lg mb-6">
                            <p class="font-semibold mb-4">{{ section.condition }}</p>
                            <div class="flex space-x-8">
                                <label 
                                    v-for="option in answerOptions.yesNo" 
                                    :key="option.value"
                                    class="flex items-center space-x-2 cursor-pointer"
                                >
                                    <input 
                                        type="radio" 
                                        :name="`condition_${key}`"
                                        :value="option.value"
                                        v-model="answers.referencia_iii[`condition_${key}`]"
                                        class="form-radio h-4 w-4 text-blue-600"
                                    >
                                    <span>{{ option.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="answers.referencia_iii[`condition_${key}`]" class="space-y-6 ml-8">
                            <div v-for="(question, qIndex) in section.questions" :key="qIndex" class="bg-gray-50 p-6 rounded-lg">
                                <p class="mb-4 text-lg">{{ qIndex }}. {{ question }}</p>
                                <div class="grid grid-cols-5 gap-4">
                                    <label 
                                        v-for="option in answerOptions.general" 
                                        :key="option.value"
                                        class="flex items-center space-x-2 cursor-pointer"
                                    >
                                        <input 
                                            type="radio" 
                                            :name="`question_${qIndex}`"
                                            :value="option.value"
                                            v-model="answers.referencia_iii[qIndex]"
                                            class="form-radio h-4 w-4 text-blue-600"
                                        >
                                        <span>{{ option.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acontecimientos traumáticos -->
                    <div class="mt-8 border-t pt-8">
                        <div class="bg-yellow-50 p-6 rounded-lg">
                            <h3 class="font-semibold mb-6 text-lg">{{ quiz.questions.acontecimientos_traumaticos.title }}</h3>
                            <div class="space-y-6">
                                <div v-for="(question, index) in quiz.questions.acontecimientos_traumaticos.questions" :key="index" class="bg-white p-6 rounded-lg">
                                    <p class="mb-4">{{ index }}. {{ question }}</p>
                                    <div class="flex space-x-8">
                                        <label 
                                            v-for="option in answerOptions.yesNo" 
                                            :key="option.value"
                                            class="flex items-center space-x-2 cursor-pointer"
                                        >
                                            <input 
                                                type="radio" 
                                                :name="`trauma_${index}`"
                                                :value="option.value"
                                                v-model="answers.referencia_iii.acontecimientos_traumaticos[index]"
                                                class="form-radio h-4 w-4 text-blue-600"
                                            >
                                            <span>{{ option.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección Referencia I -->
                <div v-if="currentSection === 'referencia_i'" class="space-y-8">
                    <div v-for="(questions, category) in quiz.reference_i" :key="category" class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="font-semibold mb-6 text-lg">{{ category }}</h3>
                        <div class="space-y-6">
                            <div v-for="(question, index) in questions" :key="index" class="bg-white p-6 rounded-lg">
                                <p class="mb-4">{{ question }}</p>
                                <div class="flex space-x-8">
                                    <label 
                                        v-for="option in answerOptions.yesNo" 
                                        :key="option.value"
                                        class="flex items-center space-x-2 cursor-pointer"
                                    >
                                        <input 
                                            type="radio" 
                                            :name="`trauma_follow_${category}_${index}`"
                                            :value="option.value"
                                            v-model="answers.referencia_i[`${category}_${index}`]"
                                            class="form-radio h-4 w-4 text-blue-600"
                                        >
                                        <span>{{ option.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección Referencia V -->
                <div v-if="currentSection === 'referencia_v'" class="space-y-8">
                    <!-- Datos personales -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="font-semibold mb-6 text-lg">Datos Personales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sexo -->
                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Sexo</label>
                                <div class="flex space-x-4">
                                    <label 
                                        v-for="option in quiz.reference_v.sexo" 
                                        :key="option"
                                        class="flex items-center space-x-2"
                                    >
                                        <input 
                                            type="radio" 
                                            :value="option"
                                            v-model="answers.referencia_v.sexo"
                                            class="form-radio"
                                        >
                                        <span>{{ option }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Edad -->
                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Edad</label>
                                <select 
                                    v-model="answers.referencia_v.edad"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                >
                                    <option value="">Seleccione un rango de edad</option>
                                    <option 
                                        v-for="edad in quiz.reference_v.edad" 
                                        :key="edad" 
                                        :value="edad"
                                    >
                                        {{ edad }}
                                    </option>
                                </select>
                            </div>

                            <!-- Estado Civil -->
                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Estado Civil</label>
                                <select 
                                    v-model="answers.referencia_v.estado_civil"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                >
                                    <option value="">Seleccione su estado civil</option>
                                    <option 
                                        v-for="estado in quiz.reference_v.estado_civil" 
                                        :key="estado" 
                                        :value="estado"
                                    >
                                        {{ estado }}
                                    </option>
                                </select>
                            </div>

                            <!-- Nivel de Estudios -->
                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Nivel de Estudios</label>
                                <select 
                                    v-model="answers.referencia_v.nivel_estudios"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                >
                                    <option value="">Seleccione su nivel de estudios</option>
                                    <template v-for="(nivel, key) in quiz.reference_v.nivel_estudios" :key="key">
                                        <template v-if="Array.isArray(nivel)">
                                            <optgroup :label="key">
                                                <option 
                                                    v-for="subnivel in nivel" 
                                                    :key="subnivel" 
                                                    :value="`${key} - ${subnivel}`"
                                                >
                                                    {{ key }} - {{ subnivel }}
                                                </option>
                                            </optgroup>
                                        </template>
                                        <option v-else :value="nivel">{{ nivel }}</option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Datos Laborales -->
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="font-semibold mb-6 text-lg">Datos Laborales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Campos de texto libre -->
                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Ocupación / Puesto</label>
                                <input 
                                    type="text"
                                    v-model="answers.referencia_v.datos_laborales.ocupacion_puesto"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                    placeholder="Ingrese su ocupación o puesto"
                                >
                            </div>

                            <div class="space-y-4">
                                <label class="block font-medium text-gray-700">Departamento / Sección / Área</label>
                                <input 
                                    type="text"
                                    v-model="answers.referencia_v.datos_laborales.departamento_seccion_area"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                    placeholder="Ingrese su departamento, sección o área"
                                >
                            </div>

                            <!-- Campos de selección -->
                            <template v-for="(options, field) in quiz.reference_v.datos_laborales" :key="field">
                                <div v-if="field !== 'ocupacion_puesto' && field !== 'departamento_seccion_area'" class="space-y-4">
                                    <label class="block font-medium text-gray-700">{{ field.replace(/_/g, ' ').charAt(0).toUpperCase() + field.replace(/_/g, ' ').slice(1) }}</label>
                                    <select 
                                        v-if="!Array.isArray(options) && typeof options === 'object'"
                                        v-model="answers.referencia_v.datos_laborales.experiencia[field]"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                    >
                                        <option value="">Seleccione una opción</option>
                                        <option 
                                            v-for="opt in options.tiempo_puesto_actual || options.tiempo_experiencia_laboral" 
                                            :key="opt" 
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                    <select 
                                        v-else
                                        v-model="answers.referencia_v.datos_laborales[field]"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                    >
                                        <option value="">Seleccione una opción</option>
                                        <option 
                                            v-for="opt in options" 
                                            :key="opt" 
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Navegación -->
                <div class="mt-8 flex justify-between">
                    <button
                        v-if="currentSection !== 'referencia_iii'"
                        @click="previousSection"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                    >
                        Anterior
                    </button>
                    <button
                        v-if="!isLastSection"
                        @click="nextSection"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        Siguiente
                    </button>
                    <button
                        v-else
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                    >
                        Finalizar
                    </button>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>