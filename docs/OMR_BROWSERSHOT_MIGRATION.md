# Migración OMR Templates de DomPDF a Browsershot

## Resumen

Este documento describe la migración de la generación de PDFs de formularios OMR (Optical Mark Recognition) desde **DomPDF** a **Spatie Browsershot** (headless Chrome/Puppeteer).

## Fecha de Migración
Septiembre 29, 2025

## Razón de la Migración

DomPDF tiene varias limitaciones que afectaban la calidad de las hojas OMR:
- Soporte limitado para Flexbox y CSS moderno
- Renderizado inconsistente de marcadores de alineación
- Dificultad para controlar precisión de posicionamiento para detección óptica
- Problemas con bordes y círculos perfectos necesarios para OMR

**Browsershot** utiliza headless Chrome vía Puppeteer, proporcionando:
- Renderizado idéntico al navegador real
- Soporte completo para CSS3 y Flexbox
- Precisión milimétrica en posicionamiento
- Círculos perfectamente redondos para burbujas OMR
- Mejor control sobre márgenes y tamaño de página

## Cambios Realizados

### 1. Instalación de Dependencias

```bash
# Instalar Browsershot
composer require spatie/browsershot

# Instalar Puppeteer (requerido por Browsershot)
npm install puppeteer --save-dev
```

### 2. Actualización del Controller

**Archivo**: `app/Http/Controllers/OMRController.php`

#### Cambios en imports:
```php
// ANTES
use Barryvdh\DomPDF\Facade\Pdf;

// DESPUÉS
use Spatie\Browsershot\Browsershot;
```

#### Actualización del método `generatePdf()`:

**Antes (DomPDF)**:
```php
$pdf = PDF::setOptions([
    'isHtml5ParserEnabled' => true,
    'isRemoteEnabled' => true,
    'defaultFont' => 'Arial',
]);
$pdf->loadHTML($htmlContent);
$pdf->setPaper('letter', 'portrait');
$pdf->setOption('margin-top', 28);
// ...
return $pdf->download($filename);
```

**Después (Browsershot)**:
```php
$browsershot = Browsershot::html($htmlContent)
    ->setOption('landscape', false)
    ->format('Letter')
    ->margins(10, 10, 10, 10) // mm
    ->showBackground()
    ->waitUntilNetworkIdle();

// Configuración especial para WSL/Windows
if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
    $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
    $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
}

$browsershot->save($tempPath);
return response()->download($tempPath)->deleteFileAfterSend(true);
```

### 3. Optimización de Templates OMR

**Archivo**: `resources/views/omr/layout.blade.php`

#### Ajuste de dimensiones para formato Letter:
```css
/* ANTES - Tamaño A4 */
.page {
    width: 210mm;
    min-height: 297mm;
    /* ... */
}

/* DESPUÉS - Tamaño Letter (US) */
.page {
    width: 215.9mm;  /* US Letter width */
    min-height: 279.4mm;  /* US Letter height */
    /* ... */
}
```

#### Optimización de marcadores de alineación:
```css
.alignment-marker {
    position: absolute;
    width: 8mm;
    height: 8mm;
    background: black;
    border-radius: 0; /* Cuadrados perfectos para mejor detección */
}
```

## Estructura de Templates OMR

Los templates OMR están ubicados en `resources/views/omr/`:

1. **layout.blade.php** - Layout base con estilos comunes
2. **referencia-i.blade.php** - Guía de Referencia I (PTSD)
3. **referencia-iii.blade.php** - Guía de Referencia III (Factores psicosociales)
4. **referencia-v.blade.php** - Guía de Referencia V (Datos demográficos)
5. **escala-cisneros.blade.php** - Escala Cisneros (Mobbing)

### Características de los Templates

Todos los templates incluyen:

#### Marcadores de Alineación
- 4 marcadores cuadrados (8mm x 8mm) en las esquinas
- Posicionados a 5mm de los bordes
- Color negro sólido para detección óptica confiable

#### Sección de Folio
- 9 posiciones para dígitos
- Burbujas para dígitos 0-9 en cada posición
- Marcadores de esquina en el bloque de folio

#### Burbujas de Respuesta
- Tamaños variados según densidad de contenido:
  - `bubble`: 4mm × 4mm (estándar)
  - `bubble-small`: 3mm × 3mm (compacto)
  - `bubble-tiny`: 3mm × 3mm con borde 1.5px (muy compacto)
- Todas con `border-radius: 50%` para círculos perfectos
- Clase `.bubble-filled` para pre-marcar respuestas

## Configuración del Sistema

### Requisitos

- **PHP**: 8.2+
- **Node.js**: Instalado en el sistema
- **Puppeteer**: Instalado vía npm
- **Browsershot**: 5.0+

### Entorno WSL/Windows

Para proyectos en WSL con Node.js en Windows:

```php
if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
    $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
    $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
}
```

## Flujo de Generación de PDF

1. Usuario selecciona tipo de evaluación en `Organizations/components/Folios.vue`
2. Usuario selecciona folios (individuales o lote completo)
3. Frontend genera URL: `/omr/{tipo}?download=pdf&folios=0001,0002,...`
4. `OMRController` recibe request y genera HTML para cada folio
5. Browsershot convierte HTML a PDF usando headless Chrome
6. PDF se guarda temporalmente en `storage/app/temp/`
7. PDF se descarga y archivo temporal se elimina automáticamente

