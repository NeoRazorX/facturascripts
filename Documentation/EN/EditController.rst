.. title:: EditController
.. highlight:: rst

##############
EditController
##############

It is a **universal controller** for views that want to display the
complete data of a record of data of a model, in format “tab” or by a
design of columns grouped according to the type of information. The use
of this controller greatly simplifies the programming required for
editing the data, as well as unify the image of the application and
plugins creating a uniform environment for the user which speeds up
learning and adaptation to **Facturascripts**.

For the use of this controller it is necessary to create the views in
XML format, as described in the document [XMLViews]
(https://github.com/ArtexTrading/facturascripts/blob/master/Documentation/XMLViews_EN.md),
included in the documentation of **Facturascripts**.

********************
Using the Controller
********************

To use *EditController* we must create a new PHP class that inherits or
extends from EditController, establishing in the constructor of our new
class the model on which we will work and having to implement the
following method:

-  **getPageData**: Sets the general data (title, icon, menu, etc) for
   the view

.. code:: php

        public function __construct(&$cache, &$i18n, &$miniLog, $className)
        {
            parent::__construct($cache, $i18n, $miniLog, $className);

            // Establecemos el modelo de datos
            $this->modelName = 'FacturaScripts\Core\Model\Familia';
        }

Indicate that the name of the XML view to be loaded will be the same as
the new class we created. There are also general methods that we can
override to customize the screen, (see below).

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

Customization
=============

There are two methods that allow us to customize the data to be
displayed in the header and footer of the data sheet.

.. code:: php

        public function getPanelHeader()
        {
            return $this->i18n->trans('header-data');
        }

        public function getPanelFooter()
        {
            return $this->i18n->trans('footer-data');
        }

We can also customize the view by including it in the group XML file
*<rows>* and create *<row type = “”>* of the classes **statistics**, to
define a list of statistical and relational buttons with other models,
and **footer**, to add information additional to display to the user
just after the data sheet.

Examples:

.. code:: xml

        <rows>
            <row type="statistics">
                <option icon="fa-files-o" label="Alb. Pdtes:" calculateby="nombre_function" onclick="#url"></option>
                <option icon="fa-files-o" label="Pdte Cobro:" calculateby="nombre_function" onclick="#url"></option>
            </row>

            <row type="footer">
                <option label="Panel Footer" footer="Panel footer" color="warning">This is an example with header and footer</option>
                <option label="Esto es un info" color="info">This is an example with header and without footer</option>
                <option footer="Texto en el footer" color="success">This is an example without header</option>
            </row>
        </rows>
