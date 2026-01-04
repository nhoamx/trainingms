# Análisis del Sistema de Audio en Evaluaciones

## Estado Actual

### Cómo Funciona Actualmente

#### 1. **Generación de URLs de Audio en las Páginas (Take.vue, TakeCisneros.vue, TakeReduced.vue)**

Cada página de quiz genera las URLs de audio usando un `computed` property:

```javascript
const audioUrls = computed(() => {
    const urls = {};
    const exampleUrl = '/assets/audios/example.mpeg'; // o '/storage/audio/example.mpeg'
    
    // Generar URLs para cada pregunta
    const generalQuestions = props.quiz?.questions?.general || {};
    Object.keys(generalQuestions).forEach((key, idx) => {
        urls[`general_${key}`] = exampleUrl;
    });
    
    // Fallback para índices simples
    for (let i = 0; i < 100; i++) {
        urls[i] = exampleUrl;
    }
    
    return urls;
});
```

#### 2. **Flujo de Datos**

```
Quiz Data (from Backend)
    ↓
audioUrls computed property (genera URLs hardcodeadas)
    ↓
Props audioUrls → Componentes (TraumaticEventsSection, GeneralQuestionsSection, etc.)
    ↓
AudioPlayer.vue (reproduce el audio)
```

#### 3. **Componente AudioPlayer.vue**

El reproductor es simple y funcional:
- **Play/Pause**: Inicia o pausa la reproducción
- **Reset**: Reinicia el audio desde el inicio
- **Stop**: Detiene y resetea la posición
- **Audio HTML5**: Usa el elemento `<audio>` nativo del navegador

```vue
<audio
    ref="audioElement"
    :src="audioUrl"
    @play="isPlaying = true"
    @pause="isPlaying = false"
></audio>
```

#### 4. **Inconsistencias Actuales**

| Archivo | Ruta de Audio | Estructura |
|---------|---------------|-----------|
| `Take.vue` | `/assets/audios/example.mpeg` | Genera para keys + índices |
| `TakeCisneros.vue` | `/storage/audio/example.mpeg` | Genera para keys + índices |
| `TakeReduced.vue` | `/storage/audio/example.mpeg` | Genera para índices solamente |

---

## Problemas Identificados

### 1. **Duplicación de Código**
- La lógica `audioUrls` se repite en 3 archivos de página
- Cada uno implementa la generación de manera ligeramente diferente

### 2. **URLs Hardcodeadas**
- Todas las preguntas comparten el mismo archivo de audio de ejemplo
- No hay forma de tener audios específicos por pregunta
- Las rutas varían entre `/assets/audios/` y `/storage/audio/`

### 3. **Sin Validación**
- No se valida si los archivos de audio existen antes de intentar reproducir
- No hay manejo de errores si falla la carga del audio
- No hay feedback al usuario sobre problemas de carga

### 4. **Sin Gestión de Audio desde Backend**
- Los audios no son enviados por el servidor
- No hay base de datos de audios asociados a preguntas
- Imposible personalizar audios por organización o evaluación

### 5. **Componentes sin Composables**
- Los componentes que usan audio (TraumaticEventsSection, GeneralQuestionsSection, etc.) 
  reciben `audioUrls` como props
- No hay lógica centralizada para resolver URLs de audio

---

## Propuestas de Mejora

### Mejora 1: Crear un Composable `useAudioUrls` (Corto Plazo)

**Objetivo**: Centralizar la lógica de generación de URLs

```javascript
// composables/useAudioUrls.js
import { computed } from 'vue';

export function useAudioUrls(quiz) {
    return computed(() => {
        const urls = {};
        const baseUrl = '/storage/audio';
        
        // Generar URLs basadas en la estructura del quiz
        const generalQuestions = quiz.questions?.general || {};
        Object.keys(generalQuestions).forEach((key) => {
            urls[key] = `${baseUrl}/general/${key}.mp3`;
        });
        
        const traumaticQuestions = quiz.questions?.acontecimientos_traumaticos?.questions || [];
        traumaticQuestions.forEach((_, idx) => {
            urls[`traumatic_${idx}`] = `${baseUrl}/traumatic/${idx}.mp3`;
        });
        
        return urls;
    });
}
```

**Uso en las páginas**:
```javascript
import { useAudioUrls } from '@/composables/useAudioUrls';

const audioUrls = useAudioUrls(props.quiz);
```

