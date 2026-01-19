# Solución: Autorización de Reportes PDF - Admin y SuperAdmin

## Problema Identificado

Al intentar descargar reportes PDF (demografico, diagnostico, ejecutivo) como **admin o super-admin**, recibías un error **403 Unauthorized** aunque tenías permisos suficientes.

### Causa Raíz

La verificación de autorización en `ReportPdfController.php` usaba una lógica **demasiado restrictiva**:

```php
// ❌ ANTES - Solo permitía usuarios con esa organización asignada
$isOwnOrganization = $user->organization_id === $organizationId;

if (! $isOwnOrganization) {
    return response()->json(['error' => 'No autorizado...'], 403);
}
```

El problema: Los **admins y superadmins** no tienen una `organization_id` asignada (es NULL), por lo que siempre fallaban en esta verificación.

---

## Solución Implementada

### 1. Usar la Política Existente `OrganizationPolicy`

La aplicación ya tenía una política que manejaba correctamente los roles:

```php
// ✅ OrganizationPolicy.php - Ya existía y funciona correctamente
if ($user->hasRole(['admin', 'super-admin'])) {
    return true;  // ✅ Permite admins/superadmins
}

// Solo usuarios de organización pueden ver su propia organización
return $user->hasRole('organization') && $user->organization_id === $organization->id;
```

### 2. Actualizar ReportPdfController

Se cambió la verificación manual por usar la política:

```php
// ✅ DESPUÉS - Usar la política de autorización
$organization = Organization::findOrFail($organizationId);

if (! $request->user()->can('viewOrganizationResults', $organization)) {
    return response()->json(['error' => 'No autorizado...'], 403);
}
```

### 3. Métodos Actualizados

Los siguientes métodos fueron actualizados para usar `OrganizationPolicy`:

- `downloadDemographicReport()` - Reportes PDF demográficos
- `downloadDiagnosticReport()` - Reportes PDF de diagnóstico  
- `downloadExecutiveReport()` - Reportes PDF ejecutivos
- `initiateWordReportGeneration()` - Generación de reportes Word
- `downloadExcelReport()` - Descarga de Excel con datos
- `downloadNom002Report()` - Reportes NOM-002 (incendios)

---

## Comportamiento Después de la Solución

| Rol | Acceso a Reportes |
|-----|-------------------|
| **Admin** | ✅ Puede descargar de **cualquier** organización |
| **Super-Admin** | ✅ Puede descargar de **cualquier** organización |
| **Organization (con org asignada)** | ✅ Solo de su propia organización |
| **Organization (sin org)** | ❌ Sin acceso |
| **Unauthenticated** | ❌ Sin acceso |

---

## Verificación

Se creó una suite de tests completa en `tests/Feature/ReportPdfAuthorizationTest.php` que verifica:

✅ Admins pueden descargar reportes de cualquier organización  
✅ SuperAdmins pueden descargar reportes de cualquier organización  
✅ Usuarios de organización solo pueden ver su propia organización  
✅ Usuarios no autorizados reciben 403  
✅ Usuarios sin autenticar no pueden acceder  

**Todos los tests pasan correctamente.**

---

## Archivos Modificados

1. **app/Http/Controllers/ReportPdfController.php**
   - 6 métodos actualizados para usar `OrganizationPolicy`
   - Código formateado con Laravel Pint

2. **tests/Feature/ReportPdfAuthorizationTest.php** (nuevo)
   - Suite completa de tests de autorización
   - 8 test cases cubriendo todos los escenarios

---

## Cómo Probar

Para verificar que funciona:

```bash
# 1. Ejecutar los tests
php artisan test tests/Feature/ReportPdfAuthorizationTest.php

# 2. En la UI, como admin/superadmin, deberías poder:
# - Navegar a cualquier organización
# - Descargar reportes PDF sin error 403
# - Descargar reportes Word sin error 403
# - Descargar Excel sin error 403
```

---

## Ventajas de Esta Solución

✅ **Reutiliza código existente** - Usa la política ya definida  
✅ **Consistente** - Mismo patrón de autorización en toda la app  
✅ **Mantenible** - Cambios en permisos solo en un lugar  
✅ **Segura** - Mantiene restricciones para usuarios normales  
✅ **Testeada** - Cobertura completa de casos de uso  
