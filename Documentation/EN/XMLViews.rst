.. title:: Views
.. highlight:: rst

#####
Views
#####

The views, in *Facturascripts 2018* are classified according to their representation
on screen both in the form of visualization and in the number of data records.

-  **List**: Views that show a list of data in rows and columns format
   can navigate, search and/or filter the data but where the data are from
   read only, that is, editing is not allowed.

-  **Edit**: Views that show a form for editing a single record of data, these data can be grouped.

-  **EditList**: Result resulting from the "union" of the previous types. That is to say,
   a list of records displayed in rows and columns but where each of the
   rows is an edit form that allows us to edit the data of that record.
   This view contains two viewing modes. A basic system like columns, when
   the view has less than 6 columns, or the entire viewing system if the view contains
   6 or more columns of data.

-  **GridView**: View that depends on a parent view of type **Edit** and in which the representation
    and data manipulation is given by a grid of rows and columns in the style of a spreadsheet.
    This type of view requires a JavaScript file where different events are controlled, such as
    creation of the data grid and data viewing and editing events.
    Form more information view `GridViews <GridViews>`__.

The naming of the views, when we create them, follows the rule: *List* or *Edit* followed
from the *name of the model*. This is true even if the view is of type *EditList* or **GridView**
in which case it will be named as if it were of type *Edit*.


*********
XML Views
*********

To create the views we will use a file with structure **XML** and, as indicated
previously, with the name of the type of view and the model, where we will establish the
Visual composition of the fields, actions and visual options of the view.

The root element of the XML file will be *<view>* and may include the following
tags as group:

-  **<columns>**: (required) To define the list of fields that are
   displayed in the view.

-  **<rows>**: (optional) Defines special conditions for the rows.

-  **<modals>**: (optional) Defines a modal form that will be displayed
   by interacting with a button defined in the view.


COLUMNS
=======

You can define by means of the tag *<column>* each one of the fields
that will be visualized in the view being able, in the *Edit* views, to
group the columns by *<group>* tag. The columns are complemented by
the mandatory *<widget>* tag, which serves to customize the type of
object used in the display/editing of the data.

Both *<group>*, *<column>* and *<widget>* tags have a set of
attributes that allow customization and vary according to the context in
which they are executed, ie if it is a *List* view or an *Edit* view. It
is possible to indicate the number of columns that will
occupy *<column>* and/or the group *<group>* within the bootstrap grid
(by default the maximum available).

Example view for ListController:

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
                <option color="info">Pending</option>
                <option color="warning">Partial</option>
            </row>
        </rows>

EditController view example:

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

We understand that it is each of the fields of the model and buttons that make up
the view and with which the user can interact. The tag *column* requires to contain one
of the tags *<widget>* or *<button>* for correct operation and is customized by
the following properties:

-  **name**: Internal identifier of the column. Its use is obligatory.
   As a rule, the use of lowercase and English identifiers is
   recommended.

-  **title**: Descriptive label of the field, in case of not being
   informed, the value of name is assumed.

-  **titleurl**: Destination URL if the user clicks on the title of the
   column.

-  **description**: Long description of the field that helps the user
   understand. In the List view it is shown as a hint on the column
   title. In Edit view it is displayed as a label inferior to the edit
   area of ​​the field.

-  **display**: Indicates whether or not to display the field and its
   alignment. If not reported, it takes *left* as its value. Values:
   *[left|center|right|none]*

-  **order**: Position that occupies the column. Indicates the order in
   which they are displayed. If not reported take the value *100* When
   no specific ordering is reported, it is sorted by the sequential
   position in the XML file, always within its group.

-  **numcolumns**: Force the size of the column to the indicated value,
   using the Bootstrap grid system being minimum 1 and maximum 12. If it
   is not reported, it takes *0* by applying Bootstrap’s automatic size
   system.


widget
------

Visual complement that is used for the visualization and/or edition of
the field/column. In List views, you can complete the *style* html
clause that will be applied to the column by a list of *<option>*,
where each attribute of the label *<option>* corresponds to its CSS
equivalent to be applied and the value of the tag is the value when the
format will be applied. To decide whether the format is applied or not
the following criteria will be applied to the value entered in the *<option>* tag:

-  If the value starts with ``>``: Applies if the value of the model
   field is greater than the value indicated after the operator.
-  If the value starts with ``<``: Applies if the field value of the
   model is less than the value indicated after the operator.
-  In any other case an equality check will be made.

Examples:

*Paint red when the field value ``pendiente`` is zero*

.. code:: xml

        <widget type="checkbox" fieldname="pendiente">
            <option color="red">0</option>
        </widget>

*Paint red and bold when the value of field ``estado`` is ``ABIERTO``*
*Paint blue when the value of field ``estado`` is ``CERRADO``*

