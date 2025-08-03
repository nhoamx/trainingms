<script setup>
defineProps({
    isSubmitting: {
        type: Boolean,
        default: false
    }
});

defineEmits(['submit']);
</script>

<template>
    <div class="space-y-6">
        <div class="text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h2 class="text-2xl font-semibold text-slate-900 mb-4">
                Evaluación Completada
            </h2>
            
            <p class="text-lg text-slate-600 mb-6">
                Has completado todas las secciones de la evaluación.
            </p>
            
            <div class="bg-slate-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-slate-800 mb-3">
                    Antes de finalizar, por favor verifica:
                </h3>
                <ul class="text-left text-slate-600 space-y-2">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-emerald-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Que has respondido todas las preguntas requeridas
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-emerald-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Que tus datos personales y laborales están correctos
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-emerald-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Que has completado todas las secciones disponibles
                    </li>
                </ul>
            </div>
            
            <p class="text-sm text-slate-500 mb-6">
                Una vez que hagas clic en "Guardar Respuestas", no podrás modificar tus respuestas.
            </p>
            
            <button
                @click="$emit('submit')"
                :disabled="isSubmitting"
                class="w-full sm:w-auto px-8 py-3 text-base font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
            >
                <span v-if="isSubmitting" class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Guardando respuestas...
                </span>
                <span v-else>
                    Guardar Respuestas
                </span>
            </button>
        </div>
    </div>
</template>
