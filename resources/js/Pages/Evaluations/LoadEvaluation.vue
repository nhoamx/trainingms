<script setup>
import { useForm } from '@inertiajs/vue3'
import Dashboard from '../../Layouts/Dashboard.vue';
import Card from "../../Components/Card.vue";
import { DocumentIcon } from '@heroicons/vue/24/solid';

const form = useForm({
    file: null,
});

// Callback para actualizar el campo file cuando se selecciona el archivo
function handleFileChange(e) {
    const files = e.target.files;
    if (files && files[0]) {
        form.file = files[0];
    }
}

function handleDrop(e) {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files && files[0]) {
        form.file = files[0];
    }
}

function handleDragOver(e) {
    e.preventDefault();
}

const submit = () => {
    if (!form.file) return;
    form.post(route('evaluations.store'), {
        preserveScroll: true,
        onProgress: (progressEvent) => {
            // Actualizamos el progreso del formulario (inertia ya lo asigna a form.progress)
            form.progress = progressEvent.percentage;
        },
        onSuccess: () => {
            form.reset('file');
        },
    });
};
</script>

<template>
    <Dashboard>
        <Card>
            <form @submit.prevent="submit">
                <div class="col-span-full">
                    <label for="cover-photo" class="block text-sm/6 font-medium text-gray-900">
                        Arrastra el documento PDF con las evaluaciones que serán añadidas al sistema.
                    </label>
                    <div
                        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10"
                        @dragover="handleDragOver"
                        @drop="handleDrop"
                    >
                        <div class="text-center">
                            <DocumentIcon class="mx-auto size-12 text-gray-300" aria-hidden="true" />

                            <div class="mt-4 flex justify-center text-sm/6 text-gray-600">
                                <label
                                    for="file-upload"
                                    class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
                                >
                                    <span>Seleccionar archivo</span>
                                    <input
                                        id="file-upload"
                                        name="file-upload"
                                        type="file"
                                        class="sr-only"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <p class="pl-1">o arrastra y suelta</p>
                            </div>
                            <p class="text-xs/5 text-gray-600">PDF, hasta 10MB</p>
                            <!-- Mostrar el nombre del archivo si se seleccionó -->
                            <div v-if="form.file" class="mt-2 text-sm text-gray-700">
                                Archivo seleccionado: <strong>{{ form.file.name }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Mostrar el progreso de la carga si existe -->
                <div v-if="form.processing" class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            class="bg-indigo-600 h-2.5 rounded-full"
                            :style="{ width: form.progress ? form.progress + '%' : '0%' }"
                        ></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        Progreso: {{ form.progress ? form.progress.toFixed(0) : 0 }}%
                    </p>
                </div>
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <!-- Deshabilitar el botón si no se ha seleccionado archivo o se está procesando -->
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        :disabled="!form.file || form.processing"
                    >
                        Cargar y registrar
                    </button>
                </div>
            </form>
        </Card>
    </Dashboard>
</template>
