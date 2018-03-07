.. title:: GridViews
.. highlight:: rst

###############
GridViews Views
###############

These views are views that depend on another parent view of type * EditView *
and in which the data "children" will be displayed within a table of rows and
columns similar to a spreadsheet. This type of visualization allows
the user to move freely through the data, change the order of the rows,
as well as being able to create new lines, eliminate them and even copy/cut and paste.

  .. note::
     It is only possible to have a Grid view inside a PanelController.
     For the correct rendering of these views it is necessary to use the template
     GridController instead of the one normally used by PanelController.


*******************
How to use the view
*******************

As mentioned earlier, this type of view needs a view parent with whom the data
is related, so it is mandatory that it be used inside a controller `PanelController <PanelController>`__
where it will be added first the 'master' view and then the Grid view.
In the same way we must establish the rendering template in GridController or
another that inherits from it.

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

The GridController template adds the file upload and the creation of the objects
needed to manage the data grid, and create a new block called *gridcard* where
you insert the same. The data is loaded into a variable of JavaScript called
*documentLineData* and the visualization is done inside of a bootstrap card,
in the 'body' with the identifier *document-lines*.

These processes are only included when there is already a data record in the
father model. If it is a new registration, only the form will be displayed
to enter the data of the father and when recording it will refresh the page visualizing
the data grid.

Although these tasks are carried out automatically it is possible to customize
the appearance creating a new Twig view that inherits from GridController overwriting
the *gridcard* block.

    ..code:: html
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

We must also include the loading of a JavaScript file to customize
the events of the grid. This file must be named just like the driver and
be located in the *Core\Assets\JS* folder. We will use the function *$(document).ready*
to enter the events to be controlled. In the current version, it is mandatory
redefining the recording of data when the parent record already exists, so that
PanelController does not use the normal recording process but the GridView process.

    ..code:: javascript
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

