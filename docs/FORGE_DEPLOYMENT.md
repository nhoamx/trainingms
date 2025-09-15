# Configuración de TrainingMS en Laravel Forge

## Variables de Entorno Requeridas

```env
# Aplicación
APP_NAME="TrainingMS NOM-035"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forge
DB_USERNAME=forge
DB_PASSWORD=tu-password-de-db

# Colas y Cache (Redis)
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alfredo@nhoamx.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=alfredo@nhoamx.com
MAIL_FROM_NAME="TrainingMS NOM-035"

# Horizon
HORIZON_PATH=horizon
```

## Script de Deployment para Forge

```bash
cd /home/forge/tu-dominio.com

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and optimize
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart workers and Horizon
php artisan queue:restart
php artisan horizon:terminate

# Clear expired cache entries
php artisan cache:prune-stale-tags
```

## Configuración de Daemon en Forge

```
Command: php artisan horizon
User: forge
Directory: /home/forge/tu-dominio.com
Processes: 1
Auto-restart: Enabled
```

## Configuración de Worker (alternativa sin Horizon)

Si prefieres usar workers tradicionales en lugar de Horizon:

```
Command: php artisan queue:work redis --timeout=300 --tries=3 --memory=256
User: forge
Directory: /home/forge/tu-dominio.com
Processes: 3
Auto-restart: Enabled
```

## Configuración de Cron Job

```
* * * * * cd /home/forge/tu-dominio.com && php artisan schedule:run >> /dev/null 2>&1
```

## Configuración de Nginx (si es necesario)

Para el dashboard de Horizon, asegúrate de que la configuración de Nginx permite el acceso:

```nginx
location /horizon {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Notificaciones de Forge

Configurar en el panel de Forge → Notifications:

- **Email**: alfredo@nhoamx.com
- **Eventos**: Daemon failures, High CPU, High Memory
- **Webhooks**: Opcional para Slack/Discord

## Checklist de Configuración

### ✅ Servidor
- [ ] PHP 8.3+
- [ ] Redis instalado
- [ ] Composer actualizado
- [ ] Node.js y npm (para assets)

### ✅ Site
- [ ] Repositorio conectado
- [ ] Branch: main
- [ ] Quick Deploy activado
- [ ] SSL habilitado

### ✅ Database
- [ ] Base de datos creada
- [ ] Usuario configurado
- [ ] Migraciones ejecutadas

### ✅ Environment
- [ ] Variables configuradas
- [ ] QUEUE_CONNECTION=redis
- [ ] MAIL configurado
- [ ] APP_URL correcto

### ✅ Daemon/Worker
- [ ] Horizon daemon creado
- [ ] Auto-restart habilitado
- [ ] Estado: Running

### ✅ Deploy Script
- [ ] Script actualizado
- [ ] horizon:terminate incluido
- [ ] cache:clear incluido

### ✅ Testing
- [ ] Site accesible
- [ ] Horizon dashboard accesible
- [ ] Evaluación de prueba funciona
- [ ] Trabajos se procesan correctamente

## Comandos Útiles SSH

```bash
# Conectar por SSH
ssh forge@tu-server-ip

# Ver estado de Redis
redis-cli ping

# Ver trabajos en cola
php artisan queue:monitor

# Ver logs de Horizon
tail -f storage/logs/laravel.log

# Reiniciar Horizon manualmente
php artisan horizon:terminate

# Ver procesos activos
ps aux | grep horizon
```

## Monitoreo y Mantenimiento

### Métricas a Vigilar
- CPU usage del servidor
- Memory usage 
- Redis memory usage
- Queue job processing time
- Failed job rate

### Alertas Recomendadas
- CPU > 80% por 5 minutos
- Memory > 90% por 5 minutos  
- Daemon failure
- Failed jobs > 10 en 1 hora

### Backups
- Database: Diario automático en Forge
- Files: Semanal (si hay uploads importantes)
- Config: Manual cuando cambien variables

---
**Configuración específica para Laravel Forge**  
**Actualizado**: Septiembre 2025