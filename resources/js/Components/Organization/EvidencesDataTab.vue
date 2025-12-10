<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Evidencias</h2>
        </div>

        <!-- Grid of Images -->
        <div class="grid grid-cols-2 gap-4">
            <div
                v-for="imageNumber in imageCount"
                :key="imageNumber"
                class="cursor-pointer overflow-hidden rounded-lg bg-gray-100 shadow-sm hover:shadow-md transition-shadow"
                @click="openImageModal(imageNumber)"
            >
                <img
                    :src="`/assets/plantas/${organizationInfo.id}/${imageNumber}.jpeg`"
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
                    :src="`/assets/plantas/${organizationInfo.id}/${selectedImage}.jpeg`"
                    :alt="`Evidencia ${selectedImage}`"
                    class="w-full h-full object-contain"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface OrganizationInfo {
    id: string | number
    name: string
    logo?: string
}

interface Props {
    organizationInfo: OrganizationInfo
}

const props = defineProps<Props>()

const selectedImage = ref<number | null>(null)
const imageCount = props.organizationInfo.id === 'a06fe33d-6955-4d24-98d1-a375ecb55645' ? 15 : 16;

function openImageModal(imageNumber: number): void {
    selectedImage.value = imageNumber
}

function closeImageModal(): void {
    selectedImage.value = null
}
</script>