# Estructura Completa de Referencia III

## 📊 Todas las Secciones de Referencia III

La **Referencia III** tiene **CINCO** secciones de burbujas que necesitas calibrar:

### 1. **Preguntas Generales (1-64)** - 320 burbujas
- 64 preguntas × 5 opciones (A, B, C, D, E)
- **Opciones**:
  - A = Siempre
  - B = Casi siempre
  - C = Algunas veces
  - D = Casi nunca
  - E = Nunca

### 2. **Pregunta Condicional: Servicio al Cliente (SÍ/NO)** - 2 burbujas
- **Condición**: "En mi trabajo debo brindar servicio a clientes o usuarios"
- 1 pregunta × 2 opciones (SÍ, NO)
- **Ubicación**: Antes de las preguntas 65-68

### 3. **Preguntas de Servicio al Cliente (65-68)** - 20 burbujas
- **Solo si respondió SÍ a la pregunta condicional**
- 4 preguntas × 5 opciones (A, B, C, D, E)
- Preguntas:
  - 65: Atiendo clientes o usuarios muy enojados
  - 66: Mi trabajo me exige atender personas muy necesitadas de ayuda o enfermas
  - 67: Para hacer mi trabajo debo demostrar sentimientos distintos a los míos
  - 68: Mi trabajo me exige atender situaciones de violencia

### 4. **Pregunta Condicional: Gestión/Supervisión (SÍ/NO)** - 2 burbujas
- **Condición**: "Soy jefe de otros trabajadores"
- 1 pregunta × 2 opciones (SÍ, NO)
- **Ubicación**: Antes de las preguntas 69-72

### 5. **Preguntas de Gestión/Supervisión (69-72)** - 20 burbujas
- **Solo si respondió SÍ a la pregunta condicional**
- 4 preguntas × 5 opciones (A, B, C, D, E)
- Preguntas:
  - 69: Comunican tarde los asuntos de trabajo
  - 70: Dificultan el logro de los resultados del trabajo
  - 71: Cooperan poco cuando se necesita
  - 72: Ignoran las sugerencias para mejorar su trabajo

### 6. **CITSATS-s1 (1-6)** - 12 burbujas
- 6 preguntas × 2 opciones (SÍ, NO)
- Preguntas sobre violencia laboral

---

## 🎯 Total de Burbujas en Referencia III

| Sección | Preguntas | Opciones | Burbujas | Tipo |
|---------|-----------|----------|----------|------|
| Generales | 64 | A-E (5) | 320 | Obligatorio |
| Cond. Servicio | 1 | SÍ/NO (2) | 2 | Condicional |
| Servicio Cliente | 4 | A-E (5) | 20 | Condicional |
| Cond. Gestión | 1 | SÍ/NO (2) | 2 | Condicional |
| Gestión/Supervisión | 4 | A-E (5) | 20 | Condicional |
| CITSATS-s1 | 6 | SÍ/NO (2) | 12 | Obligatorio |
| **TOTAL** | **80** | - | **376** | - |

---

## 📁 ¿Dónde se Guardan las Configuraciones?

### Actualmente: `docker/config_legacy.py`

Todas las secciones se guardan en **`docker/config_legacy.py`** con las siguientes variables:

```python
# En config_legacy.py:

# 1. Preguntas generales 1-64 (A, B, C, D, E)
referencia_iii = {
    '1': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '2': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    # ... hasta 64
    '64': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
}

# 2. Pregunta condicional: Servicio al Cliente (SÍ/NO)
conditional_customer_service = {
    'condition': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
}

# 3. Preguntas de Servicio al Cliente 65-68 (A, B, C, D, E)
customer_service_questions = {
    '65': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '66': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '67': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '68': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
}

# 4. Pregunta condicional: Gestión/Supervisión (SÍ/NO)
conditional_management = {
    'condition': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
}

# 5. Preguntas de Gestión/Supervisión 69-72 (A, B, C, D, E)
management_questions = {
    '69': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '70': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '71': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
    '72': {'A': (x,y,w,h), 'B': (...), 'C': (...), 'D': (...), 'E': (...)},
}

# 6. CITSATS-s1 (SÍ/NO)
citsats_s1 = {
    '1': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
    '2': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
    '3': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
    '4': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
    '5': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
    '6': {'SI': (x,y,w,h), 'NO': (x,y,w,h)},
}
```

---

## 🛠️ Opciones en la Herramienta de Calibración

Necesitas agregar estas opciones al dropdown de la herramienta web:

### Opciones Actuales (Incompletas):
```html
<option value="referencia-iii">Referencia III (Preguntas 1-46, A/B/C/D/E)</option>
<option value="referencia-iii-citsats">Referencia III - CITSATS (1-6, SI/NO)</option>
```

### Opciones Nuevas (Completas):
```html
<!-- Sección 1 -->
<option value="referencia-iii">Ref III - Generales (1-64, A/B/C/D/E)</option>

<!-- Sección 2 -->
<option value="referencia-iii-cond-customer">Ref III - Condición Servicio Cliente (SÍ/NO)</option>

<!-- Sección 3 -->
<option value="referencia-iii-customer">Ref III - Servicio Cliente (65-68, A/B/C/D/E)</option>

<!-- Sección 4 -->
<option value="referencia-iii-cond-management">Ref III - Condición Gestión (SÍ/NO)</option>

<!-- Sección 5 -->
<option value="referencia-iii-management">Ref III - Gestión/Supervisión (69-72, A/B/C/D/E)</option>

<!-- Sección 6 -->
<option value="referencia-iii-citsats">Ref III - CITSATS (1-6, SÍ/NO)</option>
```

---

## 📝 Flujo de Calibración Recomendado

### Para Referencia III (6 sesiones de calibración):

1. **Sesión 1**: Calibrar preguntas generales 1-64
   - Seleccionar: "Ref III - Generales (1-64, A/B/C/D/E)"
   - Pregunta inicial: 1
   - 320 burbujas (tardará ~40-50 minutos)

2. **Sesión 2**: Calibrar pregunta condicional de Servicio al Cliente
   - Seleccionar: "Ref III - Condición Servicio Cliente (SÍ/NO)"
   - 2 burbujas (~1 minuto)

3. **Sesión 3**: Calibrar preguntas de Servicio al Cliente 65-68
   - Seleccionar: "Ref III - Servicio Cliente (65-68, A/B/C/D/E)"
   - Pregunta inicial: 65
   - 20 burbujas (~3-4 minutos)

4. **Sesión 4**: Calibrar pregunta condicional de Gestión
   - Seleccionar: "Ref III - Condición Gestión (SÍ/NO)"
   - 2 burbujas (~1 minuto)

5. **Sesión 5**: Calibrar preguntas de Gestión 69-72
   - Seleccionar: "Ref III - Gestión/Supervisión (69-72, A/B/C/D/E)"
   - Pregunta inicial: 69
   - 20 burbujas (~3-4 minutos)

6. **Sesión 6**: Calibrar CITSATS
   - Seleccionar: "Ref III - CITSATS (1-6, SÍ/NO)"
   - 12 burbujas (~2-3 minutos)

**Tiempo Total Estimado**: ~50-65 minutos

---

## 🎯 Siguiente Paso

Necesitas actualizar `docker/calibrate_bubbles.py` para agregar las 6 opciones de Referencia III al dropdown y sus funciones de generación de código correspondientes.

¿Quieres que actualice la herramienta de calibración ahora con estas nuevas opciones?
