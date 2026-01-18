<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useAudioUrls } from '@/composables/useAudioUrls';
import { useVideoUrls } from '@/composables/useVideoUrls';
import QuizLayout from '@/Layouts/QuizLayout.vue';
import ProgressBar from '@/Components/Quiz/ProgressBar.vue';
import ViewModeToggle from '@/Components/Quiz/ViewModeToggle.vue';
import PersonalDataSection from '@/Components/Quiz/PersonalDataSection.vue';
import LaborDataSection from '@/Components/Quiz/LaborDataSection.vue';
import CustomFieldsSection from '@/Components/Quiz/CustomFieldsSection.vue';
import TraumaticEventsSection from '@/Components/Quiz/TraumaticEventsSection.vue';
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import NavigationButtons from '@/Components/Quiz/NavigationButtons.vue';
import GeneralQuestionsSection from '@/Components/Quiz/GeneralQuestionsSection.vue';
import ConditionalQuestionsSection from '@/Components/Quiz/ConditionalQuestionsSection.vue';
import FinalSection from '@/Components/Quiz/FinalSection.vue';

const currentSection = ref('referencia_v');
const showTraumaticQuestions = ref(false);
const currentPage = ref(1);
const questionsPerPage = 10;
const currentSubsection = ref('general'); // 'general', 'conditional', 'traumatic'

const props = defineProps({
    quiz: Object
});

