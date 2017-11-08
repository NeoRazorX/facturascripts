# Construcción de vistas por XML
Usaremos un archivo con estructura **XML** y con el nombre del controlador al cual define para establecer la composición visual de los campos y opciones de la vista.

El elemento raíz del archivo XML será _\<view\>_ y se podrán incluir los siguientes grupos:

* **\<columns\>** : (obligatorio) Para definir la lista de campos que se visualizan en la vista.
* **\<rows\>**    : (opcional) Permite definir condiciones especiales para la filas.
* **\<filters\>** : (opcional) Para definir la lista de filtros disponibles en la vista.


## COLUMNS
Permite definir mediante la etiqueta _\<column\>_ cada uno de los campos que se visualizarán en la vista pudiendo, en las vistas _Edit_, agrupar las
columnas mediante la etiqueta _\<group\>_. Las columnas, se complementan con la etiqueta obligatoria _\<widget\>_, que sirve para personalizar el tipo de objeto que se usa en la visualización/edición del dato.

Tanto las etiquetas _\<group\>_, _\<column\>_ como _\<widget\>_ disponen de un conjunto de atributos que permiten la personalización y que varían según
el contexto en que se ejecutan, es decir si es una vista _List_ o una vista _Edit_.
Es posible indicar el número de columnas que ocupará _\<column\>_ y/o el grupo _\<group\>_ dentro de la rejilla bootstrap (por defecto el máximo disponible).

Ejemplo vista para ListController:
    
```XML
    <columns>
        <column name="code" display="left" order="100">
            <widget type="text" fieldname="codigo" onclick="EditMyModel" />
        </column>
        <column name="description" display="left" order="105">
            <widget type="text" fieldname="descripcion" />
        </column>
        <column name="state" display="center" order="110">
            <widget type="text" fieldname="estado">
                <option color="red" font-weight="bold">ABIERTO</option>
                <option color="blue">CERRADO</option>
            </widget>
        </column>
    </columns>

    <rows>
        <row type="status" fieldname="estado">
            <option color="info">Pendiente</option>
            <option color="warning">Parcial</option>
        </row>
    </rows>
```

Ejemplo de vista para EditController:
    
```XML
    <columns>
        <group name="data" numcolumns="8" title="Identificación internacional" icon="fa-globe">
            <column name="code" display="left" numcolumns="4" order="100">
                <widget type="text" fieldname="codigo" onclick="EditMyModel" />
            </column>
            <column name="description" display="left" numcolumns="8" order="105">
                <widget type="text" fieldname="descripcion" />
            </column>
        </group>
        <group name="state" numcolumns="4">
            <column name="state" display="center" order="100">
                <widget type="text" fieldname="estado">
                    <option color="red" font-weight="bold">ABIERTO</option>
                    <option color="blue">CERRADO</option>
                </widget>
            </column>
        </group>
    </columns>
```


### column
Entendemos que es cada uno de los campos del modelo que componen la vista y con los que el usuario puede interactuar.

* **name**: Identificador interno de la columna. Es obligatorio su uso. Como norma se recomienda el uso de identificadores en minúsculas y en inglés.

* **title** : Etiqueta descriptiva del campo, en caso de no informarse se asume el valor de name.

* **titleurl** : URL destino si el usuario hace click sobre el título de la columna.

* **description** : Descripción larga del campo que ayuda la comprensión al usuario. 
En las vistas List se muestra como un hint sobre el título de la columna.
En las vistas Edit se muestra como un label inferior a la zona de edición del campo.

* **display** : Indica si se visualiza o no el campo y su alineación. Si no se informa, toma como valor _left_. Valores: **[_left|center|right|none_]**

* **order** : Posición que ocupa la columna. Sirve para indicar el orden en que se visualizan. Si no se informa toma el valor _100_
Cuando no se informa una ordenación específica, se ordena por la posición secuencial en el archivo XML, siempre dentro de su grupo.

* **numcolumns** : Fuerza el tamaño de la columna al valor indicado, usando el sistema de grid de Bootstrap siendo mínimo 1 y máximo 12.
Si no se informa toma como valor _0_ aplicando el sistema de tamaño automático de Bootstrap.


