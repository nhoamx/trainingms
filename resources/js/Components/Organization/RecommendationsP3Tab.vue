<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ t('Management of Unsatisfactory Levels') }}</h2>
        </div>

        <div class="max-w-7xl mx-auto rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-800 text-white sticky top-0">  
                        <tr>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider min-w-[200px]">
                                 {{ t('Factors').toUpperCase() }}</th> 
                               <th scope="col"
                                class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">    
                                  {{ t('Workers').toUpperCase() }}</th>   
                             <th colspan="3"
                                class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider bg-green-700">
                                 {{ t('Satisfactory Level').toUpperCase() }}  
                                  </th><th colspan="2"
                                class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider bg-red-600">
                                 {{ t('Unsatisfactory Level').toUpperCase() }} 
                                   </th><th colspan="4"
                                class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider min-w-[500px] bg-slate-800">
                                 {{ t('Recommended Actions').toUpperCase() }} 
                                   </th>   </tr>   <tr
                            class="bg-slate-500 text-white"><th scope="col"
                                class="px-3 py-2 text-left text-xs font-medium"></th>   
                             <th scope="col" class="px-3 py-2 text-center text-xs font-medium"></th>  
                           <th scope="col" class="px-3 py-2 text-center text-xs font-medium">
                                {{ t('Totally Agree') }}</th>
                           <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Agree') }}
                            </th><th scope="col"
                                class="px-3 py-2 text-center text-xs font-medium">{{ t('Total') }}</th>
                           <th scope="col" class="px-3 py-2 text-center text-xs font-medium">{{ t('Disagree') }}
                            </th><th scope="col"
                                class="px-3 py-2 text-center text-xs font-medium">{{ t('Totally Disagree') }} 
                                </th><th scope="col"
                                class="px-3 py-2 text-center text-xs font-medium">1</th>  
                              <th scope="col" class="px-3 py-2 text-center text-xs font-medium">2</th> 
                            <th scope="col" class="px-3 py-2 text-center text-xs font-medium">3
                            </th><th scope="col"
                                class="px-3 py-2 text-center text-xs font-medium">4</th>  
                        </tr>
                    </thead> <tbody class="bg-white divide-y divide-gray-200 text-gray-800">

                          <tr class="bg-yellow-50"><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Equilibrio vida 
                                laboral-personal </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">289 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                413 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 702 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">939 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1663 </td><td
                                class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">Redefinir reglas de horas
                                extra con  rotación equitativa y topes por
                                periodo. </td><td
                                class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">Ajustar patrones de turnos
                                para reducir  fatiga (descansos y rotaciones
                                planificadas) </td><td
                                class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">Establecer proceso claro y
                                humano para  ausentismo y tiempo libre </td>  
                              <td class="px-3 py-3 whitespace-normal text-xs min-w-[150px]">Monitorear bienestar con
                                encuestas pulso  y métricas de seguridad. </td> 
                             </tr>

                          <tr class="bg-yellow-50"><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Reconocimiento y recompensa
                            </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">438 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                546 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 984 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">1022 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1381 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Lanzar programa de reconocimiento
                                inmediato  por seguridad, calidad y asistencia.
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Definir criterios transparentes para  asignación
                                a roles críticos y recompensas. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Capacitar mandos en micro-reconocimiento
                                 y retroalimentación positiva especifica </td> 
                               <td class="px-3 py-3 whitespace-normal text-xs">Publicar
                                tablero mensual de logros por  equipo/turno </td>
                              </tr>

                          <tr class="bg-yellow-50"><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Avance profesional </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                2365 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">387 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">597 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 984 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">991 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1381 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Crear y comunicar rutas de carrera por 
                                familia de puestos </td> 
                               <td class="px-3 py-3 whitespace-normal text-xs">Definir criterios de promoción
                                basados  en habilidades y desempeño verificables
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Implementar programa de desarrollo de  lideres de
                                equipo. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Publicar vacantes primero internamente 
                                y medir movilidad interna. </td>    
                            </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Capacitación y desarrollo 
                                   </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">464 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                550 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1014 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">1009 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1351 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Levantar matriz de habilidades por   
                                   puesto y brechas por equipo. </td>
                             <td class="px-3 py-3 whitespace-normal text-xs">Implementar plan de
                                capacitación anual  por turno con horas minimas
                                garantizadas. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Certificar instructores internos y   
                                   asegurar materiales/recursos prácticos. </td>    
                            <td class="px-3 py-3 whitespace-normal text-xs">Evaluar eficacia
                                con pruebas de  competencia y métricas de
                                retrabajos/incidentes </td>  
                        </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Apoyo al empleado </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                2365 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">350 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">708 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1058 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">754 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1307 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Implementar o fortalecer un Programa de
                                 Asistencia al Empleado (PAE). </td> 
                            <td class="px-3 py-3 whitespace-normal text-xs">Difundir accesos
                                claros a consejería,  apoyo emocional y
                                orientación. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Capacitar lideres para detección    
                                  temprana de estrés y canalización. </td>   
                               <td class="px-3 py-3 whitespace-normal text-xs">Integrar campañas
                                periódicas de  bienestar y manejo de estrés </td>
                              </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Entorno laboral seguro.
                            </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">648 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                599 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1247 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">941 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1118 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Implementar rutina diaria de seguridad 
                                por turno (charla breve + checklist de riesgos).
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Activar protocolo antiacoso con canal 
                                confidencial, investigación y sanciones claras </td>    
                            <td class="px-3 py-3 whitespace-normal text-xs">Diagnóstico de cargas y horas extra por
                                 linea/área y plan de reducción escalonado. </td>
                           <td class="px-3 py-3 whitespace-normal text-xs">
                                Establecer comité de riesgos  psicosociales con
                                metas trimestrales y seguimiento visible </td>  
                        </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Participación de los    
                                  empleados </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">485 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                771 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1256 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">730 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">
                                1109 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Activar círculos de mejora por
                                célula/linea  con reuniones quincenales. </td> 
                               <td class="px-3 py-3 whitespace-normal text-xs">
                                Implementar sistema de ideas con 
                                retroalimentación obligatoria </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Incluir representantes operativos en  
                               actualización de estándares y procesos </td>    
                            <td class="px-3 py-3 whitespace-normal text-xs">Reconocer
                                públicamente mejoras  implementadas por equipos
                            </td>  
                        </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Compensación justa </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                2365 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">450 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1022 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1472 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">622 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">893
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Ejecutar benchmark salarial contra  industria y
                                región. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Corregir brechas con ajustes por bandas
                                 y equidad interna. </td>
                           <td class="px-3 py-3 whitespace-normal text-xs">Transparentar estructura de  
                               compensación y beneficios mediante comunicación
                                clara. </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Vincular incentivos a seguridad, calidad
                                 y productividad con reglas simples </td>    
                            </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Seguridad laboral </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                2365 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">818 </td>    
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">725 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 1543 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">695 </td>    
                              <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">822
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Comunicar un plan de estabilidad  (proyecciones
                                de demanda, criterios de contratación/ajustes). </td>   
                             <td class="px-3 py-3 whitespace-normal text-xs">Diseñar estrategia de retención para 
                                roles criticos (bonos de permanencia/movilidad
                                interna). </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Capacitar mandos en liderazgo de    
                                  confianza y comunicación de certidumbre operativa </td>
                           <td class="px-3 py-3 whitespace-normal text-xs">Aplicar
                                entrevistas de salida y  "stay interviews" con
                                acciones de causa raiz. </td>   </tr>

                          <tr><td
                                class="px-3 py-3 whitespace-normal text-sm font-medium">Comunicación abierta </td> 
                               <td class="px-3 py-3 whitespace-nowrap text-sm text-center">2365 </td>   
                            
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">1833 </td>  
                              <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
                                424 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-green-100 font-semibold">
                                 2257 </td><td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center">97 </td>
                             <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-center bg-red-100 font-semibold">108
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Implementar brief semanal por area/turno  con
                                metas, cambios y prioridades </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Establecer calendario mensual de    
                                  comunicación de planta (resultados, calidad, seguridad
                            </td><td class="px-3 py-3 whitespace-normal text-xs">
                                Capacitar lideres en trato justo y  comunicación
                                respetuosa con evaluación 180/360 </td><td
                                class="px-3 py-3 whitespace-normal text-xs">Crear canal de preguntas y respuestas 
                                con tiempos de respuesta definidas </td>
                           </tr> </tbody>
                </table> 
                 
            </div> 
        </div>    
    </div>




</template>

<script setup lang="ts">
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
</script>
