<template>
    <div class="space-y-6">
        <div
            v-for="(questions, category) in followUpQuestions"
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
                    <p class="text-slate-900 mb-4">{{ getQuestionIndex(category, index) }}. {{ question }}</p>
                    <div class="flex gap-2 mb-4">
                        <AudioPlayer
                            :audio-url="getAudioUrl(getQuestionIndex(category, index))"
                            @ready="handleAudioReady(getQuestionIndex(category, index))"
                            @ended="handleAudioEnded(getQuestionIndex(category, index))"
                            @error="handleAudioError(getQuestionIndex(category, index))"
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
                                :name="`trauma_follow_${getQuestionIndex(category, index)}`"
                                :value="option.value"
                                :checked="modelValue[getQuestionIndex(category, index)] === option.value"
                                :disabled="isDisabled(getQuestionIndex(category, index))"
                                @change="updateAnswer(getQuestionIndex(category, index), option.value)"
                                class="form-radio h-4 w-4 text-slate-800"
                            >
                            <span class="text-sm text-slate-700">{{ option.label }}</span>
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

const props = defineProps({
    followUpQuestions: {
        type: Object,
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
const questionIndexMap = ref({});

const getAudioUrl = (index) => {
    return props.audioUrls?.[index] || null;
};

// Build mapping of category_localIndex to globalIndex
const buildQuestionIndexMap = () => {
    const map = {};
    let globalIndex = 1;
    Object.entries(props.followUpQuestions).forEach(([category, questions]) => {
        questions.forEach((_, localIndex) => {
            map[`${category}_${localIndex}`] = globalIndex;
            globalIndex++;
        });
    });
    questionIndexMap.value = map;
};

// Helper function to get question index
const getQuestionIndex = (category, localIndex) => {
    return questionIndexMap.value[`${category}_${localIndex}`] || null;
};

const primeUnlockState = () => {
    const next = { ...unlocked.value };
    let globalIndex = 1;
    Object.entries(props.followUpQuestions).forEach(([category, questions]) => {
        questions.forEach(() => {
            if (!(globalIndex in next)) {
                // Por defecto, todas las preguntas están desbloqueadas
                next[globalIndex] = true;
            }
            globalIndex++;
        });
    });
    unlocked.value = next;
};

watch(() => props.followUpQuestions, () => {
    buildQuestionIndexMap();
    primeUnlockState();
}, { immediate: true });

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

const updateAnswer = (index, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [index]: value
    });
};
</script>
