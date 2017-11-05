<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DefaultItems;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;

/**
 * La clase de la que heredan todos los modelos, conecta a la base de datos,
 * comprueba la estructura de la tabla y de ser necesario la crea o adapta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ModelTrait
{

    use Utils;

    /**
     * Lista de campos de la tabla.
     *
     * @var array
     */
    protected static $fields;

    /**
     * Nombre del modelo. De la clase que inicia este trait.
     *
     * @var string
     */
    private static $modelName;

    /**
     * Lista de tablas ya comprobadas.
     *
     * @var array|null
     */
    private static $checkedTables;

    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Permite conectar e interactuar con el sistema de caché.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Clase que se utiliza para definir algunos valores por defecto:
     * codejercicio, codserie, coddivisa, etc...
     *
     * @var DefaultItems
     */
    protected $defaultItems;

    /**
     * Traductor multi-idioma.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Gestiona el log de todos los controladores, modelos y base de datos.
     *
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Constructor por defecto.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init();
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Inicializa lo necesario.
     */
    private function init()
    {
        $this->cache = new Cache();
        $this->dataBase = new DataBase();
        $this->defaultItems = new DefaultItems();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();

        if (self::$checkedTables === null) {
            self::$checkedTables = $this->cache->get('fs_checked_tables');
            if (self::$checkedTables === null || self::$checkedTables === false) {
                self::$checkedTables = [];
            }

            self::$modelName = get_class($this);
        }

        if ($this->tableName() !== '' && !in_array($this->tableName(), self::$checkedTables, false) && $this->checkTable($this->tableName())) {
            $this->miniLog->debug($this->i18n->trans('table-checked', [$this->tableName()]));
            self::$checkedTables[] = $this->tableName();
            $this->cache->set('fs_checked_tables', self::$checkedTables);
        }

        if (self::$fields === null) {
            self::$fields = ($this->dataBase->tableExists($this->tableName()) ? $this->dataBase->getColumns($this->tableName()) : []);
        }
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        if (method_exists(__CLASS__, 'cleanCache')) {
            $this->cleanCache();
        }

        return '';
    }

    /**
     * Devuelve el nombre de la clase del modelo
     *
     * @return string
     */
    public function modelClassName()
    {
        $result = explode('\\', $this->modelName());
        return end($result);
    }

    /**
     * Devuelve el nombre del modelo.
     *
     * @return string
     */
    public function modelName()
    {
        return self::$modelName;
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    abstract public function primaryColumn();

    /**
     * Devuelve el valor actual de la columna principal del modelo
     *
     * @return mixed
     */
    public function primaryColumnValue()
    {
        return $this->{$this->primaryColumn()};
    }

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    abstract public function tableName();

    /**
     * Comprueba un array de datos para que tenga la estructura correcta del modelo
     *
     * @param array $data
     */
    public function checkArrayData(&$data)
    {
        foreach (self::$fields as $field => $values) {
            if ($values['type'] === 'boolean' || $values['type'] === 'tinyint(1)') {
                if (!isset($data[$field])) {
                    $data[$field] = FALSE;
                }
            }
        }
    }

    /**
     * Devuelve el valor integer controlando casos especiales para las PK y FK
     *
     * @param array $field
     * @param string $value
     * @return integer|NULL
     */
    private function getIntergerValueForField($field, $value)
    {
        if (!empty($value)) {
            return (int) $value;
        }

        if ($field['name'] === $this->primaryColumn()) {
            return NULL;
        }

        return ($field['is_nullable'] === 'NO') ? 0 : NULL;
    }

    /**
     * Asigna a las propiedades del modelo los valores del array $data
     *
     * @param array $data
     * @param string[] $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }

            if (isset(self::$fields[$key])) {
                $field = self::$fields[$key];

                // Comprobamos si es un varchar (con longitud establecida) u otro tipo de dato
                $type = (strpos($field['type'], '(') === false) ? $field['type'] : substr($field['type'], 0, strpos($field['type'], '('));

                switch ($type) {
                    case 'tinyint':
                    case 'boolean':
                        $this->{$key} = $this->str2bool($value);
                        break;

                    case 'integer':
                    case 'int':
                        $this->{$key} = $this->getIntergerValueForField($field, $value);
                        break;

                    case 'double':
                    case 'double precision':
                    case 'float':
                        $this->{$key} = empty($value) ? 0.00 : (float) $value;
                        break;

                    case 'date':
                        $this->{$key} = empty($value) ? null : date('d-m-Y', strtotime($value));
                        break;

                    default:
                        if (empty($value)) {
                            $value = ($field['is_nullable'] === 'NO') ? '' : null;
                        }
                        $this->{$key} = $value;
                }
            }
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        foreach (self::$fields as $field) {
            $this->{$field['name']} = null;
        }
    }

    /**
     * Rellena la clase con los valores del registro
     * cuya columna primaria corresponda al valor $cod, o según la condición
     * where indicada, si no se informa valor en $cod.
     * Inicializa los valores de la clase si no existe ningún registro que
     * cumpla las condiciones anteriores.
     * Devuelve True si existe el registro y False en caso contrario.
     *
     * @param string $cod
     * @param DataBase\DataBaseWhere[] $where
     * @param array $orderby
     *
     * @return bool
     */
    public function loadFromCode($cod, $where = null, $orderby = [])
    {
        $data = $this->getRecord($cod, $where, $orderby);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
    }

    /**
     * Devuelve el modelo cuya columna primaria corresponda al valor $cod
     *
     * @param string $cod
     *
     * @return mixed|bool
     */
    public function get($cod)
    {
        $data = $this->getRecord($cod);
        if (!empty($data)) {
            $class = $this->modelName();

            return new $class($data[0]);
        }

        return false;
    }

    /**
     * Devuelve true si los datos del modelo se encuentran almacenados en la base de datos.
     *
     * @return bool
     */
    public function exists()
    {
        if ($this->primaryColumnValue() === null) {
            return false;
        }

        $sql = 'SELECT 1 FROM ' . $this->tableName()
            . ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($this->primaryColumnValue()) . ';';

        return (bool) $this->dataBase->select($sql);
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     * Se ejecuta dentro del método save.
     *
     * @return bool
     */
    public function test()
    {
        return true;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }

    /**
     * Elimina los datos del modelo de la base de datos.
     *
     * @return bool
     */
    public function delete()
    {
        if (method_exists(__CLASS__, 'cleanCache')) {
            $this->cleanCache();
        }
        $sql = 'DELETE FROM ' . $this->tableName()
            . ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($this->primaryColumnValue()) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Devuelve el número de registros en el modelo que cumplen la condición
     *
     * @param array $where filtros a aplicar a los registros del modelo. (Array de DataBaseWhere)
     *
     * @return int
     */
    public function count(array $where = [])
    {
        $sql = 'SELECT COUNT(1) AS total FROM ' . $this->tableName() . DataBase\DataBaseWhere::getSQLWhere($where);
        $data = $this->dataBase->select($sql);
        if (empty($data)) {
            return 0;
        }

        return $data[0]['total'];
    }

    /**
     * Devuelve todos los modelos que se correspondan con los filtros seleccionados.
     *
     * @param array   $where  filtros a aplicar a los registros del modelo. (Array de DataBaseWhere)
     * @param array   $order  campos a utilizar en la ordenación. Por ejemplo ['codigo' => 'ASC']
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function all(array $where = [], array $order = [], $offset = 0, $limit = 50)
    {
        $modelList = [];
        $sqlWhere = DataBase\DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT * FROM ' . $this->tableName() . $sqlWhere . $this->getOrderBy($order);
        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            $class = $this->modelName();
            foreach ($data as $d) {
                $modelList[] = new $class($d);
            }
        }

        return $modelList;
    }

    /**
     * Transforma una variable en una cadena de texto válida para ser
     * utilizada en una consulta SQL.
     *
     * @param mixed $val
     *
     * @return string
     */
    public function var2str($val)
    {
        if ($val === null) {
            return 'NULL';
        }

        if (is_bool($val)) {
            if ($val) {
                return 'TRUE';
            }

            return 'FALSE';
        }

        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i", $val)) {
            return "'" . date($this->dataBase->dateStyle(), strtotime($val)) . "'"; /// es una fecha
        }

        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i", $val)) {
            return "'" . date($this->dataBase->dateStyle() . ' H:i:s', strtotime($val)) . "'"; /// es una fecha+hora
        }

        return "'" . $this->dataBase->escapeString($val) . "'";
    }

    /**
     * Devuelve el siguiente código para el campo informado o de la primary key del modelo
     *
     * @param string $field
     *
     * @return int
     */
    public function newCode($field = '')
    {
        if (empty($field)) {
            $field = $this->dataBase->sql2Int($this->primaryColumn());
        }
        $sql = 'SELECT MAX(' . $field . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->dataBase->select($sql);
        if (empty($cod)) {
            return 1;
        }

        return 1 + (int) $cod[0]['cod'];
    }

    /**
     * Escapa las comillas de una cadena de texto.
     *
     * @param string $str cadena de texto a escapar
     *
     * @return string cadena de texto resultante
     */
    protected function escapeString($str)
    {
        return $this->dataBase->escapeString($str);
    }

    /**
     * Comprueba y actualiza la estructura de la tabla si es necesario
     *
     * @param string $tableName
     *
     * @return bool
     */
    protected function checkTable($tableName)
    {
        $done = true;
        $sql = '';
        $xmlCols = [];
        $xmlCons = [];

        if ($this->getXmlTable($tableName, $xmlCols, $xmlCons)) {
            if ($this->dataBase->tableExists($tableName)) {
                if (!$this->dataBase->checkTableAux($tableName)) {
                    $this->miniLog->critical($this->i18n->trans('error-to-innodb'));
                }

                /**
                 * Si hay que hacer cambios en las restricciones, eliminamos todas las restricciones,
                 * luego añadiremos las correctas. Lo hacemos así porque evita problemas en MySQL.
                 */
                $dbCons = $this->dataBase->getConstraints($tableName);
                $sql2 = $this->dataBase->compareConstraints($tableName, $xmlCons, $dbCons, true);
                if ($sql2 !== '') {
                    if (!$this->dataBase->exec($sql2)) {
                        $this->miniLog->critical($this->i18n->trans('check-table', [$tableName]));
                    }

                    /// leemos de nuevo las restricciones
                    $dbCons = $this->dataBase->getConstraints($tableName);
                }

                /// comparamos las columnas
                $dbCols = $this->dataBase->getColumns($tableName);
                $sql .= $this->dataBase->compareColumns($tableName, $xmlCols, $dbCols);

                /// comparamos las restricciones
                $sql .= $this->dataBase->compareConstraints($tableName, $xmlCons, $dbCons);
            } else {
                /// generamos el sql para crear la tabla
                $sql .= $this->dataBase->generateTable($tableName, $xmlCols, $xmlCons);
                $sql .= $this->install();
            }
            if ($sql !== '' && !$this->dataBase->exec($sql)) {
                $this->miniLog->critical($this->i18n->trans('check-table', [$tableName]));
                $this->cache->clear();
                $done = false;
            }
        } else {
            $this->miniLog->critical($this->i18n->trans('error-on-xml-file'));
            $done = false;
        }

        return $done;
    }

    /**
     * Obtiene las columnas y restricciones del fichero xml para una tabla
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $constraints
     *
     * @return bool
     */
    protected function getXmlTable($tableName, &$columns, &$constraints)
    {
        $return = false;

        $filename = FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (file_exists($filename)) {
            $xml = simplexml_load_string(file_get_contents($filename, FILE_USE_INCLUDE_PATH));
            if ($xml) {
                if ($xml->column) {
                    $key = 0;
                    foreach ($xml->column as $col) {
                        $columns[$key]['name'] = (string) $col->name;
                        $columns[$key]['type'] = (string) $col->type;

                        $columns[$key]['null'] = 'YES';
                        if ($col->null && strtolower($col->nulo) === 'no') {
                            $columns[$key]['null'] = 'NO';
                        }

                        if ($col->default === '') {
                            $columns[$key]['default'] = null;
                        } else {
                            $columns[$key]['default'] = (string) $col->default;
                        }

                        ++$key;
                    }

                    /// debe de haber columnas, sino es un fallo
                    $return = true;
                }

                if ($xml->constraint) {
                    $key = 0;
                    foreach ($xml->constraint as $col) {
                        $constraints[$key]['name'] = (string) $col->name;
                        $constraints[$key]['constraint'] = (string) $col->type;
                        ++$key;
                    }
                }
            } else {
                $this->miniLog->critical($this->i18n->trans('error-reading-file', [$filename]));
            }
        } else {
            $this->miniLog->critical($this->i18n->trans('file-not-found', [$filename]));
        }

        return $return;
    }

    /**
     * Lee el registro cuya columna primaria corresponda al valor $cod
     * o el primero que cumple la condición indicada
     *
     * @param string $cod
     * @param array|null $where
     * @param array $orderby
     *
     * @return array
     */
    private function getRecord($cod, $where = null, $orderby = [])
    {
        $sqlWhere = empty($where) ? ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($cod) : DataBase\DataBaseWhere::getSQLWhere($where);

        $sql = 'SELECT * FROM ' . $this->tableName() . $sqlWhere . $this->getOrderBy($orderby);
        return $this->dataBase->selectLimit($sql, 1);
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $sql = 'UPDATE ' . $this->tableName();
        $coma = ' SET';

        foreach (self::$fields as $field) {
            if ($field['name'] !== $this->primaryColumn()) {
                $sql .= $coma . ' ' . $field['name'] . ' = ' . $this->var2str($this->{$field['name']});
                if ($coma === ' SET') {
                    $coma = ', ';
                }
            }
        }

        $sql .= ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($this->primaryColumnValue()) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveInsert()
    {
        $insertFields = [];
        $insertValues = [];
        foreach (self::$fields as $field) {
            if (isset($this->{$field['name']})) {
                $insertFields[] = $field['name'];
                $insertValues[] = $this->var2str($this->{$field['name']});
            }
        }

        $sql = 'INSERT INTO ' . $this->tableName()
            . ' (' . implode(',', $insertFields) . ') VALUES (' . implode(',', $insertValues) . ');';
        if ($this->dataBase->exec($sql)) {
            if ($this->primaryColumnValue() === null) {
                $this->{$this->primaryColumn()} = $this->dataBase->lastval();
            }

            return true;
        }

        return false;
    }

    /**
     * Convierte un array de filtros order by en string
     *
     * @param array $order
     *
     * @return string
     */
    private function getOrderBy(array $order)
    {
        $result = '';
        $coma = ' ORDER BY ';
        foreach ($order as $key => $value) {
            $result .= $coma . $key . ' ' . $value;
            if ($coma === ' ORDER BY ') {
                $coma = ', ';
            }
        }

        return $result;
    }

    /**
     * Devuelve la url donde ver/modificar los datos
     *
     * @param mixed $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        $result = 'index.php?page=';
        switch ($type) {
            case 'list':
                $result .= 'List' . $model;
                break;

            case 'edit':
                $result .= 'Edit' . $model . '&code=' . $value;
                break;

            case 'new':
                $result .= 'Edit' . $model;
                break;

            default:
                $result .= empty($value) ? 'List' . $model : 'Edit' . $model . '&code=' . $value;
                break;
        }

        return $result;
    }
}
