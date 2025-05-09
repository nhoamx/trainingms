<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DimensionItemSummaryService
{
    /**
     * Mapeo de dominios a sus rangos de riesgo
     */
    private $domainRiskLevels = [
        'Condiciones en el ambiente de trabajo' => [
            ['max' => 5, 'level' => 'Nulo'],
            ['min' => 5, 'max' => 9, 'level' => 'Bajo'],
            ['min' => 9, 'max' => 11, 'level' => 'Medio'],
            ['min' => 11, 'max' => 14, 'level' => 'Alto'],
            ['min' => 14, 'level' => 'Muy Alto']
        ],
        'Carga de trabajo' => [
            ['max' => 15, 'level' => 'Nulo'],
            ['min' => 15, 'max' => 21, 'level' => 'Bajo'],
            ['min' => 21, 'max' => 27, 'level' => 'Medio'],
            ['min' => 27, 'max' => 37, 'level' => 'Alto'],
            ['min' => 37, 'level' => 'Muy Alto']
        ],
        'Falta de control sobre el trabajo' => [
            ['max' => 11, 'level' => 'Nulo'],
            ['min' => 11, 'max' => 16, 'level' => 'Bajo'],
            ['min' => 16, 'max' => 21, 'level' => 'Medio'],
            ['min' => 21, 'max' => 25, 'level' => 'Alto'],
            ['min' => 25, 'level' => 'Muy Alto']
        ],
        'Jornada de trabajo' => [
            ['max' => 1, 'level' => 'Nulo'],
            ['min' => 1, 'max' => 2, 'level' => 'Bajo'],
            ['min' => 2, 'max' => 4, 'level' => 'Medio'],
            ['min' => 4, 'max' => 6, 'level' => 'Alto'],
            ['min' => 6, 'level' => 'Muy Alto']
        ],
        'Interferencia en la relación trabajo-familia' => [
            ['max' => 4, 'level' => 'Nulo'],
            ['min' => 4, 'max' => 6, 'level' => 'Bajo'],
            ['min' => 6, 'max' => 8, 'level' => 'Medio'],
            ['min' => 8, 'max' => 10, 'level' => 'Alto'],
            ['min' => 10, 'level' => 'Muy Alto']
        ],
        'Liderazgo' => [
            ['max' => 9, 'level' => 'Nulo'],
            ['min' => 9, 'max' => 12, 'level' => 'Bajo'],
            ['min' => 12, 'max' => 16, 'level' => 'Medio'],
            ['min' => 16, 'max' => 20, 'level' => 'Alto'],
            ['min' => 20, 'level' => 'Muy Alto']
        ],
        'Relaciones en el trabajo' => [
            ['max' => 10, 'level' => 'Nulo'],
            ['min' => 10, 'max' => 13, 'level' => 'Bajo'],
            ['min' => 13, 'max' => 17, 'level' => 'Medio'],
            ['min' => 17, 'max' => 21, 'level' => 'Alto'],
            ['min' => 21, 'level' => 'Muy Alto']
        ],
        'Violencia' => [
            ['max' => 7, 'level' => 'Nulo'],
            ['min' => 7, 'max' => 10, 'level' => 'Bajo'],
            ['min' => 10, 'max' => 13, 'level' => 'Medio'],
            ['min' => 13, 'max' => 16, 'level' => 'Alto'],
            ['min' => 16, 'level' => 'Muy Alto']
        ],
        'Reconocimiento del desempeño' => [
            ['max' => 6, 'level' => 'Nulo'],
            ['min' => 6, 'max' => 10, 'level' => 'Bajo'],
            ['min' => 10, 'max' => 14, 'level' => 'Medio'],
            ['min' => 14, 'max' => 18, 'level' => 'Alto'],
            ['min' => 18, 'level' => 'Muy Alto']
        ],
        'Insuficiente sentido de pertenencia e inestabilidad' => [
            ['max' => 4, 'level' => 'Nulo'],
            ['min' => 4, 'max' => 6, 'level' => 'Bajo'],
            ['min' => 6, 'max' => 8, 'level' => 'Medio'],
            ['min' => 8, 'max' => 10, 'level' => 'Alto'],
            ['min' => 10, 'level' => 'Muy Alto']
        ]
    ];

    /**
     * Mapeo de categorías a sus rangos de riesgo
     */
    private $categoryRiskLevels = [
        'Ambiente de trabajo' => [
            ['max' => 5, 'level' => 'Nulo'],
            ['min' => 5, 'max' => 9, 'level' => 'Bajo'],
            ['min' => 9, 'max' => 11, 'level' => 'Medio'],
            ['min' => 11, 'max' => 14, 'level' => 'Alto'],
            ['min' => 14, 'level' => 'Muy Alto']
        ],
        'Factores propios de la actividad' => [
            ['max' => 15, 'level' => 'Nulo'],
            ['min' => 15, 'max' => 30, 'level' => 'Bajo'],
            ['min' => 30, 'max' => 45, 'level' => 'Medio'],
            ['min' => 45, 'max' => 60, 'level' => 'Alto'],
            ['min' => 60, 'level' => 'Muy Alto']
        ],
        'Organización del tiempo de trabajo' => [
            ['max' => 5, 'level' => 'Nulo'],
            ['min' => 5, 'max' => 7, 'level' => 'Bajo'],
            ['min' => 7, 'max' => 10, 'level' => 'Medio'],
            ['min' => 10, 'max' => 13, 'level' => 'Alto'],
            ['min' => 13, 'level' => 'Muy Alto']
        ],
        'Liderazgo y relaciones en el trabajo' => [
            ['max' => 14, 'level' => 'Nulo'],
            ['min' => 14, 'max' => 29, 'level' => 'Bajo'],
            ['min' => 29, 'max' => 42, 'level' => 'Medio'],
            ['min' => 42, 'max' => 58, 'level' => 'Alto'],
            ['min' => 58, 'level' => 'Muy Alto']
        ],
        'Entorno organizacional' => [
            ['max' => 10, 'level' => 'Nulo'],
            ['min' => 10, 'max' => 14, 'level' => 'Bajo'],
            ['min' => 14, 'max' => 18, 'level' => 'Medio'],
            ['min' => 18, 'max' => 23, 'level' => 'Alto'],
            ['min' => 23, 'level' => 'Muy Alto']
        ]
    ];

    /**
     * Obtiene todos los datos del reporte para una organización, incluyendo datos crudos y agrupados.
     *
     * @param int $organizationId
     * @return array
     */
    public function getReportSummaryByOrganization($organizationId)
    {
        // Obtiene los datos crudos
        $rawData = $this->getRawDimensionItemSummary($organizationId);
        
        // Obtiene los datos agrupados por dimensión y nivel de riesgo
        $groupedByDimension = $this->processDimensionesAgrupadasPorRiesgo($rawData);
        
        // Obtiene los datos agrupados por dominio y nivel de riesgo
        $groupedByDomain = $this->processDominiosAgrupadosPorRiesgo($rawData);

        // Obtiene los datos agrupados por categoría y nivel de riesgo
        $groupedByCategory = $this->processCategoriasAgrupadasPorRiesgo($rawData);

        // Calcula los niveles de riesgo finales
        $finalRiskLevels = $this->processFinalRiskLevel($rawData);
        
        // Retorna todos los conjuntos de datos en un array
        return [
            'raw_data' => $rawData,
            'grouped_by_dimension' => $groupedByDimension,
            'grouped_by_domain' => $groupedByDomain,
            'grouped_by_category' => $groupedByCategory,
            'final_risk_levels' => $finalRiskLevels,
            'demographic_data' => []
        ];
    }
    
    /**
     * Obtiene el resumen de dimensiones, items y puntaje por persona para una organización.
     * (Método privado ya que ahora solo se usa internamente)
     *
     * @param int $organizationId
     * @return array
     */
    private function getRawDimensionItemSummary($organizationId)
    {
        $results = DB::select('
            SELECT
              e.personal_id,
              cat.name AS categoria,
              dom.name AS dominio,
              dim.name AS dimension,
              GROUP_CONCAT(q.question ORDER BY q.question SEPARATOR ", ") AS items,
              SUM(q.value) AS puntaje_dimension
            FROM questions q
            JOIN evaluations e ON q.evaluation_id = e.id
            JOIN categories cat ON q.category_id = cat.id
            JOIN domains dom ON q.domain_id = dom.id
            JOIN dimensions dim ON q.dimension_id = dim.id
            WHERE q.reference_guide = ?
              AND q.value IS NOT NULL
              AND e.organization_id = ?
            GROUP BY e.personal_id, dim.id, dom.id, cat.id
            ORDER BY e.personal_id, cat.name, dom.name, dim.name
        ', ['III', $organizationId]);

        return $results;
    }

    /**
     * Determina el nivel de riesgo basado en el puntaje y el dominio
     * 
     * @param string $dominio Nombre del dominio
     * @param int $puntaje Puntaje obtenido
     * @return string Nivel de riesgo (Nulo, Bajo, Medio, Alto, Muy Alto)
     */
    private function determinarNivelRiesgo($dominio, $puntaje)
    {
        // Si el dominio no está definido en la matriz de niveles, retornamos 'No Determinado'
        if (!isset($this->domainRiskLevels[$dominio])) {
            return 'No Determinado';
        }

        $nivelRiesgo = 'No Determinado';
        foreach ($this->domainRiskLevels[$dominio] as $rango) {
            // Si solo hay max, es porque el puntaje es menor a ese valor
            if (isset($rango['max']) && !isset($rango['min']) && $puntaje < $rango['max']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
            // Si solo hay min, es porque el puntaje es mayor o igual a ese valor
            else if (isset($rango['min']) && !isset($rango['max']) && $puntaje >= $rango['min']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
            // Si hay min y max, el puntaje debe estar en ese rango: min <= puntaje < max
            else if (isset($rango['min']) && isset($rango['max']) && $puntaje >= $rango['min'] && $puntaje < $rango['max']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
        }

        return $nivelRiesgo;
    }

    /**
     * Determina el nivel de riesgo basado en el puntaje y la categoría
     * 
     * @param string $categoria Nombre de la categoría
     * @param int $puntaje Puntaje obtenido
     * @return string Nivel de riesgo (Nulo, Bajo, Medio, Alto, Muy Alto)
     */
    private function determinarNivelRiesgoCategoria($categoria, $puntaje)
    {
        // Si la categoría no está definida en la matriz de niveles, retornamos 'No Determinado'
        if (!isset($this->categoryRiskLevels[$categoria])) {
            return 'No Determinado';
        }

        $nivelRiesgo = 'No Determinado';
        foreach ($this->categoryRiskLevels[$categoria] as $rango) {
            // Si solo hay max, es porque el puntaje es menor a ese valor
            if (isset($rango['max']) && !isset($rango['min']) && $puntaje < $rango['max']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
            // Si solo hay min, es porque el puntaje es mayor o igual a ese valor
            else if (isset($rango['min']) && !isset($rango['max']) && $puntaje >= $rango['min']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
            // Si hay min y max, el puntaje debe estar en ese rango: min <= puntaje < max
            else if (isset($rango['min']) && isset($rango['max']) && $puntaje >= $rango['min'] && $puntaje < $rango['max']) {
                $nivelRiesgo = $rango['level'];
                break;
            }
        }

        return $nivelRiesgo;
    }

    /**
     * Procesa los datos crudos y devuelve dimensiones agrupadas por nivel de riesgo con conteo de personas
     * (Método privado ya que ahora solo se usa internamente)
     * 
     * @param array $resultados Datos crudos de dimensiones
     * @return array Dimensiones agrupadas por nivel de riesgo con conteo
     */
    private function processDimensionesAgrupadasPorRiesgo($resultados)
    {
        // Estructura para agrupar por dimensión y nivel de riesgo
        $agrupados = [];
        
        // Mapeo de personal por dimensión y nivel de riesgo
        $personalPorDimensionYRiesgo = [];
        
        foreach ($resultados as $resultado) {
            // Determinar nivel de riesgo para esta dimensión basado en el criterio del dominio
            $nivelRiesgo = $this->determinarNivelRiesgo($resultado->dominio, $resultado->puntaje_dimension);
            
            // Inicializar el array para esta dimensión si aún no existe
            if (!isset($agrupados[$resultado->dimension])) {
                $agrupados[$resultado->dimension] = [];
            }
            
            // Inicializar el contador para este nivel de riesgo si aún no existe
            if (!isset($agrupados[$resultado->dimension][$nivelRiesgo])) {
                $agrupados[$resultado->dimension][$nivelRiesgo] = 0;
            }
            
            // Incrementar el contador de personas para esta dimensión y nivel de riesgo
            $agrupados[$resultado->dimension][$nivelRiesgo]++;
            
            // Guardar el personal_id para esta dimensión y nivel de riesgo
            if (!isset($personalPorDimensionYRiesgo[$resultado->dimension])) {
                $personalPorDimensionYRiesgo[$resultado->dimension] = [];
            }
            if (!isset($personalPorDimensionYRiesgo[$resultado->dimension][$nivelRiesgo])) {
                $personalPorDimensionYRiesgo[$resultado->dimension][$nivelRiesgo] = [];
            }
            $personalPorDimensionYRiesgo[$resultado->dimension][$nivelRiesgo][] = $resultado->personal_id;
        }
        
        // Formatear los resultados en el formato solicitado
        $resultado = [];
        foreach ($agrupados as $dimension => $nivelesRiesgo) {
            foreach ($nivelesRiesgo as $nivelRiesgo => $conteo) {
                $resultado[] = [
                    'dimension' => $dimension,
                    'nivel_riesgo' => $nivelRiesgo,
                    'conteo' => $conteo,
                    'personal' => $personalPorDimensionYRiesgo[$dimension][$nivelRiesgo]
                ];
            }
        }
        
        return $resultado;
    }
    
    /**
     * Procesa los datos crudos y devuelve dominios agrupados por nivel de riesgo con conteo de personas.
     * Suma los valores de las dimensiones que pertenecen a cada dominio para obtener el puntaje del dominio.
     * 
     * @param array $resultados Datos crudos de dimensiones
     * @return array Dominios agrupados por nivel de riesgo con conteo
     */
    private function processDominiosAgrupadosPorRiesgo($resultados)
    {
        // Estructura para agrupar las sumas de puntajes por dominio, personal_id y dominio
        $sumasPorDominioYPersona = [];
        
        // Mapeo de qué dimensiones corresponden a cada dominio por personal_id
        foreach ($resultados as $resultado) {
            $personalId = $resultado->personal_id;
            $dominio = $resultado->dominio;
            $puntajeDimension = $resultado->puntaje_dimension;
            
            // Inicializar la suma para este dominio y persona si no existe
            if (!isset($sumasPorDominioYPersona[$personalId][$dominio])) {
                $sumasPorDominioYPersona[$personalId][$dominio] = 0;
            }
            
            // Sumar el puntaje de la dimensión al dominio correspondiente
            $sumasPorDominioYPersona[$personalId][$dominio] += $puntajeDimension;
        }
        
        // Estructura para agrupar por dominio y nivel de riesgo
        $agrupados = [];
        
        // Mapeo de personal_id por dominio y nivel de riesgo
        $personalPorDominioYRiesgo = [];
        
        // Determinar el nivel de riesgo para cada dominio
        foreach ($sumasPorDominioYPersona as $personalId => $dominios) {
            foreach ($dominios as $dominio => $puntajeDominio) {
                // Determinar nivel de riesgo para este dominio basado en su puntaje
                $nivelRiesgo = $this->determinarNivelRiesgo($dominio, $puntajeDominio);
                
                // Inicializar el array para este dominio si aún no existe
                if (!isset($agrupados[$dominio])) {
                    $agrupados[$dominio] = [];
                }
                
                // Inicializar el contador para este nivel de riesgo si aún no existe
                if (!isset($agrupados[$dominio][$nivelRiesgo])) {
                    $agrupados[$dominio][$nivelRiesgo] = 0;
                }
                
                // Incrementar el contador de personas para este dominio y nivel de riesgo
                $agrupados[$dominio][$nivelRiesgo]++;
                
                // Guardar el personal_id para este dominio y nivel de riesgo
                if (!isset($personalPorDominioYRiesgo[$dominio])) {
                    $personalPorDominioYRiesgo[$dominio] = [];
                }
                if (!isset($personalPorDominioYRiesgo[$dominio][$nivelRiesgo])) {
                    $personalPorDominioYRiesgo[$dominio][$nivelRiesgo] = [];
                }
                $personalPorDominioYRiesgo[$dominio][$nivelRiesgo][] = $personalId;
            }
        }
        
        // Formatear los resultados en el formato solicitado
        $resultado = [];
        foreach ($agrupados as $dominio => $nivelesRiesgo) {
            foreach ($nivelesRiesgo as $nivelRiesgo => $conteo) {
                $resultado[] = [
                    'dominio' => $dominio,
                    'nivel_riesgo' => $nivelRiesgo,
                    'conteo' => $conteo,
                    'personal' => $personalPorDominioYRiesgo[$dominio][$nivelRiesgo]
                ];
            }
        }
        
        return $resultado;
    }

    /**
     * Procesa los datos crudos y devuelve categorías agrupadas por nivel de riesgo con conteo de personas.
     * Suma los valores de las dimensiones que pertenecen a cada categoría para obtener el puntaje de la categoría.
     * 
     * @param array $resultados Datos crudos de dimensiones
     * @return array Categorías agrupadas por nivel de riesgo con conteo
     */
    private function processCategoriasAgrupadasPorRiesgo($resultados)
    {
        // Estructura para agrupar las sumas de puntajes por categoría y personal_id
        $sumasPorCategoriaYPersona = [];
        
        // Mapeo de qué dimensiones corresponden a cada categoría por personal_id
        foreach ($resultados as $resultado) {
            $personalId = $resultado->personal_id;
            $categoria = $resultado->categoria;
            $puntajeDimension = $resultado->puntaje_dimension;
            
            // Inicializar la suma para esta categoría y persona si no existe
            if (!isset($sumasPorCategoriaYPersona[$personalId][$categoria])) {
                $sumasPorCategoriaYPersona[$personalId][$categoria] = 0;
            }
            
            // Sumar el puntaje de la dimensión a la categoría correspondiente
            $sumasPorCategoriaYPersona[$personalId][$categoria] += $puntajeDimension;
        }
        
        // Estructura para agrupar por categoría y nivel de riesgo
        $agrupados = [];
        
        // Mapeo de personal_id por categoría y nivel de riesgo
        $personalPorCategoriaYRiesgo = [];
        
        // Determinar el nivel de riesgo para cada categoría
        foreach ($sumasPorCategoriaYPersona as $personalId => $categorias) {
            foreach ($categorias as $categoria => $puntajeCategoria) {
                // Determinar nivel de riesgo para esta categoría basado en su puntaje
                $nivelRiesgo = $this->determinarNivelRiesgoCategoria($categoria, $puntajeCategoria);
                
                // Inicializar el array para esta categoría si aún no existe
                if (!isset($agrupados[$categoria])) {
                    $agrupados[$categoria] = [];
                }
                
                // Inicializar el contador para este nivel de riesgo si aún no existe
                if (!isset($agrupados[$categoria][$nivelRiesgo])) {
                    $agrupados[$categoria][$nivelRiesgo] = 0;
                }
                
                // Incrementar el contador de personas para esta categoría y nivel de riesgo
                $agrupados[$categoria][$nivelRiesgo]++;
                
                // Guardar el personal_id para esta categoría y nivel de riesgo
                if (!isset($personalPorCategoriaYRiesgo[$categoria])) {
                    $personalPorCategoriaYRiesgo[$categoria] = [];
                }
                if (!isset($personalPorCategoriaYRiesgo[$categoria][$nivelRiesgo])) {
                    $personalPorCategoriaYRiesgo[$categoria][$nivelRiesgo] = [];
                }
                $personalPorCategoriaYRiesgo[$categoria][$nivelRiesgo][] = $personalId;
            }
        }
        
        // Formatear los resultados en el formato solicitado
        $resultado = [];
        foreach ($agrupados as $categoria => $nivelesRiesgo) {
            foreach ($nivelesRiesgo as $nivelRiesgo => $conteo) {
                $resultado[] = [
                    'categoria' => $categoria,
                    'nivel_riesgo' => $nivelRiesgo,
                    'conteo' => $conteo,
                    'personal' => $personalPorCategoriaYRiesgo[$categoria][$nivelRiesgo]
                ];
            }
        }
        
        return $resultado;
    }

    /**
     * Mapeo de niveles de riesgo final basado en el puntaje total
     */
    private $finalRiskLevels = [
        ['max' => 50, 'level' => 'Nulo'],
        ['min' => 50, 'max' => 75, 'level' => 'Bajo'],
        ['min' => 75, 'max' => 99, 'level' => 'Medio'],
        ['min' => 99, 'max' => 140, 'level' => 'Alto'],
        ['min' => 140, 'level' => 'Muy Alto']
    ];

    /**
     * Procesa los datos crudos y calcula el nivel de riesgo final para cada persona.
     * 
     * @param array $resultados Datos crudos de dimensiones
     * @return array Calificación final agrupada por nivel de riesgo con conteo
     */
    private function processFinalRiskLevel($resultados)
    {
        // Estructura para sumar puntajes por persona
        $sumasPorPersona = [];
        $personalPorNivelRiesgo = [
            'Nulo' => [],
            'Bajo' => [],
            'Medio' => [],
            'Alto' => [],
            'Muy Alto' => []
        ];
        $conteosPorNivel = [
            'Nulo' => 0,
            'Bajo' => 0,
            'Medio' => 0,
            'Alto' => 0,
            'Muy Alto' => 0
        ];
        
        // Sumar todos los puntajes por persona
        foreach ($resultados as $resultado) {
            $personalId = $resultado->personal_id;
            if (!isset($sumasPorPersona[$personalId])) {
                $sumasPorPersona[$personalId] = 0;
            }
            $sumasPorPersona[$personalId] += $resultado->puntaje_dimension;
        }
        
        // Determinar nivel de riesgo final para cada persona
        foreach ($sumasPorPersona as $personalId => $puntajeTotal) {
            $nivelRiesgo = $this->determinarNivelRiesgoFinal($puntajeTotal);
            $personalPorNivelRiesgo[$nivelRiesgo][] = $personalId;
            $conteosPorNivel[$nivelRiesgo]++;
        }
        
        // Formatear los resultados
        $resultado = [];
        foreach ($conteosPorNivel as $nivel => $conteo) {
            if ($conteo > 0) {
                $resultado[] = [
                    'nivel_riesgo' => $nivel,
                    'conteo' => $conteo,
                    'personal' => $personalPorNivelRiesgo[$nivel]
                ];
            }
        }
        
        return $resultado;
    }

    /**
     * Determina el nivel de riesgo final basado en el puntaje total
     * 
     * @param int $puntaje Puntaje total obtenido
     * @return string Nivel de riesgo (Nulo, Bajo, Medio, Alto, Muy Alto)
     */
    private function determinarNivelRiesgoFinal($puntaje)
    {
        foreach ($this->finalRiskLevels as $rango) {
            if (isset($rango['max']) && !isset($rango['min']) && $puntaje < $rango['max']) {
                return $rango['level'];
            }
            if (isset($rango['min']) && !isset($rango['max']) && $puntaje >= $rango['min']) {
                return $rango['level'];
            }
            if (isset($rango['min']) && isset($rango['max']) && $puntaje >= $rango['min'] && $puntaje < $rango['max']) {
                return $rango['level'];
            }
        }
        return 'No Determinado';
    }
}
