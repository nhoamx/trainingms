<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import QuizLayout from '@/Layouts/QuizLayout.vue';

const currentSection = ref('referencia_iii');
const showTraumaticQuestions = ref(false);
const currentPage = ref(1);
const questionsPerPage = 10;
const currentSubsection = ref('general'); // 'general', 'conditional', 'traumatic'

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

// Computed properties for pagination
const sections = computed(() => {
    if (currentSection.value === 'referencia_iii' && props.quiz?.questions) {
        return ['general', 'conditional', 'traumatic'];
    }
    return [];
});

const totalPages = computed(() => {
    if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general' && props.quiz?.questions?.general) {
            const totalQuestions = Object.keys(props.quiz.questions.general).length;
            return Math.ceil(totalQuestions / questionsPerPage);
        } else if (currentSubsection.value === 'conditional' && props.quiz?.questions?.conditional_sections) {
            return 1; // Una página para preguntas condicionales
        } else if (currentSubsection.value === 'traumatic' && props.quiz?.questions?.acontecimientos_traumaticos) {
            return 1; // Una página para acontecimientos traumáticos
        }
    }
    return 1;
});

const paginatedQuestions = computed(() => {
    if (currentSection.value === 'referencia_iii' && currentSubsection.value === 'general' && props.quiz?.questions?.general) {
        const start = (currentPage.value - 1) * questionsPerPage;
        const end = start + questionsPerPage;
        const questions = Object.entries(props.quiz.questions.general)
            .slice(start, end)
            .map(([key, value]) => ({
                id: key,
                text: value
            }));
        return questions;
    }
    return [];
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    } else {
        // Si estamos en la última página de la subsección actual
        const currentIndex = sections.value.indexOf(currentSubsection.value);
        if (currentIndex < sections.value.length - 1) {
            currentSubsection.value = sections.value[currentIndex + 1];
            currentPage.value = 1;
        }
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    } else {
        // Si estamos en la primera página de la subsección actual
        const currentIndex = sections.value.indexOf(currentSubsection.value);
        if (currentIndex > 0) {
            currentSubsection.value = sections.value[currentIndex - 1];
            // Establecer la página a la última de la subsección anterior
            if (currentSubsection.value === 'general') {
                const totalGeneralQuestions = Object.keys(props.quiz.questions.general).length;
                currentPage.value = Math.ceil(totalGeneralQuestions / questionsPerPage);
            } else {
                currentPage.value = 1;
            }
        }
    }
};

