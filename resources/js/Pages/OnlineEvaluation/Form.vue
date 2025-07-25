<template>
  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Evaluación en Línea</h1>
          <p class="mt-2 text-lg text-gray-600">{{ organization?.name || 'Organización' }}</p>
          <div class="mt-4 inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full">
            <span class="font-medium">Folio: {{ folio }}</span>
          </div>
        </div>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="submitEvaluation" class="space-y-6">
        <!-- Información Personal -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Información Personal</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="personal_id" class="block text-sm font-medium text-gray-700">
                ID Personal (4 dígitos)
              </label>
              <input
                type="text"
                id="personal_id"
                v-model="form.personal_id"
                maxlength="4"
                pattern="[0-9]{4}"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="0000"
              />
              <p v-if="form.errors.personal_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.personal_id }}
              </p>
            </div>
            <div>
              <label for="reference_guide" class="block text-sm font-medium text-gray-700">
                Tipo de Evaluación
              </label>
              <select
                id="reference_guide"
                v-model="form.reference_guide"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Selecciona un tipo</option>
                <option value="I">Guía I</option>
                <option value="III">Guía III</option>
                <option value="V">Guía V</option>
              </select>
              <p v-if="form.errors.reference_guide" class="mt-1 text-sm text-red-600">
                {{ form.errors.reference_guide }}
              </p>
            </div>
          </div>
        </div>

        <!-- Preguntas dinámicas basadas en el tipo -->
        <div v-if="form.reference_guide" class="bg-white shadow rounded-lg p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">
            Preguntas - {{ getGuideTitle(form.reference_guide) }}
          </h2>
          
          <!-- Preguntas para Guía I -->
          <div v-if="form.reference_guide === 'I'" class="space-y-4">
            <div v-for="(question, index) in guideIQuestions" :key="index" class="border-b pb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ index + 1 }}. {{ question.text }}
              </label>
              <div class="space-y-2">
                <label v-for="option in question.options" :key="option.value" class="flex items-center">
                  <input
                    type="radio"
                    :name="`pregunta_${index + 1}`"
                    :value="option.value"
                    v-model="form.answers[`pregunta_${index + 1}`]"
                    class="mr-2 text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="text-sm text-gray-700">{{ option.label }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Preguntas para Guía III -->
          <div v-if="form.reference_guide === 'III'" class="space-y-4">
            <div v-for="questionNum in 72" :key="questionNum" class="border-b pb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Pregunta {{ questionNum.toString().padStart(2, '0') }}
              </label>
              <div class="space-y-2">
                <label v-for="option in guideIIIOptions" :key="option.value" class="flex items-center">
                  <input
                    type="radio"
                    :name="`question_${questionNum}`"
                    :value="option.value"
                    v-model="form.answers[questionNum.toString().padStart(2, '0')]"
                    class="mr-2 text-indigo-600 focus:ring-indigo-500"
                  />
                  <span class="text-sm text-gray-700">{{ option.label }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Preguntas para Guía V -->
          <div v-if="form.reference_guide === 'V'" class="space-y-4">
            <div v-for="(question, key) in guideVQuestions" :key="key" class="border-b pb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ question.label }}
              </label>
              <div v-if="question.type === 'select'" class="mt-1">
                <select
                  v-model="form.answers[key]"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">Selecciona una opción</option>
                  <option v-for="option in question.options" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <div v-else-if="question.type === 'number'" class="mt-1">
                <input
                  type="number"
                  v-model="form.answers[key]"
                  :min="question.min"
                  :max="question.max"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div v-else class="mt-1">
                <input
                  type="text"
                  v-model="form.answers[key]"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de acción -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="flex justify-between">
            <button
              type="button"
              @click="$inertia.visit(route('evaluations.index'))"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing || !isFormValid"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="form.processing">Guardando...</span>
              <span v-else>Enviar Evaluación</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  folio: String,
  organization: Object,
  title: String
})

const form = useForm({
  folio: props.folio,
  personal_id: '',
  reference_guide: '',
  answers: {}
})

// Configuración de preguntas para cada guía
const guideIQuestions = [
  {
    text: "¿Con qué frecuencia se siente emocionalmente agotado por su trabajo?",
    options: [
      { value: "nunca", label: "Nunca" },
      { value: "pocas_veces", label: "Pocas veces al año" },
      { value: "una_vez_mes", label: "Una vez al mes" },
      { value: "pocas_veces_mes", label: "Pocas veces al mes" },
      { value: "una_vez_semana", label: "Una vez a la semana" },
      { value: "pocas_veces_semana", label: "Pocas veces a la semana" },
      { value: "todos_dias", label: "Todos los días" }
    ]
  }
  // Agregar más preguntas según sea necesario
]

const guideIIIOptions = [
  { value: "siempre", label: "Siempre" },
  { value: "casi_siempre", label: "Casi siempre" },
  { value: "algunas_veces", label: "Algunas veces" },
  { value: "casi_nunca", label: "Casi nunca" },
  { value: "nunca", label: "Nunca" }
]

const guideVQuestions = {
  sexo: {
    label: "Sexo",
    type: "select",
    options: [
      { value: "hombre", label: "Hombre" },
      { value: "mujer", label: "Mujer" }
    ]
  },
  edad: {
    label: "Edad",
    type: "number",
    min: 18,
    max: 100
  },
  estado_civil: {
    label: "Estado Civil",
    type: "select",
    options: [
      { value: "soltero", label: "Soltero(a)" },
      { value: "casado", label: "Casado(a)" },
      { value: "union_libre", label: "Unión libre" },
      { value: "divorciado", label: "Divorciado(a)" },
      { value: "viudo", label: "Viudo(a)" }
    ]
  }
  // Agregar más campos demográficos según sea necesario
}

const isFormValid = computed(() => {
  return form.personal_id.length === 4 && 
         form.reference_guide && 
         Object.keys(form.answers).length > 0
})

const getGuideTitle = (guide) => {
  const titles = {
    'I': 'Cuestionario de Maslach',
    'III': 'Factores Psicosociales',
    'V': 'Datos Demográficos'
  }
  return titles[guide] || 'Evaluación'
}

const submitEvaluation = () => {
  form.post(route('online-evaluation.store'), {
    onSuccess: (response) => {
      // Redirigir a la página de resultados
      window.location.href = route('online-evaluation.result', response.evaluation_id)
    },
    onError: (errors) => {
      console.error('Error al enviar evaluación:', errors)
    }
  })
}
</script>