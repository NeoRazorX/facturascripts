.. title:: GridViews
.. highlight:: rst

################
Vistas GridViews
################

Estas vistas son vistas que dependen de otra vista padre de tipo *EditView*
y en la que los datos "hijos" se visualizarán dentro de un tabla de filas y
columnas similar a una hoja de cálculo. Este tipo de visualización permite
al usuario moverse libremente por los datos, cambiar el orden de las filas,
así como poder crear nuevas líneas, eliminarlas e incluso copiar/cortar y pegar.

  .. note::
     Sólo es posible tener una vista Grid dentro de un PanelController.
     Para el correcto renderizado de estás vistas es necesario usar la plantilla
     GridController en vez de la usada normalmente por PanelController.


**********************
Cómo utilizar la vista
**********************

Cómo se ha mencionado con anterioridad, este tipo de vista necesita de una vista
padre con la que se relacionan los datos, por lo que es obligatorio que se use
dentro de un controlador `PanelController <PanelController>`__ donde se añadirá primero
la vista 'master' y posteriormente la vista Grid. De igual manera debemos establecer
la plantilla de renderización en GridController u otra que herede de esta.

.. code:: php

        class EditAsiento extends ExtendedController\PanelController
        {
            protected function createViews()
            {
                $this->addEditView('\FacturaScripts\Dinamic\Model\Asiento', 'EditAsiento', 'accounting-entry', 'fa-balance-scale');
                $this->addGridView('EditAsiento', '\FacturaScripts\Dinamic\Model\Partida', 'EditPartida', 'accounting-items');
                $this->setTemplate('GridController');
            }
        }

La plantilla GridController añade la carga de archivos y la creación de los objetos
necesarios para gestionar el grid de datos, y crea una nuevo bloque denominado
*gridcard* donde inserta el mismo. Los datos son cargados en una variable de
JavaScript denominada *documentLineData* y la visualización se realiza dentro
de un card de bootstrap, en el 'body' con el identificador *document-lines*.

Estos procesos sólo son incluidos cuando ya existe un registro de datos en el
modelo padre. En caso de ser un alta nueva, sólo se visualizará el formulario
para introducir los datos del padre y al grabar se refrescará la página visualizando
el grid de datos.

Aunque estas tareas se realizan de manera automática es posible personalizar
la apariencia creando una nueva vista twig que herede de GridController sobrescribiendo
el bloque *gridcard*.

.. code:: html

        {% block gridcard %}
        <div class="col-9 mr-2">
            <div class="card">
                <div class="card-header">
                    <span><small id="account-description"></small></span>
                    <span class="float-right"><small><strong>{{ i18n.trans('unbalance') }}:&nbsp;<span id="unbalance">0.00</span></strong></small></span>
                </div>
                <div class="body">
                    <div id="document-lines"></div>
                </div>
            </div>
        </div>
        {% endblock %}


Además debemos incluir la carga de un archivo JavaScript donde personalizar
los eventos del grid. Este archivo debe llamarse igual que el controlador y
estar ubicado en la carpeta *Core\Assets\JS*. Usaremos la función *$(document).ready*
para introducir los eventos a controlar. En la versión actual, es obligatorio
redefinir la grabación de datos cuando ya existe el registro padre, para que
PanelController no use el proceso de grabación normal sino el proceso de GridView.

.. code:: javascript

        function saveAccountEntry() {
            submitButton.prop("disabled", true);
            try {
                var mainForm = $("form[name^='EditAsiento-']");
                var data = {
                    action: "save-document",
                    lines: getGridData('orden'),
                    document: {}
                };

                $.each(mainForm.serializeArray(), function(key, value) {
                    switch (value.name) {
                        case 'action':
                            break;

                        case 'active':
                            data[value.name] = value.value;
                            break;

                        default:
                            data.document[value.name] = value.value;
                            break;
                    }
                });

                $.post(
                    documentUrl,
                    data,
                    function (results) {
                        if (results.error) {
                            alert(results.message);
                            return;
                        }
                        location.reload();
                    });
            } finally {
                submitButton.prop("disabled", false);
                return false;
            }
        }

        $(document).ready(function () {
            if (document.getElementById("document-lines")) {
                // Rewrite submit action
                submitButton = $("button[id^='submit-EditAsiento-']");
                submitButton.on('click', saveAccountEntry);

                // Add control events to Grid Controller
                addEvent('beforeChange', data_beforeChange);
                addEvent('afterSelection', data_afterSelection);
            }
        });

