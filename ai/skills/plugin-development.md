# Skill: Extensión mediante Plugins

Esta skill describe cómo extender FacturaScripts mediante la creación o modificación de plugins, siguiendo la arquitectura modular del sistema.

## Cuándo usar
- Añadir nuevas funcionalidades sin modificar el código del núcleo (`Core`).
- Modificar el comportamiento de modelos o controladores existentes.
- Añadir nuevos modelos, controladores, vistas o tablas.

## Reglas
- Los plugins se ubican en la carpeta `Plugins/`.
- Cada plugin tiene su propia estructura de carpetas similar al núcleo (`Controller/`, `Model/`, `Table/`, `XMLView/`, `Translation/`).
- Se debe preferir el uso de extensiones (`Extending models/controllers`) antes que la herencia directa.
- El archivo `facturascripts.ini` en la raíz del plugin define sus metadatos.

## Estructura de un plugin
```text
Plugins/MiNuevoPlugin/
├── facturascripts.ini      (Metadatos del plugin)
├── Init.php                (Registro de extensiones y lógica de arranque)
├── Controller/             (Controladores propios)
├── Model/                  (Modelos propios)
├── Table/                  (Definición de tablas)
├── XMLView/                (Definición de vistas)
├── Translation/            (Traducciones .json)
├── Extension/              (Extensiones de lógica del Core)
│   ├── Controller/         (Extender controladores existentes)
│   └── Model/              (Extender modelos existentes)
└── View/                   (Plantillas Twig personalizadas)
```

## Ejemplo de uso

### Archivo facturascripts.ini
```ini
name = 'MiNuevoPlugin'
description = 'Descripción de mi plugin'
version = 1.0
author = 'MiNombre'
```

### Registro de extensiones en Init.php
Todas las extensiones de modelos y controladores deben registrarse en el archivo `Init.php` de la raíz del plugin para que el sistema las reconozca al cargar el plugin.

```php
namespace FacturaScripts\Plugins\MiNuevoPlugin;

use FacturaScripts\Core\Controller\ApiRoot;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        // Registrar extensión de Modelo
        $this->loadExtension(new Extension\Model\Cliente());
        
        // Registrar extensión de Controlador
        $this->loadExtension(new Extension\Controller\ListCliente());

        // Registrar endpoint personalizado de la API (si aplica)
        Kernel::addRoute('/api/3/miAccion', 'ApiControllerMiAccion');
        ApiRoot::addCustomResource('miAccion');
    }
    
    public function uninstall(): void
    {
        // Lógica al desinstalar el plugin
    }

    public function update(): void
    {
        // Lógica de actualización si es necesaria
    }
}
```

### Extender un Modelo del núcleo
Para añadir lógica a un modelo existente (ej: `Cliente`), se crea un archivo en `Plugins/MiNuevoPlugin/Extension/Model/Cliente.php`:

```php
namespace FacturaScripts\Plugins\MiNuevoPlugin\Extension\Model;

use FacturaScripts\Closure;

class Cliente
{
    public function customMethod(): Closure
    {
        return function () {
            return "Lógica añadida al modelo Cliente";
        };
    }
}
```

### Extender un Controlador del núcleo
Para añadir acciones o cambiar el comportamiento de una página:

```php
namespace FacturaScripts\Plugins\MiNuevoPlugin\Extension\Controller;

use FacturaScripts\Closure;

class ListCliente
{
    public function createViews(): Closure
    {
        return function () {
            return "Lógica añadida al controlador ListCliente";
        };
    }
}
```
