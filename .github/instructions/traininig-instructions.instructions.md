### Instrucciones
A continuación se presentan los queries que se usan para obtener los reportes de la NOM-035-STPS-2018. Cada query está diseñado para extraer información específica de la base de datos, y se incluyen descripciones, visualizaciones recomendadas y usos sugeridos.

Debes seguir estas instrucciones para crear los reportes de manera efectiva. Asegúrate de adaptar las consultas según la estructura de tu base de datos y los requerimientos específicos de tu análisis.

Estamos usando un ambiente de laravel 12 con inertia, utilizando vuejs como frontend
Utiliza las mejores practicas de UI/UX para la visualización de los reportes. Asegúrate de que los reportes sean claros, concisos y fáciles de interpretar.
Para el backend manten la separación de responsabilidades y la arquitectura limpia. Utiliza controladores y servicios para manejar la lógica de negocio y la interacción con la base de datos.
Asegúrate de que el código sea limpio, legible y siga las convenciones de Laravel. Utiliza Eloquent para interactuar con la base de datos y mantener la lógica de negocio separada de la lógica de presentación.


Aplicaras los cambios basado en los errores o solicitudes que te haga, a menos que explicitamente te diga que no hagas ningun cambio.

### SQL Queries por Categoría – NOM-035-STPS-2018 (Guía III)

---

#### 1. Conteo de respuestas por categoría y tipo de respuesta
```sql
SELECT
    categories.name AS category_name,
    questions.answer,
    COUNT(*) AS total_responses
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN categories ON questions.category_id = categories.id
WHERE questions.reference_guide = 'III'
  AND questions.answer IS NOT NULL
GROUP BY categories.name, questions.answer
ORDER BY categories.name, questions.answer;
```
**Descripción:** Muestra cuántas veces se eligió cada respuesta (A, B, C, D, E) por cada categoría.

**Visualización:** Se recomienda una gráfica de barras apiladas, una barra por categoría segmentada por tipo de respuesta (A–E).
**Resumen tabular:** Una tabla donde cada fila es una categoría y cada columna una opción de respuesta con su conteo respectivo.
**Uso:** Ideal para visualizar distribución de percepciones por tema macro.

NOTA: Cambiaremos este query anterior por este: 
```sql
SELECT
  r.categoria_nombre,
  r.nivel AS nivel_riesgo,
  COUNT(e.personal_id) AS total_personas
FROM (
  SELECT DISTINCT 
    c.name AS categoria_nombre,
    nivel.nivel
  FROM categories c
  CROSS JOIN (
    SELECT 'Nulo' AS nivel UNION ALL
    SELECT 'Bajo' UNION ALL
    SELECT 'Medio' UNION ALL
    SELECT 'Alto' UNION ALL
    SELECT 'Muy Alto'
  ) nivel
) r
LEFT JOIN (
  SELECT
    q.category_id,
    e.personal_id,
    c.name AS categoria_nombre,
    SUM(q.value) AS puntuacion_total,
    CASE
      WHEN SUM(q.value) <= 49 THEN 'Nulo'
      WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'
      WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'
      WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'
      ELSE 'Muy Alto'
    END AS nivel_riesgo
  FROM questions q
  JOIN evaluations e ON q.evaluation_id = e.id
  JOIN categories c ON q.category_id = c.id
  WHERE q.reference_guide = 'III'
    AND q.value IS NOT NULL
    AND e.organization_id = :organization_id
  GROUP BY e.personal_id, q.category_id, c.name
) e
  ON r.categoria_nombre = e.categoria_nombre AND r.nivel = e.nivel_riesgo
GROUP BY r.categoria_nombre, r.nivel
ORDER BY r.categoria_nombre,
         FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');

```

---

