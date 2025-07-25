<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import QuizLayout from '@/Layouts/QuizLayout.vue';

const currentSection = ref('acontecimientos_traumaticos');
const showFollowUpQuestions = ref(false);

const props = defineProps({
    quiz: Object
});

const answers = ref({
    acontecimientos_traumaticos: {},
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
    yesNo: [
        { label: 'Sí', value: true },
        { label: 'No', value: false }
    ]
};

const checkTraumaticEvents = () => {
    const traumaticAnswers = answers.value.acontecimientos_traumaticos || {};
    showFollowUpQuestions.value = Object.values(traumaticAnswers).some(answer => answer === true);
};

const nextSection = () => {
    if (currentSection.value === 'acontecimientos_traumaticos') {
        checkTraumaticEvents();
        currentSection.value = showFollowUpQuestions.value ? 'referencia_i' : 'referencia_v';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_v';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const previousSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = showFollowUpQuestions.value ? 'referencia_i' : 'acontecimientos_traumaticos';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'acontecimientos_traumaticos';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'referencia_v';
});

const progress = computed(() => {
    const sections = ['acontecimientos_traumaticos'];
    if (showFollowUpQuestions.value) {
        sections.push('referencia_i');
    }
    sections.push('referencia_v');
    
    const currentIndex = sections.indexOf(currentSection.value);
    return ((currentIndex + 1) / sections.length) * 100;
});

// Agregar estado para el modo de visualización
const viewMode = ref('comfortable'); // 'comfortable' o 'compact'
const isSubmitting = ref(false);

const submitEvaluation = () => {
    isSubmitting.value = true;
    
    router.post(route('quiz.submit', props.quiz.id), {
        referencia_iii: { acontecimientos_traumaticos: answers.value.acontecimientos_traumaticos },
        referencia_i: answers.value.referencia_i,
        referencia_v: answers.value.referencia_v
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <QuizLayout :title="quiz.name">
        <div class="max-w-4xl mx-auto px-4 sm:px-0">
            <!-- Barra de progreso -->
            <div class="mb-8">
                <div class="h-1 bg-slate-200 rounded-full">
                    <div
                        class="h-full bg-slate-800 rounded-full transition-all duration-300"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    <span v-if="currentSection === 'acontecimientos_traumaticos'">
                        Sección 1: Acontecimientos Traumáticos
                    </span>
                    <span v-else-if="currentSection === 'referencia_i'">
                        Sección 2: Preguntas de Seguimiento
                    </span>
                    <span v-else>
                        Sección {{ showFollowUpQuestions ? '3' : '2' }}: Datos Personales
                    </span>
                </div>
            </div>

            <!-- Controles de visualización -->
            <div class="mb-6 flex justify-end">
                <div class="flex items-center space-x-2 text-sm text-slate-600">
                    <span class="hidden sm:inline">Vista:</span>
                    <button
                        @click="viewMode = 'comfortable'"
                        class="px-3 py-1 rounded-md transition-colors"
                        :class="viewMode === 'comfortable' ? 'bg-slate-100 text-slate-900' : 'hover:bg-slate-50'"
                    >
                        <span class="hidden sm:inline">Cómoda</span>
                        <span class="sm:hidden">C</span>
                    </button>
                    <button
                        @click="viewMode = 'compact'"
                        class="px-3 py-1 rounded-md transition-colors"
                        :class="viewMode === 'compact' ? 'bg-slate-100 text-slate-900' : 'hover:bg-slate-50'"
                    >
                        <span class="hidden sm:inline">Compacta</span>
                        <span class="sm:hidden">D</span>
                    </button>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-4 sm:p-6">
                    <!-- Encabezado -->
                    <div class="mb-8">
                        <h1 class="text-xl font-medium text-slate-900">{{ quiz.name }}</h1>
                        <div class="mt-2 text-sm text-slate-600">
                            Evaluación Reducida - Solo Acontecimientos Traumáticos
                        </div>
                    </div>

                    <!-- Sección Acontecimientos Traumáticos -->
                    <div v-if="currentSection === 'acontecimientos_traumaticos'" class="space-y-6">
                        <div class="bg-slate-50 p-4 rounded-lg mb-6">
                            <h3 class="font-medium text-slate-900 mb-4">{{ quiz.questions.acontecimientos_traumaticos.title }}</h3>
                            <div class="space-y-4">
                                <div
                                    v-for="(question, index) in quiz.questions.acontecimientos_traumaticos.questions"
                                    :key="index"
                                    class="bg-white p-4 rounded-lg border border-slate-100"
                                >
                                    <p class="mb-3 text-slate-900">{{ index }}. {{ question }}</p>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <label
                                            v-for="option in answerOptions.yesNo"
                                            :key="option.value"
                                            class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                                        >
                                            <input
                                                type="radio"
                                                :name="`trauma_${index}`"
                                                :value="option.value"
                                                v-model="answers.acontecimientos_traumaticos[index]"
                                                class="form-radio h-4 w-4 text-slate-800"
                                            >
                                            <span class="text-sm text-slate-700">{{ option.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia I (solo si hay respuestas positivas) -->
                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <div
                            v-for="(questions, category) in quiz.reference_i"
                            :key="category"
                            class="bg-slate-50 p-4 rounded-lg"
                        >
                            <h3 class="font-medium text-slate-900 mb-4">{{ category }}</h3>
                            <div class="space-y-4">
                                <div
                                    v-for="(question, index) in questions"
                                    :key="index"
                                    class="bg-white p-4 rounded-lg border border-slate-100"
                                >
                                    <p class="mb-3 text-slate-900">{{ question }}</p>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <label
                                            v-for="option in answerOptions.yesNo"
                                            :key="option.value"
                                            class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                                        >
                                            <input
                                                type="radio"
                                                :name="`trauma_follow_${category}_${index}`"
                                                :value="option.value"
                                                v-model="answers.referencia_i[`${category}_${index}`]"
                                                class="form-radio h-4 w-4 text-slate-800"
                                            >
                                            <span class="text-sm text-slate-700">{{ option.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia V -->
                    <div v-if="currentSection === 'referencia_v'" class="space-y-6">
                        <!-- Datos personales -->
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <h3 class="font-medium text-slate-900 mb-4">Datos Personales</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Sexo -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Sexo</label>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <label
                                            v-for="option in quiz.reference_v.sexo"
                                            :key="option"
                                            class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-white transition-colors"
                                        >
                                            <input
                                                type="radio"
                                                :value="option"
                                                v-model="answers.referencia_v.sexo"
                                                class="form-radio h-4 w-4 text-slate-800"
                                            >
                                            <span class="text-sm text-slate-700">{{ option }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Edad -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Edad</label>
                                    <select
                                        v-model="answers.referencia_v.edad"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Estado Civil</label>
                                    <select
                                        v-model="answers.referencia_v.estado_civil"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Nivel de Estudios</label>
                                    <select
                                        v-model="answers.referencia_v.nivel_estudios"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
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
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <h3 class="font-medium text-slate-900 mb-4">Datos Laborales</h3>
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-700">
                                    <strong>Nota:</strong> Su ID Personal será asignado automáticamente al completar la evaluación.
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Campos de texto libre -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Ocupación / Puesto</label>
                                    <select
                                        v-model="answers.referencia_v.datos_laborales.ocupacion_puesto"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                                    >
                                        <option value="">Seleccione su ocupación o puesto</option>
                                        <option 
                                            v-for="(name, id) in quiz.organization.occupation_positions" 
                                            :key="id" 
                                            :value="name"
                                        >
                                            {{ name }}
                                        </option>
                                        <option v-if="Object.keys(quiz.organization.occupation_positions || {}).length === 0" value="No especificado">
                                            No hay puestos configurados
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-slate-700">Departamento / Sección / Área</label>
                                    <select
                                        v-model="answers.referencia_v.datos_laborales.departamento_seccion_area"
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                                    >
                                        <option value="">Seleccione su departamento, sección o área</option>
                                        <option 
                                            v-for="(name, id) in quiz.organization.department_areas" 
                                            :key="id" 
                                            :value="name"
                                        >
                                            {{ name }}
                                        </option>
                                        <option v-if="Object.keys(quiz.organization.department_areas || {}).length === 0" value="No especificado">
                                            No hay departamentos configurados
                                        </option>
                                    </select>
                                </div>

                                <!-- Campos de selección -->
                                <template v-for="(options, field) in quiz.reference_v.datos_laborales" :key="field">
                                    <!-- Manejar el campo experiencia por separado -->
                                    <template v-if="field === 'experiencia'">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Tiempo en el puesto actual
                                            </label>
                                            <select
                                                v-model="answers.referencia_v.datos_laborales.experiencia.tiempo_puesto_actual"
                                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                                            >
                                                <option value="">Seleccione una opción</option>
                                                <option
                                                    v-for="opt in options.tiempo_puesto_actual"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    {{ opt }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-sm font-medium text-slate-700">
                                                Tiempo de experiencia laboral total
                                            </label>
                                            <select
                                                v-model="answers.referencia_v.datos_laborales.experiencia.tiempo_experiencia_laboral"
                                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                                            >
                                                <option value="">Seleccione una opción</option>
                                                <option
                                                    v-for="opt in options.tiempo_experiencia_laboral"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    {{ opt }}
                                                </option>
                                            </select>
                                        </div>
                                    </template>
                                    <!-- Campos normales (no experiencia, ocupacion_puesto, departamento_seccion_area) -->
                                    <div v-else-if="field !== 'ocupacion_puesto' && field !== 'departamento_seccion_area'" class="space-y-2">
                                        <label class="block text-sm font-medium text-slate-700">
                                            {{ field.replace(/_/g, ' ').charAt(0).toUpperCase() + field.replace(/_/g, ' ').slice(1) }}
                                        </label>
                                        <select
                                            v-model="answers.referencia_v.datos_laborales[field]"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-300 focus:ring focus:ring-slate-200 text-sm"
                                        >
                                            <option value="">Seleccione una opción</option>
                                            <option
                                                v-for="option in options"
                                                :key="option"
                                                :value="option"
                                            >
                                                {{ option }}
                                            </option>
                                        </select>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navegación -->
                <div class="px-4 py-4 sm:px-6 border-t border-slate-200 bg-slate-50 rounded-b-lg">
                    <div class="flex justify-between">
                        <button
                            v-if="currentSection !== 'acontecimientos_traumaticos'"
                            @click="previousSection"
                            class="inline-flex items-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500"
                        >
                            Anterior
                        </button>
                        <div v-else></div>

                        <button
                            v-if="!isLastSection"
                            @click="nextSection"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-slate-800 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500"
                        >
                            Siguiente
                        </button>
                        <button
                            v-else
                            @click="submitEvaluation"
                            :disabled="isSubmitting"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmitting" class="mr-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            {{ isSubmitting ? 'Enviando...' : 'Enviar Evaluación' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>