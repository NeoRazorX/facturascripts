.. title:: PanelController
.. highlight:: rst

###########################
Controlador PanelController
###########################

Este controlador, al igual que el controlador *ListController* es un
**controlador universal** para multiples vistas aunque en este caso se
permite el uso de distintos tipos de vistas: *ListView*, *EditView*,
*EditListView* and *GridView*.

El controlador divide la pantalla en dos zonas, una a la izquierda (zona
de navegación) y otra la derecha donde se visualizan las vistas con los
datos.

Para el uso de este controlador es necesario crear las vistas en formato
XML, tal y como se describe en el documento
`XMLViews <./XMLViews>`__,
incluido en la documentación de **Facturascripts**.

************************
Cómo usar el controlador
************************

Para utilizar *PanelController* debemos crearnos una nueva clase PHP que
herede o extienda de PanelController, debiendo implementar los
siguientes métodos:

-  **createViews**: Encargado de crear y añadir las vistas que deseamos
   visualizar dentro del PanelController.

-  **loadData**: Encargado de cargar los datos para cada una de las
   vistas.

-  **getPageData**: Establece los datos generales (título, icono, menú,
   etc) para la vista principal (la primera que añadimos en
   *createViews*).

createViews
===========

Dentro de este método, en nuestra nueva clase, debemos ir creando las
distintas vistas que se visualizarán, debiendo usar distintos métodos
según el tipo de vista que estamos añadiendo. Debemos indicar mediante
cadenas de texto, al añadir una vista, el modelo (Nombre completo) y el
nombre de la vista XML, y opcionalmente el título y el icono para el
grupo de navegación.

-  **addEditView**: Añade una vista para editar datos de un único
   registro de un modelo.
-  **addEditListView**: Añade una vista para editar multiples registros
   de un modelo.
-  **addListView**: Añade una vista para visualizar en modo lista
   multiples registros de un modelo.
-  **addGridView**: Añade una vista que permite editar los datos en un grid
   de datos de filas y columnas al estilo de una hoja de cálculo.

.. code:: php

        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'Cliente');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', 'Direcciones', 'fa-road');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'Mismo Grupo');
        $this->addGridView('EditAsiento', '\FacturaScripts\Dinamic\Model\Partida', 'EditPartida', 'accounting-items');

Este método tiene una visibilidad de *protected* de manera que los
plugins pueden ir extendiendo nuestra clase y añadir nuevas vistas, o
modificar las existentes.

loadData
========

Este método es llamado por cada una de las vistas para que podamos
cargar los datos específicos de la misma. En la llamada se nos informa
del identificador de la vista y el propio objeto view, pudiendo acceder
a todas las propiedades del mismo. La carga de datos puede variar según
el tipo de vista, por lo que es responsabilidad del programador realizar
la carga de datos correctamente. Aunque esto pueda suponer una
dificultad añadida, también nos permite un mayor control sobre los datos
que a leer del modelo.

Ejemplo de carga de datos para distintos tipos de vistas.

.. code:: php

        switch ($keyView) {
            case 'EditCliente':
                $value = $this->request->get('code');   // Recoge el código a leer
                $view->loadData($value);                // Carga los datos del modelo para el codigo
                break;

            case 'EditDireccionCliente':
                // creamos un filtro where para recoger los registros pertenecientes al código informado
                $where = [new DataBase\DataBaseWhere('codcliente', $this->getClientFieldValue('codcliente'))];
                $view->loadData($where);
                break;

            case 'ListCliente':
                // cargamos datos sólo si existe un grupo informado
                $codgroup = $this->getClientFieldValue('codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
                break;
        }

setTabsPosition
===============

Este método permite poner las pestaña a la izquierda (left), abajo
(bottom) o arriba (top). Por defecto están colocadas a la izquierda.

Las pestañas cuando están colocadas a la izquierda, se mostrara la información
de la pestaña seleccionada. En estos caso no es necesario especificar el método.

Ejemplo sin especificar el método.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');

Ejemplo con el método.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('left');

Las pestañas cuando están colocadas abajo, muestra ventana principal y debajo
de esta mostrara la información de la pestaña seleccionada.
seleccionada.

Ejemplo.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('bottom');

Las pestañas cuando están colocadas arriba, mostrara la información de
la pestaña seleccionada.

Ejemplo.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('top');

getPageData
===========

Este método es el encargado de devolver un array con los datos para la
instalación y configuración del controlador dentro del entorno de
**Facturascripts**. Como norma hay que llamar al *parent* del
controlador para inicializar los valores por defecto y asegurar un
correcto funcionamiento de nuestro controlador en el entorno de
Facturascripts.

Los valores que se pueden configurar son: \* **title**: Referencia de
traducción del título de la vista \* **icon**: Icono de la fuente de
texto *fontawesome* \* **menu**: Nombre del menú donde se introducirá el
controlador \* **submenu**: (opcional) Segundo nivel del menú donde se
introduciría el controlador \* **orden**: Podemos alterar el orden
natural del sistema de menú para colocar nuestro controlador más arriba
o abajo

.. code:: php

        public function getPageData()
        {
            $pagedata = parent::getPageData();
            $pagedata['title'] = 'agents';
            $pagedata['icon'] = 'fa-user-circle-o';
            $pagedata['menu'] = 'admin';
            return $pagedata;
        }
