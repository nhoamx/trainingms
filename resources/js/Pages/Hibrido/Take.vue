<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useAudioUrls } from '@/composables/useAudioUrls';
import { useVideoUrls } from '@/composables/useVideoUrls';
import QuizLayout from '@/Layouts/QuizLayout.vue';
import ProgressBar from '@/Components/Quiz/ProgressBar.vue';
import ViewModeToggle from '@/Components/Quiz/ViewModeToggle.vue';
import TraumaticEventsSection from '@/Components/Quiz/TraumaticEventsSection.vue';
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import NavigationButtons from '@/Components/Quiz/NavigationButtons.vue';
import GeneralQuestionsSection from '@/Components/Quiz/GeneralQuestionsSection.vue';
import ConditionalQuestionsSection from '@/Components/Quiz/ConditionalQuestionsSection.vue';
import FinalSection from '@/Components/Quiz/FinalSection.vue';

const currentSection = ref('referencia_iii');
const showTraumaticQuestions = ref(false);
const currentBlockIndex = ref(0);
const currentSubsection = ref('general'); // 'general', 'conditional', 'traumatic'

const props = defineProps({
    evaluationId: String,
    folio: String,
    organizationName: String,
    questions: Object,
    referencia_i_questions: Object
});

const answers = ref({
    referencia_iii: {},
    referencia_i: {}
});

