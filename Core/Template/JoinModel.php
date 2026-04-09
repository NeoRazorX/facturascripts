<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Where;

/**
 * Clase base de la que heredan todas las vistas de modelo.
 * Permite la visualización de datos de varias tablas de la base de datos.
 * Este tipo de modelo es solo para lectura de datos, no permite la modificación
 * o eliminación de datos directamente.
 *
 * Se debe indicar un modelo principal ("master"), que será el responsable de ejecutar
 * las acciones de modificación de datos. Esto significa que al insertar, modificar o eliminar,
 * solo se realiza la operación sobre el modelo master indicado.
 *
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 */
abstract class JoinModel
{
    /** @var DataBase */
    protected static $dataBase;

    /** @var ModelClass Modelo principal para las operaciones de datos. */
    protected $masterModel;

    /** @var array Atributos del modelo. */
    private $attributes = [];

    /** Devuelve la lista de tablas necesarias para la ejecución de la vista. */
    abstract protected function getTables(): array;

    /** Devuelve la lista de campos o columnas para la cláusula SELECT. */
    abstract protected function getFields(): array;

    /** Devuelve las tablas relacionadas para la cláusula FROM. */
    abstract protected function getSQLFrom(): string;

    /** Constructor e inicializador de la clase. */
    public function __construct(array $data = [])
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /** Devuelve el valor del atributo indicado. */
    public function __get($name)
    {
        if (!isset($this->attributes[$name])) {
            $this->attributes[$name] = null;
        }

        return $this->attributes[$name];
    }

    /** Comprueba si existe el atributo indicado. */
    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /** Asigna el valor al atributo indicado. */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Devuelve todos los registros que cumplen las condiciones.
     *
     * @param Where[] $where
     * @param array $order
     * @param int $offset
     * @param int $limit
     * @return static[]
     */
    public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array
    {
        $result = [];

        $instance = new static();
        if (!$instance->checkTables()) {
            return $result;
        }

        $sql = 'SELECT ' . $instance->fieldsList()
            . ' FROM ' . $instance->getSQLFrom()
            . Where::multiSqlLegacy($where) . $instance->getGroupBy()
            . $instance->getOrderBy($order);

        foreach (self::db()->selectLimit($sql, $limit, $offset) as $row) {
            $result[] = new static($row);
        }

        return $result;
    }

    /** Restablece los valores de todos los atributos del modelo. */
    public function clear(): void
    {
        foreach (array_keys($this->getFields()) as $field) {
            $this->attributes[$field] = null;
        }
    }

    /**
     * Devuelve el número de registros que cumplen las condiciones.
     *
     * @param Where[] $where
     * @return int
     */
    public static function count(array $where = []): int
    {
        $instance = new static();
        if (!$instance->checkTables()) {
            return 0;
        }

        $groupFields = $instance->getGroupFields();
        if (!empty($groupFields)) {
            $groupFields .= ', ';
        }

        // buscamos en caché
        $cacheKey = 'join-model-' . md5($instance->getSQLFrom()) . '-count';
        if (empty($where)) {
            $count = Cache::get($cacheKey);
            if (is_numeric($count)) {
                return $count;
            }
        }

        $sql = 'SELECT ' . $groupFields . 'COUNT(*) count_total'
            . ' FROM ' . $instance->getSQLFrom()
            . Where::multiSqlLegacy($where)
            . $instance->getGroupBy();

        $data = self::db()->select($sql);
        $count = count($data);
        $final = $count == 1 ? (int)$data[0]['count_total'] : $count;

        // guardamos en caché
        if (empty($where)) {
            Cache::set($cacheKey, $final);
        }

        return $final;
    }

    /** Elimina los datos del modelo master de la base de datos. */
    public function delete(): bool
    {
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            $this->masterModel->{$primaryColumn} = $this->id();
            return $this->masterModel->delete();
        }