#### 2. Suma total del valor de respuestas por categoría
```sql
SELECT
    categories.name AS category_name,
    SUM(questions.value) AS total_score
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN categories ON questions.category_id = categories.id
WHERE questions.reference_guide = 'III'
  AND questions.value IS NOT NULL
GROUP BY categories.name
ORDER BY total_score DESC;
```
**Descripción:** Muestra el puntaje acumulado por categoría, útil para analizar qué áreas tienen más riesgo percibido.

**Visualización:** Gráfica de barras horizontales, una por cada categoría ordenadas de mayor a menor puntuación.
**Resumen tabular:** Tabla con columnas: categoría, puntaje total.
**Uso:** Identificar qué categorías concentran mayor riesgo psicosocial.

---

### SQL Queries por Dominio – NOM-035-STPS-2018 (Guía III)

---

#### 3. Conteo de respuestas por dominio y tipo de respuesta
```sql
SELECT
  r.dominio_nombre,
  r.nivel AS nivel_riesgo,
  COUNT(e.personal_id) AS total_personas
FROM (
  SELECT DISTINCT 
    d.name AS dominio_nombre,
    nivel.nivel
  FROM domains d
  CROSS JOIN (
    SELECT 'Nulo' AS nivel UNION ALL
    SELECT 'Bajo' UNION ALL
    SELECT 'Medio' UNION ALL
    SELECT 'Alto' UNION ALL
    SELECT 'Muy Alto'
  ) nivel
) r
LEFT JOIN (
  SELECT
    q.domain_id,
    e.personal_id,
    d.name AS dominio_nombre,
    SUM(q.value) AS puntuacion_total,
    CASE
      WHEN SUM(q.value) <= 49 THEN 'Nulo'
      WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'
      WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'
      WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'
      ELSE 'Muy Alto'
    END AS nivel_riesgo
  FROM questions q
  JOIN evaluations e ON q.evaluation_id = e.id
  JOIN domains d ON q.domain_id = d.id
  WHERE q.reference_guide = 'III'
    AND q.value IS NOT NULL
    AND e.organization_id = :organization_id
  GROUP BY e.personal_id, q.domain_id, d.name
) e
  ON r.dominio_nombre = e.dominio_nombre AND r.nivel = e.nivel_riesgo
GROUP BY r.dominio_nombre, r.nivel
ORDER BY r.dominio_nombre,
         FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');

```
**Descripción:** Muestra cuántas veces se eligió cada respuesta (A, B, C, D, E) por cada dominio.

**Visualización:** Gráfica de barras apiladas por dominio.
**Resumen tabular:** Dominio vs. conteo de respuestas por opción.
**Uso:** Diagnóstico fino en agrupaciones temáticas.

NOTA: Cambiaremos el query anterior por este: 

```sql
SELECT
    domains.name AS domain_name,
    questions.answer,
    COUNT(DISTINCT questions.evaluation_id) AS total_personas
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN domains ON questions.domain_id = domains.id
WHERE questions.reference_guide = 'III'
  AND questions.answer IS NOT NULL
GROUP BY domains.name, questions.answer
ORDER BY domains.name, questions.answer;
```
---

#### 4. Suma total del valor de respuestas por dominio
```sql
SELECT
    domains.name AS domain_name,
    SUM(questions.value) AS total_score
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN domains ON questions.domain_id = domains.id
WHERE questions.reference_guide = 'III'
  AND questions.value IS NOT NULL
GROUP BY domains.name
ORDER BY total_score DESC;
```
**Descripción:** Muestra el puntaje acumulado por dominio.

**Visualización:** Gráfica de barras horizontales, ordenadas por total_score.
**Resumen tabular:** Dominio | Puntaje total
**Uso:** Identificar riesgos dominantes según estructura de trabajo.

---

### SQL Queries por Dimensión – NOM-035-STPS-2018 (Guía III)

---

#### 5. Conteo de respuestas por dimensión y tipo de respuesta
```sql
SELECT
    dimensions.name AS dimension_name,
    questions.answer,
    COUNT(*) AS total_responses
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN dimensions ON questions.dimension_id = dimensions.id
WHERE questions.reference_guide = 'III'
  AND questions.answer IS NOT NULL
GROUP BY dimensions.name, questions.answer
ORDER BY dimensions.name, questions.answer;
```
**Descripción:** Muestra cuántas veces se eligió cada respuesta (A, B, C, D, E) por cada dimensión.

