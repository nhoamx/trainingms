<?php

namespace App\Services;

class DemographicDataNormalizationService
{
    /**
     * Extract demographic information from raw data
     * Handles new nested structure (datos_laborales), old OCR structure, and Likert data
     *
     * @param  array  $demographicData  Raw demographic data from OCR or form submission
     * @return array Normalized demographic data with standardized keys and values
     */
    public function extractDemographicInfo(array $demographicData): array
    {
        // Check if this is Likert data (has 'questions' key indicating it's from likert_answers)
        // Use array_key_exists instead of isset because isset returns false for null values
        if (array_key_exists('questions', $demographicData)) {
            return $this->extractFromLikert($demographicData);
        }

        // Determine which structure we're dealing with for Referencia V
        if ($this->isNewStructure($demographicData)) {
            return $this->extractFromNewStructure($demographicData);
        } else {
            return $this->extractFromOldStructure($demographicData);
        }
    }

    /**
     * Extract from Likert scale data (workplace climate evaluation)
     *
     * @param  array  $likertData  Likert scale evaluation data
     * @return array Normalized demographic data from Likert structure
     */
    public function extractFromLikert(array $likertData): array
    {
        return [
            'gender' => $this->normalizeValue($likertData['genero'] ?? null, [
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
            ]),
            'age' => null, // Not provided in Likert data
            'marital_status' => null, // Not provided in Likert data
            'education_level' => null, // Not provided in Likert data
            'position' => $this->normalizePositionType($likertData['puestos'] ?? null),
            'department' => $this->normalizeDepartmentType($likertData['areas'] ?? null),
            'position_type' => null, // Not provided in Likert data
            'contract_type' => $this->normalizeContractType($likertData['tipo_contrato'] ?? null),
            'personnel_type' => null, // Not provided in Likert data
            'work_schedule' => $this->normalizeWorkSchedule($likertData['turno'] ?? null),
            'shift_rotation' => null, // Not provided in Likert data
            'time_in_current_position' => null, // Not provided in Likert data
            'work_experience' => null, // Not provided in Likert data
            'extra_fields' => [
                'questions' => $likertData['questions'] ?? null,
            ],
        ];
    }

    /**
     * Check if using new nested structure (datos_laborales)
     *
     * @param  array  $data  Raw demographic data
     * @return bool True if data uses new nested structure
     */
    public function isNewStructure(array $data): bool
    {
        return isset($data['datos_laborales']) && is_array($data['datos_laborales']);
    }

    /**
     * Extract from new nested structure (datos_laborales)
     *
     * @param  array  $demographicData  Demographic data with datos_laborales structure
     * @return array Normalized demographic data
     */
    public function extractFromNewStructure(array $demographicData): array
    {
        $laboralData = $demographicData['datos_laborales'] ?? [];
        $experiencia = $laboralData['experiencia'] ?? [];

        return [
            'gender' => $demographicData['sexo'] ?? null,
            'age' => $demographicData['edad'] ?? null,
            'marital_status' => $demographicData['estado_civil'] ?? null,
            'education_level' => $demographicData['nivel_estudios'] ?? null,
            'position' => $laboralData['ocupacion_puesto'] ?? null,
            'department' => $laboralData['departamento_seccion_area'] ?? null,
            'position_type' => $laboralData['tipo_puesto'] ?? null,
            'contract_type' => $laboralData['tipo_contratacion'] ?? null,
            'personnel_type' => $laboralData['tipo_personal'] ?? null,
            'work_schedule' => $laboralData['tipo_jornada'] ?? null,
            'shift_rotation' => $laboralData['rotacion_turnos'] ?? null,
            'time_in_current_position' => $experiencia['tiempo_puesto_actual'] ?? null,
            'work_experience' => $experiencia['tiempo_experiencia_laboral'] ?? null,
        ];
    }

    /**
     * Extract from old OCR structure
     *
     * @param  array  $demographicData  Legacy OCR demographic data
     * @return array Normalized demographic data
     */
    public function extractFromOldStructure(array $demographicData): array
    {
        // Build age from decenas/unidades if available
        $age = null;
        if (isset($demographicData['edad']) && is_array($demographicData['edad'])) {
            $decenas = $demographicData['edad']['decenas'] ?? 0;
            $unidades = $demographicData['edad']['unidades'] ?? 0;
            $ageValue = ($decenas * 10) + $unidades;
            // Convert numeric age to range format
            $age = $this->convertAgeToRange($ageValue);
        } elseif (is_string($demographicData['edad'] ?? null)) {
            $age = $demographicData['edad'];
        }

        // Extract from fila1 if value is array
        $position = $this->extractFromObject($demographicData['ocupacion_puesto'] ?? null);
        $department = $this->extractFromObject($demographicData['departamento_seccion_area'] ?? null);

        // Normalize field values (convert underscores to proper format)
        $sexo = $demographicData['sexo'] ?? null;
        $sexo = $this->normalizeValue($sexo, ['masculino' => 'Masculino', 'femenino' => 'Femenino']);

        $estadoCivil = $demographicData['estado_civil'] ?? null;
        $estadoCivil = $this->normalizeValue($estadoCivil, [
            'soltero' => 'Soltero',
            'casado' => 'Casado',
            'union_libre' => 'Unión libre',
            'divorciado' => 'Divorciado',
            'viudo' => 'Viudo',
        ]);

        $nivelEstudios = $this->extractEducationLevel($demographicData['nivel_estudios'] ?? null);

        return [
            'gender' => $sexo,
            'age' => $age,
            'marital_status' => $estadoCivil,
            'education_level' => $nivelEstudios,
            'position' => $position,
            'department' => $department,
            'position_type' => $this->normalizePosicionType($demographicData['tipo_puesto'] ?? null),
            'contract_type' => $this->normalizeContractType($demographicData['tipo_contratacion'] ?? null),
            'personnel_type' => $this->normalizePersonnelType($demographicData['tipo_personal'] ?? null),
            'work_schedule' => $this->normalizeWorkSchedule($demographicData['tipo_jornada'] ?? null),
            'shift_rotation' => $this->normalizeYesNo($demographicData['rotacion_turnos'] ?? null),
            'time_in_current_position' => $this->normalizeExperience($demographicData['tiempo_puesto_actual'] ?? null),
            'work_experience' => $this->normalizeExperience($demographicData['tiempo_experiencia_laboral'] ?? null),
        ];
    }