### widget
Complemento visual que se utiliza para la visualización y/o edición del campo/columna. 
En las vistas List, se puede completar la clusula html _style_ que se aplicará a la columna mediante una listas de _\<option\>_, 
donde cada atributo de la etiqueta _\<option\>_ se corresponde con su equivalente CSS que se desea aplicar y el valor de la etiqueta
es el valor cuando se aplicará el formato. Para decidir si se aplica el formato o no se aplicará los siguientes criterios al valor
introducido en la etiqueta _\<option\>_:

* Si el valor empieza por '>' (&gt;): Se aplicará si el valor del campo del modelo es mayor que el valor indicado después del operador.
* Si el valor empieza por '<' (&lt;): Se aplicará si el valor del campo del modelo es menor que el valor indicado después del operador.
* En cualquier otro caso se realizará una comprobación de igualdad.

Ejemplos:

_Pintar de color rojo cuando el valor del campo **pendiente es cero**_
```XML
    <widget type="checkbox" fieldname="pendiente">
        <option color="red">0</option>
    </widget>
```

_Pintar de color rojo y negrita cuando el valor del campo **estado es ABIERTO**_
_Pintar de color azul cuando el valor del campo **estado es CERRADO**_
```XML
    <widget type="text" fieldname="estado">
        <option color="red" font-weight="bold">ABIERTO</option>
        <option color="blue">CERRADO</option>
    </widget>
```

_Pintar de color rojo cuando el valor del campo **cantidad es menor de 0**_
```XML
    <widget type="number" fieldname="cantidad">
        <option color="red">&lt;0</option>
    </widget>
```

_Pintar de color rojo cuando el valor del campo **importe es mayor de treinta mil**_
```XML
    <widget type="money" fieldname="importe">
        <option color="red">&gt;30000</option>
    </widget>
```

* **type** : (obligatorio) Indica el tipo de widget a utilizar.
    * **text**: Campos varchar o de texto.
    * **number**: Campos de tipo numérico. Para este tipo se puede indicar el atributo _decimal_ para configurar la precisión a visualizar.
El atributo _step_ para indicar el aumento o decremento al realizar un "paso" mediante el control de avance/retroceso. Los atributos _min_ y _max_ 
para indicar los valores mínimo y máximo.
    * **money**: Campos de tipo float para importes. Para este tipo se puede indicar el atributo _decimal_ para configurar la precisión a visualizar en vez de los de la moneda.
    * **checkbox**: Valores booleanos que se visualizan mediante el icono de un check (true) o un guión (false) respectivamente.
    * **datepicker**: Campos de tipo fecha, que incorporan un desplegable para elegir la misma.
    * **color**: Para la selección de colores.
    * **select**: Lista de valores establecidos por un conjunto de etiquetas _\<values\>_ descritas dentro del grupo _\<widget\>_.
Los valores podrán ser fijos, incluyendo tantos _\<values\>_ como necesitemos e indicando el atributo _title_ y asignando un valor,
como dinámicos, ya sea calculados en base al contenido de los registros de una tabla de la base de datos o mediante la definición de un rango.
Para el caso de valores de una tabla se utilizará una sóla etiqueta _\<values\>_ indicando los atributos:
        * **_source_**: Indica el nombre de la tabla origen de los datos
        * **_fieldcode_**: Indica el campo que contiene el valor a grabar en el campo de la columna
        * **_fieldtitle_**: Indica el campo que contiene el valor que se visualizará en pantalla

Para el caso de valores por definición de rango una sóla etiqueta _\<values\>_ indicando los atributos:
        * _start_: Indica el valor inicial (numérico o alfabético)
        * _end_: Indica el valor final (numérico o alfabético)
        * _step_: Indica el valor del incremento (numérico)

        ```XML
            <widget type="select" fieldname="documentacion">
                <values title="Pasaporte">PASAPORTE</values>
                <values title="D.N.I.">DNI</values>
                <values title="N.I.E.">NIE</values>
            </widget>

            <widget type="select" fieldname="codgrupo">
                <values source="gruposclientes" fieldcode="codgrupo" fieldtitle="nombre"></values>
            </widget>

            <widget type="select" fieldname="codgrupo">
                <values start="0" end="6" step="1"></values>
            </widget>
        ```

    * radio: Lista de valores donde podemos seleccionar una de ellas.
