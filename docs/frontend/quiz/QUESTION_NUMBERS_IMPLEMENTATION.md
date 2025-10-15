# Agregado de Números de Pregunta a Referencia III

## Resumen de Cambios

Se ha implementado la funcionalidad para mostrar los números de las preguntas (1-72) en la visualización de resultados de Referencia III, permitiendo verificar que las preguntas están en el orden correcto.

## Cambios Realizados

### 1. Frontend - Detail.vue

**Archivo**: `resources/js/Pages/Results/Detail.vue`

#### Modificaciones en la Tabla Principal (Preguntas 1-64)

- **Agregada columna "N°"**: Nueva columna que muestra el número de pregunta
- **Color destacado**: Los números se muestran en color indigo-600 para mejor visibilidad
- **Función de mapeo**: Se usa `getQuestionNumber(question)` para obtener el número

```vue
<thead class="bg-gray-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
    </tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
    <tr v-for="(answer, question) in guideIIIResults.answers" :key="question">
        <td class="px-6 py-4 text-sm font-semibold text-indigo-600">{{ getQuestionNumber(question) }}</td>
        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ question }}</td>
        <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
    </tr>
</tbody>
```

#### Modificaciones en Preguntas Condicionales (Preguntas 65-72)

- **Agregada columna "N°"**: Para las secciones condicionales (servicio al cliente y gestión)
- **Mismo formato**: Consistencia visual con la tabla principal

```vue
<thead class="bg-gray-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
    </tr>
</thead>
```

#### Modificaciones en CITSATS (Preguntas 73-78)

- **Cambiado encabezado**: De "#" a "N°" para consistencia
- **Cálculo correcto**: Ahora muestra `idx + 73` en lugar de `idx + 1`
- **Números 73-78**: Corresponden a acontecimientos traumáticos

```vue
<thead class="bg-gray-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N°</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pregunta</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
    </tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
    <tr v-for="(answer, idx) in Object.values(guideIIIResults.citsats_s1)" :key="idx">
        <td class="px-6 py-4 text-sm font-semibold text-indigo-600">{{ idx + 73 }}</td>
        <td class="px-6 py-4 text-sm text-gray-700">
            <span v-if="citsatsQuestions[idx + 73]">{{ citsatsQuestions[idx + 73] }}</span>
        </td>
        <td class="px-6 py-4 text-sm text-gray-700">{{ answer }}</td>
    </tr>
</tbody>
```

### 2. Lógica de Mapeo de Números

Se agregó un objeto `questionNumberMap` que mapea cada texto de pregunta a su número correspondiente (1-72):

```typescript
const questionNumberMap: Record<string, number> = {
    'El espacio donde trabajo me permite realizar mis actividades de manera segura e higiénica': 1,
    'Mi trabajo me exige hacer mucho esfuerzo físico': 2,
    // ... 70 preguntas más ...
    'Ignoran las sugerencias para mejorar su trabajo': 72,
};

const getQuestionNumber = (questionText: string): number => {
    return questionNumberMap[questionText] || 0;
};
```

## Estructura de Preguntas de Referencia III

### Distribución por Secciones

1. **Preguntas Generales (1-64)**: Ambiente de trabajo, cargas, liderazgo, etc.
2. **Preguntas Condicionales (65-72)**:
   - **Servicio al Cliente (65-68)**: Solo si el trabajador atiende clientes
   - **Jefatura (69-72)**: Solo si el trabajador supervisa a otros
3. **Acontecimientos Traumáticos CITSATS (73-78)**: Eventos traumáticos laborales

### Categorías Principales

- **Ambiente de trabajo**: Preguntas 1-5
- **Factores propios de la actividad**: Preguntas 6-16, 23-30, 35-36, 65-68
- **Organización del tiempo de trabajo**: Preguntas 17-22
- **Liderazgo y relaciones**: Preguntas 31-34, 37-46, 57-64, 69-72
- **Entorno organizacional**: Preguntas 47-56

## Verificación del Orden

Con esta implementación, ahora es posible:

1. **Ver el número de cada pregunta** en orden consecutivo
2. **Identificar saltos** en la numeración (preguntas faltantes)
3. **Verificar que las condicionales** estén en el rango 65-72
4. **Confirmar que CITSATS** esté en el rango 73-78
5. **Validar el orden correcto** de las 72 preguntas de Referencia III

## Estado de Implementación

✅ **Completado**:
- Columna de números en tabla principal (1-64)
- Columna de números en preguntas condicionales (65-72)
- Números correctos en CITSATS (73-78)
- Mapeo completo de 72 preguntas
- Función `getQuestionNumber()` implementada
- Frontend compilado exitosamente
- Código formateado con Pint

## Resultado Visual

Ahora la visualización muestra:

```
N° | Pregunta                                                           | Respuesta
---+--------------------------------------------------------------------+-----------
1  | El espacio donde trabajo me permite realizar...                    | Siempre
2  | Mi trabajo me exige hacer mucho esfuerzo físico                    | Casi siempre
...
65 | Atiendo clientes o usuarios muy enojados                           | A veces
...
73 | Accidente que tenga como consecuencia la muerte...                 | Sí
```

Los números se muestran en **color indigo** para destacar la secuencia numérica y facilitar la verificación del orden.
