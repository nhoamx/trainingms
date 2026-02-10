<script setup>
import { ref, computed, watch } from 'vue';
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
import OrganizationInfoSection from '@/Components/Quiz/OrganizationInfoSection.vue';
import TraumaticEventsSection from '@/Components/Quiz/TraumaticEventsSection.vue';
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import NavigationButtons from '@/Components/Quiz/NavigationButtons.vue';
import GeneralQuestionsSection from '@/Components/Quiz/GeneralQuestionsSection.vue';
import ConditionalQuestionsSection from '@/Components/Quiz/ConditionalQuestionsSection.vue';
import FinalSection from '@/Components/Quiz/FinalSection.vue';

const currentSection = ref('referencia_v');
const showTraumaticQuestions = ref(false);
const currentBlockIndex = ref(0);
const currentSubsection = ref('general'); // 'general', 'conditional', 'traumatic'

const props = defineProps({
    quiz: Object,
    workCenterName: String,
    disableAudioValidation: {
        type: Boolean,
        default: false
    }
});

const answers = ref({
    referencia_iii: {},
    referencia_i: {},
    evaluee_name: '',
    organization_info: {
        nombre_comercial: '',
        estado: '',
        ciudad: ''
    },
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

// Watcher para debugging evaluee_name
watch(() => answers.value.evaluee_name, (newValue, oldValue) => {
    console.log('🟢 [Take] evaluee_name changed', {
        oldValue,
        newValue,
        type: typeof newValue,
        isNull: newValue === null,
        isEmpty: newValue === '',
        length: newValue?.length
    });
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
const isReferenciaVComplete = computed(() => {
    const rv = answers.value.referencia_v;
    const dl = rv.datos_laborales;
    const org = answers.value.organization_info;
    
    //return org.nombre_comercial && org.estado && org.ciudad &&
    return rv.sexo && rv.edad && rv.estado_civil && rv.nivel_estudios &&
           dl.ocupacion_puesto && dl.departamento_seccion_area && dl.tipo_puesto && dl.tipo_contratacion &&
           dl.tipo_personal && dl.rotacion_turnos &&
           dl.experiencia.tiempo_puesto_actual && dl.experiencia.tiempo_experiencia_laboral;
});

const isGeneralQuestionsComplete = computed(() => {
    const generalAnswers = answers.value.referencia_iii || {};
    
    // Si estamos en bloques, solo validar el bloque actual
    if (currentSection.value === 'referencia_iii' && currentSubsection.value === 'general') {
        if (!currentBlock.value) return false;
        
        return currentBlock.value.questions.every(q => generalAnswers[q.id] !== undefined);
    }
    
    // Validación completa si no estamos en la subsección general
    const allGeneralQuestions = props.quiz?.questions?.general || {};
    return Object.keys(allGeneralQuestions).every(key => generalAnswers[key] !== undefined);
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
    
    // Verificar que todas las preguntas (1-6) tengan respuesta
    for (let i = 1; i <= traumaticQuestions.length; i++) {
        if (traumaticAnswers[i] === undefined) {
            return false;
        }
    }
    return true;
});

const isReferenciaIComplete = computed(() => {
    if (!showTraumaticQuestions.value) return true;
    
    const followUpQuestions = props.quiz?.reference_i || {};
    const referenciaIAnswers = answers.value.referencia_i || {};
    
    // followUpQuestions es un objeto con categorías, cada una con un array de preguntas
    // Necesitamos contar el TOTAL de preguntas individuales
    if (typeof followUpQuestions !== 'object' || Object.keys(followUpQuestions).length === 0) return true;
    
    // Contar el número total de preguntas sumando las preguntas de cada categoría
    let totalQuestions = 0;
    Object.values(followUpQuestions).forEach(categoryQuestions => {
        if (Array.isArray(categoryQuestions)) {
            totalQuestions += categoryQuestions.length;
        }
    });
    
    // Validar que todas las preguntas estén respondidas (índices 1-based)
    const answeredCount = Object.keys(referenciaIAnswers).length;
    const allQuestionsAnswered = answeredCount >= totalQuestions && 
        Array.from({ length: totalQuestions }, (_, i) => i + 1)
            .every(idx => referenciaIAnswers[idx] !== undefined);
    
    // Validar que se haya ingresado el nombre
    const hasName = answers.value.evaluee_name && answers.value.evaluee_name.trim().length > 0;
    
    console.log('🔍 [VALIDATION] isReferenciaIComplete', {
        allQuestionsAnswered,
        hasName,
        evaluee_name_value: answers.value.evaluee_name,
        answered_count: answeredCount,
        required_count: totalQuestions,
        categories: Object.keys(followUpQuestions).length
    });
    
    return allQuestionsAnswered && hasName;
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

// Computed properties for blocks
const sections = computed(() => {
    if (currentSection.value === 'referencia_iii' && props.quiz?.questions) {
        return ['general', 'conditional', 'traumatic'];
    }
    return [];
});

const currentBlock = computed(() => {
    if (props.quiz?.questions?.general_blocks && currentBlockIndex.value < props.quiz.questions.general_blocks.length) {
        const block = props.quiz.questions.general_blocks[currentBlockIndex.value];
        return {
            ...block,
            blockNumber: currentBlockIndex.value + 1,
            totalBlocks: props.quiz.questions.general_blocks.length,
            questions: block.questions.map(qId => ({
                id: String(qId), // Convert to string to maintain question IDs as keys
                text: props.quiz.questions.general[qId]
            }))
        };
    }
    return null;
});

const nextSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'general';
        currentBlockIndex.value = 0;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_iii') {
        if (currentSubsection.value === 'general') {
            // Si hay más bloques, avanzar al siguiente bloque
            if (currentBlockIndex.value < (props.quiz?.questions?.general_blocks?.length || 1) - 1) {
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
            // Scroll suave hacia arriba
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
            currentBlockIndex.value = 0;
        }
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'referencia_iii';
        currentSubsection.value = 'traumatic';
        currentBlockIndex.value = 0;
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_iii') {
        // Dentro de la sección referencia_iii
        if (currentSubsection.value === 'general' && currentBlockIndex.value > 0) {
            // Si estamos en preguntas generales y no estamos en el primer bloque
            currentBlockIndex.value--;
            // Scroll suave hacia arriba
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            // Si estamos en el primer bloque de una subsección
            const currentIndex = sections.value.indexOf(currentSubsection.value);
            if (currentIndex > 0) {
                // Si no estamos en la primera subsección
                currentSubsection.value = sections.value[currentIndex - 1];
                // Establecer el bloque a la última de la subsección anterior
                if (currentSubsection.value === 'general') {
                    currentBlockIndex.value = (props.quiz?.questions?.general_blocks?.length || 1) - 1;
                } else {
                    currentBlockIndex.value = 0;
                }
                // Scroll suave hacia arriba
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                // Si estamos en la primera subsección de referencia_iii, volvemos a referencia_v
                currentSection.value = 'referencia_v';
                currentBlockIndex.value = 0;
                // Scroll suave hacia arriba
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    }
};

const isLastSection = computed(() => {
    return currentSection.value === 'final';
});

const transformToStandardizedStructure = () => {
    const refIII = answers.value.referencia_iii || {};
    const refI = answers.value.referencia_i || {};
    
    // Extraer preguntas generales (1-64)
    const generalQuestions = {};
    for (let i = 1; i <= 64; i++) {
        if (refIII[i] !== undefined) {
            generalQuestions[i] = refIII[i];
        }
    }
    
    // Construir referencia_iii estandarizada
    const referenciaIII = {
        ...generalQuestions
    };
    
    // Extraer sección customer_service (65-68) - SOLO si existe
    if (refIII.customer_service !== undefined) {
        referenciaIII.customer_service = refIII.customer_service;
    }
    
    // Extraer sección management (69-72) - SOLO si existe
    if (refIII.management !== undefined) {
        referenciaIII.management = refIII.management;
    }
    
    // Extraer acontecimientos traumáticos (ats_s1) - índices 1-6
    const atsS1 = refIII.acontecimientos_traumaticos || {};
    if (Object.keys(atsS1).length > 0) {
        referenciaIII.ats_s1 = atsS1;
    }
    
    // Referencia I ya viene con índices 1-13
    const referenciaI = { ...refI };
    
    return {
        referencia_iii: referenciaIII,
        referencia_i: referenciaI
    };
};

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
    
    // Transformar datos a estructura estandarizada
    const standardizedData = transformToStandardizedStructure();
    
    // Crear FormData para manejar archivos
    const formData = new FormData();
    
    // Agregar datos de respuestas como JSON
    formData.append('referencia_iii', JSON.stringify(standardizedData.referencia_iii));
    formData.append('referencia_i', JSON.stringify(standardizedData.referencia_i));
    
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
    
    // Agregar información de organización ingresada por el usuario
    formData.append('organization_info', JSON.stringify(answers.value.organization_info));
    
    // Agregar campos personalizados
    formData.append('custom_fields', JSON.stringify(answers.value.custom_fields || {}));
    
    // Agregar nombre del evaluado (SIEMPRE, aunque esté vacío)
    const evalueeNameValue = answers.value.evaluee_name || '';
    console.log('🟡 [SUBMIT] Preparando evaluee_name para envío', {
        raw_value: answers.value.evaluee_name,
        type: typeof answers.value.evaluee_name,
        isNull: answers.value.evaluee_name === null,
        isEmpty: answers.value.evaluee_name === '',
        value_to_send: evalueeNameValue,
        coerced_type: typeof evalueeNameValue
    });
    formData.append('evaluee_name', evalueeNameValue);
    
    console.log('📤 [SUBMIT] Enviando datos con FormData', {
        has_evaluee_name: !!answers.value.evaluee_name,
        evaluee_name_value: answers.value.evaluee_name,
        evaluee_name_length: answers.value.evaluee_name?.length || 0,
        formData_keys: Array.from(formData.keys())
    });
    
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
                    Cuestionario (GR V)
                </span>
                <span v-else-if="currentSection === 'referencia_iii'">
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
                    <div v-if="currentSection === 'referencia_v'" class="mb-8">
                        <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-l-4 border-slate-800 rounded-lg p-4 sm:p-6">
                            <h1 class="text-lg sm:text-xl font-semibold text-slate-900 mb-4">Indicaciones</h1>
                            <ol class="space-y-3 list-decimal list-inside text-sm sm:text-base text-slate-700">
                                <li class="leading-relaxed">
                                    <span class="font-medium">Contestarás tres cuestionarios</span> (Guías de referencia)
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

                    <div v-if="currentSection === 'traumatic'" class="mb-8">
                        <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-l-4 border-slate-800 rounded-lg p-4 sm:p-6">
                            <h1 class="text-lg sm:text-xl font-semibold text-slate-900 mb-4">Indicaciones</h1>
                            <div class="mt-4 pt-4 border-t border-slate-300">
                                <p class="text-sm sm:text-base text-slate-700 leading-relaxed">
                                    Si contestas por lo menos un si, de los 6 acontecimientos traumáticos severos, contestaras la sección 2, 3 y 4; que son las afectaciones  o síntomas y escribirás tu nombre en el apartado indicado.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia V -->
                    <div v-if="currentSection === 'referencia_v'" class="space-y-6">
                        <!-- Información de la Organización -->
                        <OrganizationInfoSection
                            v-model="answers.organization_info"
                            :organization-name="quiz.organization?.name || ''"
                            :work-center-name="workCenterName"
                        />

                        <!-- Datos personales -->
                        <PersonalDataSection 
                            v-model="answers.referencia_v" 
                            :reference-data="quiz.reference_v"
                        />

                        <!-- Campos Personalizados -->
                        <!-- <CustomFieldsSection 
                            v-if="quiz.custom_fields && quiz.custom_fields.length > 0"
                            :custom-fields="quiz.custom_fields" 
                            v-model="answers.custom_fields"
                        /> -->

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
                                :block="currentBlock"
                                v-model="answers.referencia_iii"
                                :answer-options="answerOptions.general"
                                :view-mode="viewMode"
                                :audio-urls="audioUrls"
                                :video-urls="videoUrls"
                                :disable-audio-validation="disableAudioValidation"
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
                                :disable-audio-validation="disableAudioValidation"
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
                                :disable-audio-validation="disableAudioValidation"
                            />
                        </div>
                    </div>

                    <!-- Sección Referencia I -->
                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <FollowUpQuestionsSection
                            :follow-up-questions="quiz.reference_i"
                            v-model="answers.referencia_i"
                            v-model:evalueName="answers.evaluee_name"
                            :answer-options="answerOptions.yesNo"
                            :audio-urls="audioUrls"
                            :video-urls="videoUrls"
                            :disable-audio-validation="disableAudioValidation"
                        />
                    </div>

                    <!-- Sección Final -->
                    <div v-if="currentSection === 'final'" class="space-y-6">
                        <FinalSection
                            :is-submitting="isSubmitting"
                            @submit="submitEvaluation"
                        />
                        <div class="flex justify-center mt-6">
                            <!-- <button
                                type="button"
                                @click="() => {
                                    currentSection = 'referencia_v';
                                    currentSubsection = 'general';
                                    currentBlockIndex = 0;
                                }"
                                class="px-6 py-2 rounded-md bg-slate-100 text-slate-800 font-medium border border-slate-300 hover:bg-slate-200 transition-colors"
                            >
                                Revisar todas mis respuestas
                            </button> -->
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
                                        (currentSubsection === 'general' && currentBlockIndex < (quiz?.questions?.general_blocks?.length || 1) - 1 ? 'Siguiente bloque' : 'Siguiente subsección') :
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