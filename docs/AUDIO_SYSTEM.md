# Sistema de Audio para Evaluaciones

## Descripción General

El sistema de audio permite reproducir archivos de audio asociados a preguntas en evaluaciones de quiz. El sistema está diseñado para ser escalable y flexible, permitiendo la personalización por organización en el futuro.

## Arquitectura Actual

### Componentes

```
┌─────────────────────────────────────────┐
│       Páginas de Quiz                   │
│  (Take.vue, TakeCisneros.vue, etc.)    │
└────────────┬────────────────────────────┘
             │ usa
             ↓
┌─────────────────────────────────────────┐
│  Composable: useAudioUrls()             │
│  - Lee estructura de preguntas          │
│  - Genera URLs de audio                 │
└────────────┬────────────────────────────┘
             │ basándose en
             ↓
┌─────────────────────────────────────────┐
│  Configuración: config/audio.php        │
│  - Base URL desde env                   │
│  - Estructura de directorios            │
└────────────┬────────────────────────────┘
             │ inyecta en window
             ↓
┌─────────────────────────────────────────┐
│  window.__AUDIO_BASE_URL                │
│  (desde resources/views/app.blade.php)  │
└────────────┬────────────────────────────┘
             │ usado por
             ↓
┌─────────────────────────────────────────┐
│  Componentes de Sección                 │
│  (GeneralQuestionsSection, etc.)        │
└────────────┬────────────────────────────┘
             │ pasan a
             ↓
┌─────────────────────────────────────────┐
│  AudioPlayer.vue                        │
│  - Reproduce el audio                   │
│  - Maneja errores                       │
└─────────────────────────────────────────┘
```

### Flujo de Datos

1. **Quiz Page** recibe datos del backend (estructura de preguntas)
2. **useAudioUrls()** composable lee la estructura y genera URLs
3. **Configuración** desde `config/audio.php` define la ruta base
4. **AudioPlayer** intenta reproducir el audio
5. **Error Handling** muestra indicadores visuales si falla

## Configuración

### Variables de Entorno

Agregar al archivo `.env`:

```bash
# Base URL donde están almacenados los archivos de audio
AUDIO_BASE_URL=/storage/audio

# Disco de almacenamiento (public, s3, etc.)
AUDIO_STORAGE_DISK=public

# Habilitar/deshabilitar audios
AUDIO_ENABLED=true

# Comportamiento cuando no encuentra el archivo
# Opciones: 'silent' (no mostrar nada), 'error' (mostrar error)
AUDIO_FALLBACK_MODE=silent
```

### Archivos de Configuración

**config/audio.php**: Definición completa de la configuración

```php
return [
    'base_url' => env('AUDIO_BASE_URL', '/storage/audio'),
    'storage_disk' => env('AUDIO_STORAGE_DISK', 'public'),
    'enabled' => env('AUDIO_ENABLED', true),
    'fallback_mode' => env('AUDIO_FALLBACK_MODE', 'silent'),
    // ...
];
```

## Estructura de Archivos de Audio

Los archivos de audio se deben organizar de la siguiente manera:

```
storage/app/public/audio/
├── general/
│   ├── 0.mp3         # Primera pregunta
│   ├── 1.mp3         # Segunda pregunta
│   └── 2.mp3         # Tercera pregunta
├── conditional/
│   ├── 0.mp3         # Primera pregunta condicional
│   ├── 1.mp3         # Segunda pregunta condicional
│   └── 2.mp3         # Tercera pregunta condicional
├── traumatic/
│   ├── 0.mp3         # Primer evento traumático
│   ├── 1.mp3         # Segundo evento
│   └── 2.mp3         # Tercer evento
├── cisneros/
│   ├── 0.mp3         # Primera pregunta
│   ├── 1.mp3         # Segunda pregunta
│   └── 2.mp3         # Tercera pregunta
└── referencia_i/
    ├── 0.mp3         # Primera pregunta de seguimiento
    ├── 1.mp3         # Segunda pregunta de seguimiento
    └── 2.mp3         # Tercera pregunta de seguimiento
```

### Patrón de Nomenclatura

**Todos los tipos usan índices numéricos**: `{index}.mp3`

- **General (Referencia III)**: `0.mp3`, `1.mp3`, `2.mp3`, etc.
- **Traumatic (Eventos)**: `0.mp3`, `1.mp3`, `2.mp3`, etc.
- **Cisneros (Escala)**: `0.mp3`, `1.mp3`, `2.mp3`, etc.
- **Conditional (Preguntas condicionales)**: `0.mp3`, `1.mp3`, `2.mp3`, etc.
- **Referencia I (Preguntas de seguimiento)**: `0.mp3`, `1.mp3`, `2.mp3`, etc.

**Importante**: Los índices empiezan en **0**, no en 1

**Ejemplo**: El audio de la pregunta número 3 de "general" es: `storage/app/public/audio/general/2.mp3` (índice 2)

## Uso en Componentes

### En Páginas de Quiz

```javascript
import { useAudioUrls } from '@/composables/useAudioUrls';

export default {
    setup(props) {
        // Genera automáticamente las URLs basándose en la estructura de preguntas
        const audioUrls = useAudioUrls(props.quiz);
        
        return {
            audioUrls
        };
    }
}
```

### En Componentes de Sección

```vue
<template>
    <GeneralQuestionsSection
        :audio-urls="audioUrls"
        :paginatedQuestions="paginatedQuestions"
        v-model="answers"
    />
</template>
```

