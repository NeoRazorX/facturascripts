.. title:: ListController
.. highlight:: rst

##########################
Controlador ListController
##########################

Este controlador es un contenedor de vistas del tipo ListView, que
gestiona automáticamente la visualización y filtrado de datos, mostrando
la información de cada vista en una tabla de filas y columnas. No
permite la edición de los datos pero al hacer click sobre una de las
filas se realiza una llamada automática al controlador de edición del
modelo. Para este evento se utiliza la configuración de la primera
columna que tenga informado el atributo *onclick*.

Para el uso de este controlador es necesario crear las vistas en formato
XML, tal y como se describe en el documento
`XMLViews <XMLViews>`__,
incluido en la documentación de **Facturascripts**.

************************
Cómo usar el controlador
************************

Para utilizar *ListController* debemos crearnos una nueva clase PHP que
herede o extienda de ListController, debiendo implementar los siguientes
métodos:

-  **createViews**: Encargado de crear y añadir las vistas que deseamos
   visualizar dentro del ListController.

-  **getPageData**: Establece los datos generales (título, icono, menú,
   etc) para la vista principal (la primera que añadimos en
   *createViews*).

createViews
===========

Dentro de este método, en nuestra nueva clase, debemos ir creando las
distintas vistas que se visualizarán, y para cada vista debemos indicar
los campos de búsqueda y los campos de ordenación. Opcionalmente
podremos añadir opciones de filtrado para que el usuario pueda
complementar el filtrado de búsqueda existente. Este método tiene una
visibilidad de *protected* de manera que los plugins pueden ir
extendiendo nuestra clase y añadir nuevas vistas, o modificar las
existentes.

La manera de añadir una vista es mediante el método ***addView***
incluido en el propio controlador. Para la correcta llamada al método
debemos informar mediante cadenas de texto: el modelo (Nombre completo),
nombre de la vista XML, el título para la pestaña que visualiza el
controlador y su icono. Si se omite alguno de estos últimos parámetros,
el controlador asignará un texto y/o un icono por defecto.

Una vez añadida la vista, debemos configurarla indicando los campos de
búsqueda y la ordenación mediante los métodos ***addSearchFields*** y
***addOrderBy***.

addSearchFields
---------------

Al añadir los campos de búsqueda debemos indicar el nombre de la vista
al que añadimos los campos y un array con los nombre de los campos.

Ejemplo de creación y adición de campos para búsqueda

.. code:: php

        /* Epigrafes */
        $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'Epigrafes');
        $this->addSearchFields('ListEpigrafe', ['descripcion', 'codepigrafe', 'codejercicio']);

        /* Clientes */
        $this->addView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'customers', 'fa-users');
        $this->addSearchFields('ListCliente', ['nombre', 'razonsocial', 'codcliente', 'email']);

        /* Grupos */
        $this->addView('FacturaScripts\Core\Model\GrupoClientes', 'ListGrupoClientes', 'groups', 'fa-folder-open');
        $this->addSearchFields('ListGrupoClientes', ['nombre', 'codgrupo']);

addOrderBy
----------

Podemos añadir todos los campos de ordenación, no confundir con los
campos de búsqueda, realizando distintas llamadas al método *addOrderBy*
e indicando el nombre de la vista a la que añadimos la ordenación, la
expresión de ordenación (cualquier expresión aceptada por la clausula
ORDER BY de SQL), texto a visualizar al usuario y el indicativo de orden
por defecto.

Consideraciones: \* si no se indica texto a visualizar, se empleará el
valor informado en la expresión de ordenación (aplicando el sistema de
traducciones) \* si no se indica valor de ordenación por defecto, se
entiende que no hay una ordenación por defecto y se aplicará el primer
orden añadido \* al añadir una ordenación **siempre** se añaden dos
opciones de ordenación, una ascendente y otra descendente \* para
establecer una ordenación por defecto, al añadir la ordenación podemos
indicar como valores 1 para la ascendente y 2 para la descendente

Ejemplo de adición de ordenación (siguiendo el ejemplo anterior) con
ordenación por código descendente

