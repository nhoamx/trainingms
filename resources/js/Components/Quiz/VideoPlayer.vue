<template>
    <div v-if="videoUrl">
        <!-- Video Button -->
        <button
            @click="openModal"
            :disabled="hasError"
            class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :title="hasError ? 'Error al cargar video' : 'Ver video explicativo'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                <path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h480q33 0 56.5 23.5T720-720v180l160-160v440L720-420v180q0 33-23.5 56.5T640-160H160Zm0-80h480v-480H160v480Zm0 0v-480 480Z"/>
            </svg>
            <span>Video</span>
        </button>

        <!-- Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-75"
                    @click.self="closeModal"
                >
                    <div
                        class="relative w-full max-w-4xl bg-white rounded-lg shadow-xl overflow-hidden"
                        @click.stop
                    >
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b border-slate-200">
                            <h3 class="text-lg font-medium text-slate-900">Video Explicativo</h3>
                            <div class="flex items-center gap-2">
                                <!-- Fullscreen Toggle -->
                                <button
                                    @click="toggleFullscreen"
                                    class="p-2 rounded-md hover:bg-slate-100 transition-colors"
                                    title="Pantalla completa"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                                        <path d="M120-120v-240h80v160h160v80H120Zm0-480v-240h240v80H200v160h-80Zm480 480v-80h160v-160h80v240H600Zm160-480v-160H600v-80h240v240h-80Z"/>
                                    </svg>
                                </button>
                                <!-- Close Button -->
                                <button
                                    @click="closeModal"
                                    class="p-2 rounded-md hover:bg-slate-100 transition-colors"
                                    title="Cerrar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                                        <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Video Container -->
                        <div class="relative bg-black" style="aspect-ratio: 16/9;">
                            <video
                                ref="videoElement"
                                class="w-full h-full"
                                :src="videoUrl"
                                controls
                                controlsList="nodownload"
                                @error="handleError"
                                @play="handlePlay"
                                @pause="handlePause"
                                @ended="handleEnded"
                            >
                                Tu navegador no soporta la reproducción de videos.
                            </video>

                            <!-- Loading Overlay -->
                            <div
                                v-if="isLoading"
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <svg
                                        class="animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        height="40px"
                                        viewBox="0 -960 960 960"
                                        width="40px"
                                        fill="currentColor"
                                    >
                                        <path d="M480-80q-82 0-155-31.5t-127.5-86Q143-252 111.5-325T80-480q0-83 31.5-156t86-127.5Q252-817 325-848.5T480-880v60q-142 0-241 99t-99 241q0 142 99 241t241 99v60Z" />
                                    </svg>
                                    <span class="text-white text-sm">Cargando video...</span>
                                </div>
                            </div>

                            <!-- Error Overlay -->
                            <div
                                v-if="hasError"
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-75"
                            >
                                <div class="flex flex-col items-center gap-2 text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="currentColor">
                                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-120q0-17-11.5-28.5T480-640q-17 0-28.5 11.5T440-600v120q0 17 11.5 28.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
                                    </svg>
                                    <span class="text-sm">Error al cargar el video</span>
                                </div>
                            </div>
                        </div>

                        <!-- Video Info (Optional) -->
                        <div v-if="!hasError" class="p-4 bg-slate-50 text-xs text-slate-600">
                            <p>💡 Tip: Puedes reproducir el video mientras respondes las preguntas.</p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const emit = defineEmits(['play', 'pause', 'ended', 'error']);

const props = defineProps({
    videoUrl: {
        type: String,
        default: null
    }
});

const videoElement = ref(null);
const isModalOpen = ref(false);
const isPlaying = ref(false);
const isLoading = ref(false);
const hasError = ref(false);

/**
 * Open the video modal
 */
const openModal = () => {
    isModalOpen.value = true;
    isLoading.value = true;
    hasError.value = false;
    
    // Reset video when opening modal
    if (videoElement.value) {
        videoElement.value.currentTime = 0;
    }
};

/**
 * Close the video modal
 */
const closeModal = () => {
    // Pause video when closing modal
    if (videoElement.value) {
        videoElement.value.pause();
    }
    isModalOpen.value = false;
    isPlaying.value = false;
};

/**
 * Toggle fullscreen mode
 */
const toggleFullscreen = () => {
    if (!videoElement.value) return;

    if (!document.fullscreenElement) {
        videoElement.value.requestFullscreen?.() ||
        videoElement.value.webkitRequestFullscreen?.() ||
        videoElement.value.msRequestFullscreen?.();
    } else {
        document.exitFullscreen?.() ||
        document.webkitExitFullscreen?.() ||
        document.msExitFullscreen?.();
    }
};

/**
 * Handle video play event
 */
const handlePlay = () => {
    isPlaying.value = true;
    isLoading.value = false;
    emit('play');
};

/**
 * Handle video pause event
 */
const handlePause = () => {
    isPlaying.value = false;
    emit('pause');
};

/**
 * Handle video ended event
 */
const handleEnded = () => {
    isPlaying.value = false;
    emit('ended');
};

/**
 * Handle video error event
 */
const handleError = (error) => {
    hasError.value = true;
    isLoading.value = false;
    console.error('Error loading video:', error);
    emit('error', error);
};

// Watch for video URL changes
watch(
    () => props.videoUrl,
    (newUrl) => {
        hasError.value = false;
        if (newUrl && videoElement.value) {
            videoElement.value.load();
        }
    }
);

// Handle ESC key to close modal
const handleKeydown = (event) => {
    if (event.key === 'Escape' && isModalOpen.value) {
        closeModal();
    }
};

// Add/remove event listener for ESC key
watch(isModalOpen, (open) => {
    if (open) {
        document.addEventListener('keydown', handleKeydown);
    } else {
        document.removeEventListener('keydown', handleKeydown);
    }
});
</script>
