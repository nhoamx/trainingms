# Correcciones Finales - Error 403 y Actualización de Vista

## 🔴 Problemas Identificados

### 1. Error 403 "This action is unauthorized"
**Causa**: El `FormRequest` tenía dos problemas:
- Verificaba solo el rol `'admin'` en lugar de `['admin', 'super-admin']`
- Usaba el parámetro incorrecto de ruta: `paper_evaluation` en lugar de `paperEvaluation`

### 2. Vista no se actualiza después de editar
**Causa**: El componente Vue no estaba emitiendo correctamente el evento `updated` y la firma del emit estaba incorrecta.

---

## ✅ Soluciones Implementadas

### 1. FormRequest - Autorización

**Archivo**: `app/Http/Requests/UpdatePaperEvaluationRequest.php`

**Antes**:
```php
public function authorize(): bool
{
    // ❌ Solo verifica 'admin'
    return $this->user()?->hasRole('admin') ?? false;
}

public function rules(): array
{
    // ❌ Parámetro incorrecto
    $evaluation = $this->route('paper_evaluation');
    // ...
}
```

**Después**:
```php
public function authorize(): bool
{
    // ✅ Verifica 'admin' y 'super-admin'
    return $this->user()?->hasRole(['admin', 'super-admin']) ?? false;
}

public function rules(): array
{
    // ✅ Parámetro correcto (camelCase)
    $evaluation = $this->route('paperEvaluation');
    // ...
}
```

**Explicación**:
- Spatie Permission permite pasar un array de roles a `hasRole()`
- Laravel convierte automáticamente los parámetros de ruta con guiones a camelCase
- El parámetro `{paperEvaluation}` en la ruta se accede como `paperEvaluation` desde el request

---

### 2. Rutas - Consistencia y Middleware

**Archivo**: `routes/web.php`

**Antes**:
```php
Route::prefix('paper-evaluations')->name('paper-evaluations.')->group(function () {
    // ❌ Sin middleware auth explícito
    // ❌ Parámetro con guion bajo
    Route::patch('/{paper_evaluation}', ...)->name('update');
    Route::patch('/{paper_evaluation}/name', ...)->name('update-name');
    Route::patch('/{paper_evaluation}/folio', ...)->name('update-folio');
    Route::post('/{paper_evaluation}/check-folio', ...)->name('check-folio');
});
```

**Después**:
```php
Route::prefix('paper-evaluations')->middleware(['auth'])->name('paper-evaluations.')->group(function () {
    // ✅ Middleware auth explícito
    // ✅ Parámetro camelCase
    Route::patch('/{paperEvaluation}', ...)->name('update');
    Route::patch('/{paperEvaluation}/name', ...)->name('update-name');
    Route::patch('/{paperEvaluation}/folio', ...)->name('update-folio');
    Route::post('/{paperEvaluation}/check-folio', ...)->name('check-folio');
});
```

**Explicación**:
- Añadido middleware `auth` para asegurar que todas las rutas requieran autenticación
- Cambiado parámetro a `{paperEvaluation}` (camelCase) para consistencia con Laravel
- El FormRequest adicional verifica el rol específico (admin/super-admin)

---

### 3. Componentes Vue - Emits y Actualización

**Archivo**: `resources/js/Components/PaperEvaluation/EditNameModal.vue`

**Antes**:
```typescript
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated', evaluation: Evaluation): void; // ❌ Parámetro innecesario
}>();

const submitForm = () => {
    form.patch(route('paper-evaluations.update-name', props.evaluation.id), {
        onSuccess: () => {
            successMessage.value = 'Nombre actualizado exitosamente';
            // ❌ No emite 'updated'
            // ❌ No resetea form
            setTimeout(() => closeModal(), 1500);
        },
    });
};
```

**Después**:
```typescript
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'updated'): void; // ✅ Sin parámetros (Inertia maneja los datos)
}>();

const submitForm = () => {
    form.patch(route('paper-evaluations.update-name', props.evaluation.id), {
        onSuccess: () => {
            successMessage.value = 'Nombre actualizado exitosamente';
            form.reset(); // ✅ Resetea el form
            emit('updated'); // ✅ Emite evento para que parent recargue
            setTimeout(() => closeModal(), 1500);
        },
    });
};
```

**Archivo**: `resources/js/Components/PaperEvaluation/EditFolioModal.vue`

**Cambios idénticos**:
- Removido parámetro `evaluation` del emit `updated`
- Añadido `form.reset()` después de éxito
- Añadido `emit('updated')` para notificar al componente padre

