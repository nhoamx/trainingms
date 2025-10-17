---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'problems', 'fetch']
model: Claude Sonnet 4.5 (copilot)
---

# 🧠 Prompt — Implementación de Visualización con Marcadores

Vamos a implementar una nueva funcionalidad que nos permita **visualizar las imágenes analizadas con marcadores y puntos clave**, aprovechando la nueva capacidad del sistema para interpretar y procesar imágenes.

---

## 🎯 Objetivo General

Agregar una nueva salida de imágenes procesadas que incluyan los **marcadores de alineación y los puntos clave (burbujas)**, mostrarlas en el frontend **solo para usuarios administradores**, y crear una tarea programada que elimine imágenes antiguas (más de 7 días) para optimizar el espacio.

---

## ⚙️ Parte 1 — Backend (Python)

**Archivo:** `docker/main.py`

1. Actualmente generamos tres tipos de imágenes:
   - `docker/output_images`
   - `docker/output_original`
   - `docker/outputs_aligned`

2. Crear una nueva carpeta de salida llamada `output_with_markers`.

3. Implementar una función (por ejemplo, `save_image_with_markers(image, markers, keypoints)`) que:
   - Reciba la imagen procesada junto con los datos de marcadores y puntos clave.
   - Dibuje los **marcadores de alineación** en un color (por ejemplo, verde).
   - Dibuje los **puntos clave (burbujas)** en otro color (por ejemplo, azul o rojo).
   - Guarde la imagen final en `docker/output_with_markers` con el mismo formato y nombre base que las demás salidas.

---

## 🧩 Parte 2 — Backend (Laravel)

**Archivo:** `app/Jobs/ProcessPaperEvaluation.php`

1. Cuando el proceso `store` finalice, copiar la imagen generada con marcadores desde `docker/output_with_markers` hacia: `storage/app/public/folios/{folio}.png`.

2. Asegurar que la imagen sea accesible públicamente (`php artisan storage:link` si no existe el symlink).

3. Implementar una **tarea programada (schedule)** para eliminar imágenes con más de **7 días** en esta carpeta:
- Crear un comando: `app/Console/Commands/CleanOldFolioImages.php`
- Registrar su ejecución diaria en `app/Console/Kernel.php`.

---

## 💻 Parte 3 — Frontend (Vue)

**Archivo:** `resources/js/Pages/Results/Detail.vue`

1. Añadir una **nueva pestaña (tab)** en la vista de detalle, visible **solo para usuarios administradores** (`user.is_admin`).

2. En esa pestaña:
- Mostrar la imagen `{folio}.png` ubicada en `storage/app/public/folios/`.
- Mantener un layout responsivo, con zoom controlado y centrado.

---

## 🧪 Parte 4 — Buenas Prácticas y Control de Calidad

1. **Branch:**
- Crear una nueva branch siguiendo Git Flow:
  ```
  feature/output-with-markers-visualization
  ```

2. **Commits:**
- Usar Conventional Commits:
  ```
  feat(python): add output_with_markers generation
  feat(laravel): copy processed images to public folios folder
  feat(frontend): add admin tab to display marked images
  chore(scheduler): delete images older than 7 days
  ```

3. **Tests:**
- Implementar tests si es necesario.
- Usar **Database Transactions** para mantener la base limpia.
- ❌ No usar `DatabaseRefresher` (evita eliminar datos existentes).
- Antes de ejecutar los tests, verificar que no se borren registros productivos.

4. **Código Limpio:**
- Seguir las mejores prácticas de **Laravel (PSR-12)** y **Python (PEP8)**.
- Agregar comentarios donde sea relevante.
- Manejar excepciones al copiar o eliminar archivos.

---

## 🗒️ Resumen

| Área | Acción Principal | Archivo |
|------|------------------|----------|
| 🐍 Python | Generar `output_with_markers` con marcadores y burbujas | `docker/main.py` |
| ⚙️ Laravel | Copiar imagen procesada a `storage/app/public/folios/` | `app/Jobs/ProcessPaperEvaluation.php` |
| ⏰ Scheduler | Eliminar imágenes con más de 7 días | `app/Console/Commands/CleanOldFolioImages.php` |
| 🖼️ Vue | Mostrar imagen con marcadores (solo admins) | `resources/js/Pages/Results/Detail.vue` |

---

**Notas finales:**
- Asegúrate de mantener consistencia en nombres y rutas.
- Valida que las imágenes se eliminen correctamente después del tiempo definido.
- Revisa permisos de escritura/lectura en el entorno Docker antes de finalizar.