const answers = ref({
    referencia_iii: {},
    referencia_i: {},
    referencia_v: {
        sexo: '',
        edad: '',
        estado_civil: '',
        nivel_estudios: '',
        ine_frente: null,
        ine_reverso: null,
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
    },
    custom_fields: {}
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

// Validaciones
const isReferenciaVComplete = computed(() => {
    const rv = answers.value.referencia_v;
    const dl = rv.datos_laborales;
    
    return rv.sexo && rv.edad && rv.estado_civil && rv.nivel_estudios &&
           dl.ocupacion_puesto && dl.tipo_puesto && dl.tipo_contratacion &&
           dl.tipo_personal && dl.tipo_jornada && dl.rotacion_turnos &&
           dl.experiencia.tiempo_puesto_actual && dl.experiencia.tiempo_experiencia_laboral;
});

const isGeneralQuestionsComplete = computed(() => {
    const generalQuestions = props.quiz?.questions?.general || {};
    const generalAnswers = answers.value.referencia_iii || {};
    
    // Si estamos paginando, solo validar las preguntas de la página actual
    if (currentSection.value === 'referencia_iii' && currentSubsection.value === 'general') {
        const start = (currentPage.value - 1) * questionsPerPage;
        const end = start + questionsPerPage;
        const currentPageQuestionKeys = Object.keys(generalQuestions).slice(start, end);
        
        return currentPageQuestionKeys.every(key => generalAnswers[key] !== undefined);
    }
    
    // Validación completa si no estamos en la subsección general
    return Object.keys(generalQuestions).every(key => generalAnswers[key] !== undefined);
});

const isConditionalQuestionsComplete = computed(() => {
    const conditionalSections = props.quiz?.questions?.conditional_sections || [];
    const answersRef = answers.value.referencia_iii || {};
    
    if (!Array.isArray(conditionalSections) || conditionalSections.length === 0) return true;
    
    return conditionalSections.every(section => {
        // Verificar pregunta inicial
        if (answersRef[section.initial_question_key] === undefined) return false;
        
        // Si respondió "Sí", verificar las preguntas de seguimiento
        if (answersRef[section.initial_question_key] === true) {
            return section.follow_up_questions.every(q => answersRef[q.key] !== undefined);
        }
        
        return true;
    });
});

const isAcontecimientosComplete = computed(() => {
    const traumaticQuestions = props.quiz?.questions?.acontecimientos_traumaticos?.questions || [];
    const traumaticAnswers = answers.value.referencia_iii.acontecimientos_traumaticos || {};
    
    if (!Array.isArray(traumaticQuestions) || traumaticQuestions.length === 0) return true;
    
    return traumaticQuestions.every((_, idx) => traumaticAnswers[idx] !== undefined);
});

const isReferenciaIComplete = computed(() => {
    if (!showTraumaticQuestions.value) return true;
    
    const followUpQuestions = props.quiz?.reference_i || [];
    const referenciaIAnswers = answers.value.referencia_i || {};
    
    if (!Array.isArray(followUpQuestions) || followUpQuestions.length === 0) return true;
    
    return followUpQuestions.every((_, idx) => referenciaIAnswers[idx] !== undefined);
});

const canAdvanceFromCurrentSection = computed(() => {
    if (currentSection.value === 'referencia_v') return isReferenciaVComplete.value;
    
    if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general') return isGeneralQuestionsComplete.value;
        if (currentSubsection.value === 'conditional') return isConditionalQuestionsComplete.value;
        if (currentSubsection.value === 'traumatic') return isAcontecimientosComplete.value;
    }
    
    if (currentSection.value === 'referencia_i') return isReferenciaIComplete.value;
    
    return true;
});

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
    if (currentSection.value === 'referencia_v') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'general';
        currentPage.value = 1;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_iii') {
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
        if (showTraumaticQuestions.value) {
            currentSection.value = 'referencia_i';
            currentPage.value = 1;
            currentSubsection.value = 'general';
        } else {
            currentSection.value = 'final';
        }
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'final';
        // Scroll suave hacia arriba  
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const previousSection = () => {
    if (currentSection.value === 'final') {
        if (showTraumaticQuestions.value) {
            currentSection.value = 'referencia_i';
        } else {
            currentSection.value = 'referencia_iii';
            currentSubsection.value = 'traumatic';
            currentPage.value = 1;
        }
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'traumatic';
        currentPage.value = 1;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_iii') {
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
            } else {
                // Si estamos en la primera subsección de referencia_iii, volvemos a referencia_v
                currentSection.value = 'referencia_v';
                currentPage.value = 1;
                // Scroll suave hacia arriba
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'final';
});

// Actualizar el progreso para incluir subsecciones
const progress = computed(() => {
    // Calcular el progreso basado en secciones y subsecciones en el nuevo orden
    const mainSections = ['referencia_v', 'referencia_iii'];
    if (showTraumaticQuestions.value) {
        mainSections.push('referencia_i');
    }
    mainSections.push('final');
    
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
const isSubmitting = ref(false);

// URLs de audio para las preguntas usando el composable
const audioUrls = useAudioUrls(props.quiz);

// URLs de video para las preguntas usando el composable
const videoUrls = useVideoUrls(props.quiz);

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

const submitEvaluation = () => {
    isSubmitting.value = true;
    
    // Crear FormData para manejar archivos
    const formData = new FormData();
    
    // Agregar datos de respuestas como JSON
    formData.append('referencia_iii', JSON.stringify(answers.value.referencia_iii));
    formData.append('referencia_i', JSON.stringify(answers.value.referencia_i || {}));
    
    // Separar archivos de imágenes de los demás datos de referencia_v
    const referenciaVData = { ...answers.value.referencia_v };
    
    // Manejar archivos de INE si existen
    if (referenciaVData.ine_frente && referenciaVData.ine_frente instanceof File) {
        formData.append('ine_frente', referenciaVData.ine_frente);
        delete referenciaVData.ine_frente; // Remover del objeto JSON
    }
    
    if (referenciaVData.ine_reverso && referenciaVData.ine_reverso instanceof File) {
        formData.append('ine_reverso', referenciaVData.ine_reverso);
        delete referenciaVData.ine_reverso; // Remover del objeto JSON
    }
    
    // Agregar el resto de datos de referencia_v
    formData.append('referencia_v', JSON.stringify(referenciaVData));
    
    // Agregar campos personalizados
    formData.append('custom_fields', JSON.stringify(answers.value.custom_fields || {}));
    
    console.log('Enviando datos con FormData');
    
    // Usar router de Inertia para enviar los datos con FormData
    router.post(route('quiz.submit', props.quiz.id), formData, {
        forceFormData: true,
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
                <span v-else-if="currentSection === 'referencia_iii'">
                    Sección 2:
                    <span v-if="currentSubsection === 'general'">Cuestionario Principal</span>
                    <span v-else-if="currentSubsection === 'conditional'">Preguntas Condicionales</span>
                    <span v-else-if="currentSubsection === 'traumatic'">Acontecimientos Traumáticos</span>
                    <span v-if="currentSubsection === 'general'"> • Página {{ currentPage }} de {{ totalPages }}</span>
                </span>
                <span v-else-if="currentSection === 'referencia_i'">
                    Sección 3: Preguntas Adicionales
                </span>
                <span v-else-if="currentSection === 'final'">
                    Finalización: Guardar Respuestas
                </span>
            </ProgressBar>

            <!-- Controles de visualización -->
            <ViewModeToggle v-model="viewMode" />

            <!-- Contenido principal -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-4 sm:p-6">
                    <!-- Encabezado -->
                    <div class="mb-8">
                        <h1 class="text-xl font-medium text-slate-900">CUESTIONARIO PARA IDENTIFICAR LOS FACTORES DE RIESGO PSICOSOCIAL Y EVALUAR EL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO</h1>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <div
                                v-for="(section, key) in {
                                    referencia_v: 'Datos Personales',
                                    referencia_iii: 'Cuestionario Principal',
                                    referencia_i: 'Preguntas Adicionales'
                                }"
                                :key="key"
                                class="px-3 py-1.5 text-sm rounded-full transition-colors"
                                :class="currentSection === key ? 'bg-slate-100 text-slate-800' : 'bg-slate-50 text-slate-600'"
                            >
                                {{ section }}
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia V -->
                    <div v-if="currentSection === 'referencia_v'" class="space-y-6">
                        <!-- Datos personales -->
                        <PersonalDataSection 
                            v-model="answers.referencia_v" 
                            :reference-data="quiz.reference_v"
                        />

                        <!-- Campos Personalizados -->
                        <CustomFieldsSection 
                            v-if="quiz.custom_fields && quiz.custom_fields.length > 0"
                            :custom-fields="quiz.custom_fields" 
                            v-model="answers.custom_fields"
                        />

                        <!-- Datos Laborales -->
                        <LaborDataSection 
                            v-model="answers.referencia_v.datos_laborales"
                            :laboral-data="quiz.reference_v.datos_laborales"
                            :organization="quiz.organization"
                        />
                    </div>

                    <!-- Sección Referencia III -->
                    <div v-if="currentSection === 'referencia_iii'" class="space-y-6">
                        <!-- Preguntas generales -->
                        <div v-if="currentSubsection === 'general'">
                            <GeneralQuestionsSection
                                :paginated-questions="paginatedQuestions"
                                v-model="answers.referencia_iii"
                                :answer-options="answerOptions.general"
                                :view-mode="viewMode"
                                :audio-urls="audioUrls"
                                :video-urls="videoUrls"
                            />
                        </div>

                        <!-- Secciones condicionales -->
                        <div v-if="currentSubsection === 'conditional'">
                            <ConditionalQuestionsSection
                                :conditional-sections="quiz.questions.conditional_sections"
                                v-model="answers.referencia_iii"
                                :yes-no-options="answerOptions.yesNo"
                                :general-options="answerOptions.general"
                                :audio-urls="audioUrls"
                                :video-urls="videoUrls"
                            />
                        </div>

                        <!-- Acontecimientos traumáticos -->
                        <div v-if="currentSubsection === 'traumatic'">
                            <TraumaticEventsSection
                                :title="quiz.questions.acontecimientos_traumaticos.title"
                                :questions="quiz.questions.acontecimientos_traumaticos.questions"
                                v-model="answers.referencia_iii.acontecimientos_traumaticos"
                                :answer-options="answerOptions.yesNo"
                                name-prefix="trauma"
                                :audio-urls="audioUrls"
                                :video-urls="videoUrls"
                            />
                        </div>
                    </div>

                    <!-- Sección Referencia I -->
                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <FollowUpQuestionsSection
                            :follow-up-questions="quiz.reference_i"
                            v-model="answers.referencia_i"
                            :answer-options="answerOptions.yesNo"
                            :audio-urls="audioUrls"
                            :video-urls="videoUrls"
                        />
                    </div>

                    <!-- Sección Final -->
                    <div v-if="currentSection === 'final'" class="space-y-6">
                        <FinalSection
                            :is-submitting="isSubmitting"
                            @submit="submitEvaluation"
                        />
                        <div class="flex justify-center mt-6">
                            <button
                                type="button"
                                @click="() => {
                                    currentSection = 'referencia_v';
                                    currentSubsection = 'general';
                                    currentPage = 1;
                                }"
                                class="px-6 py-2 rounded-md bg-slate-100 text-slate-800 font-medium border border-slate-300 hover:bg-slate-200 transition-colors"
                            >
                                Revisar todas mis respuestas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Navegación (no mostrar en la sección final ya que tiene su propio botón) -->
                <div v-if="currentSection !== 'final'" class="mt-8 flex flex-col space-y-4 px-4 sm:px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <button
                            v-if="currentSection !== 'referencia_v'"
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
                                @click="nextSection"
                                :disabled="!canAdvanceFromCurrentSection"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :title="!canAdvanceFromCurrentSection ? 'Por favor completa todas las preguntas requeridas antes de continuar' : ''"
                            >
                                {{
                                    currentSection === 'referencia_iii' ?
                                        (currentSubsection === 'general' && currentPage < totalPages ? 'Siguiente página' : 'Siguiente subsección') :
                                        'Siguiente sección'
                                }}
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