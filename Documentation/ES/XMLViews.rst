.. title:: Vistas
.. highlight:: rst

######
Vistas
######

Las vistas, en *Facturascripts 2018* están clasificadas según su representación
en pantalla tanto en la forma de visualizar como en número de registros de datos.

-  **List** : Vistas que muestran una lista de datos en formato de filas y columnas
   pudiendo navegar, buscar y/o filtrar por los datos pero donde los datos son de
   sólo lectura, es decir no se permite su edición.

-  **Edit** : Vistas que muestran un formulario de edición de un único registro de
   datos, pudiendo estar estos datos agrupados.

-  **EditList** : Vista resultante de la "unión" de los tipos anteriores. Es decir,
   una lista de registros visualizados en filas y columnas pero donde cada uno de las
   filas es un formulario de edición que nos permite editar los datos de dicho registro.
   Esta vista contiene dos modos de visionado. Un sistema básico a modo de columnas, cuando
   la vista tiene menos de 6 columnas, o el sistema completo de visionado si la vista contien
   6 o más columnas de datos.

-  **GridView** : Vista que depende de una vista padre de tipo **Edit** y en la que la representación
   y manipulación de los datos viene dada por un grid de filas y columnas al estilo de una hoja de cálculo.
   Este tipo de vista requiere de un archivo JavaScript donde se controlan distintos eventos como la
   creación del data grid y eventos de visualización y edición de datos.
   Para más información ver `GridViews <GridViews>`__.

El nombrado de las vistas, cuando las creamos, sigue la siguiente regla: *List* o *Edit* seguido
del *nombre del modelo*. Esto se cumple aún cuando la vista sea del tipo *EditList* o **GridView** en cuyo caso
se nombrará como si fuera del tipo *Edit*.


**********
Vistas XML
**********

Para crear las vistas usaremos un archivo con estructura **XML** y, como se ha indicado
anteriormente, con el nombre del tipo de vista y el modelo, donde estableceremos la
composición visual de los campos, las acciones y opciones visuales de la vista.

El elemento raíz del archivo XML será *<view>* y se podrán incluir las siguientes
etiquetas a modo de grupo:

-  **<columns>** : (obligatorio) Para definir la lista de campos que se
   visualizan en la vista.

-  **<rows>** : (opcional) Permite definir condiciones especiales para
   la filas, así como añadir botones a las vistas.

-  **<modals>** : (opcional) Define un formulario modal que será visualizado
   mediante la interacción con un botón definido en la vista.


COLUMNS
=======

Permite definir mediante la etiqueta *<column>* cada uno de los campos
que se visualizarán en la vista pudiendo, en las vistas *Edit*, agrupar
las columnas mediante la etiqueta *<group>*. Las columnas, se
complementan con la etiqueta *<widget>*, que sirve para
personalizar el tipo de objeto que se usa en la visualización/edición
del dato, o con la etiqueta *<button>* para indicar un bottón.

Tanto las etiquetas *<group>*, *<column>* como *<widget>* disponen de un
conjunto de atributos que permiten la personalización y que varían según
el contexto en que se ejecutan, es decir si es una vista *List* o una
vista *Edit*. Es posible indicar el número de columnas que ocupará
*<column>* y/o el grupo *<group>* dentro de la rejilla bootstrap (por
defecto el máximo disponible).

Ejemplo vista para ListController:

.. code:: xml

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

Ejemplo de vista para EditController:

.. code:: xml

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


column
------

Entendemos que es cada uno de los campos del modelo y botones que componen la
vista y con los que el usuario puede interactuar. La etiqueta *column* requiere contener
una de las etiquetas *<widget>* o *<button>* para su funcionamiento y se personaliza
mediante las siguientes propiedades:

-  **name**: Identificador interno de la columna. Es obligatorio su uso.
   Como norma se recomienda el uso de identificadores en minúsculas y en
   inglés.

-  **title** : Etiqueta descriptiva del campo, en caso de no informarse
   se asume el valor de name.

-  **titleurl** : URL destino si el usuario hace click sobre el título
   de la columna.

-  **description** : Descripción larga del campo que ayuda la
   comprensión al usuario. En las vistas List se muestra como un hint
   sobre el título de la columna. En las vistas Edit se muestra como un
   label inferior a la zona de edición del campo.

-  **display** : Indica si se visualiza o no el campo y su alineación.
   Si no se informa, toma como valor *left*. Valores:
   *[left|center|right|none]*

-  **order** : Posición que ocupa la columna. Sirve para indicar el
   orden en que se visualizan. Si no se informa toma el valor *100*
   Cuando no se informa una ordenación específica, se ordena por la
   posición secuencial en el archivo XML, siempre dentro de su grupo.

