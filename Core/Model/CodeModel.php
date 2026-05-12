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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Modelo auxiliar para cargar una lista de códigos y sus descripciones.
 * Se utiliza para alimentar widgets de tipo select y otros componentes
 * que necesiten pares código/descripción a partir de una tabla o modelo.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class CodeModel
{
    const ALL_LIMIT = 1000;
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const SEARCH_LIMIT = 50;

    /** @var DataBase */
    protected static $dataBase;

    /** @var int */
    protected static $limit;

    /** @var string */
    public $code;

    /** @var string */
    public $description;

    public function __construct(array $data = [])
    {
        if (empty($data)) {
            $this->code = '';
            $this->description = '';
        } else {
            $this->code = $data['code'];
            $this->description = $data['description'];
        }
    }

    /**
     * Carga una lista CodeModel (código y descripción) para la tabla indicada.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $fieldDescription
     * @param bool $addEmpty
     * @param array $where
     *
     * @return static[]
     */
    public static function all(string $tableName, string $fieldCode, string $fieldDescription, bool $addEmpty = true, array $where = []): array
    {
        // validar nombres de campos para prevenir SQL injection
        if (false === self::isValidFieldName($fieldCode)) {
            Tools::log()->error('invalid-field-name: ' . $fieldCode);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        } elseif (false === self::isValidFieldName($fieldDescription)) {
            Tools::log()->error('invalid-field-description: ' . $fieldDescription);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        }

        // inicializar
        $result = [];
        if ($addEmpty) {
            $result[] = new static(['code' => null, 'description' => '------']);
        }

        // ¿es un modelo? (admite Join\Nombre)
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'codeModelAll')) {
                return array_merge($result, $model->codeModelAll($fieldCode));
            }
            if (method_exists($model, 'modelClassName')
                && $model->modelClassName() === self::modelBaseName($tableName)) {
                return array_merge($result, self::codeModelAll($model, $fieldCode));
            }
        }

        // validar nombre de tabla para prevenir SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        }

        // comprobar caché
        $cacheKey = $addEmpty ?
            'table-' . $tableName . '-code-model-' . $fieldCode . '-' . $fieldDescription . '-empty' :
            'table-' . $tableName . '-code-model-' . $fieldCode . '-' . $fieldDescription;
        $cached = Cache::get($cacheKey);
        if (empty($where) && is_array($cached)) {
            return $cached;
        }

        // comprobar tabla
        if (!self::db()->tableExists($tableName)) {
            Tools::log()->error('table-not-found', ['%tableName%' => $tableName]);
            return $result;
        }

        $sql = 'SELECT DISTINCT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description '
            . 'FROM ' . $tableName . Where::multiSqlLegacy($where) . ' ORDER BY 2 ASC';
        foreach (self::db()->selectLimit($sql, self::getLimit()) as $row) {
            $result[] = new static($row);
        }

        // guardar caché
        if (empty($where)) {
            Cache::set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Convierte un array asociativo (código y valor) en un array de CodeModel.
     *
     * @param array $data
     * @param bool $addEmpty
     *
     * @return static[]
     */
    public static function array2codeModel(array $data, bool $addEmpty = true): array
    {
        $result = [];
        if ($addEmpty) {
            $result[] = new static(['code' => null, 'description' => '------']);
        }

        foreach ($data as $key => $value) {
            $row = ['code' => $key, 'description' => $value];
            $result[] = new static($row);
        }

        return $result;
    }

    /**
     * Devuelve un CodeModel con los datos seleccionados.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $code
     * @param string $fieldDescription
     *
     * @return static
     */
    public function get(string $tableName, string $fieldCode, $code, $fieldDescription)
    {
        // validar nombres de campos para prevenir SQL injection
        if (false === self::isValidFieldName($fieldCode)) {
            Tools::log()->error('invalid-field-name: ' . $fieldCode);
            return new static();
        } elseif (false === self::isValidFieldName($fieldDescription)) {
            Tools::log()->error('invalid-field-description: ' . $fieldDescription);
            return new static();
        }

        // ¿es una tabla o un modelo?
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if ($tableName && class_exists($modelClass)) {
            $model = new $modelClass();
            if ($model->loadWhereEq($fieldCode, $code)) {
                return new static(['code' => $model->{$fieldCode}, 'description' => $model->primaryDescription()]);
            }

            return new static();
        }

        if ($tableName && self::db()->tableExists($tableName)) {
            $sql = 'SELECT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM '
                . $tableName . ' WHERE ' . $fieldCode . ' = ' . self::db()->var2str($code);
            $data = self::db()->selectLimit($sql, 1);
            return empty($data) ? new static() : new static($data[0]);
        }

        return new static();
    }

    /**
     * Devuelve una descripción con los datos seleccionados.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $code
     * @param string $fieldDescription
     *
     * @return string
     */
    public function getDescription(string $tableName, string $fieldCode, $code, $fieldDescription): string
    {
        $model = $this->get($tableName, $fieldCode, $code, $fieldDescription);
        return empty($model->description) ? (string)$code : $model->description;
    }

    public static function getLimit(): int
    {
        return self::$limit ?? self::ALL_LIMIT;
    }

    /**
     * Carga una lista CodeModel (código y descripción) para la tabla indicada y la búsqueda.
     *
     * @param string $tableName
     * @param string $fieldCode
     * @param string $fieldDescription
     * @param string $query
     * @param Where[] $where
     *
     * @return static[]
     */
    public static function search(string $tableName, string $fieldCode, string $fieldDescription, string $query, array $where = []): array
    {
        // ¿es un modelo? (admite Join\Nombre)
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'codeModelSearch')) {
                return $model->codeModelSearch($query, $fieldCode, $where);
            }
            if (method_exists($model, 'modelClassName')
                && $model->modelClassName() === self::modelBaseName($tableName)) {
                return self::codeModelSearch($model, $query, $fieldCode, $where);
            }
        }

        // validar nombre de tabla para prevenir SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return [];
        }

        $fields = $fieldCode . '|' . $fieldDescription;
        $where[] = Where::like($fields, mb_strtolower($query, 'UTF8'));
        return self::all($tableName, $fieldCode, $fieldDescription, false, $where);
    }

    public static function setLimit(int $newLimit): void
    {
        self::$limit = $newLimit;
    }

    private static function codeModelAll(mixed $model, string $fieldCode): array
    {
        $results = [];
        $field = empty($fieldCode) ? $model::primaryColumn() : $fieldCode;

        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $model->primaryDescriptionColumn() . ' AS description '
            . 'FROM ' . $model::tableName() . ' ORDER BY 2 ASC';
        foreach (self::db()->selectLimit($sql, self::getlimit()) as $d) {
            $results[] = new static($d);
        }

        return $results;
    }

    private static function codeModelSearch(mixed $model, string $query, string $fieldCode, array $where): array
    {
        $field = empty($fieldCode) ? $model::primaryColumn() : $fieldCode;
        $fields = $field . '|' . $model->primaryDescriptionColumn();
        $where[] = Where::like($fields, mb_strtolower($query, 'UTF8'));
        return self::all($model::tableName(), $field, $model->primaryDescriptionColumn(), false, $where);
    }

    protected static function db(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    /**
     * Valida que un nombre de campo sea seguro para usar en consultas SQL.
     * Solo permite letras, números, guiones bajos y puntos (para campos con alias de tabla).
     * También permite el uso de las funciones lower(), upper(), substring() y concat().
     */
    protected static function isValidFieldName(string $fieldName): bool
    {
        // permite campos vacíos (valores por defecto)
        if ($fieldName === '') {
            return true;
        }

        // identificador: campo o tabla.campo (sin espacios, sin comillas)
        $fieldName = trim($fieldName);
        $ident = '[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?';

        // campo directo
        if (preg_match('/^' . $ident . '$/', $fieldName)) {
            return true;
        }

        // lower(campo) / upper(campo)
        if (preg_match('/^(lower|upper)\((' . $ident . ')\)$/i', $fieldName)) {
            return true;
        }

        // substring(campo, inicio, longitud) con números
        if (preg_match('/^substring\((' . $ident . '),\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $fieldName, $m)) {
            $start = (int)$m[2];
            $len = (int)$m[3];
            return $start >= 1 && $len >= 1 && $len <= 1000;
        }

        // concat(arg1, arg2, ...) donde arg es un identificador o literal simple '...' (sin comillas internas ni escapadas)
        $arg = "(?:$ident|'[^']*')";
        if (preg_match('/^concat\(\s*' . $arg . '(?:\s*,\s*' . $arg . ')+\s*\)$/i', $fieldName)) {
            return true;
        }

        return false;
    }

    /**
     * Valida que un nombre de tabla sea seguro para usar en consultas SQL.
     * Solo permite letras, números y guiones bajos.
     */
    protected static function isValidTableName(string $tableName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName) === 1;
    }

    /**
     * Devuelve el basename de un modelo (ej: "Join\PartidaAsiento" -> "PartidaAsiento")
     * para comparar contra modelClassName().
     */
    protected static function modelBaseName(string $tableName): string
    {
        $parts = explode('\\', $tableName);
        return end($parts);
    }
}
