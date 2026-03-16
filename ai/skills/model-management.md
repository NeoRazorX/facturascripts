# Skill: Gestión de Modelos (ModelClass)

Esta skill describe cómo trabajar con modelos de datos en FacturaScripts utilizando la clase base `ModelClass`.

## Cuándo usar
- Realizar operaciones CRUD (Crear, Leer, Actualizar, Borrar).
- Buscar registros en la base de datos.
- Obtener totales o conteos de registros.
- Gestionar la persistencia de datos de forma segura.

## Reglas
- Todos los modelos deben extender de `ModelClass` (o `ModelCore` indirectamente).
- Usar el método `save()` para insertar o actualizar registros. FacturaScripts detecta automáticamente si el registro existe.
- Los modelos representan tablas de la base de datos.
- No escribir consultas SQL manuales si el modelo puede resolverlo.

## Ejemplos de uso

### Obtener un registro por su clave primaria
```php
$user = new User();
if ($user->load('admin')) {
    echo "Usuario encontrado: " . $user->nick;
}
```

### Buscar múltiples registros
```php
$userModel = new User();
// all(where, order, offset, limit)
$activeUsers = $userModel->all([Where::eq('enabled' => true)], ['nick' => 'ASC'], 0, 10);

foreach ($activeUsers as $user) {
    echo $user->nick;
}
```

### Crear y guardar un nuevo registro
```php
$user = new User();
$user->nick = 'nuevo_usuario';
$user->password = password_hash('secreto', PASSWORD_DEFAULT);
$user->email = 'nuevo@ejemplo.com';
$user->enabled = true;

if ($user->save()) {
    echo "Usuario guardado correctamente.";
}
```

### Contar registros con condiciones
```php
$totalAdmins = User::count([Where::eq('admin' => true)]);
echo "Total de administradores: " . $totalAdmins;
```

### Eliminar un registro
```php
$user = new User();
if ($user->load('usuario_a_borrar')) {
    $user->delete();
}
```
