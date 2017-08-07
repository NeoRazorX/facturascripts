# Construcción de vistas por XML
Usaremos un archivo con estructura **XML** y con el nombre del controlador al cual define para establecer la composición visual de los campos y opciones de la vista.

El elemento raíz del archivo XML será _<view>_ y se podrán incluir los siguientes grupos:

**OPCIONES**
* **<columns>** : (obligatorio) Para definir la lista de campos que se visualizan en la vista.
* **<rows>**    : (opcional) Permite definir condiciones especiales para la filas en las vistas List.
* **<filters>** : (opcional) Para definir la lista de filtros disponibles en la vista.

## COLUMNS
Permite definir mediante la etiqueta _<column>_ cada uno de los campos que se visualizarán en la vista. 
Se complementa con la etiqueda _<widget>_, que sirve para personalizar el tipo de objeto que se usa en la visualización del dato.
Tanto la etiqueta _<column>_ como _<widget>_ disponen de un grupo de atributos que permiten la personalización y que varían según
el contexto en que se ejecuta, es decir si es una vista _List_ o una vista _Edit_. Para las vistas _Edit_ se podrá agrupar las
columnas en grupos _<group>_.
Es posible indicar el número de columnas que ocupará _<column>_ y el grupo _<group>_ dentro de la rejilla bootstrap (por defecto el máximo disponible).

Ejemplo ListController:
    
```XML
    <column title="Código" display="left" order="100">
        <widget type="text" fieldname="codigo" onclick="EditMyModel" />
    </column>
    <column title="Descripcion" display="left" order="105">
        <widget type="text" fieldname="descripcion" />
    </column>

    <column title="Estado" display="center" order="110">
        <widget type="text" fieldname="estado">
            <option color="red" font-weight="bold">ABIERTO</option>
            <option color="blue">CERRADO</option>
        </widget>
    </column>

    <rows>
        <row type="status" fieldname="estado">
            <option color="info">Pendiente</option>
            <option color="warning">Parcial</option>
        </row>
    </rows>
```

Ejemplo EditController:
    
```XML
    <group numcolumns="5"
        <column title="Código" display="left" order="100">
            <widget type="text" fieldname="codigo" />
        </column>
        <column title="Descripcion" display="left" order="105">
            <widget type="text" fieldname="descripcion" />
        </column>
    </group>
```

### column
Cada uno de los campos que componen la vista.

**OPCIONES**
* **title** : Etiqueta descriptiva del campo

* **titleurl** : URL destino si el usuario hace click sobre el título de la columna.

* **description** : Descripción larga del campo que ayuda la comprensión al usuario. 
En las vistas List se muestra como un hint sobre el título de la columna.
En las vistas Edit se muestra como un label inferior a la zona de edición del campo.

* **display** : Indica si se visualiza o no el campo y su alineación **[_left|center|right|none_]**

* **order** : Posición que ocupa la columna. Sirve para indicar el orden en que se visualizan.

### widget
Complemento visual que se utiliza para la visualización y/o edición del campo. 
En las vistas List, se puede completar la clusula html _style_ que se aplicará a la columna mediante una listas de _<option>_, 
donde cada atributo de la etiqueta _<option>_ se corresponde con su equivalente CSS que se desea aplicar y el valor de la etiqueta
es el valor cuando se aplicará el formato.

Ejemplo:

```XML
    <widget type="check" fieldname="pendiente">
        <option color="red">0</option>
    </widget>

    <widget type="text" fieldname="estado">
        <option color="red" font-weight="bold">ABIERTO</option>
        <option color="blue">CERRADO</option>
    </widget>
```
**OPCIONES**
* **type** : (obligatorio) Indica el tipo de widget a utilizar.
    * text : Campos varchar o de texto.
    * check: Valores booleanos que se visualizan mediante el icono de un check (true) o un guión (false) respectivamente.

* **fieldname** : (obligatorio) Nombre del campo que contiene la información.

* **onclick** : (opcional) Nombre del controlador al que llamará y se pasará el valor del campo al hacer click sobre el valor de la columna.

## ROWS
Este grupo permite añadir funcionalidad a cada una de las filas o añadir filas con procesos especiales. Así mediante la etiqueta _<row>_ 
podemos ir añadiendo las funcionalidades, de manera única (es decir, no podemos incluir dos veces el mismo tipo de row) y
mediante el atributo _type_ indicar la acción que realiza, teniendo cada tipo unos requerimientos propios.

* **status** : Permite colorear las filas en base al valor de un campo del registro. Requiere de uno o varios registros _<option>_ indicando la
configuración bootstrap para paneles que deseamos para la fila.
```XML
    <rows>
        <row type="status" fieldname="estado">
            <option color="info">Pendiente</option>
            <option color="warning">Parcial</option>
        </row>
    </rows>
```
* **<header>**  : Permite definir una lista de botones estadísticos y relacionales con otros modelos que dan información al usuario y le permite
consultar al hacer click (Futuras versiones).

* **<footer>**  : Permite añadir información adicional a visualizar al usuario en el pie de la vista (Futuras versiones).


## FILTERS
Para definir la lista de filtros disponibles en la vista (Futuras versiones).
