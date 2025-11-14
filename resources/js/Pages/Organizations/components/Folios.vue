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
                  <button v-if="batch.type === 'presencial'" @click="generatePdfForBatch(batch)" type="button" class="rounded-full bg-white p-1 text-gray-400 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" title="Generar PDF con folios">
                    <DocumentArrowDownIcon class="h-5 w-5" />
                  </button>
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
    
    <!-- Modal para generar PDF -->
    <div v-if="showPdfModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">Generar PDF de Evaluaciones</h3>
          <p class="mt-1 text-sm text-gray-500">
            Lote: {{ selectedBatch?.name }} ({{ formatFolioNumber(selectedBatch?.start_number) }} - {{ formatFolioNumber(selectedBatch?.end_number) }})
          </p>
        </div>
        
        <div class="space-y-4">
          <!-- Selector de tipo de guía -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de evaluación</label>
            <select v-model="selectedGuideType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">Selecciona un tipo</option>
              <option v-for="guide in guideTypes" :key="guide.value" :value="guide.value">
                {{ guide.label }}
              </option>
            </select>
          </div>
          
          <!-- Checkbox para generar todos -->
          <div class="flex items-center">
            <input v-model="generateAll" id="generate-all" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            <label for="generate-all" class="ml-2 block text-sm text-gray-700">Generar todos los folios del lote</label>
          </div>
          
          <!-- Selector de folios específicos -->
          <div v-if="!generateAll">
            <label class="block text-sm font-medium text-gray-700">Folios específicos (separados por coma)</label>
            <input 
              v-model="selectedFolios" 
              type="text" 
              placeholder="ej: 0001,0002,0003"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              @input="selectedFolios = $event.target.value.split(',').map(f => f.trim()).filter(f => f)"
            >
            <p class="mt-1 text-xs text-gray-500">
              Ingresa los números de folio que deseas incluir en el PDF
            </p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <button @click="closePdfModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
            Cancelar
          </button>
          <button @click="generatePdf" type="button" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
            Generar PDF
          </button>
        </div>
      </div>
    </div>
    <!-- Modal de detalles y eliminación pueden agregarse aquí si se requiere -->
  </div>
</template>

<script setup>

import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PlusIcon, EyeIcon, TrashIcon, ArchiveBoxIcon, LinkIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/solid';


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

// PDF Generation state
const showPdfModal = ref(false);
const selectedBatch = ref(null);
const selectedGuideType = ref('');
const selectedFolios = ref([]);
const generateAll = ref(false);

const guideTypes = [
  { value: 'referencia-i', label: 'Guía de Referencia I' },
  { value: 'referencia-iii', label: 'Guía de Referencia III' }, 
  { value: 'referencia-v', label: 'Guía de Referencia V' },
  { value: 'escala-cisneros', label: 'Escala Cisneros' },
  { value: 'likert', label: 'Clima laboral' },
];

const generatePdfForBatch = (batch) => {
  selectedBatch.value = batch;
  selectedGuideType.value = '';
  selectedFolios.value = [];
  generateAll.value = false;
  showPdfModal.value = true;
};

const generatePdf = () => {
  if (!selectedGuideType.value) {
    alert('Por favor selecciona un tipo de guía');
    return;
  }

  let foliosToUse = [];
  if (generateAll.value) {
    // Generar folios basados en el rango del batch
    for (let i = selectedBatch.value.start_number; i <= selectedBatch.value.end_number; i++) {
      foliosToUse.push(i.toString().padStart(4, '0'));
    }
  } else {
    if (selectedFolios.value.length === 0) {
      alert('Por favor selecciona al menos un folio o marca "Generar todos"');
      return;
    }
    foliosToUse = selectedFolios.value;
  }

  // Create a hidden form and submit it to trigger file download
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = route('omr.generate-pdf');
  form.style.display = 'none';

  // Add CSRF token
  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = '_token';
  csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  form.appendChild(csrfInput);

  // Add organization_id
  const orgInput = document.createElement('input');
  orgInput.type = 'hidden';
  orgInput.name = 'organization_id';
  orgInput.value = props.organization.id;
  form.appendChild(orgInput);

  // Add folio_batch_id
  const batchInput = document.createElement('input');
  batchInput.type = 'hidden';
  batchInput.name = 'folio_batch_id';
  batchInput.value = selectedBatch.value.id;
  form.appendChild(batchInput);

  // Add guide_type
  const typeInput = document.createElement('input');
  typeInput.type = 'hidden';
  typeInput.name = 'guide_type';
  typeInput.value = selectedGuideType.value;
  form.appendChild(typeInput);

  // Add generate_all
  const generateAllInput = document.createElement('input');
  generateAllInput.type = 'hidden';
  generateAllInput.name = 'generate_all';
  generateAllInput.value = generateAll.value ? '1' : '0';
  form.appendChild(generateAllInput);

  // Add folios array
  foliosToUse.forEach((folio, index) => {
    const folioInput = document.createElement('input');
    folioInput.type = 'hidden';
    folioInput.name = `folios[${index}]`;
    folioInput.value = folio;
    form.appendChild(folioInput);
  });

  // Append form to body, submit, and remove
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  // Close modal after submitting
  showPdfModal.value = false;
};

const closePdfModal = () => {
  showPdfModal.value = false;
  selectedBatch.value = null;
  selectedGuideType.value = '';
  selectedFolios.value = [];
  generateAll.value = false;
};
</script>
