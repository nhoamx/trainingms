<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ t('SWOT') }}</h2>
        </div>
    </div>

    <div class="space-y-6">
        <table class="w-full table-fixed border-collapse">
            <thead>
                <tr>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Factor') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Strength') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Weakness') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Opportunity') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Threat') }}
                    </th>
                </tr>
            </thead>

            <tbody class="bg-white">
                <tr v-for="foda in FODAData" :key="foda.factor" class="text-justify">
                    <td class="border-b border-gray-200 p-4 text-gray-800  px-6 py-4">
                        {{ foda.factor }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.fortaleza }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.debilidad }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.oportunidad }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.amenaza }}
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</template>


<script setup lang="ts">
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

export interface FODAItem {
  factor: string;
  fortaleza: string;
  debilidad: string;
  oportunidad: string;
  amenaza: string;
}

const FODAData: FODAItem[] = [
  {
    factor: "Entorno Laboral Seguro",
    fortaleza: "Prácticas consistentes y liderazgo que gestiona riesgos psicosociales y operativos.",
    debilidad: "Prevención de la violencia laboral, sobrecarga u horas extra descontroladas.",
    oportunidad: "Alinear programas NOM-035 con auditorías internas y liderazgo preventivo.",
    amenaza: "Accidentes, sanciones, deterioro del clima y paros operativos."
  },
  {
    factor: "Seguridad Laboral",
    fortaleza: "Percepción de estabilidad y esfuerzos visibles contra la rotación.",
    debilidad: "Incertidumbre laboral que afecta compromiso y desempeño.",
    oportunidad: "Estrategias de retención y comunicación del rumbo del negocio.",
    amenaza: "Volatilidad económica y competencia por talento."
  },
  {
    factor: "Compensación Justa",
    fortaleza: "Paquetes competitivos y equitativos.",
    debilidad: "Inequidad o rezago salarial que alimente rotación.",
    oportunidad: "Revisión de tabuladores y beneficios flexibles.",
    amenaza: "Inflación y presión del mercado/sindicatos."
  },
  {
    factor: "Comunicación Abierta",
    fortaleza: "Comunicación regular y trato justo de líderes clave.",
    debilidad: "Rumores, favoritismo percibido, baja credibilidad jerárquica.",
    oportunidad: "Estandarizar canales, formación en liderazgo justo.",
    amenaza: "Crisis operativas que deterioren confianza."
  },
  {
    factor: "Participación de los Empleados",
    fortaleza: "Involucramiento real en decisiones y mejoras.",
    debilidad: "Desconexión del personal y resistencia al cambio.",
    oportunidad: "Sistemas de sugerencias y mejora continua.",
    amenaza: "Cultura cínica si no se implementan aportes."
  },
  {
    factor: "Reconocimiento y Recompensa",
    fortaleza: "Reconocimiento frecuente y recompensas por confiabilidad.",
    debilidad: "Esfuerzo no valorado y percepción de arbitrariedad.",
    oportunidad: "Programas de reconocimiento y rutas de incentivos.",
    amenaza: "Restricciones presupuestales y fatiga organizacional."
  },
  {
    factor: "Capacitación y Desarrollo",
    fortaleza: "Capacitación adecuada y recursos suficientes.",
    debilidad: "Falta de capacitación a los líderes.",
    oportunidad: "Academias internas e instructores certificados.",
    amenaza: "Cambios tecnológicos y rotación alta."
  },
  {
    factor: "Equilibrio Vida-Trabajo",
    fortaleza: "Turnos razonables y políticas respetuosas.",
    debilidad: "Fatiga, estrés y conflictos disciplinarios.",
    oportunidad: "Rediseño de turnos y balance de cargas.",
    amenaza: "Picos de demanda y presión por productividad."
  },
  {
    factor: "Avance Profesional",
    fortaleza: "Caminos claros e inversión en desarrollo.",
    debilidad: "Estancamiento y dependencia de contratación externa.",
    oportunidad: "Mapas de carrera y planes de sucesión.",
    amenaza: "Competidores con mejores rutas de crecimiento."
  },
  {
    factor: "Apoyo al Empleado",
    fortaleza: "Recursos accesibles de apoyo personal/laboral.",
    debilidad: "Estrés no atendido y aumento de ausentismo.",
    oportunidad: "Convenios de asistencia y campañas de bienestar.",
    amenaza: "Estigma y aumento general de riesgos psicosociales."
  }
];


</script>