**Ventajas**:
- ✅ Código DRY (Don't Repeat Yourself)
- ✅ Fácil de mantener y actualizar
- ✅ Consistencia en todas las páginas
- ✅ Base para cambios futuros

---

### Mejora 2: Enviar URLs desde el Backend (Medio Plazo)

**Cambio en el Controlador**:

```php
// QuizController.php
public function show(Quiz $quiz)
{
    return Inertia::render('Quiz/Take', [
        'quiz' => $quiz->load([
            'questions',
            'referenceI',
            'referenciaV',
            'customFields',
            'audioTracks' // Nueva relación
        ])->append([
            'formatted_audio_urls' // Nuevo atributo
        ])
    ]);
}
```

**Modelo con Atributo**:

```php
// Models/Quiz.php
protected $appends = ['formatted_audio_urls'];

public function getFormattedAudioUrlsAttribute()
{
    $urls = [];
    
    // Para preguntas generales
    foreach ($this->questions['general'] ?? [] as $key => $question) {
        $urls[$key] = route('quiz.audio', [
            'quiz' => $this->id,
            'question_type' => 'general',
            'question_key' => $key
        ]);
    }
    
    // Para eventos traumáticos
    foreach ($this->questions['acontecimientos_traumaticos']['questions'] ?? [] as $idx => $question) {
        $urls["traumatic_{$idx}"] = route('quiz.audio', [
            'quiz' => $this->id,
            'question_type' => 'traumatic',
            'question_index' => $idx
        ]);
    }
    
    return $urls;
}
```

**Ventajas**:
- ✅ Backend controla las URLs
- ✅ Permite audios específicos por pregunta
- ✅ Fácil de cambiar rutas sin tocar frontend
- ✅ Posibilidad de control de acceso (permisos)
- ✅ Mejor para audios almacenados en base de datos o S3

---

### Mejora 3: Componente de Reproductor Mejorado

**Enhancements**:

```vue
<!-- AudioPlayer.vue Mejorado -->
<template>
    <div v-if="audioUrl" class="audio-player">
        <!-- Indicador de carga -->
        <div v-if="isLoading" class="animate-pulse">
            <span class="text-xs text-gray-500">Cargando audio...</span>
        </div>
        
        <!-- Indicador de error -->
        <div v-if="hasError" class="text-xs text-red-500">
            <span>Error al cargar audio</span>
        </div>
        
        <!-- Controles normales si no hay error -->
        <div v-if="!hasError" class="flex items-center gap-2">
            <!-- Play/Pause, Reset, Stop buttons... -->
        </div>
        
        <!-- Barra de progreso -->
        <input
            v-if="!hasError"
            type="range"
            :value="currentTime"
            :max="duration"
            @input="seek"
            class="w-full h-1 cursor-pointer"
        >
        
        <audio
            ref="audioElement"
            :src="audioUrl"
            @play="isPlaying = true"
            @pause="isPlaying = false"
            @loadstart="isLoading = true"
            @canplay="isLoading = false"
            @error="hasError = true"
            @timeupdate="currentTime = audioElement?.currentTime || 0"
            @loadedmetadata="duration = audioElement?.duration || 0"
        ></audio>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    audioUrl: String
});

const audioElement = ref(null);
const isPlaying = ref(false);
const isLoading = ref(false);
const hasError = ref(false);
const currentTime = ref(0);
const duration = ref(0);

// Métodos de control...
const seek = (e) => {
    if (audioElement.value) {
        audioElement.value.currentTime = e.target.value;
    }
};
</script>
```

**Ventajas**:
- ✅ Feedback visual de carga
- ✅ Manejo de errores
- ✅ Barra de progreso interactiva
- ✅ Mejor UX

---

### Mejora 4: Composable para Gestión de Audios

```javascript
// composables/useQuizAudio.js
import { ref, computed } from 'vue';

export function useQuizAudio(quiz) {
    const audioUrls = computed(() => {
        return quiz.formatted_audio_urls || {};
    });
    
    const loadedAudios = ref({});
    const failedAudios = ref({});
    
    const preloadAudios = async () => {
        // Precargar todos los audios para mejor performance
        for (const [key, url] of Object.entries(audioUrls.value)) {
            try {
                const audio = new Audio(url);
                await new Promise((resolve, reject) => {
                    audio.oncanplaythrough = resolve;
                    audio.onerror = reject;
                });
                loadedAudios.value[key] = true;
            } catch (error) {
                failedAudios.value[key] = error;
            }
        }
    };
    
    return {
        audioUrls,
        loadedAudios,
        failedAudios,
        preloadAudios
    };
}
```

**Ventajas**:
- ✅ Precargar audios antes de mostrar preguntas
- ✅ Mejor feedback de errores
- ✅ Mejor performance (evita lag al hacer play)

---

## Comparativa de Soluciones

| Aspecto | Estado Actual | Mejora 1 | Mejora 2 | Mejora 3 | Mejora 4 |
|--------|---------------|---------|---------|---------|---------|
| **Duplicación** | ❌ Alta | ✅ Resuelta | ✅ Resuelta | ✅ Mejor | ✅ Mejor |
| **Audios por pregunta** | ❌ No | ❌ No | ✅ Sí | ✅ Mejor | ✅ Sí |
| **Validación** | ❌ No | ❌ No | ❌ No | ✅ Sí | ✅ Sí |
| **Control Backend** | ❌ No | ❌ No | ✅ Sí | ✅ Mejor | ✅ Sí |
| **Dificultad** | - | 🟢 Fácil | 🟡 Media | 🟡 Media | 🔴 Difícil |
| **Impacto** | - | 🟢 Bajo | 🟡 Alto | 🟡 Medio | 🟡 Medio |

---

## Ruta Recomendada

### Fase 1 (Ahora): Mejora 1 + Mejora 3
- Crear `useAudioUrls.js` composable
- Mejorar `AudioPlayer.vue` con manejo de errores y barra de progreso
- **Tiempo**: ~2 horas
- **Impacto**: Mejora inmediata en calidad de código

### Fase 2 (Siguiente): Mejora 2
- Agregar atributo `formatted_audio_urls` al modelo Quiz
- Actualizar controladores para pasar URLs
- **Tiempo**: ~3-4 horas
- **Impacto**: Escalabilidad

### Fase 3 (Futuro): Mejora 4
- Sistema de preocarga de audios
- Gestión avanzada de caché
- **Tiempo**: ~4-5 horas
- **Impacto**: Performance

---

## Conclusión

El sistema actual funciona, pero tiene oportunidades de mejora importantes:

1. **Corto plazo**: Eliminar duplicación con un composable simple
2. **Mediano plazo**: Mover control de URLs al backend
3. **Largo plazo**: Sistema avanzado de gestión de audios

¿Cuál es la siguiente prioridad que quieres atacar?
