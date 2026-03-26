# Skill: Uso de la clase Tools

Esta skill describe cómo utilizar la clase `FacturaScripts\Core\Tools` para realizar operaciones comunes en el sistema.

## Cuándo usar
- Formateo de fechas (`date`, `dateTime`).
- Formateo de números y dinero (`number`, `money`).
- Operaciones con carpetas (`folder`, `folderCheckOrCreate`, `folderCopy`).
- Acceso a configuraciones y ajustes (`config`, `settings`).
- Generación de strings aleatorios o contraseñas.
- Obtención de la URL del sitio.

## Reglas
- La clase `Tools` es estática, se debe llamar como `Tools::metodo()`.
- Siempre que se necesite formatear una fecha para mostrar al usuario, usar `Tools::date()` o `Tools::dateTime()`.
- Para redondear importes, usar `Tools::round()`.
- Para traducciones rápidas desde `Tools`, usar `Tools::trans()`.

## Ejemplos de uso

### Formatear dinero
```php
use FacturaScripts\Core\Tools;

// Formatea 123.45 con la divisa 'EUR'
echo Tools::money(123.45, 'EUR');
```

### Obtener una configuración
```php
use FacturaScripts\Core\Tools;

// Obtiene el valor de 'base_url' en el config.php, o 'http://localhost' por defecto
$url = Tools::config('base_url', 'http://localhost');
```

### Manejo de fechas
```php
use FacturaScripts\Core\Tools;

// Formatea la fecha actual para mostrar al usuario
echo Tools::date(date('Y-m-d'));

// Sumar 1 mes a una fecha
$nextMonth = Tools::dateOperation(date('Y-m-d'), '+1 month');
```

### Acceso a ajustes (Settings)
```php
use FacturaScripts\Core\Tools;

// Obtener un ajuste de la base de datos (tabla settings)
$companyName = Tools::settings('default', 'company_name', 'Mi Empresa');
```

### Guardar o actualizar ajustes (Settings)
```php
use FacturaScripts\Core\Tools;

// Establecer un ajuste de la base de datos (tabla settings)
Tools::settingsSet('default', 'company_name', 'Mi Empresa 2');
Tools::settingsSave();
```