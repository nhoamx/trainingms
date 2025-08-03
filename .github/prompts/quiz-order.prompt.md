---
mode: agent
model: Claude Sonnet 4
tools: ['codebase', 'usages', 'vscodeAPI', 'problems', 'changes', 'testFailure', 'terminalSelection', 'terminalLastCommand', 'openSimpleBrowser', 'fetch', 'findTestFiles', 'searchResults', 'githubRepo', 'extensions', 'editFiles', 'runNotebooks', 'search', 'new', 'runCommands', 'runTasks']
description: 'Refactoriza Take y Take Reduces para alterar el orden segun lo deseado.'
---

# Instrucciones
Sigue estos pasos de forma secuencial y sin pedir confirmación adicional:

1. **Crear branch**  
   - `git checkout develop`  
   - `git pull`  
   - `git checkout -b {type}/{name}` donde name, es el nombre de la branch y type la tarea

2. **Localizar archivos**  
   - Busca `Take.vue` y `TakeReduced.vue` en tu directorio de componentes de Inertia.
   - Revisa el orden de las secciones en ambos archivos.

3. **Modificar Take.vue**  
   - Identifica la sección `referencia_v (datos personales)`.  
   - Modifica la logica y el orden para que `referencia_v` se sea lo primero que haya que responder

4. **Modificar TakeReduced.vue**  
   - Localiza la sección `referencia_v (datos personales)`.  
   - Modifica la logica y el orden para que `referencia_v` se sea lo primero que haya que responder


# Reglas
- Usa buenas prácticas de Vue 3 e Inertia (componentes, slots, directivas).  
- Mantén los tests verdes antes de hacer commit.