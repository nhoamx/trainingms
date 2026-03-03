<script setup>
import { ref, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useAudioUrls } from '@/composables/useAudioUrls';
import { useVideoUrls } from '@/composables/useVideoUrls';
import QuizLayout from '@/Layouts/QuizLayout.vue';
import ProgressBar from '@/Components/Quiz/ProgressBar.vue';
import ViewModeToggle from '@/Components/Quiz/ViewModeToggle.vue';
import PersonalDataSection from "../../Components/Quiz/PersonalDataSection.vue";
import LaborDataSection from "../../Components/Quiz/LaborDataSection.vue";
import CustomFieldsSection from "../../Components/Quiz/CustomFieldsSection.vue";
import OrganizationInfoSection from '../../Components/Quiz/OrganizationInfoSection.vue';
import TraumaticEventsSection from "../../Components/Quiz/TraumaticEventsSection.vue";
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import FinalSection from '@/Components/Quiz/FinalSection.vue';
import AudioPlayer from '@/Components/Quiz/AudioPlayer.vue';

const currentSection = ref('referencia_v');
const showFollowUpQuestions = ref(false);

const props = defineProps({
    quiz: Object,
    workCenterName: String
});

const answers = ref({
    acontecimientos_traumaticos: {},
    referencia_i: {},
    evaluee_name: '',
    custom_fields: {},
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

// Watcher para debugging evaluee_name
watch(() => answers.value.evaluee_name, (newValue, oldValue) => {
    console.log('🟢 [TakeReduced] evaluee_name changed', {
        oldValue,
        newValue,
        type: typeof newValue,
        isNull: newValue === null,
        isEmpty: newValue === '',
        length: newValue?.length
    });
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

// Validaciones
const isReferenciaVComplete = computed(() => {
    const rv = answers.value.referencia_v;
    const dl = rv.datos_laborales;
    const requiresWorkSchedule = props.quiz?.demographic_data?.work_schedule?.active === true;
    
    //return org.nombre_comercial && org.estado && org.ciudad &&
    return rv.sexo && rv.edad && rv.estado_civil && rv.nivel_estudios &&
           dl.ocupacion_puesto && dl.departamento_seccion_area && dl.tipo_puesto && dl.tipo_contratacion &&
            dl.tipo_personal && (!requiresWorkSchedule || dl.tipo_jornada) && dl.rotacion_turnos &&
           dl.experiencia.tiempo_puesto_actual && dl.experiencia.tiempo_experiencia_laboral;
});

const isAcontecimientosComplete = computed(() => {
    const traumaticQuestions = props.quiz?.questions?.acontecimientos_traumaticos?.questions || [];
    const traumaticAnswers = answers.value.acontecimientos_traumaticos || {};

    if (!Array.isArray(traumaticQuestions) || traumaticQuestions.length === 0) return true;

    return traumaticQuestions.every((_, idx) => traumaticAnswers[idx + 1] != null);
});

const isReferenciaIComplete = computed(() => {
    if (!showFollowUpQuestions.value) return true;
    
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
    if (currentSection.value === 'acontecimientos_traumaticos') return isAcontecimientosComplete.value;
    if (currentSection.value === 'referencia_i') return isReferenciaIComplete.value;
    return true;
});

const nextSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = 'acontecimientos_traumaticos';
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'acontecimientos_traumaticos') {
        checkTraumaticEvents();
        if (showFollowUpQuestions.value) {
            currentSection.value = 'referencia_i';
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
        if (showFollowUpQuestions.value) {
            currentSection.value = 'referencia_i';
        } else {
            currentSection.value = 'acontecimientos_traumaticos';
        }
        // Scroll suave hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
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
    return currentSection.value === 'final';
});

const progress = computed(() => {
    const sections = ['referencia_v', 'acontecimientos_traumaticos'];
    if (showFollowUpQuestions.value) {
        sections.push('referencia_i');
    }
    sections.push('final');
    
    const currentIndex = sections.indexOf(currentSection.value);
    return ((currentIndex + 1) / sections.length) * 100;
});

// Agregar estado para el modo de visualización
const viewMode = ref('comfortable'); // 'comfortable' o 'compact'
const isSubmitting = ref(false);

// URLs de audio para las preguntas usando el composable
const audioUrls = useAudioUrls(props.quiz);

// URLs de video para las preguntas usando el composable
const videoUrls = useVideoUrls(props.quiz);

// Helpers para la sección de acontecimientos traumáticos
const traumaticQuestions = computed(() => props.quiz?.questions?.acontecimientos_traumaticos?.questions || []);
const traumaticAnswers = computed(() => answers.value.acontecimientos_traumaticos || {});
const allTraumaticAnswered = computed(() => {
    return traumaticQuestions.value.length > 0 && traumaticQuestions.value.every((_, idx) => traumaticAnswers.value[idx + 1] !== undefined);
});
const allTraumaticNo = computed(() => {
    return traumaticQuestions.value.length > 0 && traumaticQuestions.value.every((_, idx) => traumaticAnswers.value[idx] === false);
});
const traumaticHasYes = computed(() => {
    return Object.values(traumaticAnswers.value).some(v => v === true);
});

const submitEvaluation = () => {
    isSubmitting.value = true;
    
    // Crear FormData para manejar archivos
    const formData = new FormData();
    
    // TakeReduced SOLO envía Referencia I (con acontecimientos traumáticos incluidos)
    // NO enviar referencia_iii para evitar crear 2 registros
    const referenciaIData = {
        acontecimientos_traumaticos: answers.value.acontecimientos_traumaticos,
        ...(answers.value.referencia_i || {})
    };
    formData.append('referencia_i', JSON.stringify(referenciaIData));
    
    // Separar archivos de imágenes de los demás datos de referencia_v
    const referenciaVData = { ...answers.value.referencia_v };
    
    // Manejar archivos de INE si existen
    if (referenciaVData.ine_frente && referenciaVData.ine_frente instanceof File) {
        formData.append('ine_frente', referenciaVData.ine_frente);
        delete referenciaVData.ine_frente;
    }
    
    if (referenciaVData.ine_reverso && referenciaVData.ine_reverso instanceof File) {
        formData.append('ine_reverso', referenciaVData.ine_reverso);
        delete referenciaVData.ine_reverso;
    }
    
    // Agregar el resto de datos de referencia_v
    formData.append('referencia_v', JSON.stringify(referenciaVData));
    
    // Agregar información de organización ingresada por el usuario
    formData.append('organization_info', JSON.stringify(answers.value.organization_info));
    
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
                <span v-else-if="currentSection === 'acontecimientos_traumaticos'">
                    Sección 2: Acontecimientos Traumáticos
                </span>
                <span v-else-if="currentSection === 'referencia_i'">
                    Sección 3: Preguntas de Seguimiento
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
                                    Contestar <span class="font-medium">objetivamente con sinceridad</span> tu percepción de <span class="font-medium">dos meses a la fecha</span>, tomando en cuenta el departamento y actividades que realizas.
                                </li>
                                <li class="leading-relaxed">
                                    En <span class="font-medium">algunas preguntas deberás escuchar el audio</span> antes de contestar.
                                </li>
                                <li class="leading-relaxed">
                                    <span class="font-medium">Son 2 opciones de respuesta</span>, elige solo una:
                                </li>
                            </ol>
                            <div class="mt-4 pt-4 border-t border-slate-300">
                                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold flex-shrink-0">✓</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">Sí</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white rounded-md p-2 sm:p-3 border border-slate-200">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-700 text-xs font-bold flex-shrink-0">✕</span>
                                        <span class="text-xs sm:text-sm text-slate-700 font-medium">No</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Referencia V -->
                    <div v-if="currentSection === 'referencia_v'" class="space-y-6">
                        <!-- Información de la Organización -->
                        <!-- <OrganizationInfoSection
                            v-model="answers.organization_info"
                            :organization-name="quiz.organization?.name || ''"
                            :work-center-name="workCenterName"
                        /> -->

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
                            :work-schedule-config="quiz.demographic_data?.work_schedule"
                        />
                    </div>

                    <!-- Sección Acontecimientos Traumáticos -->
                    <div v-if="currentSection === 'acontecimientos_traumaticos'" class="space-y-6">
                        <div class="mb-8">
                            <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-l-4 border-slate-800 rounded-lg p-4 sm:p-6">
                                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 mb-4">Indicaciones</h1>
                                <div class="mt-4 pt-4 border-t border-slate-300">
                                    <p class="text-sm sm:text-base text-slate-700 leading-relaxed mb-4">
                                        Si contestas por lo menos un si, de los 6 acontecimientos traumáticos severos, contestaras la sección 2, 3 y 4; que son las afectaciones  o síntomas y escribirás tu nombre en el apartado indicado.
                                    </p>
                                    <div class="flex justify-start">
                                        <AudioPlayer
                                            audio-url="https://trainingms.sfo3.cdn.digitaloceanspaces.com/devel/audios/ats-instructions.m4a"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <TraumaticEventsSection
                            :title="quiz.questions.acontecimientos_traumaticos.title"
                            :questions="quiz.questions.acontecimientos_traumaticos.questions"
                            v-model="answers.acontecimientos_traumaticos"
                            :answer-options="answerOptions.yesNo"
                            name-prefix="trauma"
                            :audio-urls="audioUrls"
                            :video-urls="videoUrls"
                        />
                    </div>

                    <!-- Sección Referencia I (solo si hay respuestas positivas) -->
                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <FollowUpQuestionsSection
                            :follow-up-questions="quiz.reference_i"
                            v-model="answers.referencia_i"
                            v-model:evalueName="answers.evaluee_name"
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
                            <!-- <button
                                type="button"
                                @click="currentSection = 'referencia_v'"
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
                            Sección anterior
                        </button>
                        <div class="flex items-center justify-center sm:justify-end space-x-4">
                            <button
                                @click="nextSection"
                                :disabled="!canAdvanceFromCurrentSection"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :title="!canAdvanceFromCurrentSection ? 'Por favor completa todas las preguntas requeridas antes de continuar' : ''"
                            >
                                Siguiente sección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>