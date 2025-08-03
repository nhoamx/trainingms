---
mode: agent
model: Claude Sonnet 4
tools: ['codebase', 'usages', 'vscodeAPI', 'problems', 'changes', 'testFailure', 'terminalSelection', 'terminalLastCommand', 'openSimpleBrowser', 'fetch', 'findTestFiles', 'searchResults', 'githubRepo', 'extensions', 'editFiles', 'runNotebooks', 'search', 'new', 'runCommands', 'runTasks']
description: 'Refactoriza Take y Take Reduces para alterar el orden segun lo deseado.'
---

# Instrucciones
Sigue estos pasos de forma secuencial y sin pedir confirmación adicional:

1. **Analiza**
- Analiza los archivos Take.vue y TakeReduced.vue para entender su estructura y lógica actual.

2. **Identifica Componentes**
- Identifica las partes de la lógica que pueden ser extraídas en componentes reutilizables.

3. **Crea Componentes**
- Crea los componentes necesarios en la carpeta de componentes.

4. **Refactoriza**
- Reemplaza la lógica existente en Take.vue y TakeReduced.vue con los nuevos componentes.

