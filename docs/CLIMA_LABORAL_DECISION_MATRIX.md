# Matriz de decisiones - Clima Laboral (Plantas 1, 3 y 5)

## Objetivo
Definir de forma operativa como se guardan las respuestas de Clima Laboral segun el folio/codigo de evaluacion, sin normalizacion adicional en esta etapa.

## Confirmacion de guardado actual
- El modelo interpreta el tipo por los primeros 2 digitos del folio.
- `05` y `06` se guardan como `evaluation_type = likert`.
- Para construir `likert_answers.questions`:
  - Si `evaluation_type_code = 06`, toma `raw_data.likert_planta_3`.
  - En otro caso de `likert` (ej. `05`), toma `raw_data.likert`.
- El dashboard de Clima consume registros por `evaluation_type = likert`, por lo que 05 y 06 se ven juntos en distribuciones.

## Matriz de decisiones

| Planta | Codigo de folio (TT) | Plantilla OMR esperada | Llave de preguntas en `raw_data` | Resultado en `likert_answers.questions` | Estado esperado |
|---|---|---|---|---|---|
| Planta 1 | `05` | Plantilla historica P1 | `likert` | Se llena desde `likert` | Correcto |
| Planta 3 | `06` | Plantilla P3 | `likert_planta_3` | Se llena desde `likert_planta_3` | Correcto |
| Planta 5 (misma plantilla P3) | `06` | Plantilla P3 | `likert_planta_3` | Se llena igual que Planta 3 | Correcto |
| Planta 5 con codigo nuevo | `07` (u otro no soportado) | Cualquiera | N/A | Falla parseo de tipo | No soportado aun |

## Regla operativa para Planta 5 (hoy)
Para guardar y visualizar Planta 5 de la misma forma que Planta 3, debe operar con folio tipo `06` y payload de preguntas en `likert_planta_3`.

## Alcance de esta decision
- No se normalizan catalogos (turno, contrato, puesto, area) en este cambio.
- Se mantiene compatibilidad con historico existente de plantas 1 y 3.

## Siguiente paso (futuro)
Cuando se decida la normalizacion, se recomienda definir un catalogo canonico por campo y una migracion/estrategia de mapeo para historicos.
