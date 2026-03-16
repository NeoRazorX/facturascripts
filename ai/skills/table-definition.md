# Skill: Definición de Tablas (XML)

Esta skill describe cómo definir la estructura de las tablas de la base de datos mediante archivos XML en FacturaScripts.

## Cuándo usar
- Crear nuevas tablas en el sistema o en un plugin.
- Añadir o modificar columnas en tablas existentes.
- Definir claves primarias y relaciones (claves foráneas).

## Reglas
- Los archivos de tablas deben ubicarse en la carpeta `Table/` del núcleo o del plugin.
- FacturaScripts detecta automáticamente los cambios en estos archivos y sincroniza la base de datos (siempre que el modo desarrollador esté activo o se fuerce la actualización).
- El nombre del archivo debe coincidir con el nombre de la tabla.
- El nombre de la tabla siempre debe ser en minúsculas, sin espacios y en plural.
- El nombre de las columnas siempre debe ser en minúsculas, en singular y guion bajo en vez de espacios.

## Estructura XML

### Etiquetas principales
- `<table>`: Nodo raíz que contiene la definición de la tabla.
- `<column>`: Define una columna de la tabla.
  - `<name>`: Nombre de la columna.
  - `<type>`: Tipo de dato (ej: `integer`, `boolean`, `character varying(50)`, `timestamp`, `double precision`).
  - `<null>`: `NO` para columnas obligatorias, `YES` para opcionales (por defecto).
  - `<default>`: Valor por defecto para la columna.
- `<constraint>`: Define restricciones como claves primarias o foráneas.

## Ejemplo de uso

### Definición de una tabla básica (`mi_tabla.xml`)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<table>
    <column>
        <name>id</name>
        <type>integer</type>
        <null>NO</null>
    </column>
    <column>
        <name>nombre</name>
        <type>character varying(100)</type>
        <null>NO</null>
    </column>
    <column>
        <name>fecha_creacion</name>
        <type>timestamp</type>
        <default>CURRENT_TIMESTAMP</default>
    </column>
    <constraint>
        <name>mi_tabla_pkey</name>
        <type>PRIMARY KEY (id)</type>
    </constraint>
</table>
```

### Definición de una clave foránea
```xml
<constraint>
    <name>ca_mi_tabla_cliente</name>
    <type>FOREIGN KEY (codcliente) REFERENCES clientes (codcliente) ON DELETE CASCADE ON UPDATE CASCADE</type>
</constraint>
```
