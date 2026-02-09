<template>
    <div class="bg-slate-50 p-4 rounded-lg mb-6">
        <h3 class="font-medium text-slate-900 mb-4">{{ title }}</h3>
        <div class="space-y-4">
            <div
                v-for="(question, index) in questions"
                :key="index"
                class="bg-white p-4 rounded-lg border border-slate-100"
            >
                <p class="text-slate-900 mb-4">{{ index + 1 }}. {{ question }}</p>
                <div class="flex gap-2 mb-4">
                    <AudioPlayer
                        :audio-url="getAudioUrl(index + 1)"
                        @ready="handleAudioReady(index + 1)"
                        @ended="handleAudioEnded(index + 1)"
                        @error="handleAudioError(index + 1)"
                    />
                </div>
                <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                    <label
                        v-for="option in answerOptions"
                        :key="option.value"
                        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                    >
                        <input
                            type="radio"
                            :name="`${namePrefix}_${index + 1}`"
                            :value="option.value"
                            :checked="modelValue?.[String(index + 1)] === option.value"
                            :disabled="isDisabled(index + 1)"
                            @change="updateAnswer(String(index + 1), option.value)"
                            class="form-radio h-4 w-4 text-slate-800"
                        >
                        <span class="text-sm text-slate-700">{{ option.label }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AudioPlayer from './AudioPlayer.vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    questions: {
        type: [Object, Array],
        required: true
    },
    modelValue: {
        type: Object,
        required: true
    },
    answerOptions: {
        type: Array,
        required: true
    },
    namePrefix: {
        type: String,
        required: true
    },
    audioUrls: {
        type: Object,
        default: () => ({})
    },
    disableAudioValidation: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const unlocked = ref({});

const primeUnlockState = () => {
    const next = { ...unlocked.value };
    const questionKeys = Array.isArray(props.questions)
        ? props.questions.map((_, idx) => idx + 1) // 1-based indexing
        : Object.keys(props.questions);
    questionKeys.forEach((index) => {
        if (!(index in next)) {
            // Por defecto, todas las preguntas están desbloqueadas
            next[index] = true;
        }
    });
    unlocked.value = next;
};

onMounted(() => {
    primeUnlockState();
});

// Obtener la URL del audio para una pregunta
const getAudioUrl = (index) => {
    return props.audioUrls?.[index] || null;
};

const isDisabled = (index) => unlocked.value[index] === false;

const handleAudioReady = (index) => {
    // Si la validación de audio está deshabilitada, no bloqueamos
    if (props.disableAudioValidation) {
        return;
    }
    // Cuando el audio se carga exitosamente, bloqueamos la pregunta
    unlocked.value = { ...unlocked.value, [index]: false };
};

const handleAudioEnded = (index) => {
    unlocked.value = { ...unlocked.value, [index]: true };
};

const handleAudioError = (index) => {
    unlocked.value = { ...unlocked.value, [index]: true };
};

// Inicializa todas las preguntas traumáticas como null si no existen
onMounted(() => {
    if (!props.modelValue) return;
    
    const newValue = { ...props.modelValue };
    const questionKeys = Array.isArray(props.questions) 
        ? props.questions.map((_, idx) => idx + 1) // 1-based indexing
        : Object.keys(props.questions);
    
    questionKeys.forEach(index => {
        if (!(String(index) in newValue)) {
            newValue[String(index)] = null;
        }
    });
    emit('update:modelValue', newValue);
});

const updateAnswer = (index, value) => {
    emit('update:modelValue', {
        ...(props.modelValue || {}),
        [index]: value
    });
};
</script>