const answerOptions = {
    general: [
        { label: 'Siempre', value: 'A' },
        { label: 'Casi siempre', value: 'B' },
        { label: 'Algunas veces', value: 'C' },
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
const isGeneralQuestionsComplete = computed(() => {
    const generalAnswers = answers.value.referencia_iii || {};
    
    // Si estamos en bloques, solo validar el bloque actual
    if (currentSection.value === 'referencia_iii' && currentSubsection.value === 'general') {
        if (!currentBlock.value) return false;
        
        return currentBlock.value.questions.every(q => generalAnswers[q.id] !== undefined);
    }
    
    // Validación completa si no estamos en la subsección general
    const allGeneralQuestions = props.questions?.general || {};
    return Object.keys(allGeneralQuestions).every(key => generalAnswers[key] !== undefined);
});

const isConditionalQuestionsComplete = computed(() => {
    const conditionalSections = props.questions?.conditional_sections || [];
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
    const traumaticQuestions = props.questions?.acontecimientos_traumaticos?.questions || [];
    const traumaticAnswers = answers.value.referencia_iii.acontecimientos_traumaticos || {};
    
    if (!Array.isArray(traumaticQuestions) || traumaticQuestions.length === 0) return true;
    
    return traumaticQuestions.every((_, idx) => traumaticAnswers[idx] !== undefined);
});

const isReferenciaIComplete = computed(() => {
    if (!showTraumaticQuestions.value) return true;
    
    const followUpQuestions = props.referencia_i_questions || [];
    const referenciaIAnswers = answers.value.referencia_i || {};
    
    if (!Array.isArray(followUpQuestions) || followUpQuestions.length === 0) return true;
    
    return followUpQuestions.every((_, idx) => referenciaIAnswers[idx] !== undefined);
});

const canAdvanceFromCurrentSection = computed(() => {
    if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general') return isGeneralQuestionsComplete.value;
        if (currentSubsection.value === 'conditional') return isConditionalQuestionsComplete.value;
        if (currentSubsection.value === 'traumatic') return isAcontecimientosComplete.value;
    }
    
    if (currentSection.value === 'referencia_i') return isReferenciaIComplete.value;
    
    return true;
});

// Computed properties for blocks
const sections = computed(() => {
    if (currentSection.value === 'referencia_iii' && props.questions) {
        return ['general', 'conditional', 'traumatic'];
    }
    return [];
});

const currentBlock = computed(() => {
    if (props.questions?.general_blocks && currentBlockIndex.value < props.questions.general_blocks.length) {
        const block = props.questions.general_blocks[currentBlockIndex.value];
        return {
            ...block,
            blockNumber: currentBlockIndex.value + 1,
            totalBlocks: props.questions.general_blocks.length,
            questions: block.questions.map(qId => ({
                id: qId,
                text: props.questions.general[qId]
            }))
        };
    }
    return null;
});

const nextSection = () => {
    if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general') {
            // Si hay más bloques, avanzar al siguiente bloque
            if (currentBlockIndex.value < (props.questions?.general_blocks?.length || 1) - 1) {
                currentBlockIndex.value++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            // Si estamos en el último bloque, pasar a la siguiente subsección
            currentSubsection.value = 'conditional';
            currentBlockIndex.value = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const currentIndex = sections.value.indexOf(currentSubsection.value);
        if (currentIndex < sections.value.length - 1) {
            // Si hay más subsecciones, vamos a la siguiente
            currentSubsection.value = sections.value[currentIndex + 1];
            currentBlockIndex.value = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        // Pasamos a la siguiente sección principal
        checkTraumaticEvents();
        if (showTraumaticQuestions.value) {
            currentSection.value = 'referencia_i';
            currentBlockIndex.value = 0;
            currentSubsection.value = 'general';
        } else {
            currentSection.value = 'final';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'final';
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
            currentBlockIndex.value = 0;
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'traumatic';
        currentBlockIndex.value = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_iii') {
        // Dentro de la sección referencia_iii
        if (currentSubsection.value === 'general' && currentBlockIndex.value > 0) {
            // Si estamos en preguntas generales y no estamos en el primer bloque
            currentBlockIndex.value--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // Si estamos en el primer bloque de una subsección
            const currentIndex = sections.value.indexOf(currentSubsection.value);
            if (currentIndex > 0) {
                // Si no estamos en la primera subsección
                currentSubsection.value = sections.value[currentIndex - 1];
                // Establecer el bloque a la última de la subsección anterior
                if (currentSubsection.value === 'general') {
                    currentBlockIndex.value = (props.questions?.general_blocks?.length || 1) - 1;
                } else {
                    currentBlockIndex.value = 0;
                }
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
    // Calcular el progreso basado en secciones y subsecciones (sin referencia_v)
    const mainSections = ['referencia_iii'];
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

// URLs de audio para las preguntas usando el composable (sin quiz, usamos props.questions)
const audioUrls = useAudioUrls({ questions: props.questions });

// URLs de video para las preguntas usando el composable
const videoUrls = useVideoUrls({ questions: props.questions });

const submitEvaluation = () => {
    isSubmitting.value = true;
    
    const payload = {
        referencia_iii: JSON.stringify(answers.value.referencia_iii),
        referencia_iii_conditional: JSON.stringify(answers.value.referencia_iii),
        referencia_i: JSON.stringify(answers.value.referencia_i || {})
    };
    
    console.log('Enviando datos híbridos:', payload);
    
    // Usar router.put() para actualizar la evaluación híbrida
    router.put(route('hybrid.update', props.evaluationId), payload, {
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
    <QuizLayout title="Evaluación Híbrida">
        <div class="max-w-4xl mx-auto px-4 sm:px-0">
            <!-- Header con Folio -->
            <div class="mb-6 bg-white rounded-lg shadow-sm border border-slate-200 p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">Evaluación Híbrida</h1>
                        <p class="text-sm text-slate-600 mt-1">{{ organizationName }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-600 font-medium">Folio:</span>
                        <div class="px-4 py-2 bg-slate-100 border border-slate-300 rounded-md">
                            <span class="text-lg font-mono font-bold text-slate-900">{{ folio }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra de progreso -->
            <ProgressBar :progress="progress">
                <span v-if="currentSection === 'referencia_iii'">
                    <span v-if="currentSubsection === 'general'">Cuestionario (GR III)</span>
                    <span v-else-if="currentSubsection === 'conditional'">Cuestionario (GR III)</span>
                    <span v-else-if="currentSubsection === 'traumatic'">Cuestionario (GR III) Acontecimientos Traumáticos</span>
                    <span v-if="currentSubsection === 'general' && currentBlock"> • Bloque {{ currentBlock.blockNumber }} de {{ currentBlock.totalBlocks }}</span>
                </span>
                <span v-else-if="currentSection === 'referencia_i'">
                    Cuestionario (GR I)
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
                    <!-- Encabezado con Indicaciones -->
                    <div v-if="currentSection === 'referencia_iii' && currentSubsection === 'general' && currentBlockIndex === 0" class="mb-8">
                        <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-l-4 border-slate-800 rounded-lg p-4 sm:p-6">
                            <h2 class="text-lg sm:text-xl font-semibold text-slate-900 mb-4">Indicaciones</h2>
                            <ol class="space-y-3 list-decimal list-inside text-sm sm:text-base text-slate-700">
                                <li class="leading-relaxed">
                                    <span class="font-medium">Completarás dos cuestionarios</span> (Guías de referencia III e I)
                                </li>
                                <li class="leading-relaxed">
                                    Contestar <span class="font-medium">objetivamente con sinceridad</span> tu percepción de <span class="font-medium">dos meses a la fecha</span>, tomando en cuenta el departamento y actividades que realizas.
                                </li>
                                <li class="leading-relaxed">
                                    En <span class="font-medium">algunas preguntas deberás escuchar el audio</span> antes de contestar.
                                </li>
                                <li class="leading-relaxed">
                                    <span class="font-medium">Son 4 opciones de respuesta</span>, elige solo una de las siguientes:
                                </li>
                            </ol>
                            <div class="mt-4 pt-4 border-t border-slate-300">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex-shrink-0">A</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">Siempre</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold flex-shrink-0">B</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">Casi siempre</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold flex-shrink-0">C</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">Algunas veces</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold flex-shrink-0">D</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">Casi nunca</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia III -->
                    <div v-if="currentSection === 'referencia_iii'" class="space-y-6">
                        <!-- Preguntas generales -->
                        <div v-if="currentSubsection === 'general'">
                            <GeneralQuestionsSection
                                :block="currentBlock"
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
                                :conditional-sections="questions.conditional_sections"
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
                                :title="questions.acontecimientos_traumaticos.title"
                                :questions="questions.acontecimientos_traumaticos.questions"
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
                            :follow-up-questions="referencia_i_questions"
                            v-model="answers.referencia_i"
                            :answer-options="answerOptions.yesNo"
                            :audio-urls="audioUrls"
                            :video-urls="videoUrls"
                        />
                    </div>

                    <!-- Sección Final -->
                    <div v-if="currentSection === 'final'" class="space-y-6">
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-600 rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-green-900 mb-4">¡Listo para enviar!</h2>
                            <p class="text-green-800 mb-6">
                                Has completado todas las secciones del cuestionario. 
                                Presiona el botón de abajo para enviar tus respuestas.
                            </p>
                            <button
                                @click="submitEvaluation"
                                :disabled="isSubmitting"
                                class="w-full sm:w-auto px-8 py-3 text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-semibold"
                            >
                                {{ isSubmitting ? 'Enviando...' : 'Enviar Evaluación' }}
                            </button>
                        </div>
                        <div class="flex justify-center mt-6">
                            <button
                                type="button"
                                @click="() => {
                                    currentSection = 'referencia_iii';
                                    currentSubsection = 'general';
                                    currentBlockIndex = 0;
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
                            v-if="currentSection === 'referencia_i' || (currentSection === 'referencia_iii' && (currentSubsection !== 'general' || currentBlockIndex > 0))"
                            @click="previousSection"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors"
                        >
                            {{
                                currentSection === 'referencia_iii' ?
                                    (currentSubsection === 'general' && currentBlockIndex > 0 ? 'Bloque anterior' : 'Subsección anterior') :
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
                                        (currentSubsection === 'general' && currentBlockIndex < (questions?.general_blocks?.length || 1) - 1 ? 'Siguiente bloque' : 'Siguiente subsección') :
                                        'Siguiente sección'
                                }}
                            </button>
                        </div>
                    </div>
                    <!-- Mostrar progreso de bloques -->
                    <div v-if="currentSection === 'referencia_iii' && currentSubsection === 'general' && currentBlock" class="flex justify-center items-center">
                        <span class="text-sm text-slate-600">
                            Bloque {{ currentBlock.blockNumber }} de {{ currentBlock.totalBlocks }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>