        return false;
    }

    /** Devuelve true si los datos del modelo existen en la base de datos. */
    public function exists(): bool
    {
        return isset($this->masterModel) ? $this->masterModel->exists() : static::count() > 0;
    }

    public function getModelFields(): array
    {
        $fields = [];
        foreach ($this->getFields() as $key => $field) {
            $fields[$key] = [
                'name' => $field,
                'type' => ''
            ];

            // si contiene paréntesis, saltamos
            if (false !== strpos($field, '(')) {
                continue;
            }

            // extraemos el nombre de la tabla
            $arrayField = explode('.', $field);
            if (false === is_array($arrayField) && false === isset($arrayField[0])) {
                continue;
            }

            // comprobamos si existe la tabla
            if (false === in_array($arrayField[0], $this->getTables())) {
                continue;
            }

            // consultamos la información de la tabla para obtener el tipo
            $columns = self::db()->getColumns($arrayField[0]);
            if (isset($columns[$arrayField[1]])) {
                $fields[$key]['type'] = $columns[$arrayField[1]]['type'];
            }
        }

        return $fields;
    }

    /**
     * Carga un registro del modelo utilizando el código de la clave primaria del master model.
     *
     * @param mixed $code
     *
     * @return bool
     */
    public function load($code): bool
    {
        if (null === $code || !isset($this->masterModel)) {
            $this->clear();
            return false;
        }

        $primaryColumn = $this->masterModel->primaryColumn();
        $where = [];
        foreach ($this->getFields() as $field => $sqlField) {
            if ($field == $primaryColumn) {
                $where = [Where::eq($sqlField, $code)];
                break;
            }
        }

        if (empty($where)) {
            $this->clear();
            return false;
        }

        return $this->loadWhere($where);
    }

    /**
     * @deprecated Usar load() cuando solo se necesita cargar por código, o loadWhere() cuando
     *             se requieren condiciones WHERE u ordenamiento adicionales.
     */
    #[Deprecated(
        reason: 'Use load() or loadWhere() instead',
    )]
    public function loadFromCode($cod, array $where = [], array $orderby = []): bool
    {
        if (!empty($where)) {
            return $this->loadWhere($where, $orderby);
        }

        return $this->load($cod);
    }

    public function loadWhereEq(string $field, $value): bool
    {
        return $this->loadWhere([Where::eq($field, $value)]);
    }

    /**
     * Carga el primer registro que coincida con las condiciones especificadas.
     *
     * @param Where[] $where
     * @param array $order
     *
     * @return bool
     */
    public function loadWhere(array $where, array $order = []): bool
    {
        $sql = 'SELECT ' . $this->fieldsList()
            . ' FROM ' . $this->getSQLFrom()
            . Where::multiSqlLegacy($where)
            . $this->getGroupBy()
            . $this->getOrderBy($order);

        $data = self::db()->selectLimit($sql, 1);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
    }

    /** Devuelve el valor de la clave primaria del modelo master. */
    public function id()
    {
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            return $this->{$primaryColumn};
        }

        return null;
    }

    /**
     * @deprecated Use id() instead
     */
    #[Deprecated(
        reason: 'Use id() instead',
        replacement: '%class%->id()',
    )]
    public function primaryColumnValue()
    {
        return $this->id();
    }

    public function totalSum(string $field, array $where = []): float
    {
        // buscamos en caché
        $cacheKey = 'join-model-' . md5($this->getSQLFrom()) . '-' . $field . '-total-sum';
        if (empty($where)) {
            $count = Cache::get($cacheKey);
            if (is_numeric($count)) {
                return $count;
            }
        }

        // obtenemos el nombre completo del campo
        $fields = $this->getFields();
        $field = $fields[$field] ?? $field;

        $sql = false !== strpos($field, '(') ?
            'SELECT ' . $field . ' AS total_sum' . ' FROM ' . $this->getSQLFrom() . Where::multiSqlLegacy($where) :
            'SELECT SUM(' . $field . ') AS total_sum' . ' FROM ' . $this->getSQLFrom() . Where::multiSqlLegacy($where);

        $data = self::db()->select($sql);
        $sum = count($data) == 1 ? (float)$data[0]['total_sum'] : 0.0;

        // guardamos en caché
        if (empty($where)) {
            Cache::set($cacheKey, $sum);
        }

        return $sum;
    }

    /** Devuelve la URL donde ver o modificar los datos. */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            $this->masterModel->{$primaryColumn} = $this->id();
            return $this->masterModel->url($type, $list);
        }

        return '';
    }

    /** Comprueba que existen todas las tablas necesarias. */
    private function checkTables(): bool
    {
        foreach ($this->getTables() as $tableName) {
            if (!self::db()->tableExists($tableName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Devuelve la instancia de la base de datos actual.
     *
     * @return DataBase
     */
    protected static function db(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    /** Convierte la lista de campos en una cadena para la cláusula SELECT. */
    private function fieldsList(): string
    {
        $result = '';
        $comma = '';
        foreach ($this->getFields() as $key => $value) {
            $result = $result . $comma . $value . ' ' . $key;
            $comma = ',';
        }
        return $result;
    }

    /** Devuelve la cláusula GROUP BY. */
    private function getGroupBy(): string
    {
        $fields = $this->getGroupFields();
        return empty($fields) ? '' : ' GROUP BY ' . $fields;
    }

    /** Devuelve los campos para la cláusula GROUP BY. */
    protected function getGroupFields(): string
    {
        return '';
    }

    /** Convierte un array de ordenamiento en una cláusula ORDER BY. */
    private function getOrderBy(array $order): string
    {
        $result = '';
        $coma = ' ORDER BY ';
        foreach ($order as $key => $value) {
            $result .= $coma . $key . ' ' . $value;
            $coma = ', ';
        }
        return $result;
    }

    /** Asigna los valores del array $data a los atributos del modelo. */
    protected function loadFromData(array $data): void
    {
        foreach ($data as $field => $value) {
            $this->attributes[$field] = $value;
        }
    }

    /** Establece el modelo master para las operaciones de datos. */
    protected function setMasterModel($model): void
    {
        $this->masterModel = $model;
    }
}
