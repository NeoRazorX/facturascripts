# Extensiones y Ganchos (Pipes y Hooks)

FacturaScripts permite extender la funcionalidad del sistema sin modificar el código del núcleo mediante **Extensiones
** (en PHP) y **Ganchos** (en plantillas Twig). Esta es la forma preferida de personalización antes que la herencia.

## Extensiones (Backend PHP)

Las extensiones se gestionan mediante el trait `FacturaScripts\Core\Template\ExtensionsTrait`, que es utilizado por
controladores y modelos.

### Cómo funcionan las extensiones (`pipe`)

En el código del núcleo, existen llamadas a `$this->pipe('nombre_del_evento', ...$argumentos)`. Los plugins pueden
registrar funciones que se ejecutan en estos puntos.

- **`pipe(string $name, ...$arguments)`**: Ejecuta las extensiones registradas para ese nombre. Si una extensión
  devuelve un valor distinto de `null`, se detiene la ejecución y se devuelve ese valor.
- **`pipeFalse(string $name, ...$arguments)`**: Ejecuta las extensiones hasta que una devuelva `false`. Si alguna
  devuelve `false`, el método devuelve `false`. Si todas devuelven algo distinto de `false`, devuelve `true`.

### Ejemplo de uso en un Controlador (Núcleo)

```php
public function createViews(): void
{
    // ... lógica para crear vistas
    $this->pipe('createViews');
}
```

### Cómo registrar una extensión desde un Plugin

Añade `pipe('nombre_extension')` en cualquier archivo php para dar soporte a aque otros plugins puedan añadir contenido
en ese pipe. Adicionalmente, puedes añadir variables después del nombre del pipe.

```php
$this->pipe('nombre_del_pipe', $data);
```

### Registro de extensiones en Init.php

Todas las extensiones deben registrarse en el archivo `Init.php` de la raíz del plugin para que FacturaScripts las
reconozca al cargar el plugin.

```php
namespace FacturaScripts\Plugins\MiNuevoPlugin;

use FacturaScripts\Core\Base\InitClass;

class Init extends InitClass
{
    public function init(): void
    {
        // Registrar extensión de Modelo
        $this->loadExtension(new Extension\Model\Cliente());
        
        // Registrar extensión de Controlador
        $this->loadExtension(new Extension\Controller\ListCliente());
    }
}
```

---

## Ganchos (Frontend Twig)

Los ganchos (hooks) permiten a los plugins añadir contenido HTML en lugares específicos de las plantillas del núcleo sin
sobrescribir el archivo `.html.twig` completo.

### Cómo funcionan los ganchos

En las plantillas Twig, se encuentran llamadas a ganchos que los plugins pueden aprovechar para inyectar código.

### Ejemplo de uso en Twig (Núcleo)

```twig
{# En alguna plantilla del núcleo #}
{% for includeView in getIncludeViews('MenuTemplate', 'HeadFirst') %}
        {% include includeView['path'] %}
    {% endfor %}
```

### Cómo usar un gancho desde un Plugin

Los plugins pueden registrar contenido para estos ganchos, lo que permite añadir campos a formularios, botones
adicionales o información extra en listados.

Crear el archivo dentro de `Extension\View\MenuTemplate_HeadFirst.html.twig` para añadir código al archivo y posición
indicada en el propio nombre del archivo, como `MenuTemplate` es esl archivo y `HeadFirst` es la posición.

---

## Reglas de Oro

1. **Prioridad**: Usa siempre extensiones/pipes si existen antes que recurrir a la herencia de clases.
2. **No invasivo**: Las extensiones permiten que múltiples plugins interactúen con la misma parte del sistema sin
   conflictos.
3. **Pipes de retorno**: Si un pipe debe devolver un valor (ej: un cálculo), asegúrate de conocer qué espera el núcleo.
4. **Ganchos de Twig**: Úsalos para añadir elementos visuales, scripts o estilos específicos en una vista.
