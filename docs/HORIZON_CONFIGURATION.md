# Guía de Laravel Horizon para TrainingMS con Laravel Forge

## Resumen

Laravel Horizon está configurado para manejar el procesamiento asíncrono de evaluaciones psicológicas NOM-035 con monitoreo avanzado y notificaciones por correo, desplegado mediante **Laravel Forge**.

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

#### Producción (Forge):
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

## Configuración en Laravel Forge

### 🚀 **1. Configurar Daemon en Forge**

En el panel de Laravel Forge:

1. **Ir a tu servidor** → **Daemons**
2. **Crear nuevo Daemon:**
   ```
   Command: php artisan horizon
   User: forge
   Directory: /home/forge/tu-dominio.com
   Processes: 1
   ```
3. **Activar**: Auto-restart on failure

### 🔧 **2. Variables de Entorno en Forge**

En **Environment Variables**:
```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
MAIL_FROM_ADDRESS=alfredo@nhoamx.com
MAIL_FROM_NAME="TrainingMS NOM-035"
HORIZON_DOMAIN=null
HORIZON_PATH=horizon
```

### 📦 **3. Script de Deployment en Forge**

En **Deploy Script**:
```bash
cd /home/forge/tu-dominio.com
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

# Restart Horizon (Forge lo reinicia automáticamente)
php artisan horizon:terminate

# Optimize for production
php artisan optimize
```

### 🔄 **4. Configurar Auto-Deploy**

1. **Quick Deploy**: Activado
2. **Deploy Branch**: main
3. **Deploy When Code is Pushed**: Activado

## Comandos Esenciales

### 🚀 **Para Desarrollo Local**
```bash
# Iniciar Horizon
php artisan horizon

# Estado actual
php artisan horizon:status

# Pausar procesamiento
php artisan horizon:pause

# Continuar procesamiento
php artisan horizon:continue
```

### 🖥️ **Para Producción con Forge**
- **Iniciar/Parar**: Se maneja desde el panel de Forge (Daemons)
- **Reiniciar**: Automático en cada deploy
- **Monitoreo**: Panel de Forge + Dashboard de Horizon

### 📊 **Monitoreo**
```bash
# Listar supervisores
php artisan horizon:supervisors

# Monitorear colas
php artisan queue:monitor

# Ver trabajos fallidos
php artisan queue:failed
```

### 🛠️ **Gestión de Trabajos**
```bash
# Limpiar cola específica
php artisan horizon:clear quiz_processing

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

## Métricas y Monitoreo en Forge

### 📈 **KPIs Disponibles**
- **Throughput**: Trabajos procesados por minuto
- **Tiempo promedio**: Duración de procesamiento
- **Tasa de fallos**: Porcentaje de trabajos fallidos
- **Uso de memoria**: Consumo por supervisor

### 🚨 **Alertas Automáticas**
- **Cola lenta**: Más de 2 minutos en `quiz_processing`
- **Daemon inactivo**: Sin procesamiento en 5 minutos
- **Memoria alta**: Más de 80% de límite
- **Fallos frecuentes**: Más de 5% de tasa de error

### 📧 **Notificaciones de Forge**
- Configurar en **Notifications** del servidor
- Agregar alfredo@nhoamx.com para alertas del daemon
- Configurar webhooks si es necesario

## Configuración Específica para NOM-035

### 🏢 **Para Evaluaciones Masivas**
1. **Aumentar procesos en Forge**: Cambiar a 2-3 daemons si es necesario
2. **Monitorear memoria**: Verificar uso durante picos de carga
3. **Usar chunking**: Nuestros jobs ya procesan en lotes de 500

### 👥 **Para Organizaciones Grandes (+500 empleados)**
1. **Configurar timeout extendido**: Aumentar timeout en config/horizon.php
2. **Aumentar memoria**: Configurar en Forge si es necesario
3. **Monitoreo activo**: Vigilar dashboard durante evaluaciones masivas

### 📋 **Para Cumplimiento Normativo**
1. **Logs automáticos**: Forge maneja rotación de logs
2. **Backup de métricas**: Exportar desde Horizon mensualmente
3. **Monitoreo 24/7**: Configurar alertas críticas en Forge

## Resolución de Problemas con Forge

### 🔧 **Horizon No Inicia en Forge**
1. **Verificar Daemon**: En panel de Forge → Daemons → Ver estado
2. **Revisar logs**: En panel de Forge → Logs → Daemon logs
3. **Verificar Redis**: Debe estar instalado y funcionando
4. **Reiniciar Daemon**: Desde panel de Forge

### 📦 **Trabajos Se Acumulan**
1. **Verificar Daemon activo**: En panel de Forge
2. **Aumentar procesos**: Crear daemon adicional si es necesario
3. **Verificar memoria**: Monitorear uso del servidor

### 🚨 **Problemas de Deployment**
1. **Verificar script de deploy**: Debe incluir `horizon:terminate`
2. **Revisar variables de entorno**: QUEUE_CONNECTION=redis
3. **Verificar permisos**: Usuario forge debe tener acceso

### 📊 **Métricas No Aparecen**
1. **Verificar configuración Redis**: Debe estar funcionando
2. **Limpiar caché**: Ejecutar desde SSH o panel
3. **Verificar dashboard**: Acceso correcto a /horizon

## Checklist de Deployment en Forge

### ✅ **Antes del Deploy**
- [ ] Redis instalado y funcionando
- [ ] Variables de entorno configuradas
- [ ] Daemon de Horizon creado en Forge
- [ ] Script de deployment configurado
- [ ] Notificaciones de correo configuradas

### ✅ **Durante el Deploy**
- [ ] Verificar que el deploy termina sin errores
- [ ] Comprobar que Horizon reinicia automáticamente
- [ ] Verificar acceso al dashboard /horizon
- [ ] Probar envío de evaluación de prueba

### ✅ **Después del Deploy**
- [ ] Monitorear dashboard por 10-15 minutos
- [ ] Verificar procesamiento de trabajos
- [ ] Confirmar notificaciones por correo
- [ ] Documentar cualquier configuración adicional

## Mantenimiento con Forge

### 📅 **Diario**
- Revisar dashboard de Horizon para trabajos fallidos
- Verificar estado del Daemon en panel de Forge
- Comprobar uso de memoria y CPU del servidor

### 📅 **Semanal**
- Limpiar trabajos completados antiguos
- Revisar métricas de rendimiento en Horizon
- Verificar logs de errores en panel de Forge

### 📅 **Mensual**
- Optimizar configuración basada en métricas
- Revisar y ajustar alertas de notificaciones
- Evaluar necesidad de escalamiento

---

**Documentación actualizada para Laravel Forge**: Septiembre 2025  
**Responsable**: alfredo@nhoamx.com  
**Sistema**: TrainingMS NOM-035