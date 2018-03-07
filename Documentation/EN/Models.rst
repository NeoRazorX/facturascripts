.. title:: Models
.. highlight:: rst

######
Models
######

The models are responsible for managing access to each of the tables of
the database where the data with which we work is persisted.
So for each table that we use in the application, your model must exist, in charge
of reading, writing and deleting the data contained in said table.
The structure of the table is defined in an XML file located in the *Table* folder
and with the name that the model returns in the public method *tableName*.

In the version *Facturascripts 2018* the use of the models has been restructured,
inheriting from the ModelClass class and complementing it in a Trait (Traits)
called *ModelTrait* thus grouping the most common operations
and general models, simplifying both the code and the treatment of
the same ones, delegating to these only the specific characteristics of each one.


*********************
Models of data tables
*********************

As mentioned in the introduction, each data table has a model in charge
of the management of its reading, writing and deletion. At the time of declaring the model,
we must create a new class that inherits from ModelClass and in which we will
include the use of the ModelTrait together with the list of available fields in
the table declared as public.

.. code:: php

    class Agente
    {
        use Base\ModelTrait;

        public $codagente;
        public $dnicif;
        public $nombre;
        public $apellidos;
    }


Required Methods
================

For a correct operation of the model, we have to link the physical structure
of the table (name of the table and primary key) with the model that manages it.
For this it is require to define two methods that will return the information through
text string: **tableName** and **primaryColumn**.

.. code:: php

    public static function tableName()
    {
        return 'agentes';
    }

    public function primaryColumn()
    {
        return 'codagente';
    }


Reading of Data
================

The models, by the fact of using the ModelTrait, put at our disposal several
methods to obtain information from the table to which it is linked:

all
---

This method returns an array of the model that executes it. That is, each element
of the returned array is an object of the same class as the model that is executing the
All method, where each of the elements is "filled" with the information of each
one of the records read from the table consulted.

When the query is made, or execution of the all method, we can report different
parameters that help us to filter the data to receive from the table of the database:

-  **where** : It allows filtering the data to be collected. The filtering system is used
    using the DataBaseWhere class included in the database management of Facturascripts 2018.

-  **order** : It allows to indicate the sorting data of the records to be collected.
    It is an array of one or more elements *(key => value)* where the key is the SQL clause
    to apply and the value indicates whether it is ascending or descending *["ASC" | "DESC"]*

-  **offset** : It allows to indicate a displacement of the first record to be collected.

-  **limit** : It allows to indicate the maximum number of records to be collected.

Example: *(Last 15 customer orders of AGENTE10 agent)*
.. code:: php

    $albaran = new AlbaranCliente();
    $where = [new DataBase\DataBaseWhere('codagente', 'AGENTE10')];
    $order = ['fecha' => 'DESC'];
    $albaranes = $albaran->all($where, $order, 0, 15);


get
---

This method returns a unique and new model of the same class as the model
that executes it, but "filled in" with the record information whose column

Example: *(reading agent AGENT10)*
.. code:: php

    $model = new Agente();
    $agente = $model->get('AGENTE10');


loadFromCode
------------

Like the previous method, this method serves to read a single record.
The big difference is that the information read is entered on the same
model that executes it, instead of returning a new model. Also note that the
reading can be done, just like the get method reporting the primary column
(primary key) or through the filtering system of the DataBaseWhere class and ordered,
similar to the all method.

The method returns a TRUE value if it manages to read the requested record. In case of
no existing initializes the model values and returns FALSE.

Example: *(Last agent AGENT10 document file)*

.. code:: php

    $albaran = new AlbaranCliente();
    $where = [new DataBase\DataBaseWhere('codagente', 'AGENTE10')];
    $order = ['fecha' => 'DESC'];
    $ok = $albaran->loadFromCode('', $where, $orderby);


Example: *(reading agent AGENT10)*

.. code:: php

    $agente = new Agente();
    $ok = $agente->loadFromCode('AGENTE10');


Data Recording
==============

In the same way for the processes of recording of data there are generic methods
to the *ModelTrait* that facilitate the work with the models. The persistence process
of data from a model has a "predefined path" or set of methods
that are executed sequentially automatically, but that we can overwrite
in our model class to customize each of the steps.

save
----

This method is the launcher of the entire recording process. He is in charge of executing
the test method to validate the data that you want to record, as well as to control if
will register a new record or modify an existing record. Returns a
Boolean value indicating whether the process has been carried out correctly or vice versa
It has not been possible.

test
----

Method responsible for calculating dependent fields of others, and validating the data
endings that will be recorded. Every new model has to overwrite this method
to validate the fields of the model. In addition to general validations
(length, type, existence of value, etc.), one of the mandatory validations is
Check that HTML code is not "injected" into any text field.

In case of breach of any validation rule we must add the error in
the alert system of the application, alert that will be shown to the
user in the window so you can correct it.

Validation example:

