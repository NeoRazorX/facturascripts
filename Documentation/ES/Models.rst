.. title:: Models
.. highlight:: rst

#######
Modelos
#######

Los modelos son los encargados de gestionar el acceso a cada una de las tablas de
la base de datos donde se persisten los datos con los que se trabajan.
Así para cada tabla que usemos en la aplicación deberá existir su modelo, encargado
de la lectura, escritura y borrado de los datos contenidos en dicha tabla.
La estructura de la tabla se define en un archivo XML ubicado en la carpeta *Table*
y con el nombre que el modelo retorna en el método público *tableName*.

En la versión *Facturascripts 2018* se ha reestructurado el uso de los modelos,
heredando de la clase ModelClass y complementandose en un Trait (Rasgos)
denominado *ModelTrait* agrupando así las operaciones más comunes
y generales de los modelos, simplificando tanto el código como el tratamiento de
los mismos, delegando a estos sólo las características específicas de cada uno.

**************************
Modelos de tablas de datos
**************************

Como se ha comentado en la introducción, cada tabla de datos tiene un modelo encargado
de la gestión de su lectura, escritura y borrado. A la hora de declarar el modelo,
debemos crear una nueva clase que hereda de ModelClass y en la que incluiremos el uso del ModelTrait
junto con la lista de campos disponibles de la tabla declarados como públicos.

.. code:: php

    class Agente
    {
        use Base\ModelTrait;

        public $codagente;
        public $dnicif;
        public $nombre;
        public $apellidos;
    }


Métodos obligatorios
====================

Para un correcto funcionamiento del modelo, tenemos que vincular la estructura física
de la tabla (nombre de la tabla y clave primaria) con el modelo que la gestiona.
Para ello es obligatorio definir dos métodos que devolverán la información mediante
cadena de texto: **tableName** y **primaryColumn**.

.. code:: php

    public static function tableName()
    {
        return 'agentes';
    }

    public function primaryColumn()
    {
        return 'codagente';
    }


Lectura de datos
================

Los modelos, por el hecho de usar el ModelTrait, ponen a nuestra disposición varios
métodos para obtener información de la tabla a la que está vinculado:

all
---

Este método nos devuelve un array del modelo que lo ejecuta. Es decir,  cada elemento
del array devuelto es un objeto de la misma clase que el modelo que está ejecutando el
método all, donde cada uno de los elementos está "rellenado" con la información de cada
uno de los registros leídos de la tabla consultada.

Cuando se realiza la consulta, o ejecución del método all, podemos informar de distintos
parámetros que nos ayudan a filtrar los datos a recibir de la tabla de la base de datos:

-  **where** : Permite filtrar los datos a recoger. Se utiliza el sistema de filtrado
   mediante la clase DataBaseWhere incluida en la gestión de base de datos de Facturascripts 2018.

-  **order** : Permite indicar los datos de ordenación de los registros a recoger.
   Es un array de uno o más elementos *(key => valor)* donde la key es la cláusula SQL
   a aplicar y el valor indica si es ascendente o descendente *["ASC" | "DESC"]*

-  **offset** : Permite indicar un desplazamiento del primer registro a recoger.

-  **limit** : Permite indicar el número máximo de registros a recoger.

Ejemplo: *(Últimos 15 albaranes de cliente del agente AGENTE10)*
.. code:: php

    $albaran = new AlbaranCliente();
    $where = [new DataBase\DataBaseWhere('codagente', 'AGENTE10')];
    $order = ['fecha' => 'DESC'];
    $albaranes = $albaran->all($where, $order, 0, 15);


get
---

Este método nos devuelve un único y nuevo modelo de la misma clase que el modelo
que lo ejecuta, pero "rellenado" con la información del registro cuya columna

Ejemplo: *(Lectura del agente AGENTE10)*

.. code:: php

    $model = new Agente();
    $agente = $model->get('AGENTE10');


loadFromCode
------------

Al igual que el método anterior, este método sirve para leer un único registro.
La gran diferencia radica en que la información leída se introduce sobre el mismo
modelo que lo ejecuta, en vez de devolver un nuevo modelo. También destacar que la
lectura se puede hacer, al igual que el método get informando la columna primaria
(primary key) o mediante el sistema de filtrado de la clase DataBaseWhere y ordenado,
de manera similar al método all.

El método retorna un valor TRUE si consigue leer el registro solicitado. En caso de
no existir inicializa los valores del modelo y retorna FALSE.

Ejemplo: *(Último albaran de cliente del agente AGENTE10)*

.. code:: php

    $albaran = new AlbaranCliente();
    $where = [new DataBase\DataBaseWhere('codagente', 'AGENTE10')];
    $order = ['fecha' => 'DESC'];
    $ok = $albaran->loadFromCode('', $where, $orderby);


Ejemplo: *(Lectura del agente AGENTE10)*

.. code:: php

    $agente = new Agente();
    $ok = $agente->loadFromCode('AGENTE10');


Grabación de datos
==================

De igual manera para los procesos de grabación de datos existen métodos genéricos
al *ModelTrait* que facilitan el trabajo con los modelos. El proceso de persistencia
de los datos desde un modelo tiene un "camino predefinido" o conjunto de métodos
que se ejecutan secuencialmente de manera automática, pero que podemos sobrescribir
en nuestra clase del modelo para personalizar cada uno de los pasos.

save
----

Este método es el lanzador de todo el proceso de grabación. Es el encargado de ejecutar
el método test para validar los datos que se quieren grabar, así como de controlar si se
realizará un alta de un registro nuevo o la modificación de uno ya existente. Retorna un
valor booleano indicando si se ha realizado el proceso correctamente o por el contrario
no ha sido posible.