## Ventajas de Browsershot

### Calidad de Renderizado
✅ Burbujas perfectamente circulares
✅ Bordes precisos y consistentes
✅ Alineación milimétrica
✅ Marcadores de referencia nítidos

### Desarrollo
✅ Vista previa en navegador = PDF final
✅ CSS moderno (Flexbox, Grid)
✅ DevTools para debugging
✅ No hay "sorpresas" entre preview y PDF

### Detección OMR
✅ Marcadores de alineación precisos
✅ Burbujas uniformes para mejor detección
✅ Posicionamiento consistente
✅ Compatible con pipeline de detección Python

## Testing

### Verificar Templates en Navegador

Accede directamente a los templates para verificar renderizado:

```
https://trainingms.test/omr/referencia-i
https://trainingms.test/omr/referencia-iii
https://trainingms.test/omr/referencia-v
https://trainingms.test/omr/escala-cisneros
```

### Generar PDFs de Prueba

```
https://trainingms.test/omr/referencia-i?download=pdf&folios=0001
https://trainingms.test/omr/referencia-iii?download=pdf&folios=0001,0002
```

### Validación Visual

Para cada template, verificar:

- [ ] Marcadores de alineación en las 4 esquinas
- [ ] Folio visible y correctamente posicionado
- [ ] Burbujas circulares y uniformes
- [ ] Todo el contenido cabe en una página Letter
- [ ] Márgenes de 10mm en todos los lados
- [ ] Texto legible y bien espaciado
- [ ] No hay elementos cortados o sobrepuestos

## Integración con OCR Pipeline

Los PDFs generados deben ser procesados por el pipeline OCR en `docker/`:

1. **PDF → Imágenes**: `pdf_to_image_converter.py`
2. **Detección de alineación**: `alinear_con_marcadores.py`
3. **Detección de burbujas**: `bubble_detector.py`
4. **Procesamiento**: `main.py`

### Ajustes de Detección

Si los marcadores no se detectan correctamente:

1. Verificar tamaño de marcadores (8mm x 8mm)
2. Verificar posición (5mm de bordes)
3. Verificar color sólido negro (#000000)
4. Ajustar parámetros en `docker/config.py`

## Mantenimiento

### Actualizar Templates

1. Editar archivo en `resources/views/omr/`
2. Verificar en navegador primero
3. Generar PDF de prueba
4. Validar con pipeline OCR
5. Formatear código con Pint

### Agregar Nuevo Tipo de Evaluación

1. Crear configuración en `config/nombre_evaluacion.php`
2. Crear template en `resources/views/omr/nombre-evaluacion.blade.php`
3. Agregar método en `OMRController`:
```php
public function nombreEvaluacion(Request $request)
{
    $config = config('nombre_evaluacion');
    
    if ($request->has('download') && $request->download === 'pdf') {
        return $this->generatePdf('nombre-evaluacion', 'Nombre Evaluación', [
            'config' => $config,
        ], $request);
    }
    
    return view('omr.nombre-evaluacion', ['config' => $config]);
}
```
4. Agregar ruta en `routes/web.php`:
```php
Route::get('/nombre-evaluacion', [OMRController::class, 'nombreEvaluacion'])->name('nombre-evaluacion');
```
5. Agregar opción en `Folios.vue`:
```javascript
{ value: 'nombre-evaluacion', label: 'Nombre Evaluación' }
```

## Troubleshooting

### Error: "Unable to find node"

**Solución**: Configurar paths de Node.js/NPM
```php
$browsershot->setNodeBinary('/ruta/a/node');
$browsershot->setNpmBinary('/ruta/a/npm');
```

### Error: "Puppeteer not found"

**Solución**: Instalar Puppeteer
```bash
npm install puppeteer --save-dev
```

### PDF vacío o corrupto

**Solución**: Agregar `waitUntilNetworkIdle()` y verificar permisos
```php
Browsershot::html($htmlContent)
    ->waitUntilNetworkIdle()
    ->showBackground()
    ->save($tempPath);
```

### Burbujas no se detectan

**Solución**: Verificar tamaño y border-radius
```css
.bubble {
    width: 4mm;
    height: 4mm;
    border: 1px solid black;
    border-radius: 50%; /* Importante para círculos perfectos */
}
```

### Contenido cortado en PDF

**Solución**: Ajustar márgenes y verificar tamaño de página
```php
->format('Letter')  // No 'A4'
->margins(10, 10, 10, 10)  // Suficiente espacio
```

## Referencias

- [Spatie Browsershot Documentation](https://github.com/spatie/browsershot)
- [Puppeteer Documentation](https://pptr.dev/)
- [NOM-035-STPS-2018](https://www.gob.mx/stps/documentos/nom-035-stps-2018)

## Notas Adicionales

- Los PDFs se generan en `storage/app/temp/` y se eliminan automáticamente después de la descarga
- Browsershot requiere más memoria que DomPDF, pero produce mejores resultados
- La primera generación puede ser lenta mientras Chrome se inicializa
- Los templates funcionan tanto en navegador como en PDF sin cambios
