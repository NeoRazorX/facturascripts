.. title:: EditController
.. highlight:: rst

##########################
Controlador EditController
##########################

Es un **controlador universal** para vistas que desean mostrar los datos
completos de un registro de datos de un modelo, en formato “ficha” o
mediante un diseño de columnas agrupadas según el tipo de información.
El uso de este controlador simplifica en gran manera la programación
necesaria para la edición de los datos, así como unificamos la imagen de
la aplicación y plugins creando un entorno uniforme para el usuario lo
que acelera el aprendizaje y adaptación a **Facturascripts**.

Para el uso de este controlador es necesario crear las vistas en formato
XML, tal y como se describe en el documento
`XMLViews <XMLViews>`__,
incluido en la documentación de **Facturascripts**.

************************
Cómo usar el controlador
************************

Para utilizar *EditController* debemos crearnos una nueva clase PHP que
herede o extienda de EditController, estableciendo en el constructor de
nuestra nueva clase el modelo sobre el que trabajaremos y debiendo
implementar el siguiente método:

-  **getPageData**: Establece los datos generales (título, icono, menú,
   etc) para la vista

.. code:: php

        public function __construct(&$cache, &$i18n, &$miniLog, $className)
        {
            parent::__construct($cache, $i18n, $miniLog, $className);

            // Establecemos el modelo de datos
            $this->modelName = 'FacturaScripts\Core\Model\Familia';
        }

Indicar que el nombre de la vista XML que se cargará será la denominada
igual que la nueva clase que hemos creado. También existen métodos
generales que podemos sobreescribir para personalizar la pantalla, (ver
más abajo).

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

Personalización
===============
Existen dos métodos que nos permiten personalizar los datos a visualizar en la cabecera y pie de la ficha de datos.

.. code:: php

        public function getPanelHeader()
        {
            return $this->i18n->trans('header-data');
        }

        public function getPanelFooter()
        {
            return $this->i18n->trans('footer-data');
        }

También podemos personalizar la vista mediante la inclusión en el fichero XML del grupo *<rows>*
y crear *<row type=“”>* de las clases **statistics**, para definir una lista de botones estadísticos y
relacionales con otros modelos, y **footer**, para añadir información adicional a visualizar al
usuario justo después de la ficha de datos.

Ejemplos:

.. code:: xml

        <rows>
            <row type="statistics">
                <option icon="fa-files-o" label="Alb. Pdtes:" calculateby="nombre_function" onclick="#url"></option>
                <option icon="fa-files-o" label="Pdte Cobro:" calculateby="nombre_function" onclick="#url"></option>
            </row>

            <row type="footer">
                <option label="Panel Footer" footer="Panel footer" color="warning">Este es un ejemplo con cabecera y footer</option>
                <option label="Esto es un info" color="info">Este es un ejemplo con cabecera y sin footer</option>
                <option footer="Texto en el footer" color="success">Este es un ejemplo sin cabecera</option>
            </row>
        </rows>
