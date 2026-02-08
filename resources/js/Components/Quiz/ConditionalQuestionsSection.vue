<template>
    <div
        v-for="(section, key) in conditionalSections"
        :key="key"
        class="mb-8 last:mb-0"
    >
        <div class="bg-slate-50 p-4 rounded-lg mb-4">
            <p class="font-medium text-slate-900 mb-3">{{ section.condition }}</p>
            <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                <label
                    v-for="option in yesNoOptions"
                    :key="option.value"
                    class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-white transition-colors"
                >
                    <input
                        type="radio"
                        :name="`condition_${key}`"
                        :value="option.value"
                        :checked="modelValue[`condition_${key}`] === option.value"
                        @change="updateCondition(`condition_${key}`, option.value)"
                        class="form-radio h-4 w-4 text-slate-800"
                    >
                    <span class="text-sm text-slate-700">{{ option.label }}</span>
                </label>
            </div>
        </div>

        <!-- Preguntas condicionales -->
        <div v-if="modelValue[`condition_${key}`] === true" class="space-y-4 ml-0 sm:ml-4">
            <div
                v-for="(question, qIndex) in section.questions"
                :key="qIndex"
                class="border-b border-slate-100 last:border-0 pb-4 last:pb-0"
            >
                <p class="text-slate-900 mb-4">{{ qIndex }}. {{ question }}</p>
                <div class="flex gap-2 mb-4">
                    <AudioPlayer
                        :audio-url="getAudioUrl(qIndex)"
                        @ready="handleAudioReady(qIndex)"
                        @ended="handleAudioEnded(qIndex)"
                        @error="handleAudioError(qIndex)"
                    />
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <label
                        v-for="option in generalOptions"
                        :key="option.value"
                        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                    >
                        <input
                            type="radio"
                            :name="`question_${qIndex}`"
                            :value="option.value"
                            :checked="modelValue[String(qIndex)] === option.value"
                            :disabled="isDisabled(qIndex)"
                            @change="updateAnswer(String(qIndex), option.value)"
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
import { ref, watch } from 'vue';
import AudioPlayer from './AudioPlayer.vue';

const props = defineProps({
    conditionalSections: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Object,
        required: true
    },
    yesNoOptions: {
        type: Array,
        required: true
    },
    generalOptions: {
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

const getAudioUrl = (questionId) => {
    return props.audioUrls?.[questionId] || null;
};

const primeUnlockState = () => {
    const next = { ...unlocked.value };
    Object.entries(props.conditionalSections).forEach(([key, section]) => {
        Object.keys(section.questions).forEach((qKey) => {
            if (!(qKey in next)) {
                // Por defecto, todas las preguntas están desbloqueadas
                next[qKey] = true;
            }
        });
    });
    unlocked.value = next;
};

watch(() => props.conditionalSections, primeUnlockState, { immediate: true });

const isDisabled = (questionId) => unlocked.value[questionId] === false;

const handleAudioReady = (questionId) => {
    // Cuando el audio se carga exitosamente, bloqueamos la pregunta
    unlocked.value = { ...unlocked.value, [questionId]: false };
};

const handleAudioEnded = (questionId) => {
    unlocked.value = { ...unlocked.value, [questionId]: true };
};

const handleAudioError = (questionId) => {
    unlocked.value = { ...unlocked.value, [questionId]: true };
};

// Watch para inicializar preguntas condicionales como null si el filtro es "No"
Object.entries(props.conditionalSections).forEach(([key, section]) => {
    watch(
        () => props.modelValue[`condition_${key}`],
        (val) => {
            if (val === false) {
                // Inicializa todas las preguntas condicionales como null
                const newValue = { ...props.modelValue };
                Object.keys(section.questions).forEach(qKey => {
                    newValue[qKey] = null;
                });
                emit('update:modelValue', newValue);
            }
        }
    );
});

const updateCondition = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value
    });
};

const updateAnswer = (questionId, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [questionId]: value
    });
};
</script>