-  **numcolumns** : Fuerza el tamaño de la columna al valor indicado,
   usando el sistema de grid de Bootstrap siendo mínimo 1 y máximo 12.
   Si no se informa toma como valor *0* aplicando el sistema de tamaño
   automático de Bootstrap.


widget
------

Complemento visual que se utiliza para la visualización y/o edición del
campo/columna. En las vistas List, se puede completar la clusula html
*style* que se aplicará a la columna mediante una listas de *<option>*,
donde cada atributo de la etiqueta *<option>* se corresponde con su
equivalente CSS que se desea aplicar y el valor de la etiqueta es el
valor cuando se aplicará el formato. Para decidir si se aplica el
formato o no se aplicará los siguientes criterios al valor introducido
en la etiqueta *<option>*:

-  Si el valor empieza por ``>``: Se aplicará si el valor del campo
   del modelo es mayor que el valor indicado después del operador.
-  Si el valor empieza por ``<``: Se aplicará si el valor del campo
   del modelo es menor que el valor indicado después del operador.
-  En cualquier otro caso se realizará una comprobación de igualdad.

Ejemplos:

*Pintar de color rojo cuando el valor del campo ``pendiente`` es cero*

.. code:: xml

        <widget type="checkbox" fieldname="pendiente">
            <option color="red">0</option>
        </widget>

*Pintar de color rojo y negrita cuando el valor del campo ``estado`` es ``ABIERTO``*
*Pintar de color azul cuando el valor del campo ``estado`` es ``CERRADO``*

.. code:: xml

        <widget type="text" fieldname="estado">
            <option color="red" font-weight="bold">ABIERTO</option>
            <option color="blue">CERRADO</option>
        </widget>

*Pintar de color rojo cuando el valor del campo ``cantidad`` es menor de 0*

.. code:: xml

        <widget type="number" fieldname="cantidad">
            <option color="red">&lt;0</option>
        </widget>

*Pintar de color rojo cuando el valor del campo ``importe`` es mayor de treinta mil*

.. code:: xml

        <widget type="money" fieldname="importe">
            <option color="red">&gt;30000</option>
        </widget>


-  **type** : (obligatorio) Indica el tipo de widget a utilizar.

   -  **text**: Campos varchar o de texto.
   -  **number**: Campos de tipo numérico. Para este tipo se puede
      indicar el atributo *decimal* para configurar la precisión a
      visualizar. El atributo *step* para indicar el aumento o
      decremento al realizar un “paso” mediante el control de
      avance/retroceso. Los atributos *min* y *max* para indicar los
      valores mínimo y máximo.
   -  **money**: Campos de tipo float para importes. Para este tipo se
      puede indicar el atributo *decimal* para configurar la precisión a
      visualizar en vez de los de la moneda.
   -  **checkbox**: Valores booleanos que se visualizan mediante el
      icono de un check (true) o un guión (false) respectivamente.
   -  **datepicker**: Campos de tipo fecha, que incorporan un
      desplegable para elegir la misma.
   -  **color**: Para la selección de colores.
   -  **filechooser**: Permite seleccionar y subir un archivo.
   -  **autocomplete**: Lista de valores que se cargan de manera dinámica de un modelo
      en función del texto introdicido por el usuario. Se utilizará una sóla
      etiqueta *<values>* indicando los atributos:

          -  *source*: Indica el nombre de la tabla origen de los datos
          -  *fieldcode*: Indica el campo que contiene el valor a grabar en el campo de la columna
          -  *fieldtitle*: Indica el campo que contiene el valor que se visualizará en pantalla

   -  **select**: Lista de valores establecidos por un conjunto de
      etiquetas *<values>* descritas dentro del grupo *<widget>*. Los
      valores podrán ser fijos, incluyendo tantos *<values>* como
      necesitemos e indicando el atributo *title* y asignando un valor,
      como dinámicos, ya sea calculados en base al contenido de los
      registros de una tabla de la base de datos o mediante la
      definición de un rango. Para el caso de valores de una tabla se
      utilizará una sóla etiqueta *<values>* indicando los atributos:

          -  *source*: Indica el nombre de la tabla origen de los datos
          -  *fieldcode*: Indica el campo que contiene el valor a grabar en el campo de la columna
          -  *fieldtitle*: Indica el campo que contiene el valor que se visualizará en pantalla

      Para el caso de valores por definición de rango una sóla etiqueta *<values>*
      indicando los atributos:
          -  *start*: Indica el valor inicial (numérico o alfabético)
          -  *end*: Indica el valor final (numérico o alfabético)
          -  *step*: Indica el valor del incremento (numérico)

   -  **radio**: Lista de valores donde podemos seleccionar una de ellas.
      Se indican las distintas opciones mediante sistema de etiquetas
      *<values>* descritas dentro del grupo *<widget>*, al estilo del tipo *select*.

