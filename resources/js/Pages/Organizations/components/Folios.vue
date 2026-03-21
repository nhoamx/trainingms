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
            <div class="sm:col-span-2">
              <label for="batch-work-center" class="block text-sm font-medium text-gray-700">Centro de Trabajo</label>
              <div class="mt-1">
                <select id="batch-work-center" v-model="folioBatchForm.work_center_id" class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600">
                  <option value="">Selecciona un centro de trabajo</option>
                  <option v-for="wc in organization.work_centers" :key="wc.id" :value="wc.id">
                    {{ wc.code }} - {{ wc.name }} {{ wc.is_primary ? '(Principal)' : '' }}
                  </option>
                </select>
                <p v-if="folioBatchForm.errors.work_center_id" class="mt-1 text-xs text-red-500">{{ folioBatchForm.errors.work_center_id }}</p>
              </div>
            </div>
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
                  <option value="hibrido">Híbrido (OMR + Online)</option>
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

      <div v-if="isAdminUser" class="mt-6 rounded-lg border border-indigo-200 bg-indigo-50/40 p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-indigo-900">Plantillas en blanco para impresión</h3>
            <p class="mt-1 text-xs text-indigo-800">
              Descarga las guías sin folio prellenado para impresión manual.
            </p>
          </div>
          <span class="inline-flex items-center rounded-full bg-white px-2 py-1 text-[11px] font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">
            Admin
          </span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div v-for="template in blankTemplateOptions" :key="template.route" class="rounded-md border border-indigo-100 bg-white p-3">
            <p class="text-sm font-medium text-gray-900">{{ template.label }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ template.description }}</p>
            <button
              type="button"
              class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              @click="downloadBlankTemplate(template.route)"
            >
              <DocumentArrowDownIcon class="mr-1.5 h-4 w-4" />
              Descargar PDF
            </button>
          </div>
        </div>
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
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" :class="batch.type === 'presencial' ? 'bg-blue-50 text-blue-700 ring-blue-700/10' : batch.type === 'hibrido' ? 'bg-purple-50 text-purple-700 ring-purple-700/10' : 'bg-green-50 text-green-700 ring-green-700/10'">
                      {{ batch.type === 'presencial' ? 'Presencial' : batch.type === 'hibrido' ? 'Híbrido' : 'En línea' }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                      {{ batch.quantity }} folios
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">{{ batch.description || 'Sin descripción' }}</p>
                  <div v-if="batch.work_center" class="mt-1">
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10">
                      <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M3.75 3v18m16.5-18v18M5.25 9h1.5m-1.5 3h1.5m-1.5 3h1.5m7.5-9h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                      {{ batch.work_center.code }} - {{ batch.work_center.name }}
                    </span>
                  </div>
                </div>
                <div class="flex shrink-0 items-center gap-x-2">
                  <button v-if="batch.type === 'presencial' || batch.type === 'hibrido'" @click="generatePdfForBatch(batch)" type="button" class="rounded-full bg-white p-1 text-gray-400 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" :title="batch.type === 'hibrido' ? 'Generar PDF con código QR (OMR + Híbrido)' : 'Generar PDF con folios'">
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
    
    <!-- Modal de progreso para lotes grandes -->
    <div v-if="showProgressModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">Generando PDFs</h3>
          <p class="mt-1 text-sm text-gray-500">
            Por favor espera mientras se generan los archivos PDF...
          </p>
        </div>
        
        <div class="space-y-4">
          <!-- Estado del proceso -->
          <div v-if="jobProgress.status === 'pending'" class="flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Iniciando generación...</span>
          </div>
          
          <div v-if="jobProgress.status === 'processing'" class="space-y-2">
            <div class="flex justify-between text-sm text-gray-600">
              <span>Progreso:</span>
              <span class="font-medium">{{ jobProgress.processed }} / {{ jobProgress.total }} folios</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" :style="`width: ${jobProgress.percentage}%`"></div>
            </div>
            <p class="text-xs text-gray-500 text-center">{{ Math.round(jobProgress.percentage) }}%</p>
          </div>
          
          <div v-if="jobProgress.status === 'completed'" class="flex items-center space-x-2 text-green-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">¡Generación completada!</span>
          </div>
          
          <div v-if="jobProgress.status === 'failed'" class="flex items-center space-x-2 text-red-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <span class="text-sm font-medium">Error al generar los PDFs</span>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            v-if="jobProgress.status === 'completed' || jobProgress.status === 'failed'" 
            @click="closeProgressModal" 
            type="button" 
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
          >
            Cerrar
          </button>
          <button 
            v-if="jobProgress.status === 'completed'" 
            @click="downloadPdf" 
            type="button" 
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Descargar PDFs
          </button>
        </div>
      </div>
    </div>
    <!-- Modal de detalles y eliminación pueden agregarse aquí si se requiere -->
  </div>
</template>

<script setup>

import { computed, onUnmounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { PlusIcon, EyeIcon, TrashIcon, ArchiveBoxIcon, LinkIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/solid';
import axios from 'axios';


const props = defineProps({
  organization: { type: Object, required: true }
});

const page = usePage();

const isAdminUser = computed(() => {
  const roles = page.props.auth?.user?.roles || [];

  return roles.some((role) => ['admin', 'super-admin'].includes(role.name));
});

const blankTemplateOptions = [
  {
    route: 'omr.download.blank.referencia-i',
    label: 'Guia de Referencia I',
    description: 'Acontecimientos traumaticos severos',
  },
  {
    route: 'omr.download.blank.referencia-iii',
    label: 'Guia de Referencia III',
    description: 'Factores de riesgo psicosocial',
  },
  {
    route: 'omr.download.blank.referencia-v',
    label: 'Guia de Referencia V',
    description: 'Datos del trabajador',
  },
  {
    route: 'omr.download.blank.escala-cisneros',
    label: 'Escala Cisneros',
    description: 'Violencia psicologica en el trabajo',
  },
];

const downloadBlankTemplate = (routeName) => {
  window.open(route(routeName), '_blank');
};

const folioBatchForm = useForm({
  organization_id: props.organization.id,
  work_center_id: '',
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
  if (!folioBatchForm.work_center_id) {
    folioBatchForm.setError('work_center_id', 'Debes seleccionar un centro de trabajo.');
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

// Progress tracking state
const showProgressModal = ref(false);
const currentJobId = ref(null);
const jobProgress = ref({
  status: 'pending',
  processed: 0,
  total: 0,
  percentage: 0,
  file_paths: []
});
let pollingInterval = null;

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

const generatePdf = async () => {
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

  // Si son más de 100 folios, usar AJAX con seguimiento de progreso
  if (foliosToUse.length > 100) {
    try {
      const response = await axios.post(route('omr.generate-pdf'), {
        organization_id: props.organization.id,
        folio_batch_id: selectedBatch.value.id,
        guide_type: selectedGuideType.value,
        generate_all: generateAll.value ? '1' : '0',
        folios: foliosToUse
      });

      if (response.data.job_id) {
        // Cerrar modal de configuración y abrir modal de progreso
        showPdfModal.value = false;
        currentJobId.value = response.data.job_id;
        showProgressModal.value = true;
        startPolling();
      }
    } catch (error) {
      console.error('Error al generar PDF:', error);
      alert('Error al iniciar la generación del PDF. Por favor intenta de nuevo.');
    }
    return;
  }

  // Para lotes pequeños (<= 100), usar el método tradicional de form submit
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

// Polling functions
const startPolling = () => {
  // Poll immediately
  checkJobStatus();
  
  // Then poll every 2 seconds
  pollingInterval = setInterval(checkJobStatus, 2000);
};

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
};

const checkJobStatus = async () => {
  if (!currentJobId.value) return;
  
  try {
    const response = await axios.get(route('api.pdf-jobs.show', currentJobId.value));
    jobProgress.value = response.data;
    
    // Si el job terminó (completed o failed), detener el polling
    if (response.data.status === 'completed' || response.data.status === 'failed') {
      stopPolling();
    }
  } catch (error) {
    console.error('Error al verificar estado del job:', error);
    stopPolling();
  }
};

const downloadPdf = async () => {
  if (!currentJobId.value) return;
  
  try {
    // Abrir la URL de descarga en una nueva ventana
    window.open(route('api.pdf-jobs.download', currentJobId.value), '_blank');
  } catch (error) {
    console.error('Error al descargar PDF:', error);
    alert('Error al descargar el PDF. Por favor intenta de nuevo.');
  }
};

const closeProgressModal = () => {
  stopPolling();
  showProgressModal.value = false;
  currentJobId.value = null;
  jobProgress.value = {
    status: 'pending',
    processed: 0,
    total: 0,
    percentage: 0,
    file_paths: []
  };
};

// Cleanup on unmount
onUnmounted(() => {
  stopPolling();
});
</script>
