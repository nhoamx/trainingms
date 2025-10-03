"""
Configuración para REFERENCIA III - Factores de Riesgo Psicosocial
====================================================================

Template Type: 02

Esta es la evaluación principal que contiene:
- Aproximadamente 46 preguntas con 5 opciones cada una (A, B, C, D, E)
  A = Siempre
  B = Casi siempre  
  C = Algunas veces
  D = Casi nunca
  E = Nunca

- Sección CITSATS-s1 con 6 preguntas de SÍ/NO

NOTA: Las coordenadas actuales son PLACEHOLDER.
      Debes calibrarlas usando la imagen alineada de Referencia III.

Formato de coordenadas: (x, y, width, height)
"""

# Preguntas principales (ejemplo con primeras 10, debes completar todas)
referencia_iii = {
    '1': {
        'A': (0, 0, 35, 35),  # TODO: Calibrar
        'B': (0, 0, 35, 35),
        'C': (0, 0, 35, 35),
        'D': (0, 0, 35, 35),
        'E': (0, 0, 35, 35),
    },
    '2': {
        'A': (0, 0, 35, 35),
        'B': (0, 0, 35, 35),
        'C': (0, 0, 35, 35),
        'D': (0, 0, 35, 35),
        'E': (0, 0, 35, 35),
    },
    '3': {
        'A': (0, 0, 35, 35),
        'B': (0, 0, 35, 35),
        'C': (0, 0, 35, 35),
        'D': (0, 0, 35, 35),
        'E': (0, 0, 35, 35),
    },
    # TODO: Añadir preguntas 4-46
    # ...
}

# Sección CITSATS-s1 (6 preguntas SÍ/NO)
citsats_s1 = {
    '1': {
        'SI': (0, 0, 35, 35),  # TODO: Calibrar
        'NO': (0, 0, 35, 35),
    },
    '2': {
        'SI': (0, 0, 35, 35),
        'NO': (0, 0, 35, 35),
    },
    '3': {
        'SI': (0, 0, 35, 35),
        'NO': (0, 0, 35, 35),
    },
    '4': {
        'SI': (0, 0, 35, 35),
        'NO': (0, 0, 35, 35),
    },
    '5': {
        'SI': (0, 0, 35, 35),
        'NO': (0, 0, 35, 35),
    },
    '6': {
        'SI': (0, 0, 35, 35),
        'NO': (0, 0, 35, 35),
    },
}

# Combinar ambas secciones en una sola configuración
referencia_iii_complete = {
    **referencia_iii,
    **{f'CITSATS_{k}': v for k, v in citsats_s1.items()}
}
