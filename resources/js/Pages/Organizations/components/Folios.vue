<template>
  <div class="space-y-6">
    <div class="border-b border-gray-900/10 pb-6">
      <h2 class="text-base font-semibold text-gray-900">Folios de la organización</h2>
      <p class="mt-1 text-sm text-gray-600">Gestiona los folios disponibles para exámenes en esta organización.</p>
      <!-- Formulario para crear nuevo lote de folios -->
      <div class="mt-6 border rounded-lg p-4 bg-gray-50">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Crear nuevo lote de folios</h3>
        <form @submit.prevent="addFolioBatch" class="space-y-4">
          <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6">
            <div>
              <label for="batch-name" class="block text-sm font-medium text-gray-700">Nombre del lote</label>
              <div class="mt-1">
                <input type="text" id="batch-name" v-model="folioBatchForm.name" placeholder="Ej: Examen Mayo 2025" class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600" />
                <p v-if="folioBatchForm.errors.name" class="mt-1 text-xs text-red-500">{{ folioBatchForm.errors.name }}</p>
              </div>
            </div>
            <div>
              <label for="batch-quantity" class="block text-sm font-medium text-gray-700">Cantidad de folios</label>
              <div class="mt-1">
                <input type="number" id="batch-quantity" v-model="folioBatchForm.quantity" min="1" placeholder="Ej: 100" class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600" />
                <p v-if="folioBatchForm.errors.quantity" class="mt-1 text-xs text-red-500">{{ folioBatchForm.errors.quantity }}</p>
              </div>
            </div>
            <div>
              <label for="batch-type" class="block text-sm font-medium text-gray-700">Tipo de examen</label>
              <div class="mt-1">
                <select id="batch-type" v-model="folioBatchForm.type" class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600">
                  <option value="">Selecciona un tipo</option>
                  <option value="presencial">Presencial</option>
                  <option value="en_linea">En línea</option>
                </select>
                <p v-if="folioBatchForm.errors.type" class="mt-1 text-xs text-red-500">{{ folioBatchForm.errors.type }}</p>
              </div>
            </div>
            <div>
              <label for="batch-description" class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
              <div class="mt-1">
                <textarea id="batch-description" v-model="folioBatchForm.description" rows="2" placeholder="Descripción o propósito de este lote de folios" class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"></textarea>
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" :disabled="folioBatchForm.processing">
              <PlusIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
              Crear lote de folios
            </button>
          </div>
        </form>
      </div>
      <!-- Listado de lotes de folios -->
      <div class="mt-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Lotes de folios registrados</h3>
        <div v-if="organization.folio_batches && organization.folio_batches.length > 0" class="overflow-hidden bg-white shadow sm:rounded-md">
          <ul role="list" class="divide-y divide-gray-200">
            <li v-for="batch in organization.folio_batches" :key="batch.id" class="relative flex flex-col px-4 py-5 hover:bg-gray-50 sm:px-6">
              <div class="flex justify-between items-center">
                <div class="min-w-0 flex-auto">
                  <div class="flex items-center gap-x-3">
                    <h4 class="text-sm font-semibold leading-6 text-gray-900">{{ batch.name }}</h4>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="batch.type === 'presencial' ? 'bg-blue-50 text-blue-700 ring-blue-700/10' : 'bg-green-50 text-green-700 ring-green-700/10'">
                      {{ batch.type === 'presencial' ? 'Presencial' : 'En línea' }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                      {{ batch.quantity }} folios
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">{{ batch.description || 'Sin descripción' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-x-2">
                  <button v-if="batch.type === 'en_linea'" @click="showOnlineLink(batch)" type="button" class="rounded-full bg-white p-1 text-gray-400 hover:text-green-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" title="Ver enlace de evaluación en línea">
                    <LinkIcon class="h-5 w-5" />
                  </button>
                  <button @click="viewBatchDetails(batch)" type="button" class="rounded-full bg-white p-1 text-gray-400 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                    <EyeIcon class="h-5 w-5" />
                  </button>
                  <button @click="deleteBatch(batch)" type="button" class="rounded-full bg-white p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                  <span>Rango de folios:</span>
                  <span class="font-medium">{{ formatFolioNumber(batch.start_number) }} - {{ formatFolioNumber(batch.end_number) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-indigo-600 h-2 rounded-full" :style="`width: ${calculateUsedPercentage(batch)}%`"></div>
                </div>
                <div class="flex justify-between text-xs mt-1">
                  <span class="text-gray-500">{{ calculateUsedFolios(batch) }} usados</span>
                  <span class="text-gray-500">{{ batch.quantity - calculateUsedFolios(batch) }} disponibles</span>
                </div>
              </div>
            </li>
          </ul>
        </div>
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <div class="flex flex-col items-center justify-center">
            <ArchiveBoxIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay lotes de folios registrados</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo lote de folios para esta organización.</p>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal de detalles y eliminación pueden agregarse aquí si se requiere -->
  </div>
</template>

<script setup>

import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PlusIcon, EyeIcon, TrashIcon, ArchiveBoxIcon, LinkIcon } from '@heroicons/vue/24/solid';


const props = defineProps({
  organization: { type: Object, required: true }
});

const folioBatchForm = useForm({
  organization_id: props.organization.id,
  name: '',
  description: '',
  quantity: '',
  type: '',
});

const addFolioBatch = () => {
  if (!folioBatchForm.name.trim()) {
    folioBatchForm.setError('name', 'El nombre del lote es requerido.');
    return;
  }
  if (!folioBatchForm.quantity || folioBatchForm.quantity <= 0) {
    folioBatchForm.setError('quantity', 'La cantidad debe ser un número mayor a 0.');
    return;
  }
  if (!folioBatchForm.type) {
    folioBatchForm.setError('type', 'El tipo de examen es requerido.');
    return;
  }
  folioBatchForm.post(route('folio-batches.store'), {
    preserveScroll: true,
    onSuccess: (response) => {
      if (response?.data?.batch) {
        if (!props.organization.folio_batches) {
          props.organization.folio_batches = [];
        }
        props.organization.folio_batches.push(response.data.batch);
      } else {
        window.location.reload();
      }
      folioBatchForm.reset();
    },
  });
};

const formatFolioNumber = (num) => {
  return num.toString().padStart(4, '0');
};

const calculateUsedFolios = (batch) => {
  return batch.used_count ?? 0;
};

const calculateUsedPercentage = (batch) => {
  const usedCount = calculateUsedFolios(batch);
  return batch.quantity > 0 ? (usedCount / batch.quantity) * 100 : 0;
};

const viewBatchDetails = (batch) => {
  // Aquí puedes implementar el modal de detalles si lo deseas
};

const showOnlineLink = (batch) => {
  const baseUrl = window.location.origin;
  const evaluationUrl = `${baseUrl}/evaluacion`;
  
  // Mostrar modal o alert con la información
  alert(`Enlace para evaluaciones en línea:\n\n${evaluationUrl}\n\nLos participantes deberán usar sus folios del lote "${batch.name}" (${formatFolioNumber(batch.start_number)} - ${formatFolioNumber(batch.end_number)})`);
};

const deleteBatch = (batch) => {
  // Aquí puedes implementar el modal de confirmación y lógica de borrado
};
</script>
