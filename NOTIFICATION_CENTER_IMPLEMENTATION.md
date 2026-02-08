# Centro de Notificaciones - Sistema de Notificaciones en Tiempo Real

## 📋 Resumen de Implementación

Se ha implementado un **Centro de Notificaciones** completo y en tiempo real para el sistema TrainingMS, que permite a los usuarios de organizaciones y centros de trabajo recibir notificaciones cuando se completan evaluaciones en línea.

## 🎯 Características Principales

### ✅ Notificaciones Persistentes
- **Base de datos**: Las notificaciones se almacenan en la tabla `notifications` de Laravel
- **Tiempo real**: Uso de Laravel Echo + Reverb para entrega instantánea
- **Persistencia**: Las notificaciones permanecen hasta que el usuario las elimine

### ✅ UI/UX Moderno
- **Bell icon con badge**: Indicador visual del número de notificaciones sin leer
- **Dropdown panel**: Panel desplegable limpio y moderno con las notificaciones
- **Indicadores visuales**: Notificaciones sin leer destacadas en azul
- **Acciones rápidas**: Marcar como leída o eliminar con un clic
- **Paginación**: Carga de más notificaciones sin recargar la página

### ✅ Experiencia de Usuario Optimizada
- **Notificaciones del navegador**: Soporte para notificaciones nativas del navegador
- **Tiempo relativo**: Muestra "hace 5 minutos" en lugar de timestamp absoluto
- **Confirmación de acciones**: Confirmación antes de eliminar notificaciones
- **Responsive**: Funciona perfectamente en móvil, tablet y desktop
- **Accesibilidad**: ARIA labels, navegación por teclado, screen reader friendly

## 🏗️ Arquitectura Técnica

### Backend (Laravel)
```
app/
├── Notifications/
│   └── EvaluationCompletedNotification.php  # Clase de notificación
├── Jobs/
│   └── ProcessOnlineEvaluation.php          # Integrado con notificaciones
├── Http/Controllers/
│   └── NotificationController.php           # API de notificaciones
```

**Canales de entrega:**
- `database`: Almacenamiento persistente
- `broadcast`: Entrega en tiempo real vía Reverb

### Frontend (Vue 3 + Inertia.js)
```
resources/js/
├── Components/
│   └── NotificationCenter.vue               # Componente UI principal
├── composables/
│   └── useNotificationCenter.ts             # Lógica reutilizable
└── Layouts/
    └── Dashboard.vue                        # Integrado en layout
```

## 📡 Flujo de Notificaciones

### 1. Creación de Notificación
```php
// Cuando se completa una evaluación en ProcessOnlineEvaluation Job
Notification::send($users, new EvaluationCompletedNotification(
    folio: $folio,
    personalId: $personalId,
    organizationId: $organizationId,
    workCenterId: $workCenterId,
    organizationName: $organizationName,
    workCenterName: $workCenterName
));
```

### 2. Almacenamiento en BD
- Se guarda en tabla `notifications`
- Contiene: tipo, datos, usuario_id, timestamp, read_at

### 3. Broadcast en Tiempo Real
```javascript
// Laravel Reverb envía el evento a:
private-App.Models.User.{userId}
```

### 4. Recepción en Frontend
```typescript
// Echo escucha el canal privado del usuario
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        // Actualiza UI instantáneamente
        // Muestra notificación del navegador
    })
```

## 🔗 API Endpoints

### GET `/notifications`
Lista las notificaciones del usuario autenticado
```json
{
    "notifications": [...],
    "unread_count": 5,
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42
    }
}
```

### GET `/notifications/unread-count`
Obtiene el contador de notificaciones sin leer
```json
{
    "unread_count": 5
}
```

### POST `/notifications/{id}/read`
Marca una notificación como leída

### POST `/notifications/read-all`
Marca todas las notificaciones como leídas

### DELETE `/notifications/{id}`
Elimina una notificación específica

### DELETE `/notifications`
Elimina todas las notificaciones del usuario

## 🎨 Componentes UI

