# 🚀 Guía Rápida: Probar Templates OMR

Esta guía te permite probar los templates OMR actualizados en **menos de 5 minutos**.

## ⚡ Prueba Rápida en Navegador

Abre estos enlaces en tu navegador para ver los templates:

### 1️⃣ Guía de Referencia I (PTSD)
```
http://trainingms.test/omr/referencia-i
```
**Qué buscar**: Preguntas SÍ/NO, folio con 9 columnas, marcadores en esquinas

### 2️⃣ Guía de Referencia III (Factores Psicosociales)
```
http://trainingms.test/omr/referencia-iii
```
**Qué buscar**: 3 columnas, 72 preguntas con 5 opciones (A-E), separadores cada 10 preguntas

### 3️⃣ Guía de Referencia V (Datos Demográficos)
```
http://trainingms.test/omr/referencia-v
```
**Qué buscar**: 3 columnas, múltiples secciones demográficas, grids de codificación

### 4️⃣ Escala Cisneros (Violencia Laboral)
```
http://trainingms.test/omr/escala-cisneros
```
**Qué buscar**: Preguntas con tipo de persona (A,B,C) y frecuencia (0-6), 2 páginas

## 📄 Generar PDFs de Prueba

Agrega `?download=pdf&folios=0001` a cualquier URL:

```
http://trainingms.test/omr/referencia-i?download=pdf&folios=0001
http://trainingms.test/omr/referencia-iii?download=pdf&folios=0001,0002
```

**El navegador descargará automáticamente el PDF.**

## ✅ Checklist de Validación Rápida

Para cada template que abras, verifica:

- [ ] ⬛ **4 marcadores cuadrados negros** en las esquinas
- [ ] 🔢 **Sección de folio** con 9 columnas y burbujas para dígitos 0-9
- [ ] ⚫ **Burbujas perfectamente circulares** (no ovaladas)
- [ ] 📝 **Todo el texto es legible**
- [ ] 📏 **Todo cabe en una página** (no hay scroll infinito)
- [ ] 🎨 **No hay elementos sobrepuestos** o mal alineados

## 🖥️ Desde la Interfaz

### Ruta Completa

1. Navega a: **Organizations** (menú principal)
2. Selecciona una organización y haz clic en **Edit** (ícono de lápiz)
3. Ve a la pestaña **"Folios"**
4. Si no existe un lote, crea uno:
   - Nombre: "Prueba OMR"
   - Cantidad: 5
   - Tipo: **Presencial**
   - Descripción: "Lote de prueba"
   - Clic en **"Crear lote de folios"**
5. Clic en el ícono de **descarga de PDF** (📄) en el lote creado
6. Selecciona **tipo de evaluación** (ej: Guía de Referencia I)
7. Marca **"Generar todos los folios del lote"** o especifica folios
8. Clic en **"Generar PDF"**

**Resultado**: El navegador descargará un PDF con las hojas OMR.

## 🧪 Validación con Pipeline OCR (Opcional)

Si deseas validar que los marcadores se detecten correctamente:

```bash
cd docker

# 1. Genera un PDF primero (desde navegador o interfaz)

# 2. Convierte a imágenes
python pdf_to_image_converter.py ../nombre_del_pdf.pdf

# 3. Detecta marcadores
python alinear_con_marcadores.py output_images/nombre_del_pdf_page_0.jpg

# 4. Revisa el resultado en:
#    - outputs_aligned/nombre_del_pdf_page_0.jpg (imagen alineada)
#    - debug_img.png (marcadores detectados)
```

**Éxito** = Los 4 marcadores se detectan y la imagen se alinea correctamente.

## 🐛 Problemas Comunes

### ❌ "Unable to find node" al generar PDF

**Causa**: Node.js no encontrado

**Solución rápida**:
```bash
# Verifica que Node.js esté disponible
which node.exe
# Debería mostrar: /mnt/c/Program Files/nodejs/node.exe

# Si no está, instala Node.js en Windows
```

### ❌ PDF vacío o corrupto

**Causa**: Puppeteer no instalado

**Solución rápida**:
```bash
npm install puppeteer --save-dev
```

### ❌ Templates no se ven en navegador

**Causa**: Rutas no registradas

**Solución rápida**:
```bash
# Verifica que las rutas estén registradas
php artisan route:list | grep omr

# Debería mostrar:
# omr.referencia-i
# omr.referencia-iii
# omr.referencia-v
# omr.escala-cisneros
```

## 📊 Resultados Esperados

### En Navegador
- ✅ Página se renderiza completamente
- ✅ Marcadores visibles en las 4 esquinas
- ✅ Folio con burbujas circulares
- ✅ Todo el contenido visible sin scroll excesivo

### En PDF
- ✅ Idéntico a lo que ves en el navegador
- ✅ 1 página por folio
- ✅ Marcadores nítidos y bien definidos
- ✅ Burbujas perfectamente circulares
- ✅ Tamaño Letter (8.5" × 11")

## 🎯 Si Todo Funciona

✅ **¡Felicidades!** Los templates OMR están listos para usar.

### Siguiente paso:
- Imprimir algunas hojas de prueba
- Llenar burbujas manualmente con lápiz/pluma
- Escanear y procesar con pipeline OCR
- Validar que las burbujas marcadas se detecten correctamente

## 📚 Más Información

Si necesitas más detalles o enfrentas problemas:

- **Documentación técnica**: `docs/OMR_BROWSERSHOT_MIGRATION.md`
- **Guía de validación completa**: `docs/OMR_VISUAL_VERIFICATION.md`
- **Resumen del proyecto**: `docs/OMR_MIGRATION_SUMMARY.md`

## ⏱️ Tiempo Estimado

- ✅ **Prueba en navegador**: 2 minutos
- ✅ **Generar PDFs**: 1 minuto
- ✅ **Validación visual**: 2 minutos
- ⏳ **Validación con OCR**: 5-10 minutos (opcional)

**Total**: ~5 minutos para validación básica

---

**¿Todo funciona?** Marca como completo ✅ y continúa con pruebas de usuario.

**¿Algo no funciona?** Consulta la sección de **Problemas Comunes** o revisa la documentación completa.

---

*Última actualización: Septiembre 29, 2025*
