<template>
  <div class="mt-4">
    <h4 class="text-sm font-medium mb-2">Detalles por nivel de riesgo:</h4>
    <div class="flex flex-wrap gap-2">
      <button 
        v-for="(count, risk) in props.itemData.risk_levels" 
        :key="risk"
        @click="() => showPersonalIds(risk, count)"
        :class="[
          'px-3 py-1 text-sm rounded-md font-medium shadow-sm focus:outline-none',
          risk === 'Nulo' ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' :
          risk === 'Bajo' ? 'bg-green-100 text-green-700 hover:bg-green-200' :
          risk === 'Medio' ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' :
          risk === 'Alto' ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' :
          'bg-red-100 text-red-700 hover:bg-red-200'
        ]"
        :title="`${count} personas en nivel ${risk}`"
      >
        {{ risk }} ({{ count }})
      </button>
    </div>

    <!-- Modal de IDs de personal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div class="p-4 border-b flex justify-between items-center">
          <h3 class="text-lg font-medium">
            Personal en nivel de riesgo <span :class="modalRiskClass">{{ selectedRisk }}</span>
          </h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="p-4 overflow-y-auto flex-grow">
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-gray-700">{{ props.itemData.name }}</h4>
              <p class="text-sm text-gray-600">Total: {{ selectedCount }} personas</p>
            </div>
            
            <div v-if="selectedPersonal.length > 0" class="border rounded-md overflow-hidden">
              <div class="bg-gray-50 px-4 py-2 border-b">
                <h5 class="text-sm font-medium text-gray-700">IDs del Personal</h5>
              </div>
              <div class="max-h-60 overflow-y-auto">
                <ul class="divide-y divide-gray-200">
                  <li v-for="id in selectedPersonal" :key="id" class="px-4 py-2 text-sm hover:bg-gray-50">
                    <Link 
                      :href="route('responses.personal', { 
                        organizationId: props.organizationId,
                        personalId: id 
                      })"
                      class="text-blue-600 hover:text-blue-800 hover:underline"
                    >
                      {{ id }}
                    </Link>
                  </li>
                </ul>
              </div>
            </div>
            
            <div v-else class="text-center text-gray-500 py-4">
              No hay personal registrado en este nivel de riesgo
            </div>
          </div>
        </div>
        
        <div class="p-4 border-t flex justify-end">
          <button 
            @click="closeModal"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-gray-800"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  itemData: {
    type: Object,
    required: true
  },
  itemType: {
    type: String,
    required: true,
    validator: value => ['dimension', 'domain', 'category', 'final'].includes(value)
  },
  // Añadir la propiedad organizationId
  organizationId: {
    type: [Number, String],
    required: true
  }
});

console.log('Categorias:', props.itemData);

// Estados para el modal
const showModal = ref(false);
const selectedRisk = ref('');
const selectedCount = ref(0);
const selectedPersonal = ref([]);

// Clase CSS según el nivel de riesgo seleccionado
const modalRiskClass = computed(() => {
  switch (selectedRisk.value) {
    case 'Nulo': return 'text-blue-700';
    case 'Bajo': return 'text-green-700';
    case 'Medio': return 'text-yellow-700';
    case 'Alto': return 'text-orange-700';
    case 'Muy Alto': return 'text-red-700';
    default: return '';
  }
});

// Método para mostrar la información del nivel de riesgo en el modal
const showPersonalIds = (risk, count) => {
  selectedRisk.value = risk;
  selectedCount.value = count;
  // Buscar los IDs del personal para este nivel de riesgo
  const matchingItems = props.itemData.personal_by_risk && 
                       props.itemData.personal_by_risk[risk] ? 
                       props.itemData.personal_by_risk[risk] : [];
  selectedPersonal.value = matchingItems;
  showModal.value = true;
};

// Método para cerrar el modal
const closeModal = () => {
  showModal.value = false;
  selectedRisk.value = '';
  selectedCount.value = 0;
  selectedPersonal.value = [];
};
</script>
