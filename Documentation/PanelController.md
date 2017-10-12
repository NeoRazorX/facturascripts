# Controlador PanelController
Este controlador, al igual que el controlador _ListController_ es un **controlador universal** para multiples vistas
aunque en este caso se permite el uso de distintos tipos de vistas: _ListView_, _EditView_ y _EditListView_

El controlador divide la pantalla en dos zonas, una a la izquierda (zona de navegación) y otra la derecha
donde se visualizan las vistas con los datos. 

Para el uso de este controlador es necesario crear las vistas en formato XML, tal y como se describe en el
documento [XMLViews](https://github.com/ArtexTrading/facturascripts/blob/master/Documentation/XMLViews.md), 
incluido en la documentación de **Facturascripts**.

## Cómo usar el controlador
Para utilizar _PanelController_ debemos crearnos una nueva clase PHP que herede o extienda de PanelController, 
debiendo implementar los siguientes métodos:

* **createViews**: Encargado de crear y añadir las vistas que deseamos visualizar dentro del PanelController.

* **loadData**: Encargado de cargar los datos para cada una de las vistas.

* **getPageData**: Establece los datos generales (título, icono, menú, etc) para la vista principal (la primera que añadimos en _createViews_).


### createViews
Dentro de este método, en nuestra nueva clase, debemos ir creando las distintas vistas que se visualizarán, 
debiendo usar distintos métodos según el tipo de vista que estamos añadiendo. Debemos indicar mediante cadenas de texto, 
al añadir una vista, el modelo (Nombre completo) y el nombre de la vista XML, y opcionalmente el título y el icono 
para el grupo de navegación.

* **addEditView**: Añade una vista para editar datos de un único registro de un modelo. 
* **addEditListView**: Añade una vista para editar multiples registros de un modelo.
* **addListView**: Añade una vista para visualizar en modo lista multiples registros de un modelo.

```PHP
    $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'Cliente');
    $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', 'Direcciones', 'fa-road');
    $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'Mismo Grupo');
```

Este método tiene una visibilidad de _protected_ de manera que los plugins pueden ir extendiendo nuestra clase
y añadir nuevas vistas, o modificar las existentes.


### loadData
Este método es llamado por cada una de las vistas para que podamos cargar los datos
específicos de la misma. En la llamada se nos informa del identificador de la vista 
y el propio objeto view, pudiendo acceder a todas las propiedades del mismo.
La carga de datos puede variar según el tipo de vista, por lo que es responsabilidad
del programador realizar la carga de datos correctamente. Aunque esto pueda suponer
una dificultad añadida, también nos permite un mayor control sobre los datos que a 
leer del modelo.

Ejemplo de carga de datos para distintos tipos de vistas.

```PHP
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
```


### getPageData
Este método es el encargado de devolver un array con los datos para la instalación y configuración del controlador
dentro del entorno de **Facturascripts**. Como norma hay que llamar al _parent_ del controlador para inicializar los
valores por defecto y asegurar un correcto funcionamiento de nuestro controlador en el entorno de Facturascripts.

Los valores que se pueden configurar son:
* title : Título de la vista
* icon : Icono de la fuente de texto _fontawesome_
* menu : Nombre del menú donde se introducirá el controlador
* submenu : (opcional) Segundo nivel del menú donde se introduciría el controlador
* orden : Podemos alterar el orden natural del sistema de menú para colocar nuestro controlador más arriba o abajo

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