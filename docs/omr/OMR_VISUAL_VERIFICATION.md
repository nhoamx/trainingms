# Guía de Verificación Visual de Templates OMR

Esta guía te ayudará a verificar que los templates OMR se vean correctamente tanto en el navegador como en los PDFs generados.

## Acceso Rápido

### Ver Templates en Navegador

Usa estos enlaces para ver los templates directamente en el navegador:

- [Referencia I](http://trainingms.test/omr/referencia-i)
- [Referencia III](http://trainingms.test/omr/referencia-iii)
- [Referencia V](http://trainingms.test/omr/referencia-v)
- [Escala Cisneros](http://trainingms.test/omr/escala-cisneros)

### Generar PDFs de Prueba

Agrega `?download=pdf&folios=0001` a cualquier URL para generar un PDF:

```
http://trainingms.test/omr/referencia-i?download=pdf&folios=0001
http://trainingms.test/omr/referencia-iii?download=pdf&folios=0001,0002
http://trainingms.test/omr/referencia-v?download=pdf&folios=0001
http://trainingms.test/omr/escala-cisneros?download=pdf&folios=0001
```

## Checklist de Verificación

### ✅ Para TODOS los Templates

#### Marcadores de Alineación
- [ ] **4 marcadores cuadrados negros** en las esquinas (8mm x 8mm)
- [ ] Marcador superior izquierdo a 5mm del borde superior y 5mm del borde izquierdo
- [ ] Marcador superior derecho a 5mm del borde superior y 5mm del borde derecho
- [ ] Marcador inferior izquierdo a 5mm del borde inferior y 5mm del borde izquierdo
- [ ] Marcador inferior derecho a 5mm del borde inferior y 5mm del borde derecho
- [ ] Todos los marcadores son perfectamente cuadrados y de color negro sólido

#### Encabezado
- [ ] Título principal: "IDENTIFICACIÓN Y ANÁLISIS DE LOS FACTORES DE RIESGO PSICOSOCIAL"
- [ ] Subtítulo: "Y EVALUACIÓN DEL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO"
- [ ] Normativa: "NOM-035-STPS-2018"
- [ ] Nombre específico de la guía (ej: "GUÍA DE REFERENCIA I")
- [ ] Todo el texto está centrado y legible

#### Sección de Folio
- [ ] Bloque de folio visible con **marcadores en las 4 esquinas del bloque**
- [ ] **9 columnas** para dígitos del folio
- [ ] **11 filas**: 1 header + 10 filas (dígitos 0-9)
- [ ] Cada celda tiene una burbuja circular
- [ ] Las burbujas están alineadas verticalmente
- [ ] Espacios para escribir el folio manualmente arriba de las burbujas

#### Formato General
- [ ] Todo el contenido **cabe en una sola página** tamaño Letter (215.9mm × 279.4mm)
- [ ] Márgenes de **10mm** en todos los lados
- [ ] No hay texto cortado o fuera de los márgenes
- [ ] No hay elementos sobrepuestos
- [ ] Fuente legible (Arial, 12px base)

#### Burbujas de Respuesta
- [ ] Todas las burbujas son **perfectamente circulares** (no ovaladas)
- [ ] Bordes uniformes (1px o 1.5px)
- [ ] Tamaño apropiado según densidad de contenido
- [ ] Espaciado uniforme entre burbujas

### ✅ Referencia I - Específico

- [ ] Sección de instrucciones presente con formato correcto
- [ ] Lista de preguntas numeradas
- [ ] Cada pregunta tiene 2 opciones de respuesta: **SÍ** y **NO**
- [ ] Burbujas alineadas a la derecha de cada pregunta
- [ ] Total de preguntas coincide con configuración (`config/referencia_i.php`)

### ✅ Referencia III - Específico

- [ ] **Layout de 3 columnas** con igual altura
- [ ] Primera columna incluye el bloque de folio
- [ ] Cada pregunta tiene **5 opciones** de respuesta (A, B, C, D, E)
- [ ] Preguntas numeradas correctamente (1-72)
- [ ] Separadores de bloque cada 10 preguntas con marcadores de esquina
- [ ] Preguntas condicionales (65-72) tienen sección SÍ/NO antes de las opciones
- [ ] Marcadores de esquina en separadores de bloques
- [ ] Todas las columnas terminan aproximadamente a la misma altura

### ✅ Referencia V - Específico

- [ ] **Layout de 3 columnas** para datos demográficos
- [ ] Bloque de folio en la parte superior con instrucciones al lado
- [ ] Secciones claramente etiquetadas:
  - Sexo/Género
  - Edad (2 dígitos con burbujas 0-9)
  - Estado Civil
  - Nivel de Estudios (con columnas Terminada/Incompleta)
  - Tipo de Personal
  - Tipo de Puesto
  - Tipo de Contratación
  - Tipo de Jornada
  - Rotación de Turnos
  - Tiempo en el Puesto Actual
  - Experiencia Vida Laboral
  - Ocupación/Profesión/Puesto (grid codificado)
  - Departamento/Sección/Área (grid codificado)
- [ ] Grids de codificación tienen estructura correcta (2 filas × 5 columnas: A, B, C, D, E)
- [ ] Edad permite ingresar 2 dígitos (decenas y unidades)

### ✅ Escala Cisneros - Específico

- [ ] Instrucciones detalladas sobre tipos de persona (A, B, C)
- [ ] Instrucciones sobre frecuencia (0-6)
- [ ] Cada pregunta tiene:
  - **Tipo de persona**: 3 opciones (A, B, C)
  - **Frecuencia**: 7 opciones (0, 1, 2, 3, 4, 5, 6)
- [ ] Total de preguntas de mobbing según configuración
- [ ] Segunda página con "ACONTECIMIENTOS TRAUMÁTICOS SEVEROS"
- [ ] Sección de traumáticos tiene 6 preguntas con SÍ/NO
- [ ] Salto de página correcto entre secciones

## Validación con OCR Pipeline

Después de verificar visualmente, prueba con el pipeline OCR:

### 1. Generar PDF de Prueba
```bash
# Desde el navegador o usando curl
curl "http://trainingms.test/omr/referencia-i?download=pdf&folios=0001" -o test_omr.pdf
```

### 2. Convertir a Imágenes
```bash
cd docker
python pdf_to_image_converter.py ../test_omr.pdf
```

### 3. Detectar Marcadores de Alineación
```bash
python alinear_con_marcadores.py output_images/test_omr_page_0.jpg
```

**Verificar**:
- [ ] Los 4 marcadores se detectan correctamente
- [ ] La imagen se alinea sin distorsión
- [ ] Las coordenadas de los marcadores son consistentes

### 4. Detectar Burbujas
```bash
python bubble_detector.py outputs_aligned/test_omr_page_0.jpg
```

**Verificar**:
- [ ] Todas las burbujas se detectan
- [ ] No hay falsos positivos
- [ ] Las coordenadas son precisas

## Mediciones Esperadas

Para validación precisa con regla o software de medición en PDF:

### Página
- **Ancho**: 215.9mm (8.5")
- **Alto**: 279.4mm (11")

### Márgenes
- **Todos los lados**: 10mm

### Marcadores de Alineación (desde bordes)
- **Superior izquierdo**: 5mm desde arriba, 5mm desde izquierda
- **Superior derecho**: 5mm desde arriba, 5mm desde derecha
- **Inferior izquierdo**: 5mm desde abajo, 5mm desde izquierda
- **Inferior derecho**: 5mm desde abajo, 5mm desde derecha
- **Tamaño de cada marcador**: 8mm × 8mm

### Burbujas
- **Estándar** (`.bubble`): 4mm diámetro
- **Pequeña** (`.bubble-small`): 3mm diámetro
- **Tiny** (`.bubble-tiny`): 3mm diámetro con borde 1.5px

## Problemas Comunes y Soluciones

### ❌ Marcadores no están en las esquinas
**Causa**: CSS de posicionamiento absoluto incorrecto
**Solución**: Verificar que el contenedor tenga `position: relative` y los marcadores `position: absolute`

### ❌ Burbujas ovaladas en lugar de circulares
**Causa**: Ancho y alto diferentes, o `border-radius` no es 50%
**Solución**: Asegurar `width = height` y `border-radius: 50%`

### ❌ Contenido se corta en los bordes
**Causa**: Márgenes insuficientes o contenido demasiado grande
**Solución**: Ajustar márgenes a 10mm o reducir tamaño de fuente/contenido

### ❌ Saltos de página no funcionan
**Causa**: Propiedad CSS incorrecta
**Solución**: Usar `page-break-after: always` o `page-break-before: always`

### ❌ PDF diferente al navegador
**Causa**: Usas DomPDF en lugar de Browsershot
**Solución**: Verificar que `OMRController` use `Spatie\Browsershot\Browsershot`

### ❌ Folio no se llena correctamente
**Causa**: Variable `$folio` no se pasa o está mal formateada
**Solución**: Verificar que el folio tenga formato `str_pad($folio, 4, '0', STR_PAD_LEFT)`

## Herramientas Útiles

### Para Navegador
- **DevTools** (F12): Inspeccionar elementos y medir
- **Ruler Extension**: Medir distancias en píxeles
- **Perfect Pixel**: Comparar con diseño de referencia

### Para PDF
- **Adobe Acrobat Reader**: Herramienta de medición
- **PDF-XChange Editor**: Medición precisa
- **Inkscape**: Importar PDF y medir en mm

### Para Debugging OMR
- `docker/debug_img.png`: Imagen con marcadores detectados
- `docker/debug_ref.png`: Imagen de referencia con burbujas
- `docker/log.log`: Log del proceso OCR

## Checklist Final

Antes de considerar el template como válido:

- [ ] ✅ Template se ve correcto en navegador (Chrome)
- [ ] ✅ PDF generado es idéntico a vista de navegador
- [ ] ✅ Todos los marcadores de alineación son detectados por OCR
- [ ] ✅ Todas las burbujas son detectadas correctamente
- [ ] ✅ No hay falsos positivos en detección
- [ ] ✅ El folio se llena correctamente cuando se pasa parámetro
- [ ] ✅ Múltiples folios generan múltiples páginas correctamente
- [ ] ✅ Contenido cabe completamente en una página Letter
- [ ] ✅ Márgenes de 10mm se respetan en todos los lados
- [ ] ✅ Código formateado con Laravel Pint

## Contacto y Soporte

Si encuentras problemas:
1. Revisa `docs/OMR_BROWSERSHOT_MIGRATION.md` para detalles técnicos
2. Verifica logs en `docker/log.log` para errores OCR
3. Consulta `storage/logs/laravel.log` para errores del backend
4. Revisa console del navegador (F12) para errores de frontend

---

**Última actualización**: Septiembre 29, 2025