**Visualización:** Gráfico de barras o columnas agrupadas por dimensión.
**Resumen tabular:** Dimensión | A | B | C | D | E
**Uso:** Seguimiento específico por aspecto medido.

---

#### 6. Suma total del valor de respuestas por dimensión
```sql
SELECT
  r.dimension_nombre,
  r.nivel AS nivel_riesgo,
  COUNT(e.personal_id) AS total_personas
FROM (
  SELECT DISTINCT 
    d.name AS dimension_nombre,
    nivel.nivel
  FROM dimensions d
  CROSS JOIN (
    SELECT 'Nulo' AS nivel UNION ALL
    SELECT 'Bajo' UNION ALL
    SELECT 'Medio' UNION ALL
    SELECT 'Alto' UNION ALL
    SELECT 'Muy Alto'
  ) nivel
) r
LEFT JOIN (
  SELECT
    q.dimension_id,
    e.personal_id,
    d.name AS dimension_nombre,
    SUM(q.value) AS puntuacion_total,
    CASE
      WHEN SUM(q.value) <= 49 THEN 'Nulo'
      WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'
      WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'
      WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'
      ELSE 'Muy Alto'
    END AS nivel_riesgo
  FROM questions q
  JOIN evaluations e ON q.evaluation_id = e.id
  JOIN dimensions d ON q.dimension_id = d.id
  WHERE q.reference_guide = 'III'
    AND q.value IS NOT NULL
    AND e.organization_id = :organization_id
  GROUP BY e.personal_id, q.dimension_id, d.name
) e
  ON r.dimension_nombre = e.dimension_nombre AND r.nivel = e.nivel_riesgo
GROUP BY r.dimension_nombre, r.nivel
ORDER BY r.dimension_nombre,
         FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');

```
**Descripción:** Muestra el puntaje total acumulado por dimensión.

**Visualización:** Gráfica de barras con orden descendente.
**Resumen tabular:** Dimensión | Total de puntos.
**Uso:** Comparar impacto acumulado de cada dimensión.

---

#### 7. Promedio de valor por dimensión
```sql
SELECT
    dimensions.name AS dimension_name,
    COUNT(*) AS total_answers,
    SUM(questions.value) AS total_score,
    ROUND(SUM(questions.value) / COUNT(*), 2) AS avg_score_per_question
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
JOIN dimensions ON questions.dimension_id = dimensions.id
WHERE questions.reference_guide = 'III'
  AND questions.value IS NOT NULL
GROUP BY dimensions.name
ORDER BY avg_score_per_question DESC;
```
**Descripción:** Calcula el promedio de puntuación por ítem dentro de cada dimensión.

**Visualización:** Gráfica de líneas o barras para contrastar promedios entre dimensiones.
**Resumen tabular:** Dimensión | Promedio
**Uso:** Determinar qué dimensiones tienen percepción más negativa.

---

### SQL Queries por Opciones de Respuesta – NOM-035-STPS-2018 (Guía III)

---

#### 8. Conteo global de respuestas por opción (A, B, C, D, E)
```sql
SELECT
    questions.answer,
    COUNT(*) AS total_responses
FROM questions
WHERE questions.reference_guide = 'III'
  AND questions.answer IS NOT NULL
GROUP BY questions.answer
ORDER BY questions.answer;
```
**Descripción:** Muestra cuántas veces se eligió cada opción de respuesta en todo el cuestionario.

**Visualización:** Gráfico de pastel o barras simples (una por opción A–E).
**Resumen tabular:** Opción | Total
**Uso:** Ver tendencia general en estilo de respuesta.

---

