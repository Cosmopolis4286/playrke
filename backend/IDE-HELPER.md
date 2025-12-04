# 🔧 Laravel IDE Helper

Este proyecto usa **Laravel IDE Helper** para eliminar los falsos positivos del IDE (VS Code, PHPStorm, etc.) y proporcionar autocompletado completo para Facades, modelos y helpers de Laravel.

## 📦 Archivos Generados

Los siguientes archivos son auto-generados y **NO** deben ser commiteados:

- `_ide_helper.php` - Definiciones para Facades y helpers globales
- `_ide_helper_models.php` - PHPDocs para modelos Eloquent
- `.phpstorm.meta.php` - Metadata para PhpStorm

## 🚀 Uso en Docker

### Regenerar archivos de ayuda

```bash
# Opción 1: Usando el script de composer
docker-compose exec backend composer ide-helper

# Opción 2: Comandos individuales
docker-compose exec backend php artisan ide-helper:generate
docker-compose exec backend php artisan ide-helper:meta
docker-compose exec backend php artisan ide-helper:models --nowrite
```

### Después de cambios importantes

Regenera los helpers después de:
- ✅ Actualizar dependencias de Composer
- ✅ Crear o modificar modelos Eloquent
- ✅ Agregar nuevas Facades personalizadas
- ✅ Cambiar la estructura de namespaces

## 📝 Configuración del IDE

### VS Code (Intelephense)

Agrega a tu `.vscode/settings.json`:

```json
{
  "intelephense.stubs": [
    "laravel",
    "Core",
    "PDO",
    "mysql",
    "redis"
  ],
  "intelephense.files.associations": [
    "*.php"
  ]
}
```

### PHPStorm

PHPStorm detecta automáticamente `.phpstorm.meta.php`. No requiere configuración adicional.

## ⚙️ Comandos Disponibles

| Comando | Descripción |
|---------|-------------|
| `ide-helper:generate` | Genera `_ide_helper.php` con Facades y helpers |
| `ide-helper:models` | Genera PHPDocs para modelos (agrega `--write` para escribir) |
| `ide-helper:meta` | Genera `.phpstorm.meta.php` para PhpStorm |
| `ide-helper:eloquent` | Genera código de ayuda para Eloquent |

## 🔄 Integración CI/CD

**NO** ejecutes estos comandos en CI/CD ya que:
- Son solo para desarrollo local
- Los archivos están en `.gitignore`
- No afectan la ejecución del código

## 📚 Documentación

- [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)
- [Intelephense](https://intelephense.com/)
