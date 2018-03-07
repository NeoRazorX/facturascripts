.. title:: PanelController
.. highlight:: rst

###############
PanelController
###############

This controller, like the *ListController*, is a **universal
controller** for multiple views although in this case the use of
different types of views is allowed: *ListView*, *EditView*,
*EditListView* and *GridView*.

The controller divides the screen into two zones, one to the left
(navigation zone) and one to the right where the views with the data are
displayed.

For the use of this controller it is necessary to create the views in
XML format, as described in the document [XMLViews]
(https://github.com/ArtexTrading/facturascripts/blob/master/Documentation/XMLViews_EN.md),
included in the documentation of **Facturascripts**.

********************
Using the Controller
********************

To use *PanelController* we must create a new PHP class that inherits or
extends from PanelController, having to implement the following methods:

-  **createViews**: In charge of creating and adding the views that we
   want to visualize inside the PanelController.

-  **loadData**: Charge the data for each of the views.

-  **getPageData**: Sets the general data (title, icon, menu, etc) for
   the main view (the first one we added in *createViews*).

createViews
===========

Within this method, in our new class, we must create the different views
to be visualized, using different methods depending on the type of view
we are adding. We must indicate by means of text strings, when adding a
view, the model (Full name) and the name of the XML view, and optionally
the title and icon for the navigation group.

-  **addEditView**: Adds a view to edit data from a single record of a
   model.
-  **addEditListView**: Adds a view to edit multiple records of a model.
-  **addListView**: Adds a view to display in multiple record list mode
   of a model.
-  **addGridView**: Add a view that allows you to edit the data in a grid
   of rows and columns data in the style of a spreadsheet.

.. code:: php

        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'Cliente');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', 'Direcciones', 'fa-road');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'Mismo Grupo');
        $this->addGridView('EditAsiento', '\FacturaScripts\Dinamic\Model\Partida', 'EditPartida', 'accounting-items');

This method has a visibility of *protected* so that plugins can extend
our class and add new views, or modify existing ones.

loadData
========

This method is called by each of the views so that we can load the data
specific information. In the call we are informed of the identifier of
the view and the view object itself, being able to access all the
properties of the same. The data load may vary depending on the type of
view, so it is the responsibility of the programmer to perform data
loading correctly. Although this may suppose an added difficulty, also
allows us greater control over the data that read from the model.

Example of loading data for different types of views.

.. code:: php

        switch ($keyView) {
            case 'EditCliente':
                $value = $this->request->get('code');   // Pick up the code to read
                $view->loadData($value);                // Load the model data for the code
                break;

            case 'EditDireccionCliente':
                // we create a where filter to collect the records belonging to the informed code
                $where = [new DataBase\DataBaseWhere('codcliente', $this->getClientFieldValue('codcliente'))];
                $view->loadData($where);
                break;

            case 'ListCliente':
                // we load data only if there is an informed group
                $codgroup = $this->getClientFieldValue('codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
                break;
        }

setTabsPosition
===============

This method let you put tabs on left, bottom or on top of the page. Left
is the default position.

The tabs when they are placed on the left, the information will be displayed
of the selected tab. In these cases it is not necessary to specify the method.

Example without specifying the method.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');

Example with the method.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('left');

The tabs when placed below, shows main window and below
This will show the information of the selected tab.

Example.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('bottom');

The tabs when they are placed above, will show the information of
the selected tab.

Example.

.. code:: php

    $this->addEditView('FacturaScripts\Core\Model\Asiento', 'EditAsiento', 'accounting-entries', 'fa-balance-scale');
    $this->addListView('FacturaScripts\Core\Model\Partida', 'ListPartida', 'accounting-items', 'fa-book');
    $this->setTabsPosition('top');

getPageData
===========

This method is responsible for returning an array with the data for the
installation and configuration of the controller within the environment
of **Facturascripts**. As a rule, you must call the *parent* of the
controller to initialize the default values and ensure a proper
operation of our controller in the Facturascripts environment.

The values that can be configured are: \* **title**: Translation
reference for view title \* **icon**: Text font icon *fontawesome* \*
**menu**: Name of the menu where the controller will be inserted \*
**submenu**: (optional) Second level of the menu where the controller
would be entered \* **order**: We can alter the natural order of the
menu system to place our controller higher or lower

.. code:: php

        public function getPageData()
        {
            $pagedata = parent::getPageData();
            $pagedata['title'] = 'agents';
            $pagedata['icon'] = 'fa-user-circle-o';
            $pagedata['menu'] = 'admin';
            return $pagedata;
        }
