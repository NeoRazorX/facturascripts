# Skill: Traducción e Internacionalización (Translator)

Esta skill detalla cómo usar la clase `FacturaScripts\Core\Translator` para traducir textos y manejar idiomas.

## Cuándo usar
- Traducir una cadena de texto a un idioma determinado.
- Consultar los idiomas instalados o disponibles.
- Cambiar el idioma de la sesión actual.
- Buscar claves de traducción.

## Reglas
- Para traducciones rápidas dentro de controladores o modelos, usar `Tools::trans()`.
- Para obtener la instancia del traductor de un idioma específico, usar `Tools::lang('es_ES')`.
- Las traducciones se definen en archivos JSON dentro de `Core/Translation/` o en las carpetas `Translation/` de cada plugin.

## Ejemplos de uso

### Traducción simple con parámetros
```php
use FacturaScripts\Core\Tools;

// Traduce la cadena y reemplaza %1 por el valor indicado
// Las claves de traducción suelen ser palabras en inglés
echo Tools::trans('order-number', ['%1' => '2025-001']);
```

### Usar una instancia de Translator directamente
```php
use FacturaScripts\Core\Tools;

$traductor = Tools::lang('es_ES');
echo $traductor->trans('save');
```

### Consultar idiomas disponibles
```php
use FacturaScripts\Core\Tools;

$traductor = Tools::lang();
$idiomas = $traductor->getAvailableLanguages();
foreach ($idiomas as $id => $nombre) {
    echo "ID: $id, Nombre: $nombre";
}
```

### Traducir a un idioma específico (sin cambiar el global)
```php
use FacturaScripts\Core\Translator;

// Traduce 'invoice' al francés
echo Translator::customTrans('fr_FR', 'invoice');
```
