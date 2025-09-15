#!/bin/bash

# Script de configuración de Horizon para producción
# TrainingMS - Sistema de Evaluaciones NOM-035-STPS-2018
# ====================================================

echo "🚀 Configurando Horizon para producción..."

# 1. Verificar que Redis está funcionando
echo "📡 Verificando conexión Redis..."
if redis-cli ping >/dev/null 2>&1; then
    echo "✅ Redis está funcionando correctamente"
else
    echo "❌ Error: Redis no está funcionando. Instalar e iniciar Redis primero."
    exit 1
fi

# 2. Limpiar caché y configuraciones
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Optimizar para producción
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 4. Ejecutar migraciones pendientes
echo "🗃️ Ejecutando migraciones..."
php artisan migrate --force

# 5. Crear tabla de trabajos fallidos si no existe
echo "📋 Configurando tabla de trabajos fallidos..."
php artisan queue:failed-table
php artisan migrate --force

# 6. Crear tabla de jobs de Redis si no existe
echo "📋 Configurando tabla de jobs..."
php artisan queue:table
php artisan migrate --force

# 7. Limpiar trabajos anteriores
echo "🧽 Limpiando trabajos anteriores..."
php artisan horizon:clear default
php artisan horizon:clear quiz_processing
php artisan horizon:clear image_processing
php artisan queue:flush

# 8. Verificar configuración de Horizon
echo "🔍 Verificando configuración de Horizon..."
php artisan horizon:status

echo ""
echo "✅ Configuración de Horizon completada"
echo ""
echo "📋 Para iniciar Horizon en producción:"
echo "   sudo supervisorctl start trainingms-horizon:*"
echo ""
echo "📊 Para acceder al dashboard:"
echo "   https://tu-dominio.com/horizon"
echo ""
echo "📧 Notificaciones configuradas para: alfredo@nhoamx.com"
echo ""
echo "🔧 Comandos útiles:"
echo "   php artisan horizon:status        - Ver estado"
echo "   php artisan horizon:pause         - Pausar procesamiento"
echo "   php artisan horizon:continue      - Continuar procesamiento"
echo "   php artisan horizon:terminate     - Terminar y reiniciar"
echo "   php artisan queue:monitor         - Monitorear colas"
echo ""