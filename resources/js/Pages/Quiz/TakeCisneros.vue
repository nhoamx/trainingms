<template>
    <QuizLayout :title="quiz.name">
        <div class="max-w-4xl mx-auto px-4 sm:px-0">
            <!-- Barra de progreso -->
            <ProgressBar :progress="progress">
                <span v-if="currentSection === 'referencia_v'">
                    Sección 1: Datos Personales
                </span>
                <span v-else-if="currentSection === 'escala_cisneros'">
                    Sección 2: Escala Cisneros
                </span>
                <span v-else-if="currentSection === 'acontecimientos_traumaticos'">
                    Sección 3: Acontecimientos Traumáticos
                </span>
                <span v-else-if="currentSection === 'referencia_i'">
                    Sección 4: Preguntas de Seguimiento
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
                        <h1 class="text-xl font-medium text-slate-900">{{ quiz.name }}</h1>
                        <div class="mt-2 text-sm text-slate-600">
                            Evaluación Escala Cisneros
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
                        <PersonalDataSection v-model="answers.referencia_v" :reference-data="quiz.reference_v" />

                        <!-- Campos Personalizados -->
                        <CustomFieldsSection 
                            v-if="quiz.custom_fields && quiz.custom_fields.length > 0"
                            :custom-fields="quiz.custom_fields" 
                            v-model="answers.custom_fields"
                        />

                        <!-- Datos Laborales -->
                        <LaborDataSection v-model="answers.referencia_v.datos_laborales"
                            :laboral-data="quiz.reference_v.datos_laborales" :organization="quiz.organization" />
                    </div>

                    <!-- Sección Escala Cisneros -->
                    <div v-if="currentSection === 'escala_cisneros'" class="space-y-6">
                        <EscalaCisneros v-model="answers.escala_cisneros" :audio-urls="audioUrls" :video-urls="videoUrls" />
                    </div>

                    <!-- Sección Acontecimientos Traumáticos -->
                    <div v-if="currentSection === 'acontecimientos_traumaticos'" class="space-y-6">
                        <TraumaticEventsSection
                            :title="quiz.questions?.acontecimientos_traumaticos?.title || 'Acontecimientos Traumáticos'"
                            :questions="traumaticQuestions" v-model="answers.acontecimientos_traumaticos"
                            :answer-options="answerOptions.yesNo" name-prefix="trauma" :audio-urls="audioUrls" :video-urls="videoUrls" />
                    </div>

                    <div v-if="currentSection === 'referencia_i'" class="space-y-6">
                        <FollowUpQuestionsSection :follow-up-questions="quiz.reference_i" v-model="answers.referencia_i"
                            :answer-options="answerOptions.yesNo" :audio-urls="audioUrls" :video-urls="videoUrls" />
                    </div>

                    <!-- Sección Final -->
                    <div v-if="currentSection === 'final'" class="space-y-6">
                        <FinalSection :is-submitting="isSubmitting" @submit="submitEvaluation" />
                        <div class="flex justify-center mt-6">
                            <!-- <button type="button" @click="currentSection = 'referencia_v'"
                                class="px-6 py-2 rounded-md bg-slate-100 text-slate-800 font-medium border border-slate-300 hover:bg-slate-200 transition-colors">
                                Revisar todas mis respuestas
                            </button> -->
                        </div>
                    </div>
                </div>

                <!-- Navegación (no mostrar en la sección final ya que tiene su propio botón) -->
                <div v-if="currentSection !== 'final'" class="mt-8 flex flex-col space-y-4 px-4 sm:px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
                        <button v-if="currentSection !== 'referencia_v'" @click="previousSection"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
                            Sección anterior
                        </button>
                        <div class="flex items-center justify-center sm:justify-end space-x-4">
                            <button @click="nextSection"
                                class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-slate-800 rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Siguiente sección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>

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
import OrganizationInfoSection from '@/Components/Quiz/OrganizationInfoSection.vue';
import EscalaCisneros from '@/Components/Quiz/EscalaCisneros.vue';
import TraumaticEventsSection from '@/Components/Quiz/TraumaticEventsSection.vue';
import FollowUpQuestionsSection from '@/Components/Quiz/FollowUpQuestionsSection.vue';
import FinalSection from '@/Components/Quiz/FinalSection.vue';

