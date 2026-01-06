<template>
    <div v-if="audioUrl">
        <div v-show="isVisible" class="flex items-center gap-2">
            <!-- Play/Pause Button -->
            <button
                @click="togglePlayPause"
                :disabled="isLoading || hasError"
                class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :title="isLoading ? 'Cargando audio...' : hasError ? 'Error al cargar audio' : (isPlaying ? 'Pausar audio' : 'Reproducir audio')"
            >
                <svg
                    v-if="!isPlaying && !isLoading"
                    xmlns="http://www.w3.org/2000/svg"
                    height="20px"
                    viewBox="0 -960 960 960"
                    width="20px"
                    fill="currentColor"
                >
                    <path d="M320-200v-560l440 280-440 280Zm80-280Zm0 134 210-134-210-134v268Z" />
                </svg>
                <svg
                    v-else-if="isPlaying && !isLoading"
                    xmlns="http://www.w3.org/2000/svg"
                    height="20px"
                    viewBox="0 -960 960 960"
                    width="20px"
                    fill="currentColor"
                >
                    <path d="M520-200v-560h240v560H520Zm-320 0v-560h240v560H200Zm400-80h80v-400h-80v400Zm-320 0h80v-400h-80v400Zm0-400v400-400Zm320 0v400-400Z" />
                </svg>
                <!-- Loading spinner -->
                <svg
                    v-else
                    class="animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    height="20px"
                    viewBox="0 -960 960 960"
                    width="20px"
                    fill="currentColor"
                >
                    <path d="M480-80q-82 0-155-31.5t-127.5-86Q143-252 111.5-325T80-480q0-83 31.5-156t86-127.5Q252-817 325-848.5T480-880v60q-142 0-241 99t-99 241q0 142 99 241t241 99v60Z" />
                </svg>
            </button>

            <!-- Reset Button -->
            <button
                @click="resetAudio"
                :disabled="isLoading || hasError"
                class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 hover:bg-gray-400 text-gray-700 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                title="Reiniciar audio"
            >
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                    <path d="M480-80q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T120-440h80q0 117 81.5 198.5T480-160q117 0 198.5-81.5T760-440q0-117-81.5-198.5T480-720h-6l62 62-56 58-160-160 160-160 56 58-62 62h6q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-440q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-80Z" />
                </svg>
            </button>

            <!-- Stop Button -->
            <button
                @click="stopAudio"
                :disabled="isLoading || hasError"
                class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 hover:bg-gray-400 text-gray-700 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                title="Detener audio"
            >
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                    <path d="M320-640v320-320Zm-80 400v-480h480v480H240Zm80-80h320v-320H320v320Z" />
                </svg>
            </button>

            <!-- Error indicator (small visual feedback) -->
            <div v-if="hasError" class="flex-shrink-0 w-4 h-4">
                <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="rgb(239, 68, 68)" title="Error al cargar audio">
                    <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-120q0-17-11.5-28.5T480-640q-17 0-28.5 11.5T440-600v120q0 17 11.5 28.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-60q142 0 241-99t99-241q0-142-99-241t-241-99q-142 0-241 99t-99 241q0 142 99 241t241 99Zm0-340Z" />
                </svg>
            </div>
        </div>

        <audio
            ref="audioElement"
            class="hidden"
            :src="audioUrl"
            @play="handlePlay"
            @pause="handlePause"
            @loadstart="handleLoadStart"
            @canplay="handleCanPlay"
            @error="handleError"
            @ended="handleEnded"
        ></audio>
    </div>
</template>
<script setup>
import { ref, watch } from 'vue';

const emit = defineEmits(['ready', 'ended', 'error', 'started']);

const props = defineProps({
    audioUrl: {
        type: String,
        default: null
    }
});

const audioElement = ref(null);
const isPlaying = ref(false);
const isLoading = ref(false);
const hasError = ref(false);
const isVisible = ref(false);

const stopAndReset = () => {
    if (!audioElement.value) return;
    try {
        audioElement.value.pause();
        audioElement.value.currentTime = 0;
    } catch (error) {
        console.error('Error resetting audio element:', error);
    }
};

watch(
    () => props.audioUrl,
    (newUrl) => {
        stopAndReset();
        isPlaying.value = false;
        isLoading.value = false;
        hasError.value = false;
        isVisible.value = false;
        if (newUrl) {
            // Defer visibility until canplay confirms load success
            audioElement.value?.load?.();
        }
    },
    { immediate: true }
);

/**
 * Toggle between play and pause states
 */
const togglePlayPause = () => {
    if (!audioElement.value || !isVisible.value) return;
    
    try {
        if (isPlaying.value) {
            audioElement.value.pause();
        } else {
            audioElement.value.play();
        }
    } catch (error) {
        console.error('Error toggling audio playback:', error);
        hasError.value = true;
    }
};

/**
 * Reset audio to the beginning and play
 */
const resetAudio = () => {
    if (!audioElement.value || !isVisible.value) return;
    
    try {
        audioElement.value.currentTime = 0;
        audioElement.value.play();
    } catch (error) {
        console.error('Error resetting audio:', error);
        hasError.value = true;
    }
};

/**
 * Stop audio and reset to the beginning
 */
const stopAudio = () => {
    if (!audioElement.value) return;
    
    try {
        audioElement.value.pause();
        audioElement.value.currentTime = 0;
    } catch (error) {
        console.error('Error stopping audio:', error);
        hasError.value = true;
    }
};

/**
 * Handle play event
 */
const handlePlay = () => {
    isPlaying.value = true;
    emit('started');
};

/**
 * Handle pause event
 */
const handlePause = () => {
    isPlaying.value = false;
};

/**
 * Handle loadstart event
 */
const handleLoadStart = () => {
    isLoading.value = true;
    hasError.value = false;
    isVisible.value = false;
};

/**
 * Handle canplay event - audio is ready to play
 */
const handleCanPlay = () => {
    isLoading.value = false;
    hasError.value = false;
    isVisible.value = true;
    emit('ready');
};

/**
 * Handle error event - audio failed to load
 */
const handleError = () => {
    isLoading.value = false;
    hasError.value = true;
    isPlaying.value = false;
    isVisible.value = false;
    console.warn(`Failed to load audio: ${props.audioUrl}`);
    emit('error');
};

/**
 * Handle ended event - audio finished playing
 */
const handleEnded = () => {
    isPlaying.value = false;
    emit('ended');
};
</script>

