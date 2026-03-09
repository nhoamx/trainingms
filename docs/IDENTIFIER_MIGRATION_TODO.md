# TODO - Migracion de `identifier` (Puestos y Departamentos)

## Estado Actual
- [x] Objetivo confirmado: migrar a nuevo formato de `identifier` compatible con el modelo de bloques para OMR.
- [x] Unicidad confirmada: solo por organizacion.
- [x] Orden base confirmado: secuencia incremental por bloques (`1x`, `2x`, `1x2x`, `1xx#x`, ...), manteniendo orden de letras (`a..e`).
- [x] Politica de importacion confirmada:
	- [x] Si `identificador` se omite, se asigna automaticamente.
	- [x] Si `identificador` se envia, se valida en modo estricto y se rechaza si no cumple formato.

## Fase 1 - Reglas y Generador
- [x] Definir formalmente el formato valido del nuevo `identifier` (regla centralizada).
- [x] Implementar generador deterministico de siguientes IDs segun el orden acordado.
- [x] Integrar generador en:
	- [x] `app/Services/OccupationPositionService.php`
	- [x] `app/Services/DepartmentAreaService.php`
- [x] Asegurar que la asignacion sea por organizacion y sin colisiones.

## Fase 2 - Validacion de Entradas
- [x] Validar `customIdentifier` en creacion manual.
- [x] Validar `customIdentifier` en importaciones Excel.
- [x] Mostrar mensaje de validacion claro con ejemplo de formato valido cuando falle (`1a`, `2a`, `1a2b`, etc.).
- [x] Ajustar longitud maxima de `identificador` en importaciones para cubrir el nuevo formato.

## Fase 3 - Auditoria de Datos Actuales
- [x] Levantar inventario de IDs actuales de puestos y departamentos.
- [x] Clasificar cada registro: `nuevo_formato`, `legacy`, `invalido`.
- [x] Detectar colisiones potenciales dentro de cada organizacion.
- [x] Generar reporte resumido para aprobacion previa a migracion.

## Fase 4 - Migracion
- [x] Crear comando Artisan idempotente para migrar IDs existentes.
- [x] Incluir modo `--dry-run` (solo simulacion y mapeo viejo -> nuevo).
- [x] Incluir modo ejecucion real (actualizacion en DB).
- [x] Registrar bitacora simple de cambios aplicados.

## Fase 5 - Pruebas Minimas
- [x] Unit tests del generador (orden y cobertura de secuencia).
- [x] Unit tests de validador (casos validos e invalidos).
- [x] Test de servicio para creacion de puesto/departamento con nuevo formato.
- [x] Test del comando de migracion (`dry-run` y ejecucion real).

## Fase 6 - Despliegue Seguro
- [ ] Deploy de codigo sin migrar datos.
- [ ] Ejecutar auditoria en entorno objetivo (`dry-run`).
- [ ] Ejecutar migracion real tras validacion.
- [ ] Monitorear errores de alta/importacion posteriores.

## Nota
- No hay decisiones abiertas para iniciar implementacion de Fase 1 y Fase 2.
