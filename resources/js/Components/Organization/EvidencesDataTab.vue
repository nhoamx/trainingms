<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Evidencias</h2>
        </div>

        <!-- Grid of Images -->
        <div class="grid grid-cols-2 gap-4">
            <div
                v-for="imageNumber in 16"
                :key="imageNumber"
                class="cursor-pointer overflow-hidden rounded-lg bg-gray-100 shadow-sm hover:shadow-md transition-shadow"
                @click="openImageModal(imageNumber)"
            >
                <img
                    :src="`/assets/${imageNumber}.jpeg`"
                    :alt="`Evidencia ${imageNumber}`"
                    class="w-full h-auto object-cover hover:scale-105 transition-transform"
                />
            </div>
        </div>

        <!-- Image Modal -->
        <div v-if="selectedImage" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-80" @click="closeImageModal">
            <div class="relative max-w-2xl max-h-96 bg-white rounded-lg shadow-xl" @click.stop>
                <button
                    @click="closeImageModal"
                    class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-md"
                >
                    ✕
                </button>
                <img
                    :src="`/assets/${selectedImage}.jpeg`"
                    :alt="`Evidencia ${selectedImage}`"
                    class="w-full h-full object-contain"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const selectedImage = ref(null)

function openImageModal(imageNumber) {
    selectedImage.value = imageNumber
}

function closeImageModal() {
    selectedImage.value = null
}
</script>