.. code:: xml

        <widget type="text" fieldname="estado">
            <option color="red" font-weight="bold">ABIERTO</option>
            <option color="blue">CERRADO</option>
        </widget>

*Paint red when the field value ``cantidad`` is less than zero*

.. code:: xml

        <widget type="number" fieldname="cantidad">
            <option color="red">&lt;0</option>
        </widget>

*Paint red when the value of the field ``importe`` is greater than 30000*

.. code:: xml

        <widget type="money" fieldname="importe">
            <option color="red">&gt;30000</option>
        </widget>

-  **type**: (mandatory) Indicates the type of widget to use.
   -  **text**: varchar or text fields.     
   -  **number**: Numeric type fields. For this type you can specify
      the *decimal* attribute to configure the precision to be displayed.
      The *step* attribute to indicate the increase or decrease when performing
      a “step” by the forward/reverse control. The attributes *min* and *max*
      to indicate the minimum and maximum values.
   -  **money**: Fields of type float for amounts.
      For this type you can specify the *decimal* attribute to set the precision to
      be displayed instead of the currency.
   -  **checkbox**: Boolean values ​​that are displayed by
      the icon of a check (true) or a dash (false) respectively.
   -  **datepicker**: Date type fields, which include a drop-down to
      choose it.
   -  **color**: For color selections.
   -  **filechooser**: Allows you to select and upload a file.
   -  **autocomplete**: List of values that are loaded dynamically from a model
       depending on the text entered by the user. Only one will be used
       label *<values>* indicating the attributes:

          -  *source*: Indicates the name of the data source table
          -  *fieldcode*: Indicates the field that contains the value to be recorded in the field of the column
          -  *fieldtitle*: Indicates the field that contains the value that will be displayed on the screen

   -  **select**: List of values ​​set by a set of tags *<values>* described
      within the group *<widget>*. The values ​​can be fixed, including as many
      *<values>* as we need and indicating the attribute *title* and assigning a
      value, as dynamic, either calculated based on the contents of the records of
      a table in the database or by defining a range.
      For the case of values ​​of a table will be used a single tag *<values>* indicating
      the attributes:
          -  *source*: Indicates the name of the source table of the data
          -  *fieldcode*: Indicates the field containing the value to be recorded in the column field
          -  *fieldtitle*: Indicates the field containing the value that will be displayed on the screen

      For the case of values ​​by definition of range a single tag *<values>* indicating the attributes:
          -  *start*: Indicates the initial value (numeric or alphabetical)
          -  *end*: Indicates the final value (numeric or alphabetical)
          -  *step*: Indicates the increment value (numeric)

    -  **radio**: List of values ​​where we can select one of them. The various
       options are indicated by the tag system *<values>* described in the group *<widget>*,
       in the style of the *select* type.

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

-  **fieldname**: (required) Name of the field containing the
   information.

-  **onclick**: (optional) Name of the controller to call and pass the
   value of the field when clicking on the value of the column.

-  **required**: Optional attribute to indicate that the column must
   have a value at the time the data persist in the database.
   **[required = “true”]**

-  **readonly**: Optional attribute to indicate that the column is not
   editable. **[readonly = “true”]**

-  **maxlength** : Maximum number of characters allowed by the field.

-  **icon**: (optional) If indicated, the icon will be displayed to the
   left of the field.

-  **hint**: (optional) Explanatory text that is displayed by placing
   the mouse over the title in the Edit controller.



button
------

This visual element is available only in views of type *Edit* and *EditList* and
As its name suggests it allows to include a button in one of the editing columns.
There are three types of buttons declared by the ``type`` attribute and with functions
different:

*  *calculate* : Button to show a statistical calculation.
*  *action* : Button to execute an action in the controller.
*  *modal* : Button to show a modal form.
*  *js* : Button to execute a JavaScript function.

The button of type *calculate* is exclusive of the group *<rows>* and is detailed later.
For the *action* and *modal* buttons we can customize them using the attributes:

-  **type**: indicates the type of button.

-  **icon**: icon that will be displayed to the left of the label.

-  **label**: text or label that will be displayed on the button.

-  **color**: indicates the color of the button, according to the colors of Bootstrap for buttons.

-  **hint**: help displayed to the user when placing the mouse pointer over the button.
    This option is only available for buttons of type ``action``.

-  **action**: this property varies according to the type. For ``action`` buttons indicates the action
    which is sent to the controller, so that it performs some kind of special process.
    For buttons of type ``modal`` indicates the modal form that should be shown to the user.
    For buttons of type ``js`` indicates the name of the function to execute.

Example:

.. code:: xml

        <column name="action1" order="100">
            <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Run the controller with action=process1" />
        </column>

        <column name="action2" order="100">
            <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
        </column>


group
-----

Create a bootstrap grid where it will include each of the *<column>*
columns declared within the group. You can customize the group through
the following attributes:

