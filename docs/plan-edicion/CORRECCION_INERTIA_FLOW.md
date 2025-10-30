# Corrección del Error de Inertia - Flujo Correcto

## 🔴 Problema Identificado

```
All Inertia requests must receive a valid Inertia response, however a plain JSON response was received.
```

Este error ocurre cuando un controlador Laravel retorna una respuesta JSON (`JsonResponse`) en lugar de seguir el protocolo de Inertia.

## ❌ Código Incorrecto (Anterior)

```php
public function updateName(Request $request, PaperEvaluation $paperEvaluation): JsonResponse
{
    $request->validate([
        'evaluee_name' => 'required|string|max:255',
    ]);

    $paperEvaluation->updateName($request->input('evaluee_name'));

    // ❌ INCORRECTO: Inertia no espera JSON responses
    return response()->json([
        'message' => 'Nombre actualizado exitosamente',
        'data' => $paperEvaluation->fresh(),
    ]);
}
```

## ✅ Código Correcto (Actual)

```php
public function updateName(Request $request, PaperEvaluation $paperEvaluation): RedirectResponse
{
    $request->validate([
        'evaluee_name' => 'required|string|max:255',
    ]);

    $paperEvaluation->updateName($request->input('evaluee_name'));

    // ✅ CORRECTO: Redirect con flash message
    return back()->with('success', 'Nombre actualizado exitosamente');
}
```

## 📚 Documentación de Inertia v2

Según la documentación oficial de Inertia.js v2:

### Flujo de Validación
> "Handling server-side validation errors in Inertia works differently than a classic XHR-driven form that requires you to catch the validation errors from 422 responses and manually update the form's error state - because **Inertia never receives 422 responses**."

### Respuestas del Servidor
> "When using Inertia, you don't typically inspect form responses client-side like you would with traditional XHR/fetch requests. Instead, your server-side route or controller issues a **redirect response** after processing the form, often redirecting to a success page."

### Redirects después de POST/PUT/PATCH
> "When making a non-GET Inertia request... you should ensure that you always respond with a proper **Inertia redirect response**."

## 🔄 Flujo Correcto de Inertia

### 1. Frontend (Vue Component)
```typescript
// EditNameModal.vue
const submitForm = () => {
    form.patch(route('paper-evaluations.update-name', props.evaluation.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia automáticamente recarga la página con nuevos datos
            successMessage.value = 'Nombre actualizado exitosamente';
            setTimeout(() => closeModal(), 1500);
        },
        onError: (errors) => {
            // Errores automáticamente disponibles en form.errors
            console.error('Error updating name:', errors);
        },
    });
};
```

### 2. Backend (Laravel Controller)
```php
public function updateName(Request $request, PaperEvaluation $paperEvaluation): RedirectResponse
{
    $request->validate([
        'evaluee_name' => 'required|string|max:255',
    ]);

    $paperEvaluation->updateName($request->input('evaluee_name'));

    // Redirect back con flash message
    return back()->with('success', 'Nombre actualizado exitosamente');
}
```

### 3. Inertia Protocol
1. Cliente hace request PATCH con header `X-Inertia: true`
2. Servidor valida datos
3. Si hay errores: redirect back con `errors` en sesión
4. Si success: redirect back con `success` en sesión flash
5. Inertia intercepta el redirect (código 303)
6. Hace un GET a la URL de redirect
7. Servidor retorna página Inertia actualizada (JSON con page object)
8. Cliente actualiza componente Vue reactivamente

## 🎯 Excepciones - Cuándo SÍ usar JSON

### Validación en Tiempo Real (AJAX)
El endpoint `checkFolioAvailability` **sí debe retornar JSON** porque:
- Es una petición **separada** del flujo principal de Inertia
- Se usa para validación en tiempo real (cada keystroke)
- Se hace con `axios` directamente, **no con Inertia form**

```php
// ✅ CORRECTO: Este endpoint puede retornar JSON
public function checkFolioAvailability(Request $request, PaperEvaluation $paperEvaluation): JsonResponse
{
    $validated = $request->validate([
        'personal_folio' => ['required', 'string', 'regex:/^\d{4}$/'],
    ]);

    $newFolio = PaperEvaluation::generateFolio(
        $paperEvaluation->evaluation_type_code,
        $paperEvaluation->organization_code,
        $validated['personal_folio']
    );

    $isAvailable = PaperEvaluation::isFolioAvailable($newFolio, $paperEvaluation->id);

    return response()->json([
        'available' => $isAvailable,
        'new_folio' => $newFolio,
        'message' => $isAvailable
            ? 'Folio disponible'
            : "El folio {$newFolio} ya está en uso",
    ]);
}
```

### En el Frontend (Vue)
```typescript
// EditFolioModal.vue - Validación AJAX separada
const checkFolioAvailability = async () => {
    checking.value = true;
    
    try {
        // ✅ axios para AJAX, no Inertia form
        const response = await axios.post(
            route('paper-evaluations.check-folio', props.evaluation.id), 
            { personal_folio: form.personal_folio }
        );
        
        isAvailable.value = response.data.available;
    } catch (error: any) {
        validationError.value = error.response?.data?.message;
    } finally {
        checking.value = false;
    }
};
```

## 📋 Resumen de Cambios Realizados

### Backend
1. **Controlador** (`PaperEvaluationController.php`):
   - ✅ Añadido import: `use Illuminate\Http\RedirectResponse;`
   - ✅ Cambiado return type de métodos `update()`, `updateName()`, `updateFolio()` de `JsonResponse` a `RedirectResponse`
   - ✅ Reemplazado `return response()->json(...)` por `return back()->with('success', '...')`
   - ✅ Mantenido `checkFolioAvailability()` con `JsonResponse` (validación AJAX)

### Frontend
1. **EditNameModal.vue**:
   - ✅ Removido código que parseaba JSON response
   - ✅ Simplificado `onSuccess` callback
   - ✅ Inertia maneja automáticamente la recarga de datos

2. **EditFolioModal.vue**:
   - ✅ Removido código que parseaba JSON response
   - ✅ Mantenido `axios` para `checkFolioAvailability` (AJAX separado)
   - ✅ Simplificado `onSuccess` callback

3. **Detail.vue**:
   - ✅ Simplificado `handleEvaluationUpdate()` 
   - ✅ Inertia recarga automáticamente con `router.reload()`

## 🧪 Testing

### Prueba Manual
1. Abrir página de detalle de evaluación
2. Click en botón de editar nombre ✏️
3. Modificar nombre
4. Submit form
5. **Resultado esperado**: 
   - Modal se cierra después de 1.5s
   - Mensaje "Nombre actualizado exitosamente"
   - Página muestra nuevo nombre sin recarga completa
   - **NO** aparece error de JSON response

### Validación de Errores
1. Dejar nombre vacío
2. Submit form
3. **Resultado esperado**:
   - Error "El nombre es requerido" aparece en modal
   - Modal **NO** se cierra
   - Form mantiene estado

## 🔗 Referencias

- [Inertia.js v2 - Validation](https://inertiajs.com/validation)
- [Inertia.js v2 - Redirects](https://inertiajs.com/redirects)
- [Inertia.js v2 - Forms](https://inertiajs.com/forms)
- [Inertia.js v2 - The Protocol](https://inertiajs.com/the-protocol)

---

**Conclusión**: El error se solucionó cambiando de respuestas JSON a redirects con flash messages, siguiendo el protocolo correcto de Inertia.js v2.
