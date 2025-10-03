"""
Configuración para REFERENCIA V - Datos Demográficos
=====================================================

Template Type: 03

Esta evaluación recopila información demográfica del evaluado:
- Sexo (H/M)
- Edad (rangos)
- Estado civil
- Nivel de estudios
- Tipo de puesto
- Tipo de contrato
- Jornada de trabajo
- Y más secciones según NOM-035

NOTA: Las coordenadas actuales son PLACEHOLDER.
      Debes calibrarlas usando la imagen alineada de Referencia V.

Formato de coordenadas: (x, y, width, height)
"""

referencia_v = {
    # Sección: Sexo
    'sexo': {
        'H': (0, 0, 35, 35),  # TODO: Calibrar - Hombre
        'M': (0, 0, 35, 35),  # TODO: Calibrar - Mujer
    },
    
    # Sección: Edad (rangos)
    'edad': {
        '15-19': (0, 0, 35, 35),  # TODO: Calibrar
        '20-24': (0, 0, 35, 35),
        '25-29': (0, 0, 35, 35),
        '30-34': (0, 0, 35, 35),
        '35-39': (0, 0, 35, 35),
        '40-44': (0, 0, 35, 35),
        '45-49': (0, 0, 35, 35),
        '50-54': (0, 0, 35, 35),
        '55-59': (0, 0, 35, 35),
        '60-64': (0, 0, 35, 35),
        '65-69': (0, 0, 35, 35),
    },
    
    # Sección: Estado Civil
    'estado_civil': {
        'soltero': (0, 0, 35, 35),  # TODO: Calibrar
        'casado': (0, 0, 35, 35),
        'union_libre': (0, 0, 35, 35),
        'separado': (0, 0, 35, 35),
        'divorciado': (0, 0, 35, 35),
        'viudo': (0, 0, 35, 35),
    },
    
    # Sección: Nivel de Estudios
    'estudios': {
        'ninguno': (0, 0, 35, 35),  # TODO: Calibrar
        'primaria': (0, 0, 35, 35),
        'secundaria': (0, 0, 35, 35),
        'bachillerato': (0, 0, 35, 35),
        'licenciatura': (0, 0, 35, 35),
        'posgrado': (0, 0, 35, 35),
    },
    
    # TODO: Añadir más secciones según tu template:
    # - Tipo de puesto (operativo, supervisor, gerente, etc.)
    # - Tipo de contrato (indefinido, temporal, etc.)
    # - Tipo de jornada (diurna, nocturna, mixta, etc.)
    # - Tiempo en la empresa
    # - Tiempo en el puesto actual
    # - etc.
}
