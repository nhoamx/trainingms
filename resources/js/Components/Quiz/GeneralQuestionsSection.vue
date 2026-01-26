<template>
    <div v-if="block" class="space-y-6">
        <!-- Instrucciones del Bloque - Rediseñadas -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-600 p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 7a1 1 0 000 2h6a1 1 0 000-2H8zm0 4a1 1 0 000 2h6a1 1 0 000-2H8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-base leading-relaxed text-blue-900 font-medium">{{ block.instructions }}</p>
                </div>
            </div>
            <!-- Audio del Bloque -->
            <div v-if="block.audio_url" class="mt-4 pt-4 border-t border-blue-200">
                <AudioPlayer
                    :audio-url="block.audio_url"
                    @ready="handleBlockAudioReady"
                    @ended="handleBlockAudioEnded"
                    @error="handleBlockAudioError"
                />
            </div>
        </div>

        <!-- Preguntas del Bloque -->
        <div class="space-y-6">
            <div
                v-for="question in block.questions"
                :key="question.id"
                class="border-b border-slate-100 last:border-0 pb-6 last:pb-0"
                :class="{ 'bg-slate-50 p-4 rounded-lg': viewMode === 'comfortable' }"
            >
                <p class="text-slate-900 mb-4">{{ question.id }}. {{ question.text }}</p>
                <div class="flex gap-2 mb-4">
                    <AudioPlayer
                        :audio-url="getAudioUrl(question.id)"
                        @ready="handleAudioReady(question.id)"
                        @ended="handleAudioEnded(question.id)"
                        @error="handleAudioError(question.id)"
                    />
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <label
                        v-for="option in answerOptions"
                        :key="option.value"
                        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                    >
                        <input
                            type="radio"
                            :name="`question_${question.id}`"
                            :value="option.value"
                            :checked="modelValue[question.id] === option.value"
                            :disabled="isDisabled(question.id)"
                            @change="updateAnswer(question.id, option.value)"
                            class="form-radio h-4 w-4 text-slate-800"
                        >
                        <span class="text-sm text-slate-700">{{ option.label }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-yellow-800 font-medium">Sin datos de bloque disponibles</p>
        <p class="text-yellow-700 text-sm mt-2">Por favor verifica que los bloques estén configurados correctamente.</p>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import AudioPlayer from './AudioPlayer.vue';

const props = defineProps({
    block: {
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
    viewMode: {
        type: String,
        default: 'comfortable'
    },
    audioUrls: {
        type: Object,
        default: () => ({})
    },
});

const emit = defineEmits(['update:modelValue']);

const unlocked = ref({});
const blockAudioUnlocked = ref(false);

const getAudioUrl = (questionId) => {
    return props.audioUrls?.[questionId] || null;
};

const isDisabled = (questionId) => {
    // Si hay audio de bloque y no se ha desbloqueado, bloquear todas las preguntas
    if (props.block?.audio_url && !blockAudioUnlocked.value) {
        return true;
    }
    // Si la pregunta individual tiene audio y no se ha desbloqueado
    return unlocked.value[questionId] === false;
};

const handleBlockAudioReady = () => {
    // Bloquear todas las preguntas hasta que termine el audio del bloque
    blockAudioUnlocked.value = false;
};

const handleBlockAudioEnded = () => {
    // Desbloquear todas las preguntas cuando termine el audio del bloque
    blockAudioUnlocked.value = true;
};

const handleBlockAudioError = () => {
    // Si falla el audio del bloque, habilitar las respuestas
    blockAudioUnlocked.value = true;
};

const handleAudioReady = (questionId) => {
    // Cuando el audio se carga exitosamente, bloqueamos la pregunta hasta que termine
    unlocked.value = { ...unlocked.value, [questionId]: false };
};

const handleAudioEnded = (questionId) => {
    unlocked.value = { ...unlocked.value, [questionId]: true };
};

const handleAudioError = (questionId) => {
    // Si falla el audio, habilitamos las respuestas para no bloquear al usuario
    unlocked.value = { ...unlocked.value, [questionId]: true };
};

const primeUnlockState = () => {
    const next = { ...unlocked.value };
    if (props.block && props.block.questions) {
        props.block.questions.forEach((question) => {
            if (!(question.id in next)) {
                // Por defecto, todas las preguntas están desbloqueadas
                // Se bloquearán solo cuando un audio se cargue exitosamente (@ready)
                next[question.id] = true;
            }
        });
    }
    unlocked.value = next;
    
    // Si el bloque no tiene audio, desbloquear inmediatamente
    if (!props.block?.audio_url) {
        blockAudioUnlocked.value = true;
    } else {
        blockAudioUnlocked.value = false;
    }
};

watch(() => props.block, primeUnlockState, { immediate: true });

const updateAnswer = (questionId, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [questionId]: value
    });
};
</script>