.. code:: php

    $this->nombre = self::noHtml($this->nombre);
    $this->apellidos = self::noHtml($this->apellidos);
    $this->dnicif = self::noHtml($this->dnicif);

    if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 50)) {
        $this->miniLog->alert($this->i18n->trans('agent-name-between-1-50'));
        return false;
    }

    if ($this->codagente === null) {
        $this->codagente = $this->newCode();
    }

    return true;


checkArrayData
--------------

This is a "special" method, in charge of verifying the data sent by the user
from a form in an Edit or EditList controller. It is important to understand that this
method is executed before beginning the recording process. The process receives an array
with the information sent by the user, and if there are special fields, no
informed from the form, we must overwrite the method and add to the data array
the fields not included. After this method, the normal recording process will begin
of data.


Método url
==========

The controllers use the url method to know the different navigation urls
between windows. The ModelTrait has a url method that establishes a generic way
what should be the url of the model for each of the cases "list" and "edit" *(List and Edit)*,
but there are occasions when the model needs to personalize said urls. In these cases
we can overwrite this method to return the correct url for each case.

Ejemplo:

.. code:: php

    class CuentaEspecial
    {
        use Base\ModelTrait {
            url as private traitURL;
        }

        public function url($type = 'auto')
        {
            return $this->traitURL($type, 'ListCuenta&active=List');
        }
    }


***********
Model Trait
***********

From version 5.4.0, PHP implements a code reuse methodology
called Traits. In *Facturascripts 2018* we make use of this methodology
to unify multiple processes of models that would otherwise be repeated
in each model created. This simplifies the code of the models and allows to maintain
the unified code in a single class: **ModelTrait**

When creating a new model, we must include the instruction for using the ModelTrait:

.. code:: php

    class Agente
    {
        use Base\ModelTrait;

        [ ... ]
    }


Métodos comunes
===============

-  **primaryColumnValue** : Returns the value of the key field (Primary Key).

-  **primaryDescription** : Returns the descriptive identifier for the data record.

-  **loadFromData** : Load the data of the model with the data array that is passed to it by parameter.

-  **loadFromCode** : Load the model data from the value of the key field being reported, or from a where (SQL) condition.

-  **get** : Returns a new model with the data loaded from the value of the key field being reported.

-  **clear** : Initializes the model data to null.

-  **save** : The data of the model persists in the database.

-  **delete** : Remove the record with the primary key equal to the model from the database.

-  **count** : Returns the number of records that meet the where (SQL) condition reported.

-  **all** : Returns an array of models that meet the where (SQL) informed condition.


Colisiones
==========

Sometimes you need to overwrite methods defined in ModelTrait, but the
Traits is not a class of which we inherit but rather it is a class that "we use"
so it is not possible to overwrite directly as we would with an inheritance.
Instead we need to "rename" or give an alias to the method that we need to overwrite,
include the method in our model in a "normal" manner but including a call
to the "alias" that we have created.

.. code:: php

    class Agente
    {
        use Base\ModelTrait {
            test as testTrait;
        }

        public function test()
        {
            $this->apellidos = self::noHtml($this->apellidos);
            $this->nombre = self::noHtml($this->nombre);
            if (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 50)) {
                $this->miniLog->alert($this->i18n->trans('agent-name-between-1-50'));
                return false;
            }
            return $this->testTrait();
        }
    }



**************
Special models
**************

There are several models that do not correspond to physical tables in the
database, so they can not be used for recording or deleting data.
The function of these models is to serve as a complement to the rest of the models
to perform special operations to read information, globally,
thus avoiding having to create repeated methods in different models.

CodeModel
=========

This model is used in cases where we are interested in obtaining a list of records
of some table, but only a code or identification field and its description.
Being a very simple model, it does not include all the loading processes that normally
they carry the models limited only to the reading and return of the data requested.
This model is used for example in loading the Widget of type "select" where it is displayed
to the user a list of options so you can select one. The only method that
has is the all, but unlike the other models in this case is a method
static so it does not require us to create a CodeModel object for its execution.

Example of load data *código + descripción*:
*The last parameter of the call **($addEmpty)** allows us to indicate if we need to
At the beginning of the array that is returned with the data, insert a blank CodeModel.*

.. code:: php

    $rows = CodeModel::all('agentes', 'codagente', 'nombre', false);


TotalModel
==========

This model is specially designed for statistical calculations *(SUM, AVG, COUNT, MAX, MIN, etc.)*.
Although it is not mandatory, we can execute the calculations with grouping by a "code" field.
So when executing the model all returns an array of **TotalModel** (code, totals)
where code contains the grouping identifier and totals is an array with each one
of the calculations that have been requested.

Example invoices for sale without invoicing per customer

.. code:: php

    $where = [new DataBase\DataBaseWhere('ptefactura', TRUE)];
    $totals = Model\TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)', 'count' => 'COUNT(1)'], 'codcliente');
