# Core
En esta carpeta se encuentra la mayor parte del código de la aplicación.
En ella se encuentran los modelos, las vistas, los controladores y otras
clases de la aplicación.

## App
Aquí están las clases encargadas de iniciar la aplicación, el cron, la API y el instalador.
**No es posible sobreescribir** estas clases **mediante plugins**.
**Será eliminada en 2023** cuando reemplacemos estas clases por un nuevo Kernel.

## Assets
En esta carpeta se encuentran los archivos estáticos de la aplicación: CSS, JavaScript, imágenes, etc.
Todos estos archivos pueden ser reemplazados en los plugins.

## Base
Aquí están las clases críticas de la aplicación que **no pueden ser reemplazadas mediante plugins**.
Estas clases se moverán paulatinamente a la carpeta Core durante el proceso de refactorización
y la carpeta será eliminada en 2024.

## Controller
En esta carpeta se encuentran los controladores de la aplicación. Todos los controladores
pueden ser reemplazados o extendidos mediante plugins.

## Data
Aquí están los archivos CSV que se utilizan para inicializar tablas en la base de datos.
Todos los archivos pueden ser reemplazados en los plugins.

## DataSrc
En esta carpeta se encuentran algunos repositorios o cachés de acceso rápido para modelos muy utilizados.
Estas clases **no pueden ser reemplazadas mediante plugins**.

## Lib
Aquí se encuentran clases auxiliares y servicios. Estas clases pueden ser reemplazadas o extendidas mediante plugins.

## Model
En esta carpeta se encuentran los modelos de la aplicación. Todos los modelos pueden ser reemplazados o extendidos mediante plugins.

## Table
Aquí están los archivos xml que definen las tablas de la base de datos.
Todos los archivos pueden ser reemplazados en los plugins.

## Translation
En esta carpeta se encuentran los archivos json de traducción de la aplicación.
Estos archivos pueden ser extendidos mediante plugins.

## View
Aquí están las plantillas twig de la aplicación, para todas aquellas partes que se generan mediante HTML.
Todas las vistas pueden ser reemplazadas o extendidas mediante plugins.

## XMLView
En esta carpeta se encuentran los archivos XML que definen los listados y formularios de la aplicación.
Todos los archivos pueden ser reemplazados en los plugins.