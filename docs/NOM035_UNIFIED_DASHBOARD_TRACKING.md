# NOM-035 Unified Dashboard Flow Tracking

## Objetivo del feature
Unificar la experiencia NOM-035 para que:

1. El usuario consulte información general del centro de trabajo en un solo lugar.
2. El usuario identifique problemas por instrumento con una estructura homogénea de etapas.
3. El análisis permita filtrar consistentemente por datos demográficos.
4. Los participantes puedan segmentarse por instrumento.
5. La prevención sea gestionada por el equipo administrativo de T&MS.

## Alcance funcional acordado

### 1) Capa General (en index)
- Información de la empresa
- Evaluación
- Comité
- Sensibilización
- Política

### 2) Etapas compartidas (GRI y GRIII)
- Analizar
- Participantes
- Prevenir
- Identificar > Bloques
- Identificar > Preguntas

### 3) Etapas exclusivas GRIII
- Identificar > Global
- Identificar > Categorías
- Identificar > Dominios
- Identificar > Dimensiones

## Estandarización UX

### Estados UX obligatorios por módulo
- Cargando
- Vacío (sin datos para filtros)
- Error
- Listo

### Filtros demográficos base
- Género
- Puesto
- Área
- Turno

## Severidad y taxonomía
Se usa la taxonomía única definida en `config/nom035_risk_levels.php`:
- Nulo
- Bajo
- Medio
- Alto
- Muy Alto

## Checklist de implementación

### Backend
- [x] Exponer data de capa general en el index NOM-035.
- [x] Unificar payload para tabs generales (empresa/evaluación/comité/sensibilización/política).
- [x] Exponer en Ref I data para etapas: identificar (bloques/preguntas), analizar, participantes, prevenir.
- [x] Mantener estructura de severidad unificada para análisis por instrumento.
- [x] Asegurar consistencia de filtros demográficos para datos de análisis.

### Frontend
- [x] Actualizar `Nom035DashboardIndex.vue` para incluir capa general.
- [x] Ajustar `Nom035RefIIIDashboard.vue` para enfoque en etapas.
- [x] Ajustar `Nom035RefIDashboard.vue` para enfoque en etapas.
- [x] Implementar componente de etapas para Ref I con subtabs compartidos.
- [x] Alinear labels, orden y navegación para GRI/GRIII.

### Pruebas
- [x] Actualizar/crear tests Feature Inertia para payload y estructura de páginas.
- [x] Verificar que la capa general y etapas renderizan con props esperados.
- [x] Verificar filtros demográficos en payload de análisis.

### Calidad
- [x] Ejecutar pruebas enfocadas del módulo NOM-035.
- [x] Ejecutar `vendor/bin/pint --dirty`.

## Riesgos y control
- Riesgo: divergencia de data model entre Ref I y Ref III.
  - Mitigación: contratos explícitos de props por componente de etapas.
- Riesgo: inconsistencias de campos demográficos.
  - Mitigación: normalización de llaves (`genero`, `puesto`, `area`, `turno`) y fallback `No especificado`.

## Definición de Done
- Navegación general + etapas implementada según alcance.
- GRIII conserva vistas exclusivas de identificación.
- GRI elimina tabs antiguas no equivalentes y adopta etapas.
- Tests del flujo NOM-035 pasan satisfactoriamente.
- Formato de código aplicado con Pint.
