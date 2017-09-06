# Controlador ListController
Siguiendo con el modelo MVC, _ListController_ es un **controlador universal** para vistas que desean 
visualizar los datos de un modelo en formato "lista" o mediante un diseño de filas y columnas. 
Al usar este controlador simplificamos el tratamiento de filtros y ordenación, así como unificamos la 
imagen de la aplicación y plugins creando un entorno uniforme para el usuario lo que acelera el aprendizaje 
y adaptación a **Facturascripts**.

Para el uso de este controlador es necesario crear las vistas en formato XML, tal y como se describe en el
documento XMLViews, incluido en la documentación de **Facturascripts**.

## Cómo usar el controlador
Para utilizar _ListController_ debemos crearnos una nueva clase PHP que herede o extienda de ListController, 
debiendo implementar los siguientes métodos:

* **createViews**: Encargado de crear y añadir las vistas que deseamos visualizar dentro del ListController.

* **getPageData**: Establece los datos generales (título, icono, menú, etc) para la vista principal (la primera que añadimos en _createViews_).


### createViews
Dentro de este método, en nuestra nueva clase, debemos ir creando las distintas vistas que se visualizarán, 
y para cada vista debemos indicar los campos de búsqueda y los campos de ordenación. Opcionalmente podremos
añadir opciones de filtrado para que el usuario pueda complementar el filtrado de búsqueda existente. Este 
método tiene una visibilidad de _protected_ de manera que los plugins pueden ir extendiendo nuestra clase
y añadir nuevas vistas, o modificar las existentes.

La manera de añadir una vista es mediante el método _**addView**_ incluido en el propio controlador. Para la
correcta llamada al método debemos informar mediante cadenas de texto del modelo (Nombre completo), 
nombre de la vista XML y del título para la pestaña que visualiza el controlador. Si se omite este último 
parámetro, el controlador asignará un texto por defecto. El método nos devolverá el índice que se le ha 
asignado a la nueva vista, el cuál deberemos guardarnos para procesos posteriores.

Una vez añadida la vista, debemos configurarla indicando los campos de búsqueda y la ordenación mediante 
los métodos _**addSearchFields**_ y _**addOrderBy**_.

#### addSearchFields
Al añadir los campos de búsqueda debemos indicar el índice de la vista al que añadimos los campos y un 
array con los nombre de los campos.

Ejemplo de creación y adición de campos para búsqueda

```PHP
    $index = $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'Epigrafes');
    $this->addSearchFields($index, ['descripcion', 'codepigrafe', 'codejercicio']);
```

#### addOrderBy
Podemos añadir todos los campos de ordenación, no confundir con los campos de búsqueda, realizando distintas
llamadas al método _addOrderBy_ e indicando el índice de la vista a la que añadimos la ordenación, la expresión
de ordenación (cualquier expresión aceptada por la clausula ORDER BY de SQL), texto a visualizar al usuario y el
indicativo de orden por defecto.

Consideraciones:
* si no se indica texto a visualizar, se empleará el valor informado en la expresión de ordenación (aplicando el sistema de traducciones)
* si no se indica valor de ordenación por defecto, se entiende que no hay una ordenación por defecto y se aplicará el primer orden añadido
* al añadir una ordenación **siempre** se añaden dos opciones de ordenación, una ascendente y otra descendente
* para establecer una ordenación por defecto, al añadir la ordenación podemos indicar como valores 1 para la ascendente y 2 para la descendente

Ejemplo de adición de ordenación (siguiendo el ejemplo anterior) con ordenación por código descendente

```PHP
    $this->addOrderBy($index, 'descripcion||codejercicio', 'description');
    $this->addOrderBy($index, 'codepigrafe||codejercicio', 'code', 2);
```

#### Adición de filtros
El controlador _ListController_ integra un sistema de filtrado de datos que permite personalizar de manera sencilla
las opciones de filtrado que se presentan al usuario. Cada tipo de filtro requiere de una parametrización propia para 
su funcionamiento como el indice de la vista a la que lo añadimos, y entre los tipos de filtros disponibles están:

* **addFilterSelect** : Filtro tipo selección de una lista de valores.
     * key : Es el nombre interno del filtro y debe coincidir con el nombre del campo del modelo que se está visualizando y por el que se quiere filtrar.
     * table : Nombre de la tabla de donde se leerán las opciones para la lista desplegable.
     * where : Cláusula WHERE a pasar en la selección de datos de la tabla origen de la lista.
     * field : Nombre del campo que se visualiza en la lista desplegable. Si no se informa se muestra el campo key.

* **addFilterCheckbox** : Filtro tipo checkbox o de selección booleana.
     * key : Es el nombre interno del filtro.
     * label : Es la descripción a visualizar y que indica al usuario la función del filtro.
     * field : Nombre del campo del modelo donde se aplica el filtro. Si no se indica se usa el valor de key.
     * inverse : Permite invertir los valores booleanos.

* **addFilterDatePicker** : Filtro de tipo fecha.
     * key : Es el nombre interno del filtro.
     * label : Es la descripción a visualizar y que indica al usuario la función del filtro.
     * field : Nombre del campo del modelo donde se aplica el filtro. Si no se indica se usa el valor de key.

Ejemplos de filtros

```PHP
    $this->addFilterSelect($index, 'codepigrafe', 'co_epigrafes', '', 'descripcion');
    $this->addFilterCheckbox($index, 'debaja', 'De baja');
    $this->addFilterDatePicker($index, 'fecha', 'Fec. Alta');
```

### getPageData
Este método es el encargado de devolver un array con los datos para la instalación y configuración del controlador
dentro del entorno de **Facturascripts**. Como norma hay que llamar al _parent_ del controlador para inicializar los
valores por defecto y asegurar un correcto funcionamiento de nuestro controlador en el entorno de Facturascripts.

Los valores necesarios a configurar son:
* title : Título de la vista
* icon : Icono de la fuente de texto _fontawesome_
* menu : Nombre del menú donde se introducirá el controlador
* submenu : (opcional) Segundo nivel del menú donde se introduciría el controlador
* orden : Podemos alterar el orden natural del sistema de menú para colocar nuestro controlador más arriba o abajo