---

### 4. Flujo de Actualización con Inertia

**Archivo**: `resources/js/Pages/Results/Detail.vue`

El handler ya estaba correcto:
```typescript
const handleEvaluationUpdate = () => {
    router.reload({ only: ['evaluation', 'personalFolio'] });
};
```

**Cómo funciona**:
1. Usuario edita nombre/folio en modal
2. Modal hace PATCH request via Inertia
3. Servidor actualiza BD y retorna `back()` con flash message
4. Inertia intercepta el redirect (303) automáticamente
5. Modal emite evento `updated`
6. `Detail.vue` ejecuta `router.reload({ only: ['evaluation', 'personalFolio'] })`
7. Inertia hace GET request al servidor pidiendo solo esas props
8. Vue actualiza reactivamente la vista con los nuevos datos

**Ventajas de este enfoque**:
- ✅ No recarga la página completa
- ✅ Solo recarga los datos necesarios (`only`)
- ✅ Mantiene el scroll position (`preserveScroll: true`)
- ✅ Actualización instantánea en UI
- ✅ Sigue el protocolo correcto de Inertia v2

---

## 📋 Resumen de Archivos Modificados

### Backend
1. **`app/Http/Requests/UpdatePaperEvaluationRequest.php`**:
   - ✅ Cambiado `hasRole('admin')` → `hasRole(['admin', 'super-admin'])`
   - ✅ Cambiado `route('paper_evaluation')` → `route('paperEvaluation')`

2. **`routes/web.php`**:
   - ✅ Añadido `middleware(['auth'])` al grupo
   - ✅ Cambiado `{paper_evaluation}` → `{paperEvaluation}` en todas las rutas

### Frontend
3. **`resources/js/Components/PaperEvaluation/EditNameModal.vue`**:
   - ✅ Removido parámetro `evaluation` del emit `updated`
   - ✅ Añadido `form.reset()` en `onSuccess`
   - ✅ Añadido `emit('updated')` en `onSuccess`

4. **`resources/js/Components/PaperEvaluation/EditFolioModal.vue`**:
   - ✅ Mismos cambios que EditNameModal
   - ✅ Tipado correcto con `any` para errors

---

## 🧪 Testing

### Verificar Error 403 Solucionado

1. Login como usuario admin
2. Ir a página de detalle de evaluación
3. Click en botón editar folio ✏️
4. Modificar folio personal (ej: 0001 → 0002)
5. Click "Actualizar Folio"
6. **Resultado esperado**: 
   - ✅ Modal muestra "Folio actualizado exitosamente"
   - ✅ **NO** aparece error 403
   - ✅ Modal se cierra después de 1.5s
   - ✅ Página muestra nuevo folio inmediatamente

### Verificar Actualización de Vista

1. Login como usuario admin
2. Ir a página de detalle de evaluación
3. Click en botón editar nombre ✏️
4. Cambiar nombre (ej: "" → "Juan Pérez")
5. Click "Actualizar Nombre"
6. **Resultado esperado**:
   - ✅ Modal muestra "Nombre actualizado exitosamente"
   - ✅ Modal se cierra después de 1.5s
   - ✅ **Header de la página muestra "Juan Pérez" inmediatamente**
   - ✅ **SIN recarga completa de página**
   - ✅ Scroll position se mantiene

### Verificar Validación en Tiempo Real

1. Abrir modal de editar folio
2. Escribir folio duplicado (ej: folio que ya existe)
3. **Resultado esperado**:
   - ✅ Mensaje "El folio [XXXXXXXX] ya está en uso" después de 500ms
   - ✅ Botón "Actualizar Folio" está deshabilitado
   - ✅ Indicador rojo de error visible

---

## 🔗 Referencias

- **Spatie Permission - Checking Roles**: `hasRole()` acepta string o array
- **Laravel Route Model Binding**: Parámetros automáticamente convertidos a camelCase
- **Inertia.js v2 - Partial Reloads**: `router.reload({ only: ['prop1', 'prop2'] })`
- **Vue 3 - defineEmits**: TypeScript type-safe event emitters

---

**Conclusión**: Ambos problemas se solucionaron corrigiendo:
1. La verificación de roles para incluir `super-admin`
2. El nombre del parámetro de ruta para ser consistente
3. Los emits de Vue para actualizar correctamente la vista
4. El flujo de Inertia con `router.reload()` parcial
