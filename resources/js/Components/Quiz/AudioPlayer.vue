<template>
    <div v-if="audioUrl" class="flex items-center gap-2">
        <!-- Play/Pause Button -->
        <button
            @click="togglePlayPause"
            class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center transition-colors"
            :title="isPlaying ? 'Pausar audio' : 'Reproducir audio'"
        >
            <svg
                v-if="!isPlaying"
                xmlns="http://www.w3.org/2000/svg"
                height="20px"
                viewBox="0 -960 960 960"
                width="20px"
                fill="currentColor"
            >
                <path d="M320-200v-560l440 280-440 280Zm80-280Zm0 134 210-134-210-134v268Z" />
            </svg>
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                height="20px"
                viewBox="0 -960 960 960"
                width="20px"
                fill="currentColor"
            >
                <path d="M520-200v-560h240v560H520Zm-320 0v-560h240v560H200Zm400-80h80v-400h-80v400Zm-320 0h80v-400h-80v400Zm0-400v400-400Zm320 0v400-400Z" />
            </svg>
        </button>

        <!-- Reset Button -->
        <button
            @click="resetAudio"
            class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 hover:bg-gray-400 text-gray-700 flex items-center justify-center transition-colors"
            title="Reiniciar audio"
        >
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                <path d="M480-80q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T120-440h80q0 117 81.5 198.5T480-160q117 0 198.5-81.5T760-440q0-117-81.5-198.5T480-720h-6l62 62-56 58-160-160 160-160 56 58-62 62h6q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-440q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-80Z" />
            </svg>
        </button>

        <!-- Stop Button -->
        <button
            @click="stopAudio"
            class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 hover:bg-gray-400 text-gray-700 flex items-center justify-center transition-colors"
            title="Detener audio"
        >
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                <path d="M320-640v320-320Zm-80 400v-480h480v480H240Zm80-80h320v-320H320v320Z" />
            </svg>
        </button>

        <audio
            ref="audioElement"
            :src="audioUrl"
            @play="isPlaying = true"
            @pause="isPlaying = false"
        ></audio>
    </div>
</template>
<script setup>
import { ref } from 'vue';

const props = defineProps({
    audioUrl: {
        type: String,
        default: null
    }
});

const audioElement = ref(null);
const isPlaying = ref(false);

const togglePlayPause = () => {
    if (!audioElement.value) return;
    
    if (isPlaying.value) {
        audioElement.value.pause();
    } else {
        audioElement.value.play();
    }
};

const resetAudio = () => {
    if (!audioElement.value) return;
    audioElement.value.currentTime = 0;
    audioElement.value.play();
};

const stopAudio = () => {
    if (!audioElement.value) return;
    audioElement.value.pause();
    audioElement.value.currentTime = 0;
};
</script>
