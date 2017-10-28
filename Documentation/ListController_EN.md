# ListController

For the use of this controller it's necessary create the views in XML format, as described in the
document [XMLViews] (https://github.com/ArtexTrading/facturascripts/blob/master/Documentation/XMLViews_EN.md), 
included in the documentation for **Facturascripts**.

## Using the Controller
To use _ListController_ we must create a new PHP class that inherits or extends from ListController,
having to implement the following methods:

* **createViews**: For create and add the views that we want to visualize inside the ListController.

* **getPageData**: Sets the general data (title, icon, menu, etc) for the main view (the first one we added in _createViews_).


### createViews
Within this method, in our new class, we must create the different views to be visualized,
and for each view we must indicate the search fields and sorting fields. Optionally we will be able to
add filtering options so the user can complement existing search filtering. East
method has a visibility of _protected_ so that plugins can extend our class
and add new views, or modify existing ones.

The way to add a view is by the _**addView**_ method included in the controller itself. For the
correct call to the method we must inform through text strings: the model (Full name),
name of the XML view and title for the tab that the controller displays. If the latter is omitted
parameter, the controller assigns a default text.

Once added the view, we must configure it indicating the fields of search and the ordering through
the methods _**addSearchFields**_ and _**addOrderBy**_.


#### addSearchFields
When adding the fields of search we must indicate the index name for the view to which we add the fields and a
array with the field names.

Example of creating and adding fields for search.

```PHP
    $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'Epigrafes');
    $this->addSearchFields('ListEpigrafe', ['descripcion', 'codepigrafe', 'codejercicio']);
```


#### addOrderBy
We can add all the sorting fields, not to be confused with the search fields, making different
calls to the _addOrderBy_ method and indicating the name of the view to which we added the order, the expression
(any expression accepted by the SQL ORDER BY clause), text to be displayed to the user and the
default indicative of order.

Considerations:
* if no text is displayed, the value entered in the sort expression (using the translation system) will be used,
* if no default sort value is indicated, it is understood that there is no default ordering and the first added order will be applied
* When adding a **always** sort, two sorting options are added, one ascending and one descending
* to set a default order, when adding the ordering we can indicate as values ​​1 for the ascender and 2 for the descending

Example of sorting addition (following the example above) with sorting by descending code

```PHP
    $this->addOrderBy('ListEpigrafe', 'descripcion', 'description');
    $this->addOrderBy('ListEpigrafe', 'codepigrafe||codejercicio', 'code', 2);
    $this->addOrderBy('ListEpigrafe', 'codejercicio');
```


#### Adding Filters
The _ListController_ controller integrates a data filtering system that allows easy customization
the filtering options that are presented to the user. Each type of filter requires its own parameterization to
its operation as the name of the view to which we add it, and among the types of filters available are:

* **addFilterSelect**: Filter type selection from a list of values.
     * key: This is the internal name of the filter and must match the name of the field of the model being displayed and the one to be filtered.
     * table: Name of the table where the options for the drop - down list will be read.
     * where: WHERE clause to pass in the data selection of the source table in the list.
     * field: The name of the field that is displayed in the drop-down list. If not, the key field is displayed.

* **addFilterCheckbox**: Checkbox or Boolean selection filter.
     * key: This is the internal name of the filter.
     * label: This is the description to be displayed and indicates to the user the function of the filter.
     * field: Name of the field of the model where the filter is applied. If not indicated the key value is used.
     * inverse: Allows you to invert the Boolean values.

* **addFilterDatePicker**: Date type filter.
      * key: This is the internal name of the filter.
      * label: This is the description to be displayed and indicates to the user the function of the filter.
      * field: Name of the field of the model where the filter is applied. If not indicated the key value is used.

Examples of filters

```PHP
    $this->addFilterSelect('ListEpigrafe', 'codepigrafe', 'co_epigrafes', '', 'descripcion');
    $this->addFilterCheckbox('ListCliente', 'debaja', 'De baja');
    $this->addFilterDatePicker(ListArticulo, 'fecha', 'Fec. Alta');
```


### getPageData
This method is responsible for returning an array with the data for the installation and configuration of the controller
within the environment of **Facturascripts**. As a rule, you must call the _parent_ of the controller to initialize the
default values and ensure a proper operation of our controller in the Facturascripts environment.

The values that can be configured are:
* title: View title
* icon: Text font icon _fontawesome_
* menu: Name of the menu where the controller will be inserted
* submenu: (optional) Second level of the menu where the controller would be entered
* order: We can alter the natural order of the menu system to place our controller higher or lower

```PHP
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Agentes';
        $pagedata['icon'] = 'fa-user-circle-o';
        $pagedata['menu'] = 'admin';
        return $pagedata;
    }
```