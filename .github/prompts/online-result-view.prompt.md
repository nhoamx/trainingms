---
mode: agent
model: Claude Sonnet 4.5 (copilot)
tools: ['edit', 'search', 'runCommands', 'runTasks', 'laravel-boost/*', 'changes', 'fetch']
---
# 🎯 Prompt de Desarrollo — Captura y Visualización de Resultados en Línea

Eres un **experto en análisis y visualización de datos**. Tu objetivo es **mejorar el sistema de captura y visualización de resultados en línea**, integrando correctamente la lógica de guardado con su respectiva presentación visual.

---

## 🧠 Contexto del Proyecto

El modelo principal a analizar es: `app/Models/PaperEvaluation.php`.

- Este modelo maneja dos tipos de evaluaciones: `online` y `paper`.
- Los **exámenes presenciales (paper)** ya funcionan correctamente.
- Tu enfoque estará en **cómo almacenamos y visualizamos los resultados de los exámenes online**.

---

## 📦 Estructura y Tipos de Examen

### Códigos Actuales
| Código | Nombre         |
|--------|----------------|
| 01     | Referencia I   |
| 02     | Referencia III |
| 03     | Referencia V   |
| 04     | Cisneros       |

> 🔹 *Referencia III* se divide en dos tablas: `citsat` y `referencia_iii_answers`.

---

### Tipos de Examen en Línea

#### 🧩 Completo
Incluye:
- Referencia I  
- Referencia III (con CITSAT)  
- Referencia V *(solo si alguna CITSAT está marcada como “sí”)*

#### ⚙️ Parcial / Reducido
Incluye:
- Referencia I  
- Solo CITSAT  
- Referencia V *(solo si alguna CITSAT está marcada como “sí”)*

#### 🧾 Cisneros
Incluye:
- Referencia I  
- Cisneros  

---

## 🧰 Tareas Técnicas

### 1. **Revisión y Captura de Datos**
- Analiza cómo se guardan actualmente los resultados.  
- Examina el flujo de captura en: `app/Http/Controllers/QuizController.php`
- Modifica el método `store` para que:
- Guarde los resultados de los exámenes **online** en el modelo `PaperEvaluation`.
- Mantenga la coherencia con la lógica existente de exámenes presenciales.

---

### 2. **Visualización de Resultados (Frontend)**

El sistema usa **Inertia.js + Vue.js**.

Archivo principal: `resources/js/Components/AdminDashboard.vue`


Desde este dashboard se deben mostrar:
- 📋 **Lista de resultados individuales en línea**
- 📊 **Reporte general de resultados en línea**

Actualmente, ambas vistas se basan en resultados presenciales.
Debes **crear nuevas vistas adaptadas a los datos online**, incluyendo:

#### 🖼️ Vistas a crear
- `resources/js/Pages/Results/OnlineList.vue`  
  → Lista de resultados individuales online.  
- `resources/js/Pages/Results/OnlineReport.vue`  
  → Reporte general online.  
- `resources/js/Pages/Results/Detail.vue`  
  → Vista de detalle (puedes tomar este archivo como referencia).

---

## ✅ Entregables y Buenas Prácticas

1. **Branching**
   - Crea una nueva rama basada en `develop`.

2. **Lista de Objetivos**
   - Documenta una lista de tareas y márcalas conforme avances.

3. **Documentación**
   - Mantén un solo archivo `README.md` con:
     - Descripción de los cambios.
     - Progreso de cada objetivo.
     - Notas técnicas relevantes.

4. **Commits**
   - Usa formato **Conventional Commits** (`feat:`, `fix:`, `chore:`, etc.)
   - Mensajes claros y descriptivos.

5. **Pruebas**
   - Asegura que:
     - Los datos se guarden correctamente.
     - Las vistas rendericen y muestren los datos esperados.
     - El flujo general de captura y visualización funcione sin errores.

---

## 🧩 Stack Principal
- **Backend:** Laravel 11  
- **Frontend:** Inertia.js + Vue.js  
- **Objetivo:** Integrar correctamente los resultados *online* y mejorar su presentación visual


