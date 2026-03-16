# Skill: Definición de Vistas (XMLView)

Esta skill describe cómo definir de forma declarativa las vistas (listados y formularios) en FacturaScripts mediante archivos XML.

## Cuándo usar
- Crear un nuevo listado de registros.
- Crear o modificar un formulario de edición o creación.
- Definir qué campos se muestran y qué widgets (controles) se usan.

## Reglas
- Los archivos XML deben estar en la carpeta `XMLView/` del núcleo o del plugin.
- El nombre del archivo suele coincidir con el nombre del controlador que lo usa (ej: `EditProducto.xml`).
- Los widgets deben estar vinculados a nombres de campos del modelo (`fieldname`).

## Estructura XML

### Etiquetas principales
- `<view>`: Nodo raíz.
- `<columns>`: Contenedor de las columnas o grupos de campos.
- `<group>`: Agrupa campos visualmente. Permite definir el número de columnas de rejilla (`numcolumns`).
- `<column>`: Define un campo en la vista.
  - `<widget>`: Define el tipo de control (ej: `text`, `select`, `number`, `checkbox`, `date`, `password`, `textarea`).

## Ejemplo de uso

### Un formulario de edición simple (`EditAlgo.xml`)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <group name="datos_principales" numcolumns="12">
            <column name="nombre" order="100">
                <widget type="text" fieldname="nombre" required="true" maxlength="100"/>
            </column>
            <column name="activo" order="110">
                <widget type="checkbox" fieldname="activo"/>
            </column>
        </group>
        <group name="configuracion" title="configuracion" numcolumns="12">
            <column name="tipo" order="100">
                <widget type="select" fieldname="codtipo">
                    <values source="tipos_algo" fieldcode="codtipo" fieldtitle="descripcion"/>
                </widget>
            </column>
        </group>
    </columns>
</view>
```

### Un listado simple (`ListAlgo.xml`)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <column name="id" order="100">
            <widget type="text" fieldname="id"/>
        </column>
        <column name="nombre" order="110">
            <widget type="text" fieldname="nombre"/>
        </column>
        <column name="fecha" order="120">
            <widget type="date" fieldname="fecha"/>
        </column>
    </columns>
</view>
```