const nextSection = () => {
    if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general' && currentPage.value < totalPages.value) {
            // Si estamos en preguntas generales y hay más páginas, avanzar a la siguiente página
            currentPage.value++;
            // Scroll suave hacia arriba
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const currentIndex = sections.value.indexOf(currentSubsection.value);
        if (currentIndex < sections.value.length - 1) {
            // Si hay más subsecciones, vamos a la siguiente
            currentSubsection.value = sections.value[currentIndex + 1];
            currentPage.value = 1;
            // Scroll suave hacia arriba
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Pasamos a la siguiente sección principal
        checkTraumaticEvents();
        currentSection.value = showTraumaticQuestions.value ? 'referencia_i' : 'referencia_v';
        currentPage.value = 1;
        currentSubsection.value = 'general';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_v';
        currentPage.value = 1;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const previousSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = showTraumaticQuestions.value ? 'referencia_i' : 'referencia_iii';
        currentSubsection.value = 'traumatic'; // La última subsección de referencia_iii
        currentPage.value = 1;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'traumatic';
        currentPage.value = 1;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        // Dentro de la sección referencia_iii
        if (currentSubsection.value === 'general' && currentPage.value > 1) {
            // Si estamos en preguntas generales y no estamos en la primera página
            currentPage.value--;
            // Scroll suave hacia arriba
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // Si estamos en la primera página de una subsección
            const currentIndex = sections.value.indexOf(currentSubsection.value);
            if (currentIndex > 0) {
                // Si no estamos en la primera subsección
                currentSubsection.value = sections.value[currentIndex - 1];
                // Establecer la página a la última de la subsección anterior
                if (currentSubsection.value === 'general') {
                    const totalGeneralQuestions = Object.keys(props.quiz.questions.general).length;
                    currentPage.value = Math.ceil(totalGeneralQuestions / questionsPerPage);
                } else {
                    currentPage.value = 1;
                }
                // Scroll suave hacia arriba
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'referencia_v';
});

// Actualizar el progreso para incluir subsecciones
const progress = computed(() => {
    // Calcular el progreso basado en secciones y subsecciones
    const mainSections = ['referencia_iii', 'referencia_i', 'referencia_v'];
    const currentMainIndex = mainSections.indexOf(currentSection.value);

    let sectionProgress = currentMainIndex / mainSections.length;

    // Agregar progreso de subsección si estamos en referencia_iii
    if (currentSection.value === 'referencia_iii') {
        const subSectionProgress = sections.value.indexOf(currentSubsection.value) / sections.value.length;
        sectionProgress += subSectionProgress / mainSections.length;
    }

    return sectionProgress * 100;
});

// Agregar estado para el modo de visualización
const viewMode = ref('comfortable'); // 'comfortable' o 'compact'

const canAccessSubsection = (subsection) => {
    const subsectionOrder = ['general', 'conditional', 'traumatic'];
    const currentIndex = subsectionOrder.indexOf(currentSubsection.value);
    const targetIndex = subsectionOrder.indexOf(subsection);

    // Solo permitir acceso a subsecciones que ya se han completado o la siguiente en orden
    return targetIndex <= currentIndex + 1;
};

const handleSubsectionChange = (subsection) => {
    if (!canAccessSubsection(subsection)) return;

    // Si estamos en la última página de la subsección actual
    if (currentSubsection.value === 'general' && currentPage.value < totalPages.value) {
        return; // No permitir cambiar de subsección si no se han completado todas las páginas
    }

    currentSubsection.value = subsection;
    currentPage.value = 1;
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
                    <span v-if="currentSection === 'referencia_iii'">
                        Sección 1 de 3:
                        <span v-if="currentSubsection === 'general'">Cuestionario Principal</span>
                        <span v-else-if="currentSubsection === 'conditional'">Preguntas Condicionales</span>
                        <span v-else-if="currentSubsection === 'traumatic'">Acontecimientos Traumáticos</span>
                        <span v-if="currentSubsection === 'general'"> • Página {{ currentPage }} de {{ totalPages }}</span>
                    </span>
                    <span v-else-if="currentSection === 'referencia_i'">
                        Sección 2 de 3: Preguntas Adicionales
                    </span>
                    <span v-else>
                        Sección 3 de 3: Datos Personales
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
                        <div class="mt-4 flex flex-wrap gap-2">
                            <div
                                v-for="(section, key) in {
                                    referencia_iii: 'Cuestionario Principal',
                                    referencia_i: 'Preguntas Adicionales',
                                    referencia_v: 'Datos Personales'
                                }"
                                :key="key"
                                class="px-3 py-1.5 text-sm rounded-full transition-colors"
                                :class="currentSection === key ? 'bg-slate-100 text-slate-800' : 'bg-slate-50 text-slate-600'"
                            >
                                {{ section }}
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia III -->
                    <div v-if="currentSection === 'referencia_iii'" class="space-y-6">
                        <!-- Preguntas generales -->
                        <div v-if="currentSubsection === 'general'">
                            <div
                                v-for="question in paginatedQuestions"
                                :key="question.id"
                                class="border-b border-slate-100 last:border-0 pb-6 last:pb-0 mb-6"
                                :class="{ 'bg-slate-50 p-4 rounded-lg': viewMode === 'comfortable' }"
                            >
                                <p class="mb-3 text-slate-900">{{ question.id }}. {{ question.text }}</p>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                    <label
                                        v-for="option in answerOptions.general"
                                        :key="option.value"
                                        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                                    >
                                        <input
                                            type="radio"
                                            :name="`question_${question.id}`"
                                            :value="option.value"
                                            v-model="answers.referencia_iii[question.id]"
                                            class="form-radio h-4 w-4 text-slate-800"
                                        >
                                        <span class="text-sm text-slate-700">{{ option.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Secciones condicionales -->
                        <div v-if="currentSubsection === 'conditional'">
                            <div
                                v-for="(section, key) in quiz.questions.conditional_sections"
                                :key="key"
                                class="mb-8 last:mb-0"
                            >
                                <div class="bg-slate-50 p-4 rounded-lg mb-4">
                                    <p class="font-medium text-slate-900 mb-3">{{ section.condition }}</p>
                                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <label
                                            v-for="option in answerOptions.yesNo"
                                            :key="option.value"
                                            class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-white transition-colors"
                                        >
                                            <input
                                                type="radio"
                                                :name="`condition_${key}`"
                                                :value="option.value"
                                                v-model="answers.referencia_iii[`condition_${key}`]"
                                                class="form-radio h-4 w-4 text-slate-800"
                                            >
                                            <span class="text-sm text-slate-700">{{ option.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Preguntas condicionales -->
                                <div v-if="answers.referencia_iii[`condition_${key}`] === true" class="space-y-4 ml-0 sm:ml-4">
                                    <div
                                        v-for="(question, qIndex) in section.questions"
                                        :key="qIndex"
                                        class="border-b border-slate-100 last:border-0 pb-4 last:pb-0"
                                    >
                                        <p class="mb-3 text-slate-900">{{ qIndex }}. {{ question }}</p>
                                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                            <label
                                                v-for="option in answerOptions.general"
                                                :key="option.value"
                                                class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                                            >
                                                <input
                                                    type="radio"
                                                    :name="`question_${qIndex}`"
                                                    :value="option.value"
                                                    v-model="answers.referencia_iii[qIndex]"
                                                    class="form-radio h-4 w-4 text-slate-800"
                                                >
                                                <span class="text-sm text-slate-700">{{ option.label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acontecimientos traumáticos -->
                        <div v-if="currentSubsection === 'traumatic'">
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
                                                    v-model="answers.referencia_iii.acontecimientos_traumaticos[index]"
                                                    class="form-radio h-4 w-4 text-slate-800"
                                                >
                                                <span class="text-sm text-slate-700">{{ option.label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia I -->
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
                <div class="mt-8 flex flex-col space-y-4 px-4 sm:px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <button
                            v-if="currentSection !== 'referencia_iii' || currentSubsection !== 'general' || currentPage > 1"
                            @click="previousSection"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors"
                        >
                            {{
                                currentSection === 'referencia_iii' ?
                                    (currentSubsection === 'general' && currentPage > 1 ? 'Página anterior' : 'Subsección anterior') :
                                    'Sección anterior'
                            }}
                        </button>
                        <div class="flex items-center justify-center sm:justify-end space-x-4">
                            <button
                                v-if="!isLastSection || (currentSection === 'referencia_iii' && currentSubsection !== 'traumatic') || (currentSubsection === 'general' && currentPage < totalPages)"
                                @click="nextSection"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors"
                            >
                                {{
                                    currentSection === 'referencia_iii' ?
                                        (currentSubsection === 'general' && currentPage < totalPages ? 'Siguiente página' : 'Siguiente subsección') :
                                        'Siguiente sección'
                                }}
                            </button>
                            <button
                                v-else
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors"
                            >
                                Finalizar
                            </button>
                        </div>
                    </div>
                    <!-- Contador de páginas movido aquí -->
                    <div v-if="currentSection === 'referencia_iii' && currentSubsection === 'general' && totalPages > 1" class="flex justify-center items-center">
                        <span class="text-sm text-slate-600">
                            Página {{ currentPage }} de {{ totalPages }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>