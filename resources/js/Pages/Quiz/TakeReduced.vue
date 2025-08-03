<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import QuizLayout from '@/Layouts/QuizLayout.vue';
import ProgressBar from '@/Components/Quiz/ProgressBar.vue';
import ViewModeToggle from '@/Components/Quiz/ViewModeToggle.vue';
import PersonalDataSection from '@/Components/Quiz/PersonalDataSection.vue';
import LaborDataSection from '@/Components/Quiz/LaborDataSection.vue';
import TraumaticEventsSection from '@/Components/Quiz/TraumaticEventsSection.vue';
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import NavigationButtons from '@/Components/Quiz/NavigationButtons.vue';

const currentSection = ref('referencia_v');
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
    if (currentSection.value === 'referencia_v') {
        currentSection.value = 'acontecimientos_traumaticos';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'acontecimientos_traumaticos') {
        checkTraumaticEvents();
        if (showFollowUpQuestions.value) {
            currentSection.value = 'referencia_i';
        }
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const previousSection = () => {
    if (currentSection.value === 'referencia_i') {
        currentSection.value = 'acontecimientos_traumaticos';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'acontecimientos_traumaticos') {
        currentSection.value = 'referencia_v';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'referencia_i' || 
           (currentSection.value === 'acontecimientos_traumaticos' && !showFollowUpQuestions.value);
});

const progress = computed(() => {
    const sections = ['referencia_v', 'acontecimientos_traumaticos'];
    if (showFollowUpQuestions.value) {
        sections.push('referencia_i');
    }
    
    const currentIndex = sections.indexOf(currentSection.value);
    return ((currentIndex + 1) / sections.length) * 100;
});

// Agregar estado para el modo de visualización
const viewMode = ref('comfortable'); // 'comfortable' o 'compact'
const isSubmitting = ref(false);

// Helpers para la sección de acontecimientos traumáticos
const traumaticAnswers = computed(() => answers.value.acontecimientos_traumaticos || {});
const allTraumaticAnswered = computed(() => {
    const questions = props.quiz?.questions?.acontecimientos_traumaticos?.questions || [];
    return questions.length > 0 && questions.every((_, idx) => traumaticAnswers.value[idx] !== undefined);
});
const allTraumaticNo = computed(() => {
    const questions = props.quiz?.questions?.acontecimientos_traumaticos?.questions || [];
    return questions.length > 0 && questions.every((_, idx) => traumaticAnswers.value[idx] === false);
});
const traumaticHasYes = computed(() => {
    return Object.values(traumaticAnswers.value).some(v => v === true);
});

const submitEvaluation = () => {
    isSubmitting.value = true;
    
    const dataToSend = {
        referencia_iii: { acontecimientos_traumaticos: answers.value.acontecimientos_traumaticos },
        referencia_i: answers.value.referencia_i || {},
        referencia_v: answers.value.referencia_v
    };
    
    console.log('Enviando datos:', dataToSend);
    
    router.post(route('quiz.submit', props.quiz.id), dataToSend, {
        onFinish: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <QuizLayout :title="quiz.name">
        <div class="max-w-4xl mx-auto px-4 sm:px-0">
            <!-- Barra de progreso -->
            <ProgressBar :progress="progress">
                <span v-if="currentSection === 'referencia_v'">
                    Sección 1: Datos Personales
                </span>
                <span v-else-if="currentSection === 'acontecimientos_traumaticos'">
                    Sección 2: Acontecimientos Traumáticos
                </span>
                <span v-else>
                    Sección 3: Preguntas de Seguimiento
                </span>
            </ProgressBar>

            <!-- Controles de visualización -->
            <ViewModeToggle v-model="viewMode" />

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

                    <!-- Sección Referencia V -->
                    <div v-if="currentSection === 'referencia_v'" class="space-y-6">
                        <!-- Datos personales -->
                        <PersonalDataSection 
                            v-model="answers.referencia_v" 
                            :reference-data="quiz.reference_v"
                        />

                        <!-- Datos Laborales -->
                        <LaborDataSection 
                            v-model="answers.referencia_v.datos_laborales"
                            :laboral-data="quiz.reference_v.datos_laborales"
                            :organization="quiz.organization"
                        />
                    </div>

                    <!-- Sección Acontecimientos Traumáticos -->
                    <div v-if="currentSection === 'acontecimientos_traumaticos'" class="space-y-6">
                        <TraumaticEventsSection
                            :title="quiz.questions.acontecimientos_traumaticos.title"
                            :questions="quiz.questions.acontecimientos_traumaticos.questions"
                            v-model="answers.acontecimientos_traumaticos"
                            :answer-options="answerOptions.yesNo"
                            name-prefix="trauma"
                        />
                    </div>

                    <!-- Sección Referencia I (solo si hay respuestas positivas) -->
                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <FollowUpQuestionsSection
                            :follow-up-questions="quiz.reference_i"
                            v-model="answers.referencia_i"
                            :answer-options="answerOptions.yesNo"
                        />
                    </div>

                <!-- Navegación -->
                <NavigationButtons
                    :show-previous="currentSection !== 'referencia_v'"
                    :show-next="currentSection === 'acontecimientos_traumaticos' ? (traumaticHasYes && allTraumaticAnswered) : !isLastSection"
                    :show-submit="(currentSection === 'acontecimientos_traumaticos' && (!traumaticHasYes || !allTraumaticAnswered || allTraumaticNo)) || isLastSection"
                    :is-last-section="isLastSection"
                    :next-disabled="currentSection === 'acontecimientos_traumaticos' && !allTraumaticAnswered"
                    :submit-disabled="currentSection === 'acontecimientos_traumaticos' ? (!allTraumaticAnswered || (!allTraumaticNo && !traumaticHasYes)) : isSubmitting"
                    :is-submitting="isSubmitting"
                    :submit-label="isSubmitting ? 'Enviando...' : (currentSection === 'acontecimientos_traumaticos' ? 'Finalizar' : 'Enviar Evaluación')"
                    @previous="previousSection"
                    @next="nextSection"
                    @submit="submitEvaluation"
                />
                </div>
            </div>
        </div>
    </QuizLayout>
</template>