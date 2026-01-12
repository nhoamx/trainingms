# Sistema de Videos - Guía de Implementación

## Resumen

Se ha implementado un sistema completo de videos siguiendo el mismo patrón que el sistema de audios existente. Los videos se muestran en un modal al hacer clic en el botón "Video" junto a cada pregunta.

## Archivos Creados

### 1. Configuración
- **`config/video.php`**: Configuración de videos similar a `audio.php`
  - URL base: `/storage/video` (configurable vía `VIDEO_BASE_URL`)
  - Formatos soportados: mp4, webm, mov, avi
  - Tamaño máximo: 50MB
  - Organización por tipo de pregunta

### 2. Composable
- **`resources/js/composables/useVideoUrls.js`**: Genera URLs de videos
  - Misma lógica que `useAudioUrls`
  - Mapea preguntas a archivos de video por índice
  - Soporta todos los tipos de preguntas: general, conditional, traumatic, cisneros, referencia_i

### 3. Componente VideoPlayer
- **`resources/js/Components/Quiz/VideoPlayer.vue`**: Reproductor de video con modal
  - Botón morado "Video" junto al botón de audio
  - Modal con video en formato 16:9
  - Controles nativos del navegador
  - Opción de pantalla completa
  - Loading state y manejo de errores
  - Cierre con ESC o botón X
  - Teleport para renderizar fuera del flujo normal

## Archivos Modificados

### Componentes de Preguntas (agregado VideoPlayer)
1. **`GeneralQuestionsSection.vue`**
2. **`TraumaticEventsSection.vue`**
3. **`ConditionalQuestionsSection.vue`**
4. **`EscalaCisneros.vue`**
5. **`FollowUpQuestionsSection.vue`**

Cambios en cada componente:
- Import del componente `VideoPlayer`
- Prop `videoUrls` agregado
- Función `getVideoUrl()` para obtener URL de video
- VideoPlayer agregado junto al AudioPlayer en el template
- Layout cambiado de `w-48` a `flex gap-2` para acomodar ambos botones

### Vistas Principales
1. **`Take.vue`** - Quiz completo
2. **`TakeReduced.vue`** - Quiz reducido
3. **`TakeCisneros.vue`** - Escala Cisneros

Cambios:
- Import de `useVideoUrls` composable
- Declaración de `videoUrls` usando el composable
- Prop `:video-urls` pasado a todos los componentes de preguntas

### Vista Base
- **`resources/views/app.blade.php`**: Inyección de configuración de videos
  - Variables globales: `__VIDEO_BASE_URL` y `__VIDEO_ENABLED`

## Estructura de Archivos de Video

Los videos deben organizarse en `storage/app/public/video/` (o donde apunte `VIDEO_BASE_URL`):

```
storage/app/public/video/
├── general/
│   ├── 0.mp4
│   ├── 1.mp4
│   ├── 2.mp4
│   └── ...
├── conditional/
│   ├── 0.mp4
│   ├── 1.mp4
│   └── ...
├── traumatic/
│   ├── 0.mp4
│   ├── 1.mp4
│   └── ...
├── cisneros/
│   ├── 0.mp4
│   ├── 1.mp4
│   └── ...
└── referencia_i/
    ├── 0.mp4
    ├── 1.mp4
    └── ...
```

## Uso

### Para subir videos:

1. **Via storage public disk:**
   ```php
   Storage::disk('public')->put('video/general/0.mp4', $videoFile);
   ```

2. **Via filesystem manual:**
   ```bash
   # Copiar videos a storage/app/public/video/
   cp video.mp4 storage/app/public/video/general/0.mp4
   
   # Crear symlink si no existe
   php artisan storage:link
   ```

### Nombrado de archivos:

- **Preguntas generales**: `0.mp4`, `1.mp4`, `2.mp4`... (índice numérico)
- **Preguntas condicionales**: `0.mp4`, `1.mp4`, `2.mp4`... (índice secuencial)
- **Acontecimientos traumáticos**: `0.mp4`, `1.mp4`... (índice de pregunta)
- **Escala Cisneros**: `0.mp4`, `1.mp4`... (índice de pregunta)
- **Referencia I**: `0.mp4`, `1.mp4`... (índice de pregunta)

## Variables de Entorno

Agregar al `.env`:

```env
# Video Configuration
VIDEO_BASE_URL=/storage/video
VIDEO_ENABLED=true
VIDEO_STORAGE_DISK=public
```

## Características del VideoPlayer

- ✅ Modal centrado con fondo oscuro
- ✅ Video responsive (aspect ratio 16:9)
- ✅ Controles nativos del navegador
- ✅ Botón de pantalla completa
- ✅ Cierre con ESC o botón X
- ✅ Loading state mientras carga
- ✅ Manejo de errores si falla el video
- ✅ Video no se descarga automáticamente (usa `controlsList="nodownload"`)
- ✅ El video puede reproducirse mientras el usuario responde preguntas

## Diferencias con AudioPlayer

| Característica | Audio | Video |
|----------------|-------|-------|
| Presentación | Inline player | Modal |
| Tamaño archivo | 5MB max | 50MB max |
| Formatos | mp3, wav, ogg, m4a | mp4, webm, mov, avi |
| Bloqueo de respuestas | Sí (hasta terminar audio) | No |
| Pantalla completa | No | Sí |
| Controles | Personalizados | Nativos del navegador |

## Próximos Pasos (Opcional)

1. **Subir videos de prueba** a `storage/app/public/video/`
2. **Verificar symlink**: `php artisan storage:link`
3. **Probar modal** en los quiz online
4. **Crear comando Artisan** para listar videos disponibles/faltantes
5. **Panel de administración** para subir videos via UI (similar a custom fields manager)
6. **Asociar videos en DB** para soporte multi-idioma y versiones

## Notas

- Los videos son **opcionales** - si no existe video para una pregunta, el botón no se muestra
- El sistema funciona independientemente del sistema de audios
- Los videos pueden usarse junto con audios en la misma pregunta
- El modal usa Teleport para evitar problemas de z-index
- Compatible con todos los navegadores modernos