.. code:: php

        /* Epigrafes */
        $this->addOrderBy('ListEpigrafe', 'descripcion', 'description');
        $this->addOrderBy('ListEpigrafe', 'codepigrafe||codejercicio', 'code', 2);
        $this->addOrderBy('ListEpigrafe', 'codejercicio');

        /* Clientes */
        $this->addOrderBy('ListCliente', 'codcliente', 'code');
        $this->addOrderBy('ListCliente', 'nombre', 'name', 1);
        $this->addOrderBy('ListCliente', 'fecha', 'date');

        /* Grupos */
        $this->addOrderBy('ListGrupoClientes', 'codgrupo', 'code');
        $this->addOrderBy('ListGrupoClientes', 'nombre', 'name', 1);

Adición de filtros
==================

El controlador *ListController* integra un sistema de filtrado de datos
que permite personalizar de manera sencilla las opciones de filtrado que
se presentan al usuario. Cada tipo de filtro requiere de una
parametrización propia para su funcionamiento como el nombre de la vista
a la que lo añadimos, y entre los tipos de filtros disponibles están:

-  **addFilterSelect** : Filtro tipo selección de una lista de valores.

   -  key : Es el nombre interno del filtro y debe coincidir con el
      nombre del campo del modelo que se está visualizando y por el que
      se quiere filtrar.
   -  table : Nombre de la tabla de donde se leerán las opciones para la
      lista desplegable.
   -  where : Cláusula WHERE a pasar en la selección de datos de la
      tabla origen de la lista.
   -  field : Nombre del campo que se visualiza en la lista desplegable.
      Si no se informa se muestra el campo key.

-  **addFilterCheckbox** : Filtro tipo checkbox o de selección booleana.

   -  key : Es el nombre interno del filtro.
   -  label : Es la descripción a visualizar y que indica al usuario la
      función del filtro.
   -  field : Nombre del campo del modelo donde se aplica el filtro. Si
      no se indica se usa el valor de key.
   -  inverse : Permite comprobar el valor inverso.
   -  matchValue : Permite especificar el valor a comprobar.

-  **addFilterDatePicker** : Filtro de tipo fecha.
-  **addFilterText** : Filtro de tipo alfanumérico o texto libre.
-  **addFilterNumber** : Filtro de tipo numérico y/o importes.

   -  key : Es el nombre interno del filtro.
   -  label : Es la descripción a visualizar y que indica al usuario la
      función del filtro.
   -  field : Nombre del campo del modelo donde se aplica el filtro. Si
      no se indica se usa el valor de key.

Estos últimos filtros, al ser añadidos, insertan dos campos de filtrado
en la misma columna, junto con unos botones que permiten seleccionar el
tipo de operador [Igual, Mayor o Igual, Menor o Igual, Diferente] que se
aplicará en el filtro. La combinación de operadores y valores
informados, permite establecer filtrados de mayor complejidad dándole al
usuario una gran diversidad en la búsqueda de información.

Ejemplos de filtros

.. code:: php

        $this->addFilterSelect('ListEpigrafe', 'codepigrafe', 'co_epigrafes', '', 'descripcion');
        $this->addFilterCheckbox('ListCliente', 'debaja', 'De baja');
        $this->addFilterDatePicker(ListArticulo, 'fecha', 'Fec. Alta');

getPageData
===========

Este método es el encargado de devolver un array con los datos para la
instalación y configuración del controlador dentro del entorno de
**Facturascripts**. Como norma hay que llamar al *parent* del
controlador para inicializar los valores por defecto y asegurar un
correcto funcionamiento de nuestro controlador en el entorno de
Facturascripts.

Los valores que se pueden configurar son: \* title : Título de la vista
\* icon : Icono de la fuente de texto *fontawesome* \* menu : Nombre del
menú donde se introducirá el controlador \* submenu : (opcional) Segundo
nivel del menú donde se introduciría el controlador \* orden : Podemos
alterar el orden natural del sistema de menú para colocar nuestro
controlador más arriba o abajo

.. code:: php

        public function getPageData()
        {
            $pagedata = parent::getPageData();
            $pagedata['title'] = 'Agentes';
            $pagedata['icon'] = 'fa-user-circle-o';
            $pagedata['menu'] = 'admin';
            return $pagedata;
        }
