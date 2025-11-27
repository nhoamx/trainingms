# Parche: Soporte para Likert Planta 3 (Tipo 06)

Este parche añade soporte completo para procesar evaluaciones de tipo 06 (Likert Planta 3) en el sistema de procesamiento de evaluaciones en papel.

## Archivos a Modificar

### 1. `app/Models/PaperEvaluation.php`

**Ubicación:** Líneas 88-96 (método `getEvaluationTypeFromCode`)

**Cambio:** Agregar el caso '06' al match statement

```php
// ANTES:
public static function getEvaluationTypeFromCode(string $code): string
{
    return match ($code) {
        '01' => 'referencia_i',
        '02' => 'referencia_iii',
        '03' => 'referencia_v',
        '04' => 'cisneros',
        '05' => 'likert',
        default => throw new \InvalidArgumentException("Invalid evaluation type code: {$code}"),
    };
}

// DESPUÉS:
public static function getEvaluationTypeFromCode(string $code): string
{
    return match ($code) {
        '01' => 'referencia_i',
        '02' => 'referencia_iii',
        '03' => 'referencia_v',
        '04' => 'cisneros',
        '05' => 'likert',
        '06' => 'likert_planta_3',
        default => throw new \InvalidArgumentException("Invalid evaluation type code: {$code}"),
    };
}
```

---

### 2. `app/Jobs/ProcessPaperEvaluation.php`

**Ubicación:** Líneas 392-407 (método `extractStructuredData`, dentro del switch)

**Cambio:** Agregar el caso para 'likert_planta_3' después del caso 'likert'

```php
// ANTES (final del switch, después del case 'likert'):
            case 'likert':
                // Likert - Workplace climate evaluation (23 questions + demographics)
                $structuredData['likert_answers'] = [
                    'questions' => $rawData['likert'] ?? null,
                    'genero' => $rawData['genero'] ?? null,
                    'turno' => $rawData['turno'] ?? null,
                    'tipo_contrato' => $rawData['tipo_contrato'] ?? null,
                    'puestos' => $rawData['puestos'] ?? null,
                    'areas' => $rawData['areas'] ?? null,
                ];
                break;
        }

        return $structuredData;
    }

// DESPUÉS:
            case 'likert':
                // Likert - Workplace climate evaluation (23 questions + demographics)
                $structuredData['likert_answers'] = [
                    'questions' => $rawData['likert'] ?? null,
                    'genero' => $rawData['genero'] ?? null,
                    'turno' => $rawData['turno'] ?? null,
                    'tipo_contrato' => $rawData['tipo_contrato'] ?? null,
                    'puestos' => $rawData['puestos'] ?? null,
                    'areas' => $rawData['areas'] ?? null,
                ];
                break;

            case 'likert_planta_3':
                // Likert Planta 3 - Workplace climate evaluation (23 questions + demographics)
                $structuredData['likert_answers'] = [
                    'questions' => $rawData['likert_planta_3'] ?? null,
                    'genero' => $rawData['genero'] ?? null,
                    'turno' => $rawData['turno'] ?? null,
                    'tipo_contrato' => $rawData['tipo_contrato'] ?? null,
                    'puestos' => $rawData['puestos'] ?? null,
                    'areas' => $rawData['areas'] ?? null,
                ];
                break;
        }

        return $structuredData;
    }
```

---

## Verificación de Funcionamiento

### Estructura de Datos Esperada del OCR (Python)

El script de Python (`docker/main.py`) debe generar un JSON con esta estructura para folios tipo 06:

```json
{
  "likert_planta_3": {
    "1": "A",
    "2": "B",
    "3": "C",
    ...
    "23": "D"
  },
  "genero": "Masculino",
  "turno": "Turno 1",
  "tipo_contrato": "Opción 1",
  "puestos": "OPERADOR COSTURA",
  "areas": "PRODUCCIÓN"
}
```

### Almacenamiento en Base de Datos

1. **Tabla `paper_evaluations`:**
   - Campo `evaluation_type`: `'likert_planta_3'`
   - Campo `evaluation_type_code`: `'06'`
   - Campo `likert_answers`: JSON con estructura:
     ```json
     {
       "questions": {"1": "A", "2": "B", ...},
       "genero": "Masculino",
       "turno": "Turno 1",
       "tipo_contrato": "Opción 1",
       "puestos": "OPERADOR COSTURA",
       "areas": "PRODUCCIÓN"
     }
     ```

2. **Tabla `demographic_data`:**
   - Campo `gender`: `'Masculino'` o `'Femenino'`
   - Campo `position`: Valor de `puestos` (ej: `'OPERADOR COSTURA'`)
   - Campo `department`: Valor de `areas` (ej: `'PRODUCCIÓN'`)
   - Campo `contract_type`: Valor de `tipo_contrato` (ej: `'Opción 1'`)
   - Campo `work_schedule`: Valor de `turno` (ej: `'Turno 1'`)

### Flujo Completo

1. Usuario sube PDF con folios tipo `06XXXNNNN`
2. Docker OCR procesa y genera JSON con claves de puestos/áreas reales
3. `ProcessPaperEvaluation` lee el JSON
4. `parseFolio()` identifica código `'06'`
5. `getEvaluationTypeFromCode('06')` retorna `'likert_planta_3'`
6. `extractStructuredData()` procesa caso `'likert_planta_3'`
7. Datos se guardan en:
   - `paper_evaluations.likert_answers` (preguntas + demografía)
   - `demographic_data` (demografía estructurada)

---

## Comandos de Aplicación

```bash
# Aplicar cambios manualmente editando los archivos mencionados

# Ejecutar Pint para formatear
vendor/bin/pint app/Models/PaperEvaluation.php app/Jobs/ProcessPaperEvaluation.php

# Verificar con Tinker
php artisan tinker
>>> App\Models\PaperEvaluation::getEvaluationTypeFromCode('06');
# Debe retornar: "likert_planta_3"
```

---

## Notas Importantes

- Los valores de `puestos` y `areas` ya están configurados en `docker/config_legacy.py` con nombres reales
- El procesamiento demográfico en `extractFromLikert()` (línea 535) ya maneja correctamente estos datos
- No se requieren cambios en migraciones ya que el campo `likert_answers` es JSON y acepta cualquier estructura
- El método `extractFromLikert()` normaliza automáticamente los valores de género, puestos y áreas

---

**Fecha:** 2025-11-26  
**Autor:** GitHub Copilot (Claude Sonnet 4.5)
