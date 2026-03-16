# Uso de la clase Http en FacturaScripts

La clase `FacturaScripts\Core\Http` es una utilidad que envuelve cURL para realizar peticiones HTTP externas de forma sencilla y orientada a objetos.

## Peticiones GET

```php
use FacturaScripts\Core\Http;

$http = new Http();
$response = $http->get('https://api.ejemplo.com/datos', ['param' => 'valor']);

if ($response->ok()) {
    $body = $response->body();
}
```

## Peticiones POST y Envío de JSON

```php
use FacturaScripts\Core\Http;

$http = new Http();
$data = ['nombre' => 'Juan', 'email' => 'juan@ejemplo.com'];

// POST normal
$response = $http->post('https://api.ejemplo.com/usuarios', $data);

// POST JSON (envía los datos como cuerpo JSON y añade la cabecera Content-Type correspondiente)
$response = $http->postJson('https://api.ejemplo.com/usuarios', $data);

if ($response->ok()) {
    $result = $response->json(); // devuelve un objeto (o array si se pasa true)
}
```

## Otros Métodos

- `put(url, data)`: Petición PUT.
- `patch(url, data)`: Petición PATCH.
- `delete(url, data)`: Petición DELETE.

## Configuración de la Petición

Se pueden encadenar métodos para configurar la petición antes de ejecutarla (aunque `get`, `post`, etc., la ejecutan inmediatamente, se pueden configurar cabeceras previamente).

```php
$http = new Http();
$http->setHeaders(['Authorization' => 'Bearer token123'])
     ->setTimeout(10)
     ->setUserAgent('MiApp/1.0')
     ->post('https://api.ejemplo.com', $data);
```

### Autenticación Común

- `setToken(token)`: Añade cabecera `Authorization: Token {token}`.
- `setBearerToken(token)`: Añade cabecera `Authorization: Bearer {token}`.
- `setUser(user, password)`: Añade autenticación básica HTTP.

## Manejo de la Respuesta

- `ok()`: Devuelve `true` si el código de estado es 200-299.
- `failed()`: Devuelve `true` si no es `ok()`.
- `status()`: Devuelve el código de estado HTTP (ej: 404, 500).
- `body()`: El cuerpo de la respuesta en crudo.
- `json(associative = false)`: Parsea el cuerpo como JSON.
- `errorMessage()`: Devuelve el mensaje de error de cURL si hubo fallo.
- `saveAs(filename)`: Guarda la respuesta directamente en un archivo (ideal para descargas).