const currentSection = ref('referencia_v');
const showFollowUpQuestions = ref(false);

const props = defineProps({
    quiz: Object,
    workCenterName: String
});

const answers = ref({
    escala_cisneros: {},
    acontecimientos_traumaticos: {},
    referencia_i: {},
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

const answerOptions = {
    yesNo: [
        { label: 'Sí', value: true },
        { label: 'No', value: false }
    ]
};

// Computed para obtener las preguntas traumáticas del quiz
const traumaticQuestions = computed(() => props.quiz?.questions?.acontecimientos_traumaticos?.questions || []);

// Helpers para la sección de acontecimientos traumáticos
const traumaticAnswers = computed(() => answers.value.acontecimientos_traumaticos || {});
const allTraumaticAnswered = computed(() => {
    return traumaticQuestions.value.length > 0 && traumaticQuestions.value.every((_, idx) => traumaticAnswers.value[idx] !== undefined);
});
const allTraumaticNo = computed(() => {
    return traumaticQuestions.value.length > 0 && traumaticQuestions.value.every((_, idx) => traumaticAnswers.value[idx] === false);
});
const traumaticHasYes = computed(() => {
    return Object.values(traumaticAnswers.value).some(v => v === true);
});

const checkTraumaticEvents = () => {
    const traumaticAnswers = answers.value.acontecimientos_traumaticos || {};
    showFollowUpQuestions.value = Object.values(traumaticAnswers).some(answer => answer === true);
};

const nextSection = () => {
    if (currentSection.value === 'referencia_v') {
        currentSection.value = 'escala_cisneros';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'escala_cisneros') {
        currentSection.value = 'acontecimientos_traumaticos';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'acontecimientos_traumaticos') {
        checkTraumaticEvents();
        if (showFollowUpQuestions.value) {
            currentSection.value = 'referencia_i';
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
        if (showFollowUpQuestions.value) {
            currentSection.value = 'referencia_i';
        } else {
            currentSection.value = 'acontecimientos_traumaticos';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'referencia_i') {
        currentSection.value = 'acontecimientos_traumaticos';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'acontecimientos_traumaticos') {
        currentSection.value = 'escala_cisneros';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (currentSection.value === 'escala_cisneros') {
        currentSection.value = 'referencia_v';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const progress = computed(() => {
    const sections = ['referencia_v', 'escala_cisneros', 'acontecimientos_traumaticos'];
    if (showFollowUpQuestions.value) {
        sections.push('referencia_i');
    }
    sections.push('final');
    
    const currentIndex = sections.indexOf(currentSection.value);
    return ((currentIndex + 1) / sections.length) * 100;
});

// Agregar estado para el modo de visualización
const viewMode = ref('comfortable');
const isSubmitting = ref(false);

// URLs de audio para las preguntas usando el composable
const audioUrls = useAudioUrls(props.quiz);

// URLs de video para las preguntas usando el composable
const videoUrls = useVideoUrls(props.quiz);

const submitEvaluation = () => {
    isSubmitting.value = true;

    // Crear FormData para manejar archivos
    const formData = new FormData();
    
    // Agregar datos de respuestas como JSON
    formData.append('escala_cisneros', JSON.stringify(answers.value.escala_cisneros));
    formData.append('referencia_iii', JSON.stringify({ acontecimientos_traumaticos: answers.value.acontecimientos_traumaticos }));
    formData.append('referencia_i', JSON.stringify(answers.value.referencia_i || {}));
    
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

    console.log('Enviando datos con FormData');

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
