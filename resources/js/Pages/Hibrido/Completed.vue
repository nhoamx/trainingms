<script setup>
import { Head } from '@inertiajs/vue3';
import QuizLayout from '@/Layouts/QuizLayout.vue';

defineProps({
    folio: String,
    organizationName: String,
    completedAt: String,
});
</script>

<template>
    <QuizLayout title="Evaluación Completada">
        <Head title="Evaluación Completada" />
        
        <div class="min-h-screen flex items-center justify-center px-4 py-12">
            <div class="max-w-2xl w-full">
                <!-- Tarjeta principal -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header con gradiente -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-12 text-center">
                        <!-- Ícono de check animado -->
                        <div class="inline-flex items-center justify-center w-24 h-24 mb-6 bg-white rounded-full shadow-lg">
                            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        
                        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
                            ¡Evaluación Completada!
                        </h1>
                        <p class="text-green-50 text-lg">
                            Esta evaluación ya ha sido respondida exitosamente
                        </p>
                    </div>

                    <!-- Contenido -->
                    <div class="px-8 py-10">
                        <!-- Información del folio -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-6 mb-8 border border-slate-200">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-slate-800 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-slate-600 mb-1">Folio de Evaluación</h3>
                                    <p class="text-2xl font-bold text-slate-900 tracking-wide font-mono">
                                        {{ folio }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles adicionales -->
                        <div class="space-y-4 mb-8">
                            <div class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <div>
                                    <span class="text-sm text-slate-500">Organización:</span>
                                    <span class="ml-2 font-medium">{{ organizationName }}</span>
                                </div>
                            </div>

                            <div v-if="completedAt" class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <span class="text-sm text-slate-500">Fecha de completado:</span>
                                    <span class="ml-2 font-medium">{{ completedAt }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje informativo -->
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-900 mb-1">
                                        Información importante
                                    </h4>
                                    <p class="text-sm text-blue-700 leading-relaxed">
                                        Los datos de esta evaluación ya han sido procesados y guardados en nuestro sistema. 
                                        No es necesario volver a responder el cuestionario. Si tienes dudas o necesitas hacer 
                                        alguna modificación, por favor contacta con el administrador de tu organización.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje de agradecimiento -->
                        <div class="mt-8 text-center">
                            <p class="text-slate-600 leading-relaxed">
                                Gracias por tu participación en el proceso de evaluación.<br>
                                Tu colaboración es muy importante para nosotros.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-500">
                        Si consideras que esto es un error, por favor contacta al equipo de soporte.
                    </p>
                </div>
            </div>
        </div>
    </QuizLayout>
</template>

<style scoped>
/* Animación sutil para el check */
@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

svg path {
    animation: scaleIn 0.5s ease-out;
}
</style>
