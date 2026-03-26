# AGENTS.md

Este archivo proporciona contexto y reglas para agentes de IA que trabajan sobre este repositorio.

## Propósito

FacturaScripts es un software ERP y CRM de código abierto escrito en PHP. Permite gestionar facturación, contabilidad,
inventario, clientes, proveedores, informes y análisis. La aplicación está diseñada con una arquitectura modular y
extensible mediante plugins.

---

## Stack técnico

Backend

- PHP >= 8.0
- MySQL, MariaDB o PostgreSQL
- Composer

Frontend

- Bootstrap 5
- Twig (plantillas)
- jQuery
- Select2
- Chart.js

Herramientas de desarrollo

- PHPUnit (tests)
- PHPStan (análisis estático)
- PHPCS (estándares de código)

---

## Estructura del proyecto

Código principal:

Core/

Directorios más importantes:

- Core/Controller/ → controladores de páginas
- Core/Model/ → modelos de datos (entidades de base de datos)
- Core/Lib/ → lógica de negocio y utilidades
- Core/Table/ → definición XML de tablas de base de datos
- Core/XMLView/ → definición XML de formularios y listados
- Core/View/ → plantillas Twig
- Core/Worker/ → tareas en segundo plano
- Core/Translation/ → traducciones

Otros directorios:

- Dinamic/ → clases generadas automáticamente
- Test/ → pruebas automatizadas
- vendor/ → dependencias PHP
- node_modules/ → dependencias JavaScript

---

## Arquitectura general

Controladores  
→ gestionan las peticiones y acciones de las páginas.

Modelos  
→ representan entidades de base de datos.

Lib  
→ contiene la lógica de negocio y servicios reutilizables.

XMLView  
→ define formularios y listados de forma declarativa.

Tablas XML
→ define la estructura de las tablas de base de datos.

---

## Reglas de arquitectura

Los agentes deben seguir estas reglas:

- La lógica de negocio debe ir en Core/Lib.
- Los controladores deben ser ligeros.
- Los modelos representan tablas de base de datos.
- La estructura de la base de datos se define en Core/Table mediante XML.
- Los formularios y listados se definen en Core/XMLView.
- Las plantillas de interfaz usan Twig en Core/View.
- No modificar código dentro de vendor/ o node_modules/.

---

## Código generado

El directorio Dinamic/ contiene clases generadas automáticamente. Los agentes nunca deben modificar archivos dentro de
Dinamic/.

---

## Sistema de plugins

FacturaScripts está diseñado para ser extendido mediante plugins.

Los plugins pueden:

- añadir controladores
- extender modelos
- modificar vistas XML
- añadir campos a tablas
- modificar comportamiento del sistema

Siempre que sea posible, se debe preferir crear o modificar un plugin en lugar de modificar el núcleo del sistema.
Además, los plugins deben usar extensiones principalmente, antes que usar herencia.

---

## Flujo de trabajo recomendado

Cuando se implementa una funcionalidad o se corrige un error:

1. Identificar el módulo afectado (Controlador, Modelo o Lib).
2. Implementar la lógica en Core/Lib si es lógica de negocio.
3. Modificar o crear modelos si se necesita acceso a base de datos.
4. Modificar XMLView si hay cambios en formularios o listados.
5. Actualizar la estructura de base de datos mediante XML en Core/Table.
6. Añadir o actualizar pruebas en Test/.

---

## Comandos y desarrollo

Los comandos de desarrollo se documentan en:

`ia/DEVELOPMENT.md`

Este archivo contiene cómo instalar dependencias, ejecutar tests, verificar estilo y usar `fsmaker` para generar
plugins.

---

## Skills

Consultar el siguiente archivo para ver todas las skills disponibles:
`ai/skills.yaml`

Skills más utilizadas:

- use-tools → uso de la clase Tools
- use-translator → uso del traductor (Translator)
- api-usage → uso de la API (Endpoints, tokens)
- use-http → uso de la clase Http (Peticiones externas)
- extensions-and-hooks → uso de pipes (PHP) y ganchos (Twig) para extensiones