test
----

Método encargado de calcular campos dependientes de otros, y de validar los datos
finales que serán grabados. Todo nuevo modelo tiene que sobrescribir este método
para validar los campos propios del modelo. Además de validaciones generales
(longitud, tipo, existencia de valor, etc), una de las validaciones obligatorias es
comprobar que no se "inyecta" código HTML en cualquier campo de texto.

En caso de incumplimiento de alguna regla de validación debemos añadir el error en
el sistema de registro de alertas de la aplicación, alerta que será mostrada al
usuario en la ventana para que pueda subsanarla.

Ejemplo validación:

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

Este es un método "especial", encargado de verificar los datos enviados por el usuario
desde un formulario de un controlador Edit o EditList. Es importante entender que este
método se ejecuta antes de comenzar el proceso de grabación. El proceso recibe un array
con la información enviada por el usuario, y en caso de existir campos especiales no
informados desde el formulario, debemos sobrescribir el método y añadir al array de datos
los campos no incluidos. Tras este método, se comenzará el proceso normal de grabación
de datos.


Método url
==========

Los controladores utilizan el método url para conocer las distintas urls de navegación
entre ventanas. El ModelTrait dispone de un método url que establece de manera genérica
cual debería ser la url del modelo para cada uno de los casos "listar" y "editar" *(List y Edit)*,
pero existen ocasiones que el modelo necesite personalizar dichas urls. En estos casos
podemos sobrescribir este método para devolver para cada caso la url correcta.

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

Desde su versión 5.4.0, PHP implementa una metodología de reutilización de código
llamada Traits (Rasgos). En *Facturascripts 2018* hacemos uso de esta metodología
para unificar múltiples procesos de los modelos que de otra manera se repetirían
en cada modelo creado. Esto simplifica el código de los modelos y permite mantener
el código unificado en una sola clase: **ModelTrait**

Al crear un nuevo modelo, debemos incluir la instrucción de uso del ModelTrait:

.. code:: php

    class Agente
    {
        use Base\ModelTrait;

        [ ... ]
    }


Métodos comunes
===============

-  **primaryColumnValue** : Devuelve el valor del campo clave (Primary Key).

-  **primaryDescription** : Devuelve el identificador descriptivo para del registro de datos.

-  **loadFromData** : Carga los datos del modelo con el array de datos que se le pasa por parámetro.

-  **loadFromCode** : Carga los datos del modelo a partir del valor del campo clave que se informa, o de una condición where (SQL).

-  **get** : Retorna un nuevo modelo con los datos cargados a partir del valor del campo clave que se informa.

-  **clear** : Inicializa a nulo los datos del modelo.

-  **save** : Persiste en la base de datos los datos del modelo.

-  **delete** : Elimina de la base de datos el registro con clave primaria igual a la del modelo.

-  **count** : Retorna el número de registros que cumplen con la condición where (SQL) informada.

-  **all** : Retorna un array de modelos que cumplen con la condición where (SQL) informada.


Colisiones
==========

En ocasiones se necesita sobrescribir métodos definidos en ModelTrait, pero los
Traits no es una clase de la cual heredemos sino más bien es una clase que "usamos"
por lo que no es posible sobrescribir directamente como haríamos con una herencia.
En su lugar necesitamos "renombrar" o darle un alias al método que necesitamos sobrescribir,
incluir el método en nuestro modelo de manera "normal" pero incluyendo una llamada
al "alias" que hemos creado.

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


******************
Modelos especiales
******************

Existen varios modelos que no tienen una correspondencia con tablas físicas en la
base de datos, por lo que no pueden ser usados para grabación o borrado de datos.
La función de estos modelos es de servir de complemento sobre el resto de modelos
para realizar operaciones especiales de lectura de información, de manera global,
evitando así tener que crear métodos repetidos en distintos modelos.

CodeModel
=========

Este modelo se utiliza en los casos que nos interesa obtener una lista registros
de alguna tabla, pero sólo un campo código o identificativo y su descripción.
Al ser un modelo muy simple, no incluye todos los procesos de carga que normalmente
llevan los modelos limitándose sólo a la lectura y devolución de los datos solicitados.
Este modelo se usa por ejemplo en la carga del Widget de tipo "select" donde se visualiza
al usuario una lista de opciones para que pueda seleccionar una. El único método que
tiene es el all, pero a diferencia del resto de modelos en este caso es un método
estático por lo que no obliga a crearnos un objeto CodeModel para su ejecución.

Ejemplo de carga de lista *código + descripción*:
*El último parámetro de la llamada **($addEmpty)** permite indicar si necesitamos que
al principio del array que se devuelve con los datos, inserte un CodeModel en blanco.*

.. code:: php

    $rows = CodeModel::all('agentes', 'codagente', 'nombre', false);


TotalModel
==========

Este modelo está especialmente pensado para cálculos estadísticos *(SUM, AVG, COUNT, MAX, MIN, etc)*.
Aunque no es obligatorio, podemos ejecutar los cálculos con agrupación por un campo "código".
Así al ejecutar el modelo all nos devuelve un array de **TotalModel** (code, totals)
donde code contiene el identificador de agrupación y totals es un array con cada uno
de los cálculos que se han solicitado.

Ejemplo albaranes de venta sin facturar por cliente

.. code:: php

    $where = [new DataBase\DataBaseWhere('ptefactura', TRUE)];
    $totals = Model\TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)', 'count' => 'COUNT(1)'], 'codcliente');