### En AudioPlayer

```vue
<template>
    <AudioPlayer :audio-url="audioUrls[questionId]" />
</template>
```

## Mejoras Implementadas (Enero 2026)

### 1. Composable useAudioUrls.js ✅

**Eliminó la duplicación de código** en los 3 archivos de página:

```javascript
// Antes: Cada página tenía su propia lógica
const audioUrls = computed(() => {
    const urls = {};
    const exampleUrl = '/storage/audio/example.mpeg';
    // ... lógica repetida ...
    return urls;
});

// Ahora: Centralizado en un composable
const audioUrls = useAudioUrls(props.quiz);
```

**Beneficios**:
- ✅ DRY principle
- ✅ Mantenimiento más sencillo
- ✅ Consistencia garantizada
- ✅ Base para cambios futuros

### 2. Archivo de Configuración config/audio.php ✅

**Centraliza configuración** del sistema de audio:

```php
config('audio.base_url')        // '/storage/audio'
config('audio.enabled')          // true/false
config('audio.fallback_mode')    // 'silent', 'error', 'default'
```

**Beneficios**:
- ✅ Gestión centralizada
- ✅ Fácil cambio de URLs
- ✅ Soporte para diferentes discos (local, S3, etc.)
- ✅ Variables de entorno

### 3. AudioPlayer.vue Mejorado ✅

**Mejoras sin cambiar diseño**:

```javascript
// Estados adicionales
const isLoading = ref(false);      // Indicador de carga
const hasError = ref(false);       // Indicador de error
```

**Features**:
- ✅ Indicador de carga con spinner
- ✅ Indicador visual de error (icono rojo pequeño)
- ✅ Botones deshabilitados durante carga/error
- ✅ Manejo de eventos de audio completo
- ✅ Logs de error en console para debugging

**Eventos manejados**:
- `loadstart` - Inicia carga
- `canplay` - Audio listo
- `error` - Error al cargar
- `play` / `pause` - Control de reproducción
- `ended` - Fin de reproducción

### 4. Inyección de Configuración en Frontend ✅

**Desde resources/views/app.blade.php**:

```html
<script>
    window.__AUDIO_BASE_URL = '{{ config("audio.base_url") }}';
    window.__AUDIO_ENABLED = {{ config("audio.enabled") ? "true" : "false" }};
</script>
```

**Beneficios**:
- ✅ Configuración dinámica desde backend
- ✅ Sin hardcodeo en frontend
- ✅ Fácil cambio sin rebuild

## Próximos Pasos (Plan de Mejora)

### Fase 2: Backend-Driven URLs (GitHub Issue: AUDIO_BACKEND_MIGRATION.md)

- [ ] Crear modelos `AudioTrack` y `QuestionAudio`
- [ ] Implementar API de upload/gestión
- [ ] Migrar lógica de URLs al backend
- [ ] Soportar audio por organización
- [ ] Crear UI de gestión

### Fase 3: Optimizaciones

- [ ] Precargar audios en background
- [ ] Caché de URLs con quiz
- [ ] Soporte para CDN
- [ ] Compresión de audio

## Troubleshooting

### El audio no se reproduce

1. **Verificar archivo existe**:
   ```bash
   ls -la storage/app/public/audio/{tipo}/{id}.mp3
   ```

2. **Verificar configuración**:
   ```php
   php artisan tinker
   config('audio.base_url')  // Debe retornar '/storage/audio'
   ```

3. **Revisar consola del navegador**:
   - DevTools → Console
   - Buscar errores relacionados a audio
   - Verificar URL en Network tab

4. **Verificar permisos**:
   ```bash
   chmod -R 755 storage/app/public/audio
   php artisan storage:link  # Si no existe el symlink
   ```

### El AudioPlayer muestra error rojo

- Revisa la consola (F12) para el mensaje de error específico
- Verifica que la URL del archivo sea correcta
- Asegúrate que el archivo existe y es accesible
- Comprueba el tipo MIME (mp3, wav, ogg, m4a)

### Audios en desarrollo vs producción

**Desarrollo**:
```bash
AUDIO_BASE_URL=/storage/audio
php artisan storage:link  # Crea symlink
```

**Producción** (S3):
```bash
AUDIO_BASE_URL=https://s3.amazonaws.com/bucket/audio
AUDIO_STORAGE_DISK=s3
```

## Testing

Para probar el sistema de audio:

```bash
# 1. Crear archivo de prueba
php artisan tinker
$path = storage_path('app/public/audio/general/test.mp3');
// Copiar un archivo mp3 a esa ruta

# 2. Visitar una evaluación
# 3. Abrir DevTools → Network
# 4. Hacer click en reproducir
# 5. Verificar que la request se hace a la URL correcta
# 6. Escuchar el audio
```

## Referencias

- Configuración: `config/audio.php`
- Composable: `resources/js/composables/useAudioUrls.js`
- Componente: `resources/js/Components/Quiz/AudioPlayer.vue`
- Vista: `resources/views/app.blade.php`
- Variables de entorno: `.env.example`
- Plan futuro: `.github/AUDIO_BACKEND_MIGRATION.md`

## Notas

**Fecha de implementación**: Enero 4, 2026

**Estado**: ✅ Completado (Mejoras 1, 2, 3)

**Próxima fase**: Backend-driven audio system (ver AUDIO_BACKEND_MIGRATION.md)
