# Browsershot - Solución para Producción Linux

## Problema

Al intentar generar PDFs con Browsershot en producción (servidor Linux), se presenta el siguiente error:

```
Error: Failed to launch the browser process: Code: null

stderr:
[FATAL:content/browser/zygote_host/zygote_host_impl_linux.cc:132] No usable sandbox! 
If you are running on Ubuntu 23.10+ or another Linux distro that has disabled unprivileged 
user namespaces with AppArmor, see https://chromium.googlesource.com/chromium/src/+/main/docs/security/apparmor-userns-restrictions.md
```

### Causa

Chrome/Chromium en Linux requiere privilegios especiales para ejecutar su sandbox de seguridad. En servidores de producción con:
- Ubuntu 23.10+
- Otras distribuciones Linux con restricciones de AppArmor
- Ambientes containerizados
- Usuarios sin privilegios elevados

El sandbox no puede ejecutarse correctamente, causando que Puppeteer/Browsershot falle.

## Solución

### 1. Agregar Flags de Chrome

Se agregó el método `configureBrowsershot()` en `ReportPdfController` que detecta automáticamente si está en producción Linux y agrega los flags necesarios:

```php
protected function configureBrowsershot(string $html): Browsershot
{
    $browsershot = Browsershot::html($html)
        ->paperSize(8.5, 11, 'in')
        ->margins(0, 0, 0, 0)
        ->waitUntilNetworkIdle()
        ->timeout(120)
        ->showBackground();

    // Add --no-sandbox flag for production Linux servers
    if (PHP_OS_FAMILY === 'Linux' && app()->isProduction()) {
        $browsershot->addChromiumArguments([
            'no-sandbox',
            'disable-setuid-sandbox',
        ]);
    }

    return $browsershot;
}
```

### 2. Flags Explicados

- **`no-sandbox`**: Deshabilita el sandbox de Chrome, permitiendo que se ejecute sin privilegios especiales
- **`disable-setuid-sandbox`**: Deshabilita el sandbox SUID alternativo

### 3. Consideraciones de Seguridad

⚠️ **Importante**: Deshabilitar el sandbox de Chrome reduce la seguridad del proceso de renderizado. Sin embargo:

1. **Solo se aplica en producción**: No afecta desarrollo local
2. **Solo en Linux**: No afecta Windows/Mac
3. **Alternativas más complejas**:
   - Configurar namespaces de usuario en el servidor
   - Ejecutar con privilegios elevados (más riesgoso)
   - Usar containers específicos para Chrome

4. **Mitigación de riesgos**:
   - El HTML renderizado proviene de vistas blade controladas (no input de usuarios)
   - El proceso es temporal y se elimina después de generar el PDF
   - Solo usuarios admin/super-admin pueden generar reportes

## Funciona En Local

El problema **no ocurre en desarrollo local** porque:
- Windows/Mac no tienen las mismas restricciones de AppArmor
- Los usuarios de desarrollo típicamente tienen más privilegios
- Laravel Herd/Valet configuran automáticamente el ambiente

## Testing en Producción

Para verificar que funciona correctamente en producción:

1. Deploy los cambios al servidor
2. Intentar generar un reporte PDF
3. Verificar que se descarga correctamente
4. Revisar logs en caso de errores: `storage/logs/laravel.log`

## Referencias

- [Spatie Browsershot Documentation](https://github.com/spatie/browsershot)
- [Puppeteer Troubleshooting](https://pptr.dev/troubleshooting)
- [Chrome AppArmor Restrictions](https://chromium.googlesource.com/chromium/src/+/main/docs/security/apparmor-userns-restrictions.md)
- [Chrome SUID Sandbox](https://chromium.googlesource.com/chromium/src/+/main/docs/linux/suid_sandbox_development.md)

## Archivos Modificados

- `app/Http/Controllers/ReportPdfController.php`
  - Agregado método `configureBrowsershot()`
  - Actualizado `downloadDemographicReport()` para usar el método
  - Actualizado `downloadDiagnosticReport()` para usar el método

## Fecha de Implementación

26 de octubre, 2025
