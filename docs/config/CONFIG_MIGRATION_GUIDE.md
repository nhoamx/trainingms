"""
===================================================================================
GUÍA DE CONFIGURACIÓN MODULAR OMR
===================================================================================

NUEVO SISTEMA (Recomendado para nuevas calibraciones):
------------------------------------------------------
Las configuraciones están separadas en archivos individuales:

1. docker/config/folio.py         - Folio (compartido por todos)
2. docker/config/referencia_i.py  - Referencia I (template 01)
3. docker/config/referencia_iii.py - Referencia III (template 02)  
4. docker/config/referencia_v.py   - Referencia V (template 03)

MIGRACIÓN GRADUAL:
-----------------
1. Las configuraciones ACTUALES en config.py siguen funcionando
2. Puedes ir migrando template por template a los nuevos archivos
3. Una vez migrado, importas desde config/ en lugar de definir aquí

INSTRUCCIONES:
-------------
1. Calibra un template nuevo usando las imágenes alineadas
2. Edita el archivo correspondiente en docker/config/
3. Las coordenadas se cargarán automáticamente

VENTAJAS DEL SISTEMA MODULAR:
-----------------------------
✅ Cada template en su propio archivo (más organizado)
✅ Fácil de mantener y actualizar
✅ Evita confusiones entre templates diferentes
✅ Permite trabajo en paralelo en diferentes templates
✅ Mejor documentación por template

===================================================================================
"""

# ============================================================================
# IMPORTAR DESDE SISTEMA MODULAR (cuando estén listos)
# ============================================================================

# from config.folio import folio_configuration
# from config.referencia_i import reference_i
# from config.referencia_iii import referencia_iii_complete
# from config.referencia_v import referencia_v

# ============================================================================
# CONFIGURACIONES ACTUALES (mantener hasta migrar)
# ============================================================================

# NOTA: Este archivo contiene las configuraciones legacy que ya funcionan.
# Gradualmente iremos migrando a docker/config/*.py