.. code:: xml

        <widget type="autocomplete" fieldname="referencia">
            <values source="articulos" fieldcode="referencia" fieldtitle="descripcion"></values>
        </widget>

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

        <widget type="radio" fieldname="regimeniva">
            <values title="general">General</values>
            <values title="exempt">Exento</values>
        </widget>

-  **fieldname** : (obligatorio) Nombre del campo que contiene la
   información.

-  **onclick** : (opcional) Nombre del controlador al que llamará y se
   pasará el valor del campo al hacer click sobre el valor de la
   columna.

-  **required** : Atributo opcional para indicar que la columna debe
   tener un valor en el momento de persistir los datos en la base de
   datos. **[required=“true”]**

-  **readonly** : Atributo opcional para indicar que la columna no es
   editable. **[readonly=“true”]**

-  **maxlength** : Número máximo de carácteres que permite la campo.

-  **icon** : (opcional) Si se indica se visualizará el icono a la
   izquierda del campo.

-  **hint** : (opcional) Texto explicativo que se visualiza al colocar
   el ratón sobre el título en el controlador Edit.


button
------

Este elemento visual está disponible sólo en vistas de tipo *Edit* y *EditList* y
como su nombre indica permite incluir un botón en una de las columnas de edición.
Existen tres tipos de botones declarados mediante el atributo ``type`` y con funciones
distintas:

*  *calculate* : Botón para mostrar un cálculo estadístico.
*  *action* : Botón para ejecutar una acción en el controlador.
*  *modal* : Botón para mostrar un formulario modal.
*  *js* : Botón para ejecutar una función JavaScript.

El botón de tipo *calculate* es exclusivo del grupo *<rows>* y se detalla más adelante.
Para los botones *action* y *modal* podemos personalizarlos mediante los atributos:

-  **type** : indica el tipo de botón.

-  **icon** : icono que se visualizará a la izquierda de la etiqueta.

-  **label** : texto o etiqueta que se visualizará en el botón.

-  **color** : indica el color del botón, según los colores de Bootstrap para botones.

-  **hint** : ayuda que se muestra al usuario al poner el puntero del ratón sobre el botón.
   Esta opción sólo está disponible para botones del tipo ``action``.

-  **action** : esta propiedad varía según el tipo. Para botones ``action`` indica la acción
   que se envía al controlador, para que éste realice algún tipo de proceso especial.
   Para botones de tipo ``modal`` indica el formulario modal que se debe mostrar al usuario.
   Para botones de tipo ``js`` indica el nombre de la función a ejecutar.


Ejemplo:

.. code:: xml

        <column name="action1" order="100">
            <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Ejecuta el controlador con action=process1" />
        </column>

        <column name="action2" order="100">
            <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
        </column>


group
-----

Crea una rejilla bootstrap donde incluirá cada una de las columnas
*<column>* declaradas dentro del grupo. Se puede personalizar el grupo
mediante los siguientes atributos:

-  **name** : Identificador interno del grupo. Es obligatorio su uso.
   Como norma se recomienda el uso de identificadores en minúsculas y en
   inglés.

-  **title** : Etiqueta descriptiva del grupo. Para los grupos **no se
   usará** el valor name en caso de no informarse un title.

-  **titleurl** : URL destino si el usuario hace click sobre el título
   del grupo.

-  **icon** : Si se indica se visualizará el icono a la izquierda del
   título. El icono de el grupo sólo se mostrará si el atributo title
   está presente.

-  **order** : Posición que ocupa el grupo. Sirve para indicar el orden
   en que se visualizara.

-  **numcolumns** : Fuerza el tamaño al valor indicado, usando el
   sistema de grid de Bootstrap siendo mínimo 1 y máximo 12. Si no se
   informa toma como valor *0* aplicando el sistema de tamaño automático
   de Bootstrap. Es importante recordar que un grupo tiene siempre 12
   columnas disponibles en su *interior*, independientemente del tamaño
   que tenga definido el grupo.


ROWS
====

Este grupo permite añadir funcionalidad a cada una de las filas o añadir
filas con procesos especiales. Así mediante la etiqueta *<row>* podemos
ir añadiendo las funcionalidades, de manera única (es decir, no podemos
incluir dos veces el mismo tipo de row) y mediante el atributo *type*
indicar la acción que realiza, teniendo cada tipo unos requerimientos
propios.

status
------

Este tipo permite colorear las filas en base al valor de un campo del registro.
Requiere de uno o varios registros *<option>* indicando la configuración de colores
bootstrap para paneles que deseamos para la fila.

Ejemplo:

*pinta la fila de color “info” si el campo ``estado`` es ``Pendiente``*
*pinta la fila de color “warning” si el campo ``estado`` es ``Parcial``*

