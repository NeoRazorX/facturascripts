# Guía de desarrollo - FacturaScripts

Este archivo contiene los comandos más importantes para el desarrollo y mantenimiento del proyecto.

---

## Instalación de dependencias

- Instalar las dependencias de PHP con Composer:
  
  ```
  composer install
  ```

- Instalar las dependencias de JavaScript con npm:
  
  ```
  npm install
  ```

## Ejecutar tests

Para ejecutar las pruebas unitarias del proyecto, utiliza:

```
vendor/bin/phpunit
```

## Análisis estático

Para realizar un análisis estático del código en el directorio `Core` con PHPStan:

```
vendor/bin/phpstan analyse Core
```

## Verificar y corregir estilo de código

- Verificar el estilo de código con PHP_CodeSniffer:

  ```
  vendor/bin/phpcs
  ```

- Corregir automáticamente los errores de estilo:

  ```
  vendor/bin/phpcbf
  ```

---

## Instalación de fsmaker

`fsmaker` es una herramienta CLI diseñada para acelerar el desarrollo de plugins para FacturaScripts.

Para instalar `fsmaker` globalmente con Composer, ejecuta:

```
composer global require facturascripts/fsmaker
```

Luego, asegúrate de que el binario esté en tu PATH creando un enlace simbólico:

```
sudo ln -s ~/.config/composer/vendor/bin/fsmaker /usr/local/bin/fsmaker
```