# Persistent Notifications System

## Overview

Sistema de notificaciones persistentes que sobreviven a la navegación entre páginas en aplicaciones Inertia.js. Las notificaciones permanecen visibles incluso cuando el usuario cambia de página, lo que es crítico para procesos de largo plazo como el procesamiento de evaluaciones OCR.

## Problema Resuelto

Anteriormente, cuando se iniciaba el procesamiento de una evaluación y el usuario era redirigido al dashboard, las notificaciones desaparecían inmediatamente porque el componente Vue se desmontaba durante la navegación de Inertia.

## Solución Implementada

### 1. Composable de Notificaciones Globales

**Archivo:** `resources/js/composables/useNotifications.ts`

Composable que maneja un estado reactivo global de notificaciones que persiste entre navegaciones de página:

```typescript
const { notifications, success, error, info, warning, processing } = useNotifications()

// Agregar notificación de éxito
success('Operación exitosa', 'Los datos se guardaron correctamente')

// Agregar notificación de error (persistente por defecto)
error('Error crítico', 'No se pudo conectar al servidor')

// Agregar notificación de procesamiento (persistente, sin auto-cerrar)
const processingId = processing('Procesando...', 'Analizando documentos')

// Actualizar notificación existente
updateNotification(processingId, {
    type: 'success',
    title: 'Completado',
    message: 'Documentos procesados exitosamente',
    persistent: false
})
```

**Características:**
- Estado reactivo global que persiste entre navegaciones
- Auto-cerrado configurable con timeout
- Notificaciones persistentes (no se cierran automáticamente)
- Actualización de notificaciones existentes
- Múltiples notificaciones simultáneas

### 2. Componente NotificationStack

**Archivo:** `resources/js/Components/NotificationStack.vue`

Reemplaza el anterior componente `Notification.vue` con soporte para:
- Múltiples notificaciones apiladas
- Transiciones suaves (slide-in/fade-out)
- Integración con Laravel Echo para actualizaciones en tiempo real
- Persistencia entre navegaciones de Inertia

**Tipos de Notificación:**
- `success` - Verde con CheckCircleIcon
- `error` - Rojo con ExclamationCircleIcon
- `warning` - Amarillo con ExclamationTriangleIcon
- `info` - Azul con InformationCircleIcon
- `processing` - Azul con ArrowPathIcon animado (spinner)

### 3. Integración con Laravel Echo

El componente escucha eventos de Laravel Echo y automáticamente:
1. Crea notificación "processing" cuando inicia el job
2. Actualiza la misma notificación con el progreso
3. Convierte a "success" cuando termina exitosamente
4. Convierte a "error" si falla el procesamiento

**Eventos escuchados:**
- Canal: `evaluation-processing` (público) o `evaluation-processing.{userId}` (privado)
- Evento: `.evaluation.status`

**Payload esperado:**
```javascript
{
    status: 'running' | 'finished' | 'error',
    message: string,
    finished: boolean
}
```

## Archivos Modificados

### Creados
1. `resources/js/composables/useNotifications.ts` - Composable de notificaciones
2. `resources/js/Components/NotificationStack.vue` - Componente UI persistente
3. `resources/js/types/global.d.ts` - Declaraciones TypeScript para Echo
4. `tsconfig.json` - Configuración TypeScript

### Modificados
1. `resources/js/Layouts/Dashboard.vue` - Reemplaza `<Notification>` con `<NotificationStack>`
2. `vite.config.js` - Agrega alias `@` para imports TypeScript

## Uso

### En el Layout (Dashboard.vue)

```vue
<template>
    <div>
        <!-- Persistent notification stack - survives page navigation -->
        <NotificationStack :user-id="user?.id" />
        
        <main>
            <slot />
        </main>
    </div>
</template>

<script setup>
import NotificationStack from "../Components/NotificationStack.vue"
import { usePage } from '@inertiajs/vue3'

const user = computed(() => usePage().props.auth.user)
</script>
```

### Desde Cualquier Componente Vue

```vue
<script setup>
import { useNotifications } from '@/composables/useNotifications'

const { success, error, processing, updateNotification } = useNotifications()

async function uploadFile() {
    const notifId = processing('Subiendo archivo', 'Procesando documento PDF...')
    
    try {
        await api.upload(file)
        
        updateNotification(notifId, {
            type: 'success',
            title: 'Archivo subido',
            message: 'El documento se procesó correctamente',
            persistent: false,
            timeout: 5000
        })
    } catch (err) {
        updateNotification(notifId, {
            type: 'error',
            title: 'Error al subir',
            message: err.message,
            persistent: true
        })
    }
}
</script>
```

### Desde Backend (Laravel)

El sistema se integra automáticamente con los eventos de broadcast existentes:

```php
// En ProcessPaperEvaluation.php
broadcast(new EvaluationProcessingStatusChanged(
    'running',
    'Procesando evaluación...',
    false,
    $this->initiatorUserId
));
```

El frontend automáticamente:
1. Detecta el evento
2. Crea/actualiza la notificación correspondiente
3. Mantiene la notificación visible durante navegación
4. Cierra automáticamente al finalizar (success) o deja abierta (error)

## Ventajas

✅ **Persistencia:** Las notificaciones sobreviven a navegación entre páginas  
✅ **Múltiples notificaciones:** Stack de notificaciones apiladas  
✅ **Actualización en tiempo real:** Integración con Laravel Echo  
✅ **Type-safe:** Completamente tipado con TypeScript  
✅ **Flexible:** Configuración de timeout y persistencia por notificación  
✅ **UX mejorada:** Usuarios no pierden visibilidad del progreso al cambiar de página  

## Configuración TypeScript

El proyecto ahora incluye:

```json
// tsconfig.json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./resources/js/*"]
    }
  }
}
```

```javascript
// vite.config.js
{
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    }
}
```

Esto permite imports limpios:
```typescript
import { useNotifications } from '@/composables/useNotifications'
```

## Testing

Después de la implementación:

```bash
# Build frontend
npm run build

# Verificar TypeScript
npx tsc --noEmit

# Test manual:
# 1. Subir evaluación desde /evaluations/load
# 2. Observar notificación "Procesando..."
# 3. Navegar a /dashboard
# 4. Verificar que la notificación persiste
# 5. Esperar completación
# 6. Verificar cambio a notificación "success"
```

## Migración desde Notification.vue

El componente anterior `Notification.vue` mostraba una sola notificación y se desmontaba con la navegación. El nuevo sistema:

- Soporta múltiples notificaciones simultáneas
- Persiste entre navegaciones de Inertia
- Mantiene estado reactivo global
- Auto-actualiza notificaciones de procesos largos

**Cambio mínimo requerido:** Solo reemplazar el import en el layout.

## Próximos Pasos

Posibles mejoras futuras:
- [ ] Persistencia en localStorage para sobrevivir refresh de página
- [ ] Sonidos de notificación configurables
- [ ] Agrupación de notificaciones similares
- [ ] Vista de historial de notificaciones
- [ ] Acciones personalizadas en notificaciones (botones CTA)