.. code:: xml

        <rows>
            <row type="status" fieldname="estado">
                <option color="info">Pendiente</option>
                <option color="warning">Parcial</option>
            </row>
        </rows>


statistics
----------

Permite definir una lista de botones estadísticos y relacionales con otros modelos
que dan información al usuario y le permite consultar al hacer click.
Cada uno de los botones se definen mediante la etiqueta *<button>* seguido de las propiedades:

-  **type** : para este caso siempre contiene el valor ``calculate``.

-  **icon** : icono que se visualizará a la izquierda de la etiqueta.

-  **label** : texto o etiqueta que se visualizará en el botón.

-  **calculateby** : nombre de la función del controlador que se ejecuta para calcular el importe a visualizar.

-  **onclick** : URL destino, donde se redigirá al usuario al hacer click sobre el botón.


Ejemplo:

.. code:: xml

        <rows>
            <row type="statistics">
                <button icon="fa-files-o" label="Alb. Pdtes:" calculateby="nombre_function" onclick="#url"></option>
                <button icon="fa-files-o" label="Pdte Cobro:" calculateby="nombre_function" onclick="#url"></option>
            </row>
        </rows>


actions
-------

Permite definir un grupo de botones de tipos *action* y *modal* que se visualizarán
en el pié del formulario de edición, entre los botones de eliminar y grabar. Este *row*
es específico de las vistas *Edit*. La declaración de los botones se realiza de manera
similar a lo descripto en el apartado `button`_ con la salvedad de que no es necesaria
la etiqueta *column*.

Ejemplo:

.. code:: xml

        <rows>
            <row type="actions">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Ejecuta el controlador con action=process1" />
            </row>
        </rows>


header y footer
---------------

Permite añadir información adicional a visualizar al usuario en la cabecera y/o el pie de la vista.
La información se muestra en forma de paneles ("cards" de Bootstrap) donde podemos
incluir mensajes y botones tanto de acción como modales. Para declarar un panel usaremos
la etiqueta *<group>* en la que incluiremos etiquetas *button* (si los necesitamos).
Podemos personalizar cada uno de los apartado del panel como la cabecera, el cuerpo
y/o el pie con atributos:

-  **name** : establece el identificador para el panel.

-  **title** : indica un texto para la cabecera del panel.

-  **label** : indica un texto para el cuerpo del panel.

-  **footer** : indica un texto para el pie del panel.

Ejemplo: (Cabecera de vista)

.. code:: xml

        <row type="header">
            <group name="footer1" footer="specials-actions" label="Esto es una muestra de botones en un 'bootstrap card'">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Ejecuta el controlador con action=process1" />
            </group>
        </row>

Ejemplo: (Pie de vista)

.. code:: xml

        <row type="footer">
            <group name="footer1" footer="specials-actions" label="Esto es una muestra de botones en un 'bootstrap card'">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Ejecuta el controlador con action=process1" />
            </group>
        </row>


MODALS
======

Los formularios modales son vistas complementarias a la vista principal, que permanecen
ocultas hasta que son necesarias para la realización de una tarea específica. Estos formularios
se declaran de manera muy similar a lo detallado en la sección `COLUMNS`_.

Para crear un formulario modal, debemos incluir una etiqueta *group* con un identificador *name* único.
Dentro de este grupo podemos definir y personalizar las columnas que necesitemos, pero no se pueden crear
nuevos grupos como se podía en la sección COLUMNS.

Podemos declarar todos los formularios modales que necesitemos, declarando distintas etiquetas *group* dentro
del grupo *modals*, y respetando la unicidad de sus identificadores. Para mostrar cualquiera de los formularios
modales declarados, tendremos que definir un botón de tipo modal en la vista principal, ya sea en una columna o
en un *row* de tipo ``actions`` o ``footer``, donde el atributo ``action`` del *button* sea igual al identificador
del formulario modal.

El formulario modal mostrará la relación de columnas declaradas junto con unos botones de ``Aceptar`` y ``Cancelar``
para que el usuario pueda confirmar o cancelar el proceso a realizar.

Ejemplo:

.. code:: xml

        <modals>
            <group name="test" title="other-data" icon="fa-users">
                <column name="name" numcolumns="12" description="desc-custommer-name">
                    <widget type="text" fieldname="nombre" required="true" hint="desc-custommer-name-2" />
                </column>

                <column name="create-date" numcolumns="6">
                    <widget type="datepicker" fieldname="fechaalta" readonly="true" />
                </column>

                <column name="blocked-date" numcolumns="6">
                    <widget type="datepicker" fieldname="fechabaja" />
                </column>

                <column name="blocked">
                    <widget type="checkbox" fieldname="debaja" />
                </column>
            </group>
        </modals>