-  **name**: Internal group identifier. Its use is obligatory. As a
   rule, the use of lowercase and English identifiers is recommended.

-  **title**: Group descriptive label. For groups the name value
   **will not be used** if a title is not entered.

-  **titleurl**: Destination URL if the user clicks on the group title.

-  **icon**: If indicated the icon will be displayed to the left of the
   title. The icon group only will be showed if title is present.

-  **order**: Position of the group. It is used to indicate the order in
   which it will be displayed.

-  **numcolumns**: Force the size to the indicated value, using the
   Bootstrap grid system being minimum 1 and maximum 12. If it is not
   reported, it takes *0* by applying Bootstrap’s automatic size system.
   It is important to remember that a group always has 12 columns available
   *inside*, regardless of the size of the group.


ROWS
====

This group allows you to add functionality to each of the rows or add
rows with special processes. Thus by the label *<row>* we can add the
functionalities, in a unique way (that is, we can not include twice the
same type of row) and using the *type* attribute to indicate the action
performed, each type having its own requirements.

status
------

This type colorize rows based on the value of a field in the record.
Requires one or more registers *<option>* indicating the bootstrap color
configuration for panels that we want for the row.

Example:

*paints the row with “info” color if field ``estado`` is ``Pendiente``*
*paints the row with “warning” color if field ``estado`` is ``Parcial``*

.. code:: xml

        <rows>
            <row type="status" fieldname="estado">
                <option color="info">Pending</option>
                <option color="warning">Partial</option>
            </row>
        </rows>


statistics
----------

Defines a list of statistical and relational buttons with other models that give
information to the user and allows consult when you click.
Each of the buttons are defined by the label * <button> * followed by the properties:

-  **type** : for this case always have ``calculate`` value.

-  **icon**: icon that will be displayed to the left of the label.

-  **label**: text or label that will be displayed on the button.

-  **calculateby**: name of the function of the controller that is executed to calculate the amount to be displayed.

-  **onclick**: destination URL, where the user will be redirected when clicking on the button.


Example:

.. code:: xml

        <rows>
            <row type="statistics">
                <button icon="fa-files-o" label="Pending delivery notes:" calculateby="function_name" onclick="#url"></option>
                <button icon="fa-files-o" label="Pending collection:" calculateby="function_name" onclick="#url"></option>
            </row>
        </rows>


actions
-------

It allows to define a group of buttons of types *action* and *modal* that will be displayed
at the bottom of the edit form, enter the delete and record buttons. This *row*
it is specific to the *Edit* views. The declaration of the buttons is done in a
similar to the one described in the `button`_ section except that the label *column*
is not necessary.

Example:

.. code:: xml

        <rows>
            <row type="actions">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Ejecuta el controlador con action=process1" />
            </row>
        </rows>



header and footer
-----------------

It allows adding additional information to visualize the user at the top and/or the bottom of the view.
The information is displayed in the form of panels ("Bootstrap cards") where we can
include messages and buttons for both action and modals. To declare a panel we will use
the tag *<group>* in which we will include tags *button* (if we need them).
We can customize each of the section of the panel as the header, the body
and/or the footer with attributes:

-  **name**: set the identifier for the panel.

-  **title**: indicates a text for the panel header.

-  **label**: indicates a text for the body of the panel.

-  **footer**: indicates a text for the foot of the panel.

Example: (Top of the view)

.. code:: xml

        <row type="header">
            <group name="footer1" footer="specials-actions" label="This is a sample of buttons on a 'bootstrap card'">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Run the controller with action=process1" />
            </group>
        </row>

Example: (Bottom of the view)

.. code:: xml

        <row type="footer">
            <group name="footer1" footer="specials-actions" label="This is a sample of buttons on a 'bootstrap card'">
                <button type="modal" label="Modal" color="primary" action="test" icon="fa-users" />
                <button type="action" label="Action" color="info" action="process1" icon="fa-book" hint="Run the controller with action=process1" />
            </group>
        </row>


MODALS
======

Modal forms are complementary views to the main view, which remain hidden until they
are necessary for the accomplishment of a specific task. These forms they are declared
in a very similar way to what is detailed in the section `COLUMNS`_.

To create a modal form, we must include a *group* tag with a unique *name* identifier.
Within this group we can define and customize the columns we need, but can not be created
new groups as you could in the COLUMNS section.

We can declare all the modal forms that we need, stating different *group* tags inside
of the group *modals*, and respecting the uniqueness of their identifiers. To display any of the forms
declared manners, we will have to define a modal type button in the main view, either in a column or
in a *row* of type ``actions`` or ``footer``, where the ``action`` attribute of the *button* is equal
to the identifier of the modal form.

The modal form will show the list of columns declared together with some buttons
of ``Accept`` and ``Cancel`` so that the user can confirm or cancel the process to
be performed.

Example:

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
