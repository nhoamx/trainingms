# Guía de Laravel Horizon para TrainingMS

## Resumen

Laravel Horizon está configurado para manejar el procesamiento asíncrono de evaluaciones psicológicas NOM-035 con monitoreo avanzado y notificaciones por correo.

## Configuración Implementada

### 🔧 **Colas Especializadas**

- **`quiz_processing`**: Procesamiento de evaluaciones psicológicas
- **`image_processing`**: Procesamiento de imágenes INE
- **`default`**: Trabajos generales del sistema

### 📊 **Supervisores Configurados**

#### Desarrollo Local:
- **Procesos máximos**: 5
- **Memoria**: 256MB por proceso
- **Timeout**: 5 minutos

#### Producción:
- **Procesos máximos**: 15 (para evaluaciones masivas)
- **Memoria**: 512MB por proceso
- **Timeout**: 10 minutos
- **Balance automático**: Activado

### 📧 **Notificaciones Configuradas**

- **Correo**: alfredo@nhoamx.com
- **Alertas por**: Trabajos fallidos, colas lentas, supervisores inactivos
- **Tema**: Modo oscuro para monitoreo nocturno

### ⏱️ **Tiempos de Espera**

- **Evaluaciones**: 2 minutos antes de alerta
- **Imágenes INE**: 1 minuto antes de alerta
- **Trabajos generales**: 3 minutos antes de alerta

## Comandos Esenciales

### Iniciar Horizon
```bash
# Desarrollo
php artisan horizon

# Producción (con Supervisor)
sudo supervisorctl start trainingms-horizon:*
```

### Monitoreo
```bash
# Estado actual
php artisan horizon:status

# Listar supervisores
php artisan horizon:supervisors

# Monitorear colas
php artisan queue:monitor
```

### Control de Procesamiento
```bash
# Pausar todo
php artisan horizon:pause

# Continuar procesamiento
php artisan horizon:continue

# Terminar y reiniciar
php artisan horizon:terminate
```

### Gestión de Trabajos
```bash
# Limpiar cola específica
php artisan horizon:clear quiz_processing

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Eliminar trabajos fallidos
php artisan queue:flush
```

## Acceso al Dashboard

### Local
```
https://trainingms.test/horizon
```

### Producción
```
https://tu-dominio.com/horizon
```

**Acceso autorizado para**: alfredo@nhoamx.com

## Métricas y Monitoreo

### KPIs Principales
- **Throughput**: Trabajos procesados por minuto
- **Tiempo promedio**: Duración de procesamiento
- **Tasa de fallos**: Porcentaje de trabajos fallidos
- **Uso de memoria**: Consumo por supervisor

### Alertas Automáticas
- **Cola lenta**: Más de 2 minutos en `quiz_processing`
- **Supervisor inactivo**: Sin procesamiento en 5 minutos
- **Memoria alta**: Más de 80% de límite
- **Fallos frecuentes**: Más de 5% de tasa de error

## Configuración de Producción

### 1. Prerequisitos
```bash
# Instalar Redis
sudo apt install redis-server

# Instalar Supervisor
sudo apt install supervisor
```

### 2. Configurar Supervisor
```bash
# Copiar configuración
sudo cp supervisor/trainingms-horizon.conf /etc/supervisor/conf.d/

# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar Horizon
sudo supervisorctl start trainingms-horizon:*
```

### 3. Configurar Variables de Entorno
```bash
# Copiar configuración de producción
cp .env.production .env

# Editar valores específicos:
# - DB_PASSWORD
# - MAIL_PASSWORD
# - AWS_ACCESS_KEY_ID
# - AWS_SECRET_ACCESS_KEY
# - REVERB_APP_KEY, etc.
```

### 4. Ejecutar Script de Configuración
```bash
chmod +x scripts/setup-horizon-production.sh
./scripts/setup-horizon-production.sh
```

## Resolución de Problemas

### Horizon No Inicia
```bash
# Verificar Redis
redis-cli ping

# Verificar configuración
php artisan horizon:status

# Revisar logs
tail -f /var/log/supervisor/horizon.log
```

### Trabajos Se Acumulan
```bash
# Verificar supervisores activos
php artisan horizon:supervisors

# Aumentar procesos (temporal)
php artisan horizon:pause
# Editar config/horizon.php
php artisan horizon:continue
```

### Memoria Alta
```bash
# Reiniciar Horizon
php artisan horizon:terminate

# Limpiar caché
php artisan cache:clear
php artisan config:clear
```

### Trabajos Fallidos Frecuentes
```bash
# Ver últimos errores
php artisan queue:failed

# Revisar logs específicos
tail -f storage/logs/laravel.log | grep "ProcessQuizSubmission"
```

## Optimizaciones Específicas NOM-035

### Para Evaluaciones Masivas
1. **Aumentar procesos**: Editar `maxProcesses` en config/horizon.php
2. **Usar chunking**: Nuestros jobs ya procesan en lotes de 500
3. **Monitorear memoria**: Vigilar uso durante picos de carga

### Para Organizaciones Grandes (+500 empleados)
1. **Configurar timeout extendido**: 10-15 minutos
2. **Aumentar memoria**: 1GB por proceso
3. **Usar colas dedicadas**: Separar por organización si es necesario

### Para Cumplimiento Normativo
1. **Mantener logs**: Configurar rotación automática
2. **Backup de trabajos**: Exportar métricas mensualmente
3. **Monitoreo 24/7**: Configurar alertas críticas

## Mantenimiento Regular

### Diario
- Revisar dashboard para trabajos fallidos
- Verificar uso de memoria y CPU
- Comprobar que no hay acumulación de trabajos

### Semanal
- Limpiar trabajos completados antiguos
- Revisar métricas de rendimiento
- Verificar logs de errores

### Mensual
- Optimizar configuración basada en métricas
- Actualizar documentación de incidentes
- Revisar y ajustar alertas

---

**Documentación actualizada**: Septiembre 2025  
**Responsable**: alfredo@nhoamx.com  
**Sistema**: TrainingMS NOM-035