Se indican las distintas opciones mediante sistema de etiquetas _\<values\>_ descritas dentro del grupo _\<widget\>_, al estilo del tipo _select_.

        ```XML
                <widget type="radio" fieldname="regimeniva">
                    <values title="general">General</values>
                    <values title="exempt">Exento</values>
                </widget>
        ```

* **fieldname** : (obligatorio) Nombre del campo que contiene la información.

* **onclick** : (opcional) Nombre del controlador al que llamará y se pasará el valor del campo al hacer click sobre el valor de la columna.

* **required** : Atributo opcional para indicar que la columna debe tener un valor en el momento de persistir los datos en la base de datos. **[required="true"]**

* **readonly** : Atributo opcional para indicar que la columna no es editable. **[readonly="true"]**

* **icon** : (opcional) Si se indica se visualizará el icono a la izquierda del campo.

* **hint** : (opcional) Texto explicativo que se visualiza al colocar el ratón sobre el título en el controlador Edit.


### group
Crea una rejilla bootstrap donde incluirá cada una de las columnas _\<column\>_ declaradas dentro del grupo. Se puede personalizar el grupo
mediante los siguientes atributos:

* **name** : Identificador interno del grupo. Es obligatorio su uso. Como norma se recomienda el uso de identificadores en minúsculas y en inglés.

* **title** : Etiqueta descriptiva del grupo. Para los grupos **no se usará** el valor name en caso de no informarse un title.

* **titleurl** : URL destino si el usuario hace click sobre el título del grupo.

* **icon** : Si se indica se visualizará el icono a la izquierda del título. El icono de el grupo sólo se mostrará si el atributo title está presente.

* **order** : Posición que ocupa el grupo. Sirve para indicar el orden en que se visualizara.

* **numcolumns** : Fuerza el tamaño al valor indicado, usando el sistema de grid de Bootstrap siendo mínimo 1 y máximo 12.
Si no se informa toma como valor _0_ aplicando el sistema de tamaño automático de Bootstrap. Es importante recordar que
un grupo tiene siempre 12 columnas disponibles en su _interior_, independientemente del tamaño que tenga definido el grupo.


## ROWS
Este grupo permite añadir funcionalidad a cada una de las filas o añadir filas con procesos especiales. Así mediante la etiqueta _\<row\>_ 
podemos ir añadiendo las funcionalidades, de manera única (es decir, no podemos incluir dos veces el mismo tipo de row) y
mediante el atributo _type_ indicar la acción que realiza, teniendo cada tipo unos requerimientos propios.

* **status** : Permite colorear las filas en base al valor de un campo del registro. Requiere de uno o varios registros _\<option\>_ indicando la
configuración bootstrap para paneles que deseamos para la fila.

Ejemplo:

_pinta la fila de color "info" si el campo **estado es Pendiente**_
_pinta la fila de color "warning" si el campo **estado es Parcial**_
```XML
    <rows>
        <row type="status" fieldname="estado">
            <option color="info">Pendiente</option>
            <option color="warning">Parcial</option>
        </row>
    </rows>
```
* **\<header\>** : Permite definir una lista de botones estadísticos y relacionales con otros modelos que dan información al usuario y le permite
consultar al hacer click.

Ejemplo:

```XML
    <rows>
        <row type="header">
            <option icon="fa-files-o" label="Alb. Pdtes:" calculateby="nombre_function" onclick="#url"></option>
            <option icon="fa-files-o" label="Pdte Cobro:" calculateby="nombre_function" onclick="#url"></option>
        </row>        
    </rows>
```

* **\<footer\>** : Permite añadir información adicional a visualizar al usuario en el pie de la vista.

Ejemplo:

```XML
    <rows>
        <row type="footer">
            <option>Este es un ejemplo con sólo texto</option>
            <option label="Panel Footer" footer="Panel footer" color="warning">Este es un ejemplo con cabecera y footer</option>
            <option label="Esto es un info" color="info">Este es un ejemplo con cabecera y sin footer</option>
            <option footer="Texto en el footer" color="success">Este es un ejemplo sin cabecera</option>
        </row>    
    </rows>
```


## FILTERS
Para definir la lista de filtros disponibles en la vista (Futuras versiones).
