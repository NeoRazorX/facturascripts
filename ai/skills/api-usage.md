# Uso de la API en FacturaScripts

FacturaScripts proporciona una API RESTful para interactuar con el sistema de forma programática. La API permite tanto el acceso a modelos existentes como la creación de endpoints personalizados.

## Estructura de la URL

La URL base para la API es: `/api/{version}/{resource}`

- **Versión**: Actualmente es `3`.
- **Recurso**: El nombre del recurso (ej: `productos`, `facturas`, `clientes`).

## Autenticación

La API requiere un token de autenticación que debe enviarse en las cabeceras HTTP:

- `Token`: Tu clave de API generada en el panel de control.

## Modelos automáticos (APIModel)

Por defecto, FacturaScripts expone todos los modelos situados en `Dinamic/Model` a través de la API de forma automática mediante la clase `APIModel`. Los nombres de los recursos se pluralizan (ej: `producto` -> `productos`).

Soportan las siguientes operaciones estándar:
- `GET /api/3/{recurso}`: Listar registros (soporta filtros, limit, offset y sort).
- `GET /api/3/{recurso}/{id}`: Obtener un registro específico.
- `POST /api/3/{recurso}`: Crear un nuevo registro.
- `PUT /api/3/{recurso}/{id}`: Actualizar un registro.
- `DELETE /api/3/{recurso}/{id}`: Eliminar un registro.

## Añadir un endpoint personalizado

Existen dos formas de ampliar la API:

### 1. Añadir un Recurso
Todos los modelos listados en `Dinamic/Model` apareceran como recursos disponibles.


### 2. Añadir un Endpoint de Acción (ApiController)
Para endpoints que ejecutan acciones específicas (ej: `crearFactura`):

1. Crea un controlador en `Plugins/{TuPlugin}/Controller/ApiController{Accion}.php`.
2. La clase debe heredar de `FacturaScripts\Core\Template\ApiController`.
3. Implementa el método `runResource()`.
4. **IMPORTANTE**: Debes registrar el endpoint en el archivo `Init.php` de tu plugin.

#### Registro en Init.php
```php
namespace FacturaScripts\Plugins\MiPlugin;

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Controller\ApiRoot;
use FacturaScripts\Core\Template\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        Kernel::addRoute('/api/3/ApiControllerPruebas', 'ApiControllerPruebas', -1);
        ApiRoot::addCustomResource('pruebas');
    }
    // ...
}
```

#### Ejemplo de ApiController
```php
namespace FacturaScripts\Plugins\MiPlugin\Controller;

use FacturaScripts\Core\Template\ApiController;

class ApiControllerMiAccion extends ApiController
{
    public function runResource(): void
    {
        // Tu lógica aquí
        $this->response->json(['ok' => 'Acción ejecutada']);
    }
}
```

## Filtrado y paginación (en APIModel)

Cuando se consultan modelos automáticos, se pueden usar parámetros en la URL:
- `limit`: Número de resultados (por defecto 50).
- `offset`: Desplazamiento para paginación.
- `filter[campo]`: Filtrar por un campo (ej: `filter[codcliente]=1`).
- `filter[campo_gt]`, `filter[campo_lt]`, `filter[campo_gte]`, `filter[campo_lte]`, `filter[campo_neq]`, `filter[campo_like]`: Operadores de comparación.
- `sort[campo]`: Ordenar por campo (`asc` o `desc`).

## Respuestas

La API responde siempre con un objeto JSON.

### Éxito (200 OK)
```json
{
  "ok": "Mensaje informativo",
  "data": { ... }
}
```

### Error (400 Bad Request, 401 Unauthorized, etc.)
```json
{
  "error": "Descripción del error",
  "data": { ... }
}
```