    /**
     * Extract value from object (fila1 or direct value)
     *
     * @param  mixed  $value  Value that may be an array with 'fila1' key or direct string
     * @return string|null Extracted string value
     */
    public function extractFromObject($value): ?string
    {
        if (is_array($value) && isset($value['fila1'])) {
            return $value['fila1'] ?: null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Convert numeric age to age range format
     *
     * @param  int  $age  Numeric age value
     * @return string Age range string (e.g., "25 - 29")
     */
    public function convertAgeToRange(int $age): string
    {
        if ($age < 15) {
            return '15 - 19';
        }
        if ($age <= 19) {
            return '15 - 19';
        }
        if ($age <= 24) {
            return '20 - 24';
        }
        if ($age <= 29) {
            return '25 - 29';
        }
        if ($age <= 34) {
            return '30 - 34';
        }
        if ($age <= 39) {
            return '35 - 39';
        }
        if ($age <= 44) {
            return '40 - 44';
        }
        if ($age <= 49) {
            return '45 - 49';
        }
        if ($age <= 54) {
            return '50 - 54';
        }
        if ($age <= 59) {
            return '55 - 59';
        }
        if ($age <= 64) {
            return '60 - 64';
        }
        if ($age <= 69) {
            return '65 - 69';
        }

        return '70 o más';
    }

    /**
     * Normalize position type
     *
     * @param  string|null  $value  Raw position type value
     * @return string|null Normalized position type
     */
    public function normalizePosicionType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'operativo' => 'Operativo',
            'profesional_o_tecnico' => 'Profesional o técnico',
            'supervisor' => 'Supervisor',
            'gerente' => 'Gerente',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize contract type
     *
     * @param  string|null  $value  Raw contract type value
     * @return string|null Normalized contract type
     */
    public function normalizeContractType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $lowerValue = strtolower($value);

        // Map for standard Likert (05) - Spanish descriptions
        $mapLikert = [
            'por_obra_o_proyecto' => 'Por obra o proyecto',
            'por_tiempo_determinado_temporal' => 'Por tiempo determinado (temporal)',
            'tiempo_indeterminado' => 'Tiempo indeterminado',
            'tiempo_determinado' => 'Tiempo determinado',
            'honorarios' => 'Honorarios',
            'confianza' => 'Confianza',
            'sindicalizado' => 'Sindicalizado',
        ];

        // Map for Likert Planta 3 (06) - opcion_N format
        $mapPlanta3 = [
            'opcion_1' => 'Opción 1',
            'opcion_2' => 'Opción 2',
        ];

        // Try Planta 3 format first (opcion_N)
        if (isset($mapPlanta3[$lowerValue])) {
            return $mapPlanta3[$lowerValue];
        }

        // Then try standard Likert format
        if (isset($mapLikert[$lowerValue])) {
            return $mapLikert[$lowerValue];
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize position
     *
     * @param  string|null  $value  Raw position value (text or numeric)
     * @return string|null Normalized position
     */
    public function normalizePositionType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Get Likert standard position map
        $mapLikert = config('likert-value.puestos', []);

        // Try direct lookup first (for text values)
        if (isset($mapLikert[strtolower($value)])) {
            return $mapLikert[strtolower($value)];
        }

        // For numeric indices (1-19 from Planta 3), map to config values
        // Get Planta 3 position config
        $mapPlanta3 = config('likert-value.puestos_planta_3', []);

        if (is_numeric($value) && isset($mapPlanta3[$value])) {
            return $mapPlanta3[$value];
        }

        // Also check old likert_puestos config by numeric index
        if (is_numeric($value)) {
            $puestoNumber = (int) $value;
            $puestos = config('likert-value.puestos', []);

            // Try to find by position in array
            $puestosArray = array_values($puestos);
            if (isset($puestosArray[$puestoNumber - 1])) {
                return $puestosArray[$puestoNumber - 1];
            }
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize department
     *
     * @param  string|null  $value  Raw department value (text or numeric)
     * @return string|null Normalized department
     */
    public function normalizeDepartmentType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Get Likert standard areas map
        $mapLikert = config('likert-value.areas', []);

        // Try direct lookup first (for text values)
        if (isset($mapLikert[strtolower($value)])) {
            return $mapLikert[strtolower($value)];
        }

        // For numeric indices (1-10 from Planta 3), map to config values
        // Get Planta 3 areas config
        $mapPlanta3 = config('likert-value.areas_planta_3', []);

        if (is_numeric($value) && isset($mapPlanta3[$value])) {
            return $mapPlanta3[$value];
        }

        // Also check old likert_areas config by numeric index
        if (is_numeric($value)) {
            $areaNumber = (int) $value;
            $areas = config('likert-value.areas', []);

            // Try to find by position in array
            $areasArray = array_values($areas);
            if (isset($areasArray[$areaNumber - 1])) {
                return $areasArray[$areaNumber - 1];
            }
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize personnel type
     *
     * @param  string|null  $value  Raw personnel type value
     * @return string|null Normalized personnel type
     */
    public function normalizePersonnelType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'sindicalizado' => 'Sindicalizado',
            'confianza' => 'Salary',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize work schedule
     *
     * @param  string|null  $value  Raw work schedule value
     * @return string|null Normalized work schedule
     */
    public function normalizeWorkSchedule(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $configuredOptions = config('demographic-data.work_schedule.options', []);
        if (is_array($configuredOptions) && array_key_exists($value, $configuredOptions)) {
            return (string) $configuredOptions[$value];
        }

        $lowerValue = strtolower($value);

        // Map for standard Likert (05) - Detailed shift names
        $mapLikert = [
            'fijo_nocturno_(entre_las_20:00_y_6:00_hrs)' => 'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
            'fijo_diurno_(entre_las_6:00_y_20:00_hrs)' => 'Fijo diurno (entre las 6:00 y 20:00 hrs)',
            'fijo_mixto_(combinacion_de_nocturno_y_diurno)' => 'Fijo mixto (combinación de nocturno y diurno)',
            'rotativo' => 'Rotativo',
            'nocturno' => 'Nocturno',
            'diurno' => 'Diurno',
            'mixto' => 'Mixto',
        ];

        // Map for Likert Planta 3 (06) - turno_N format
        $mapPlanta3 = [
            'turno_1' => 'Turno 1',
            'turno_2' => 'Turno 2',
            'turno_3' => 'Turno 3',
            'turno_4' => 'Turno 4',
            'turno_5' => 'Turno 5',
        ];

        // Try Planta 3 format first (turno_N)
        if (isset($mapPlanta3[$lowerValue])) {
            return $mapPlanta3[$lowerValue];
        }

        // Then try standard Likert format
        if (isset($mapLikert[$lowerValue])) {
            return $mapLikert[$lowerValue];
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize yes/no values
     *
     * @param  string|null  $value  Raw yes/no value
     * @return string|null Normalized yes/no value ("Sí" or "No")
     */
    public function normalizeYesNo(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return match (strtolower($value)) {
            'si', 'yes', 'true' => 'Sí',
            'no', 'false' => 'No',
            default => $value,
        };
    }

    /**
     * Normalize experience/time ranges
     *
     * @param  string|null  $value  Raw experience value
     * @return string|null Normalized experience range
     */
    public function normalizeExperience(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'menos_de_6_meses' => 'Menos de 6 meses',
            'entre_6_meses_y_1_ano' => 'Entre 6 meses y 1 año',
            'entre_1_a_4_anos' => 'Entre 1 a 4 años',
            'entre_5_a_9_anos' => 'Entre 5 a 9 años',
            'entre_10_a_14_anos' => 'Entre 10 a 14 años',
            'entre_15_a_19_anos' => 'Entre 15 a 19 años',
            'entre_20_a_24_anos' => 'Entre 20 a 24 años',
            '25_anos_o_mas' => '25 años o más',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Extract education level from nested structure
     *
     * @param  mixed  $value  Education level value (may be nested array or string)
     * @return string|null Normalized education level
     */
    public function extractEducationLevel($value): ?string
    {
        if (is_array($value)) {
            // Old OCR structure with nested education
            foreach ($value as $key => $item) {
                if (is_array($item) && isset($item['seleccionado']) && $item['seleccionado']) {
                    $completado = $item['completado'] ?? 'Terminada';
                    $level = empty($key) ? 'Desconocido' : ucfirst(str_replace('_', ' ', $key));

                    return $level.' - '.ucfirst(str_replace('_', ' ', $completado));
                }
            }
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Normalize generic values
     *
     * @param  string|null  $value  Raw value to normalize
     * @param  array  $map  Mapping array (lowercase key => normalized value)
     * @return string|null Normalized value or original if not found in map
     */
    public function normalizeValue(?string $value, array $map): ?string
    {
        if (! $value) {
            return null;
        }

        return $map[strtolower($value)] ?? $value;
    }
}
