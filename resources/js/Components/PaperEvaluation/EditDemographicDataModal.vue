<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Editar Datos Demográficos
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    Folio: <span class="font-semibold">{{ evaluation.folio }}</span>
                                </p>
                                
                                <div class="max-h-96 overflow-y-auto space-y-4 pr-2">
                                    <!-- Sexo -->
                                    <div>
                                        <label for="sexo" class="block text-sm font-medium text-gray-700 mb-1">
                                            Sexo
                                        </label>
                                        <select
                                            id="sexo"
                                            v-model="form.sexo"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.sexo }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                        </select>
                                        <p v-if="form.errors.sexo" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.sexo }}
                                        </p>
                                    </div>

                                    <!-- Edad -->
                                    <div>
                                        <label for="edad" class="block text-sm font-medium text-gray-700 mb-1">
                                            Edad
                                        </label>
                                        <input
                                            id="edad"
                                            type="number"
                                            v-model="form.edad"
                                            min="15"
                                            max="99"
                                            placeholder="Ingrese la edad"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.edad }"
                                        />
                                        <p v-if="form.errors.edad" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.edad }}
                                        </p>
                                    </div>

                                    <!-- Estado Civil -->
                                    <div>
                                        <label for="estado_civil" class="block text-sm font-medium text-gray-700 mb-1">
                                            Estado Civil
                                        </label>
                                        <select
                                            id="estado_civil"
                                            v-model="form.estado_civil"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.estado_civil }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Casado">Casado</option>
                                            <option value="Soltero">Soltero</option>
                                            <option value="Unión libre">Unión libre</option>
                                            <option value="Divorciado">Divorciado</option>
                                            <option value="Viudo">Viudo</option>
                                        </select>
                                        <p v-if="form.errors.estado_civil" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.estado_civil }}
                                        </p>
                                    </div>

                                    <!-- Nivel de Estudios -->
                                    <div>
                                        <label for="nivel_estudios" class="block text-sm font-medium text-gray-700 mb-1">
                                            Nivel de Estudios
                                        </label>
                                        <select
                                            id="nivel_estudios"
                                            v-model="form.nivel_estudios"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.nivel_estudios }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Sin formación">Sin formación</option>
                                            <option value="Primaria Terminada">Primaria - Terminada</option>
                                            <option value="Primaria Incompleta">Primaria - Incompleta</option>
                                            <option value="Secundaria Terminada">Secundaria - Terminada</option>
                                            <option value="Secundaria Incompleta">Secundaria - Incompleta</option>
                                            <option value="Preparatoria o Bachillerato Terminada">Preparatoria o Bachillerato - Terminada</option>
                                            <option value="Preparatoria o Bachillerato Incompleta">Preparatoria o Bachillerato - Incompleta</option>
                                            <option value="Técnico Superior Terminada">Técnico Superior - Terminada</option>
                                            <option value="Técnico Superior Incompleta">Técnico Superior - Incompleta</option>
                                            <option value="Licenciatura Terminada">Licenciatura - Terminada</option>
                                            <option value="Licenciatura Incompleta">Licenciatura - Incompleta</option>
                                            <option value="Maestría Terminada">Maestría - Terminada</option>
                                            <option value="Maestría Incompleta">Maestría - Incompleta</option>
                                            <option value="Doctorado Terminada">Doctorado - Terminada</option>
                                            <option value="Doctorado Incompleta">Doctorado - Incompleta</option>
                                        </select>
                                        <p v-if="form.errors.nivel_estudios" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.nivel_estudios }}
                                        </p>
                                    </div>
                                
                                    <!-- Ocupación/Puesto -->
                                    <div>
                                        <label for="ocupacion" class="block text-sm font-medium text-gray-700 mb-1">
                                            Ocupación / Puesto
                                        </label>
                                        <select
                                            id="ocupacion"
                                            v-model="form.ocupacion"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.ocupacion }"
                                        >
                                            <option value="">Seleccione un puesto</option>
                                            <option v-for="position in occupationPositions" :key="position.id" :value="position.name">
                                                {{ position.name }}
                                            </option>
                                            <option value="__custom__">Otro (escribir manualmente)</option>
                                        </select>
                                        
                                        <!-- Campo de texto personalizado si selecciona "Otro" -->
                                        <input
                                            v-if="form.ocupacion === '__custom__'"
                                            type="text"
                                            v-model="customOcupacion"
                                            placeholder="Escriba la ocupación/puesto"
                                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            maxlength="100"
                                        />
                                        
                                        <p v-if="form.errors.ocupacion" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.ocupacion }}
                                        </p>
                                    </div>

                                    <!-- Departamento/Sección/Área -->
                                    <div>
                                        <label for="departamento" class="block text-sm font-medium text-gray-700 mb-1">
                                            Departamento / Sección / Área
                                        </label>
                                        <select
                                            id="departamento"
                                            v-model="form.departamento"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.departamento }"
                                        >
                                            <option value="">Seleccione un departamento</option>
                                            <option v-for="dept in departmentAreas" :key="dept.id" :value="dept.name">
                                                {{ dept.name }}
                                            </option>
                                            <option value="__custom__">Otro (escribir manualmente)</option>
                                        </select>
                                        
                                        <!-- Campo de texto personalizado si selecciona "Otro" -->
                                        <input
                                            v-if="form.departamento === '__custom__'"
                                            type="text"
                                            v-model="customDepartamento"
                                            placeholder="Escriba el departamento/sección/área"
                                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            maxlength="100"
                                        />
                                        
                                        <p v-if="form.errors.departamento" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.departamento }}
                                        </p>
                                    </div>

                                    <!-- Tipo de Puesto -->
                                    <div>
                                        <label for="tipo_puesto" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tipo de Puesto
                                        </label>
                                        <select
                                            id="tipo_puesto"
                                            v-model="form.tipo_puesto"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tipo_puesto }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Operativo">Operativo</option>
                                            <option value="Profesional o técnico">Profesional o técnico</option>
                                            <option value="Supervisor">Supervisor</option>
                                            <option value="Gerente">Gerente</option>
                                        </select>
                                        <p v-if="form.errors.tipo_puesto" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tipo_puesto }}
                                        </p>
                                    </div>

                                    <!-- Tipo de Contratación -->
                                    <div>
                                        <label for="tipo_contratacion" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tipo de Contratación
                                        </label>
                                        <select
                                            id="tipo_contratacion"
                                            v-model="form.tipo_contratacion"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tipo_contratacion }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Por obra o proyecto">Por obra o proyecto</option>
                                            <option value="Por tiempo determinado (temporal)">Por tiempo determinado (temporal)</option>
                                            <option value="Tiempo indeterminado">Tiempo indeterminado</option>
                                            <option value="Honorarios">Honorarios</option>
                                        </select>
                                        <p v-if="form.errors.tipo_contratacion" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tipo_contratacion }}
                                        </p>
                                    </div>

                                    <!-- Tipo de Personal -->
                                    <div>
                                        <label for="tipo_personal" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tipo de Personal
                                        </label>
                                        <select
                                            id="tipo_personal"
                                            v-model="form.tipo_personal"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tipo_personal }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Sindicalizado">Sindicalizado</option>
                                            <option value="Confianza">Confianza</option>
                                            <option value="Ninguno">Ninguno</option>
                                        </select>
                                        <p v-if="form.errors.tipo_personal" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tipo_personal }}
                                        </p>
                                    </div>

                                    <!-- Tipo de Jornada -->
                                    <div>
                                        <label for="tipo_jornada" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tipo de Jornada
                                        </label>
                                        <select
                                            id="tipo_jornada"
                                            v-model="form.tipo_jornada"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tipo_jornada }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Fijo nocturno (entre las 20:00 y 6:00 hrs)">Fijo nocturno (entre las 20:00 y 6:00 hrs)</option>
                                            <option value="Fijo diurno (entre las 6:00 y 20:00 hrs)">Fijo diurno (entre las 6:00 y 20:00 hrs)</option>
                                            <option value="Fijo mixto (combinación de nocturno y diurno)">Fijo mixto (combinación de nocturno y diurno)</option>
                                        </select>
                                        <p v-if="form.errors.tipo_jornada" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tipo_jornada }}
                                        </p>
                                    </div>

                                    <!-- Rotación de Turnos -->
                                    <div>
                                        <label for="rotacion_turnos" class="block text-sm font-medium text-gray-700 mb-1">
                                            Rotación de Turnos
                                        </label>
                                        <select
                                            id="rotacion_turnos"
                                            v-model="form.rotacion_turnos"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.rotacion_turnos }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Sí">Sí</option>
                                            <option value="No">No</option>
                                        </select>
                                        <p v-if="form.errors.rotacion_turnos" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.rotacion_turnos }}
                                        </p>
                                    </div>

                                    <!-- Tiempo en el Puesto Actual -->
                                    <div>
                                        <label for="tiempo_puesto_actual" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tiempo en el Puesto Actual
                                        </label>
                                        <select
                                            id="tiempo_puesto_actual"
                                            v-model="form.tiempo_puesto_actual"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tiempo_puesto_actual }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Menos de 6 meses">Menos de 6 meses</option>
                                            <option value="Entre 6 meses y 1 año">Entre 6 meses y 1 año</option>
                                            <option value="Entre 1 a 4 años">Entre 1 a 4 años</option>
                                            <option value="Entre 5 a 9 años">Entre 5 a 9 años</option>
                                            <option value="Entre 10 a 14 años">Entre 10 a 14 años</option>
                                            <option value="Entre 15 a 19 años">Entre 15 a 19 años</option>
                                            <option value="Entre 20 a 24 años">Entre 20 a 24 años</option>
                                            <option value="25 años o más">25 años o más</option>
                                        </select>
                                        <p v-if="form.errors.tiempo_puesto_actual" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tiempo_puesto_actual }}
                                        </p>
                                    </div>

                                    <!-- Tiempo de Experiencia Laboral -->
                                    <div>
                                        <label for="tiempo_experiencia_laboral" class="block text-sm font-medium text-gray-700 mb-1">
                                            Tiempo de Experiencia Laboral
                                        </label>
                                        <select
                                            id="tiempo_experiencia_laboral"
                                            v-model="form.tiempo_experiencia_laboral"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            :class="{ 'border-red-300': form.errors.tiempo_experiencia_laboral }"
                                        >
                                            <option value="">Seleccione</option>
                                            <option value="Menos de 6 meses">Menos de 6 meses</option>
                                            <option value="Entre 6 meses y 1 año">Entre 6 meses y 1 año</option>
                                            <option value="Entre 1 a 4 años">Entre 1 a 4 años</option>
                                            <option value="Entre 5 a 9 años">Entre 5 a 9 años</option>
                                            <option value="Entre 10 a 14 años">Entre 10 a 14 años</option>
                                            <option value="Entre 15 a 19 años">Entre 15 a 19 años</option>
                                        </select>
                                        <p v-if="form.errors.tiempo_experiencia_laboral" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.tiempo_experiencia_laboral }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Success message -->
                                <div v-if="successMessage" class="mt-4 rounded-md bg-green-50 p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="ml-3 text-sm font-medium text-green-800">
                                            {{ successMessage }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Guardando...' : 'Guardar' }}
                    </button>
                    <button
                        type="button"
                        @click="closeModal"
                        :disabled="form.processing"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface DemographicData {
    sexo?: string;
    edad?: {
        decenas: number;
        unidades: number;
    } | string;
    estado_civil?: string;
    nivel_estudios?: any;
    ocupacion_puesto?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
    ocupacion?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
    departamento_seccion_area?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
    departamento?: {
        fila1: string | null;
        fila2: string | null;
    } | string;
    tipo_puesto?: string;
    tipo_contratacion?: string;
    tipo_personal?: string;
    tipo_jornada?: string;
    rotacion_turnos?: string;
    tiempo_puesto_actual?: string;
    tiempo_experiencia_laboral?: string;
    experiencia_laboral?: string;
}

interface Evaluation {
    id: string;
    folio: string;
    demographic_data?: DemographicData | null;
}

interface OccupationPosition {
    id: number;
    name: string;
}

interface DepartmentArea {
    id: number;
    name: string;
}

interface Props {
    show: boolean;
    evaluation: Evaluation;
    occupationPositions: OccupationPosition[];
    departmentAreas: DepartmentArea[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated'): void;
}>();

const successMessage = ref<string>('');
const customOcupacion = ref<string>('');
const customDepartamento = ref<string>('');

// Helper function to extract text from demographic data structure
const extractText = (data: any): string => {
    if (!data) return '';
    if (typeof data === 'string') return data;
    if (typeof data === 'object') {
        // Combinar fila1 y fila2 si existen
        const parts = [];
        if (data.fila1) parts.push(data.fila1);
        if (data.fila2) parts.push(data.fila2);
        return parts.join(' ');
    }
    return '';
};

const form = useForm({
    sexo: '',
    edad: '',
    estado_civil: '',
    nivel_estudios: '',
    ocupacion: '',
    departamento: '',
    tipo_puesto: '',
    tipo_contratacion: '',
    tipo_personal: '',
    tipo_jornada: '',
    rotacion_turnos: '',
    tiempo_puesto_actual: '',
    tiempo_experiencia_laboral: '',
});

// Reset form when modal opens
watch(() => props.show, (newValue) => {
    if (newValue) {
        const demographicData = props.evaluation.demographic_data;
        
        // Sexo - normalizar a capitalizado
        const sexo = extractText(demographicData?.sexo) || '';
        form.sexo = sexo ? sexo.charAt(0).toUpperCase() + sexo.slice(1).toLowerCase() : '';
        
        // Edad - puede venir como { decenas, unidades } o como string
        const edad = demographicData?.edad;
        if (edad && typeof edad === 'object' && 'decenas' in edad && 'unidades' in edad) {
            const edadNum = parseInt(`${edad.decenas}${edad.unidades}`);
            form.edad = edadNum.toString();
        } else if (typeof edad === 'string') {
            form.edad = edad;
        } else {
            form.edad = '';
        }
        
        // Estado Civil - normalizar para comparación
        const estadoCivil = extractText(demographicData?.estado_civil) || '';
        const estadoCivilNormalized = estadoCivil.toLowerCase().replace(/\s+/g, '_');
        
        const estadoCivilMap: Record<string, string> = {
            'union_libre': 'Unión libre',
            'unionlibre': 'Unión libre',
            'casado': 'Casado',
            'soltero': 'Soltero',
            'divorciado': 'Divorciado',
            'viudo': 'Viudo'
        };
        
        form.estado_civil = estadoCivilMap[estadoCivilNormalized] || estadoCivil;
        
        // Nivel de estudios - puede venir como objeto complejo o string
        const nivelEstudios = demographicData?.nivel_estudios;
        let nivelEstudiosValue = '';
        
        if (nivelEstudios && typeof nivelEstudios === 'object') {
            for (const [nivel, datos] of Object.entries(nivelEstudios)) {
                if (datos && typeof datos === 'object' && 'seleccionado' in datos && datos.seleccionado) {
                    // Mapeo de claves a nombres legibles
                    const nivelMap: Record<string, string> = {
                        'sin_formacion': 'Sin formación',
                        'primaria': 'Primaria',
                        'secundaria': 'Secundaria',
                        'preparatoria_o_bachillerato': 'Preparatoria o Bachillerato',
                        'tecnico_superior': 'Técnico Superior',
                        'licenciatura': 'Licenciatura',
                        'maestria': 'Maestría',
                        'doctorado': 'Doctorado'
                    };
                    
                    const nivelNombre = nivelMap[nivel] || nivel.split('_').map((word: string) => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                    
                    if ('completado' in datos) {
                        const completado = datos.completado === 'completo' ? 'Terminada' : 'Incompleta';
                        nivelEstudiosValue = `${nivelNombre} ${completado}`;
                    } else {
                        nivelEstudiosValue = nivelNombre;
                    }
                    break;
                }
            }
        } else if (typeof nivelEstudios === 'string') {
            nivelEstudiosValue = nivelEstudios;
        }
        
        form.nivel_estudios = nivelEstudiosValue;
        
        // Ocupación y Departamento con lógica personalizada
        const extractedOcupacion = extractText(demographicData?.ocupacion_puesto || demographicData?.ocupacion) || '';
        const extractedDepartamento = extractText(demographicData?.departamento_seccion_area || demographicData?.departamento) || '';
        
        const ocupacionExists = props.occupationPositions.some(p => p.name === extractedOcupacion);
        const departamentoExists = props.departmentAreas.some(d => d.name === extractedDepartamento);
        
        if (extractedOcupacion && !ocupacionExists) {
            form.ocupacion = '__custom__';
            customOcupacion.value = extractedOcupacion;
        } else {
            form.ocupacion = extractedOcupacion;
            customOcupacion.value = '';
        }
        
        if (extractedDepartamento && !departamentoExists) {
            form.departamento = '__custom__';
            customDepartamento.value = extractedDepartamento;
        } else {
            form.departamento = extractedDepartamento;
            customDepartamento.value = '';
        }
        
        // Resto de campos laborales - convertir de snake_case a formato legible
        const tipoPuesto = extractText(demographicData?.tipo_puesto) || '';
        const tipoPuestoMap: Record<string, string> = {
            'operativo': 'Operativo',
            'profesional_o_tecnico': 'Profesional o técnico',
            'supervisor': 'Supervisor',
            'gerente': 'Gerente'
        };
        form.tipo_puesto = tipoPuestoMap[tipoPuesto] || tipoPuesto;
        
        const tipoContratacion = extractText(demographicData?.tipo_contratacion) || '';
        const tipoContratacionMap: Record<string, string> = {
            'por_obra_o_proyecto': 'Por obra o proyecto',
            'por_tiempo_determinado_(temporal)': 'Por tiempo determinado (temporal)',
            'tiempo_indeterminado': 'Tiempo indeterminado',
            'honorarios': 'Honorarios'
        };
        form.tipo_contratacion = tipoContratacionMap[tipoContratacion] || tipoContratacion;
        
        const tipoPersonal = extractText(demographicData?.tipo_personal) || '';
        const tipoPersonalMap: Record<string, string> = {
            'sindicalizado': 'Sindicalizado',
            'confianza': 'Confianza',
            'ninguno': 'Ninguno'
        };
        form.tipo_personal = tipoPersonalMap[tipoPersonal] || tipoPersonal;
        
        const tipoJornada = extractText(demographicData?.tipo_jornada) || '';
        const tipoJornadaMap: Record<string, string> = {
            'fijo_nocturno_(entre_las_20:00_y_6:00_hrs)': 'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
            'fijo_diurno_(entre_las_6:00_y_20:00_hrs)': 'Fijo diurno (entre las 6:00 y 20:00 hrs)',
            'fijo_mixto_(combinacion_de_nocturno_y_diurno)': 'Fijo mixto (combinación de nocturno y diurno)'
        };
        form.tipo_jornada = tipoJornadaMap[tipoJornada] || tipoJornada;
        
        const rotacionTurnos = extractText(demographicData?.rotacion_turnos) || '';
        const rotacionTurnosMap: Record<string, string> = {
            'si': 'Sí',
            'no': 'No'
        };
        form.rotacion_turnos = rotacionTurnosMap[rotacionTurnos] || rotacionTurnos;
        
        const tiempoPuesto = extractText(demographicData?.tiempo_puesto_actual) || '';
        const tiempoPuestoMap: Record<string, string> = {
            'menos_de_6_meses': 'Menos de 6 meses',
            'entre_6_meses_y_1_ano': 'Entre 6 meses y 1 año',
            'entre_1_a_4_anos': 'Entre 1 a 4 años',
            'entre_5_a_9_anos': 'Entre 5 a 9 años',
            'entre_10_a_14_anos': 'Entre 10 a 14 años',
            'entre_15_a_19_anos': 'Entre 15 a 19 años',
            'entre_20_a_24_anos': 'Entre 20 a 24 años',
            '25_anos_o_mas': '25 años o más'
        };
        form.tiempo_puesto_actual = tiempoPuestoMap[tiempoPuesto] || tiempoPuesto;
        
        const tiempoExperiencia = extractText(demographicData?.tiempo_experiencia_laboral || demographicData?.experiencia_laboral) || '';
        const tiempoExperienciaMap: Record<string, string> = {
            'menos_de_6_meses': 'Menos de 6 meses',
            'entre_6_meses_y_1_ano': 'Entre 6 meses y 1 año',
            'entre_1_a_4_anos': 'Entre 1 a 4 años',
            'entre_5_a_9_anos': 'Entre 5 a 9 años',
            'entre_10_a_14_anos': 'Entre 10 a 14 años',
            'entre_15_a_19_anos': 'Entre 15 a 19 años'
        };
        form.tiempo_experiencia_laboral = tiempoExperienciaMap[tiempoExperiencia] || tiempoExperiencia;
        
        form.clearErrors();
        successMessage.value = '';
    }
});

const submitForm = () => {
    if (form.processing) return;

    // Si el usuario seleccionó "Otro", usar el valor personalizado
    const finalOcupacion = form.ocupacion === '__custom__' ? customOcupacion.value : form.ocupacion;
    const finalDepartamento = form.departamento === '__custom__' ? customDepartamento.value : form.departamento;

    // Actualizar el formulario con los valores finales
    form.ocupacion = finalOcupacion;
    form.departamento = finalDepartamento;

    form.patch(route('paper-evaluations.update-demographic-data', props.evaluation.id), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Datos demográficos actualizados exitosamente';
            emit('updated');
            
            setTimeout(() => {
                closeModal();
            }, 1500);
        },
        onError: (errors: any) => {
            console.error('Error updating demographic data:', errors);
        },
    });
};

const closeModal = () => {
    if (!form.processing) {
        emit('close');
    }
};
</script>
