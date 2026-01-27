<template>
    <div class="bg-slate-50 p-4 rounded-lg">
        <h3 class="font-medium text-slate-900 mb-4">Escala Cisneros</h3>
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded text-slate-800 text-sm">
            <p class="mb-2 font-semibold">Indicaciones:</p>
            <ul class="list-disc pl-5 mb-2">
                <li>Para cada pregunta, selecciona primero el tipo de persona involucrada:
                    <ul class="list-none pl-4">
                        <li><b>A</b>: Jefas/jefes o personas supervisoras</li>
                        <li><b>B</b>: Personas compañeras de trabajo</li>
                        <li><b>C</b>: Personas subordinadas</li>
                    </ul>
                </li>
                <li>Después, selecciona la frecuencia con la que has experimentado la situación:
                    <ul class="list-none pl-4">
                        <li><b>0</b>: Nunca</li>
                        <li><b>1</b>: Pocas veces al año o menos</li>
                        <li><b>2</b>: Una vez al mes o menos</li>
                        <li><b>3</b>: Algunas veces al mes</li>
                        <li><b>4</b>: Una vez a la semana</li>
                        <li><b>5</b>: Varias veces a la semana</li>
                        <li><b>6</b>: Todos los días</li>
                    </ul>
                </li>
            </ul>
            <p>Responde considerando tu experiencia en los últimos 6 meses.</p>
        </div>
        <div class="space-y-4">
            <div v-for="(question, idx) in cisnerosQuestions" :key="idx" class="mb-6">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <label class="block text-sm font-medium text-slate-700 flex-grow">{{ idx + 1 }}: {{ question }}</label>
                    <div class="flex-shrink-0 flex gap-2">
                        <AudioPlayer
                            :audio-url="getAudioUrl(`cisneros_${idx + 1}`)"
                            @ended="handleAudioEnded(`cisneros_${idx + 1}`)"
                            @error="handleAudioError(`cisneros_${idx + 1}`)"
                        />
                        <VideoPlayer
                            :video-url="getVideoUrl(`cisneros_${idx + 1}`)"
                        />
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <!-- ABC selector -->
                    <div class="flex items-center space-x-4 bg-slate-100 p-2 rounded">
                        <span class="text-xs font-semibold text-slate-600 mr-2 w-20">Persona:</span>
                        <label v-for="option in ['A', 'B', 'C']" :key="'abc-' + option + '-' + idx" class="flex items-center space-x-1 cursor-pointer p-1 rounded-md hover:bg-white transition-colors">
                            <input
                                type="radio"
                                :name="'abc-' + idx"
                                :value="option"
                                :checked="modelValue['persona' + (idx + 1)] === option"
                                :disabled="isDisabled(`cisneros_${idx + 1}`)"
                                @change="updateField('persona' + (idx + 1), option)"
                                class="form-radio h-4 w-4 text-slate-800"
                            >
                            <span class="text-xs text-slate-700">{{ option }}</span>
                        </label>
                    </div>
                    <!-- 0-6 selector -->
                    <div class="flex items-center space-x-2 bg-slate-100 p-2 rounded">
                        <span class="text-xs font-semibold text-slate-600 mr-2 w-20">Frecuencia:</span>
                        <label v-for="option in [0,1,2,3,4,5,6]" :key="'freq-' + option + '-' + idx" class="flex flex-col items-center cursor-pointer p-1 rounded-md hover:bg-white transition-colors">
                            <input
                                type="radio"
                                :name="'freq-' + idx"
                                :value="option"
                                :checked="modelValue['frecuencia' + (idx + 1)] == option"
                                :disabled="isDisabled(`cisneros_${idx + 1}`)"
                                @change="updateField('frecuencia' + (idx + 1), option)"
                                class="form-radio h-4 w-4 text-slate-800"
                            >
                            <span class="text-xs text-slate-700">{{ option }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import AudioPlayer from './AudioPlayer.vue';
import VideoPlayer from './VideoPlayer.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    audioUrls: {
        type: Object,
        default: () => ({})
    },
    videoUrls: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const cisnerosQuestions = [
    "Mi superior restringe mis posibilidades de comunicarme, hablar o reunirme con él",
    "Me ignoran, me excluyen o me hacen el vacío, fingen no verme o me hacen «invisible»",
    "Me interrumpen continuamente impidiendo expresarme",
    "Me fuerzan a realizar trabajos que van contra mis principios o mi ética",
    "Evalúan mi trabajo de manera inequitativa o de forma sesgada",
    "Me dejan sin ningún trabajo que hacer, ni siquiera a iniciativa propia",
    "Me asignan tareas o trabajos absurdos o sin sentido",
    "Me asignan tareas o trabajos por debajo de mi capacidad profesional o mis competencias",
    "Me asignan tareas rutinarias o sin valor o interés alguno",
    "Me abruman con una carga de trabajo insoportable de manera malintencionada",
    "Me asignan tareas que ponen en peligro mi integridad física o mi salud a propósito",
    "Me impiden que adopte las medidas de seguridad necesarias para realizar mi trabajo con la debida seguridad",
    "Se me ocasionan gastos con intención de perjudicarme económicamente",
    "Prohíben a mis compañeros o colegas hablar conmigo",
    "Minusvaloran y echan por tierra mi trabajo, no importa lo que haga",
    "Me acusan injustificadamente de incumplimientos, errores, fallos, inconcretos y difusos",
    "Recibo críticas y reproches por cualquier cosa que haga o decisión que tome en mi trabajo",
    "Se amplifican y dramatizan de manera injustificada errores pequeños o intrascendentes",
    "Me humillan, desprecian o minusvaloran en público ante otros colegas o ante terceros",
    "Me amenazan con usar instrumentos disciplinarios (rescisión de contrato, expedientes, despido, traslados, etc.)",
    "Intentan aislarme de mis compañeros dándome trabajos o tareas que me alejan físicamente de ellos",
    "Distorsionan malintencionadamente lo que digo o hago en mi trabajo",
    "Se intenta buscarme las cosquillas para «hacerme explotar»",
    "Me menosprecian personal o profesionalmente",
    "Hacen burla de mí o bromas intentando ridiculizar mi forma de hablar, de andar, etc.",
    "Recibo feroces e injustas críticas acerca de aspectos de mi vida personal",
    "Recibo amenazas verbales o mediante gestos intimidatorios",
    "Recibo amenazas por escrito o por teléfono en mi domicilio",
    "Me chillan o gritan, o elevan la voz de manera a intimidarme",
    "Me zarandean, empujan o avasallan físicamente para intimidarme",
    "Se hacen bromas inapropiadas y crueles acerca de mí",
    "Inventan y difunden rumores y calumnias acerca de mí de manera malintencionada",
    "Me privan de información imprescindible y necesaria para hacer mi trabajo",
    "Limitan malintencionadamente mi acceso a cursos, promociones, ascensos, etc.",
    "Me atribuyen malintencionadamente conductas ilícitas o antiéticas para perjudicar mi imagen y reputación",
    "Recibo una presión indebida para sacar adelante el trabajo",
    "Me asignan plazos de ejecución o cargas de trabajo irrazonables",
    "Modifican mis responsabilidades o las tareas a ejecutar sin decirme nada",
    "Desvaloran continuamente mi esfuerzo profesional",
    "Intentan persistentemente desmoralizarme",
    "Utilizan varias formas de hacerme incurrir en errores profesionales de manera malintencionada",
    "Controlan aspectos de mi trabajo de forma malintencionada para intentar «pillarme en algún renuncio»",
    "Me lanzan insinuaciones o proposiciones sexuales directas o indirectas",
    "En el transcurso de los últimos 6 meses, ¿ha sido Ud víctima de por lo menos alguna de las anteriores formas de maltrato psicológico de manera continuada (con una frecuencia de más de1 vez por semana)?, (ver lista de preguntas 1 a 43)"
];

const getAudioUrl = (questionId) => {
    return props.audioUrls?.[questionId] || null;
};

const getVideoUrl = (questionId) => {
    return props.videoUrls?.[questionId] || null;
};

const unlocked = ref({});

const primeUnlockState = () => {
    const next = { ...unlocked.value };
    cisnerosQuestions.forEach((_, idx) => {
        const key = `cisneros_${idx + 1}`;
        const hasAudio = Boolean(getAudioUrl(key));
        if (!(key in next)) {
            next[key] = !hasAudio;
        }
    });
    unlocked.value = next;
};

watch(cisnerosQuestions, primeUnlockState, { immediate: true });

const isDisabled = (key) => unlocked.value[key] === false;

const handleAudioEnded = (key) => {
    unlocked.value = { ...unlocked.value, [key]: true };
};

const handleAudioError = (key) => {
    unlocked.value = { ...unlocked.value, [key]: true };
};

const updateField = (field, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [field]: value
    });
};
</script>