### NotificationCenter.vue
Componente principal con:
- **Bell icon** con badge contador
- **Dropdown panel** con lista de notificaciones
- **Estados**: Loading, Empty, Listado
- **Acciones**: Marcar como leída, Eliminar, Cargar más

### Características UI/UX:
- ✅ **Indicador visual** de notificaciones sin leer (fondo azul)
- ✅ **Barra lateral azul** en notificaciones sin leer
- ✅ **Hover actions** para marcar como leída o eliminar
- ✅ **Confirmación** antes de eliminar
- ✅ **Click outside** para cerrar panel
- ✅ **Tiempo relativo** humanizado
- ✅ **line-clamp** para textos largos
- ✅ **Transitions** suaves de entrada/salida

## 👥 Usuarios Notificados

### Lógica de Notificación:
1. **Si existe work_center_id**:
   - Usuarios del centro de trabajo
   - Administradores de la organización
   
2. **Si solo existe organization_id**:
   - Todos los usuarios de la organización

## 🔐 Seguridad

- ✅ **Autenticación requerida**: Todas las rutas protegidas con middleware `auth`
- ✅ **Autorización**: Usuarios solo acceden a sus propias notificaciones
- ✅ **Validación**: IDs validados antes de operaciones
- ✅ **Canales privados**: Broadcasting solo a usuarios autorizados

## 🚀 Cómo Usar

### 1. Configurar Reverb
Asegurarse de que Laravel Reverb esté corriendo:
```bash
php artisan reverb:start
```

### 2. Variables de Entorno
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 3. Frontend Build
```bash
npm run dev
# o para producción
npm run build
```

### 4. Permisos del Navegador
El sistema solicitará permiso para mostrar notificaciones del navegador automáticamente.

## 📊 Eventos que Generan Notificaciones

Actualmente implementado:
- ✅ **Evaluación en línea completada** (`evaluation_completed`)

Fácilmente extensible para:
- 📝 Evaluación editada
- 🗑️ Evaluación eliminada
- 📊 Reporte generado
- 👥 Usuario añadido a organización
- 🏢 Datos de organización actualizados
- ... y cualquier otro evento del sistema

## 🧪 Testing

### Probar Notificación Manual
```php
use App\Notifications\EvaluationCompletedNotification;
use App\Models\User;

$user = User::find(1);
$user->notify(new EvaluationCompletedNotification(
    folio: 'TEST-001-0001',
    personalId: '0001',
    organizationId: '...',
    organizationName: 'Test Org'
));
```

### Probar en Tinker
```bash
php artisan tinker
```
```php
$user = User::first();
$user->notify(new \App\Notifications\EvaluationCompletedNotification(
    'TEST-001-0001', '0001', null, null, 'Test Organization', null
));
```

## 📝 Notas de Desarrollo

### Composable Pattern
Se utiliza `useNotificationCenter` como composable reutilizable:
- Estado global compartido
- Lifecycle hooks (onMounted, onUnmounted)
- Funciones reactivas
- Integración con Echo

### TypeScript
Tipos definidos para:
- Notification interface
- Pagination interface
- Return types
- Event handlers

### Performance
- **Paginación**: Solo carga 15 notificaciones a la vez
- **Lazy loading**: Más notificaciones se cargan bajo demanda
- **Optimistic updates**: UI actualizada antes de respuesta del servidor
- **Debouncing**: Evita múltiples llamadas simultáneas

## 🔄 Próximas Mejoras Potenciales

- [ ] Filtros por tipo de notificación
- [ ] Búsqueda en notificaciones
- [ ] Preferencias de notificación por usuario
- [ ] Notificaciones por email (ya preparado en backend)
- [ ] Sonido al recibir notificación
- [ ] Agrupar notificaciones similares
- [ ] Marcar como no leída
- [ ] Vista de notificación completa (modal)

## 📚 Referencias

- [Laravel Notifications](https://laravel.com/docs/11.x/notifications)
- [Laravel Reverb](https://laravel.com/docs/11.x/reverb)
- [Laravel Echo](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
- [Inertia.js v2](https://inertiajs.com/)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

---

**Implementado**: 2026-02-08  
**Branch**: `feature/notification-center`  
**Status**: ✅ Completado y funcional