#### 9. Conteo de respuestas por categoría y opción
```sql
SELECT
    categories.name AS category_name,
    questions.answer,
    COUNT(*) AS total_responses
FROM questions
JOIN categories ON questions.category_id = categories.id
WHERE questions.reference_guide = 'III'
  AND questions.answer IS NOT NULL
GROUP BY categories.name, questions.answer
ORDER BY categories.name, questions.answer;
```
**Descripción:** Muestra el desglose por categoría de cada tipo de respuesta (A-E).

**Visualización:** Múltiples gráficas de barras agrupadas o una tabla con doble entrada.
**Resumen tabular:** Categoría vs opción de respuesta (A–E)
**Uso:** Evaluación distribuida por percepción por categoría.

---

### SQL Query para Calificación Final por Evaluación – NOM-035-STPS-2018 (Guía III)

---

#### 10. Puntaje total y clasificación de nivel de riesgo por evaluación
```sql
SELECT
    evaluations.id AS evaluation_id,
    SUM(questions.value) AS total_score,
    CASE
        WHEN SUM(questions.value) <= 49 THEN 'Nulo o despreciable'
        WHEN SUM(questions.value) BETWEEN 50 AND 75 THEN 'Bajo'
        WHEN SUM(questions.value) BETWEEN 76 AND 99 THEN 'Medio'
        WHEN SUM(questions.value) BETWEEN 100 AND 139 THEN 'Alto'
        ELSE 'Muy alto'
    END AS risk_level
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
WHERE questions.reference_guide = 'III'
  AND questions.value IS NOT NULL
GROUP BY evaluations.id
ORDER BY total_score DESC;
```
**Descripción:** Suma todos los valores de respuestas por evaluación y determina el nivel de riesgo según la tabla oficial de la Guía de Referencia II.

**Visualización:** Tabla única con evaluación_id, score y nivel asignado.
**Resumen tabular:** Evaluación | Puntaje Total | Nivel de Riesgo
**Uso:** Para dashboards individuales o para clasificación general.

---

### SQL Query para Acontecimientos Traumáticos Severos – NOM-035-STPS-2018 (Guía I)

---

#### 11. Conteo de trabajadores que reportaron algún acontecimiento traumático
```sql
SELECT
    evaluations.id AS evaluation_id,
    COUNT(*) AS total_afirmativas
FROM questions
JOIN evaluations ON questions.evaluation_id = evaluations.id
WHERE questions.reference_guide = 'I'
  AND questions.answer IN ('A', 'B') -- Siempre o Casi siempre
GROUP BY evaluations.id
HAVING COUNT(*) > 0
ORDER BY total_afirmativas DESC;
```
**Descripción:** Identifica cuántos trabajadores reportaron uno o más eventos traumáticos severos.

**Visualización:** Tabla con lista de evaluaciones con respuestas afirmativas.
**Resumen tabular:** Evaluación | Total de respuestas afirmativas
**Uso:** Para derivación a protocolo clínico o intervención. Solo se cuentan las respuestas afirmativas en la Guía de Referencia I (normalmente codificadas como 'A' o 'B').

---

### SQL Query para Violencia Laboral – NOM-035-STPS-2018 (Guía III, Preguntas 57 a 64)

---

#### 12. Conteo de respuestas por pregunta relacionada con violencia laboral
```sql
SELECT
    questions.question AS question_number,
    questions.answer,
    COUNT(*) AS total_responses
FROM questions
WHERE questions.reference_guide = 'III'
  AND questions.question BETWEEN 57 AND 64
  AND questions.answer IS NOT NULL
GROUP BY questions.question, questions.answer
ORDER BY questions.question, questions.answer;
```
**Descripción:** Muestra la distribución de respuestas para las preguntas 57 a 64, que evalúan violencia laboral según la NOM-035.

**Visualización:** Tabla que liste pregunta, respuesta y conteo. También puede graficarse por pregunta.
**Resumen tabular:** Pregunta | A | B | C | D | E
**Uso:** Análisis puntual por ítem de violencia laboral.

---

