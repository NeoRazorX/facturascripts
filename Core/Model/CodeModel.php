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
use FacturaScripts\Core\Template\JoinModel;
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

    /** @var DataBase Conexión compartida con la base de datos. */
    protected static $dataBase;

    /** @var int Número máximo de resultados que se pueden obtener. */
    protected static $limit;

    /** @var string Código identificativo del elemento. */
    public $code;

    /** @var string Descripción asociada al código. */
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
        // validamos los nombres de campos para evitar SQL injection
        if (false === self::isValidFieldName($fieldCode)) {
            Tools::log()->error('invalid-field-name: ' . $fieldCode);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        } elseif (false === self::isValidFieldName($fieldDescription)) {
            Tools::log()->error('invalid-field-description: ' . $fieldDescription);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        }

        // inicializamos el resultado
        $result = [];
        if ($addEmpty) {
            $result[] = new static(['code' => null, 'description' => '------']);
        }

        // comprobamos si se trata de un modelo (admite Join\Nombre)
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'codeModelAll')) {
                return array_merge($result, $model->codeModelAll($fieldCode));
            }
            if (
                method_exists($model, 'modelClassName')
                && $model->modelClassName() === self::modelBaseName($tableName)
            ) {
                return array_merge($result, self::codeModelAll($model, $fieldCode));
            }
            if ($model instanceof JoinModel) {
                return array_merge($result, self::joinModelAll($model, $fieldCode, $fieldDescription, $where));
            }
        }

        // validamos el nombre de tabla para evitar SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return $addEmpty ? [new static(['code' => null, 'description' => '------'])] : [];
        }

        // comprobamos la caché
        $cacheKey = $addEmpty ?
            'table-' . $tableName . '-code-model-' . $fieldCode . '-' . $fieldDescription . '-empty' :
            'table-' . $tableName . '-code-model-' . $fieldCode . '-' . $fieldDescription;
        $cached = Cache::get($cacheKey);
        if (empty($where) && is_array($cached)) {
            return $cached;
        }

        // comprobamos que la tabla existe
        if (!self::db()->tableExists($tableName)) {
            Tools::log()->error('table-not-found', ['%tableName%' => $tableName]);
            return $result;
        }

        $sql = 'SELECT DISTINCT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description '
            . 'FROM ' . $tableName . Where::multiSqlLegacy($where) . ' ORDER BY 2 ASC';
        foreach (self::db()->selectLimit($sql, self::getLimit()) as $row) {
            $result[] = new static($row);
        }

        // guardamos en caché
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
    public function get(string $tableName, string $fieldCode, $code, string $fieldDescription)
    {
        if (empty($tableName)) {
            return new static();
        }

        // sin código no hay nada que buscar (WHERE campo = NULL nunca casa)
        if ($code === null || $code === '') {
            return new static();
        }

        // validamos los nombres de campos para evitar SQL injection
        if (false === self::isValidFieldName($fieldCode)) {
            Tools::log()->error('invalid-field-name: ' . $fieldCode);
            return new static();
        } elseif (false === self::isValidFieldName($fieldDescription)) {
            Tools::log()->error('invalid-field-description: ' . $fieldDescription);
            return new static();
        }

        // comprobamos si se trata de un modelo (admite Join\Nombre)
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (
                method_exists($model, 'modelClassName')
                && $model->modelClassName() === self::modelBaseName($tableName)
            ) {
                $field = empty($fieldCode) ? $model::primaryColumn() : $fieldCode;

                // cacheamos con clave prefijada por la tabla real del modelo, para
                // que clearCache() (deleteMulti 'table-<tabla>-') la purgue al cambiar
                $cacheKey = 'table-' . $model::tableName() . '-codemodel-' . md5($field . '|' . $code);
                $data = Cache::remember($cacheKey, function () use ($model, $field, $code) {
                    return $model->loadWhereEq($field, $code) ?
                        ['code' => $model->{$field}, 'description' => $model->primaryDescription()] :
                        [];
                });

                return empty($data) ? new static() : new static($data);
            }
            if ($model instanceof JoinModel) {
                if (empty($fieldCode)) {
                    return new static();
                }
                if ($model->loadWhereEq($fieldCode, $code)) {
                    $codeAlias = self::stripTablePrefix($fieldCode);
                    $descAlias = self::stripTablePrefix($fieldDescription);
                    return new static([
                        'code' => $model->{$codeAlias},
                        'description' => empty($descAlias) ? (string)$model->{$codeAlias} : (string)$model->{$descAlias},
                    ]);
                }
                return new static();
            }
        }

        // validamos el nombre de tabla para evitar SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return new static();
        }

        // sin nombre de campo no se puede construir el WHERE
        if (empty($fieldCode)) {
            return new static();
        }

        if (self::db()->tableExists($tableName)) {
            // cacheamos el resultado con clave prefijada por tabla, de modo que
            // ModelClass::clearCache() la purgue al cambiar cualquier registro de la tabla
            $cacheKey = 'table-' . $tableName . '-codemodel-' . md5($fieldCode . '|' . $fieldDescription . '|' . $code);
            $data = Cache::remember($cacheKey, function () use ($tableName, $fieldCode, $fieldDescription, $code) {
                $sql = 'SELECT ' . $fieldCode . ' AS code, ' . $fieldDescription . ' AS description FROM '
                    . $tableName . ' WHERE ' . $fieldCode . ' = ' . self::db()->var2str($code);
                return self::db()->selectLimit($sql, 1);
            });
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
        // comprobamos si se trata de un modelo (admite Join\Nombre)
        $modelClass = self::MODEL_NAMESPACE . $tableName;
        if (class_exists($modelClass)) {
            $model = new $modelClass();
            if (method_exists($model, 'codeModelSearch')) {
                return $model->codeModelSearch($query, $fieldCode, $where);
            }
            if (
                method_exists($model, 'modelClassName')
                && $model->modelClassName() === self::modelBaseName($tableName)
            ) {
                return self::codeModelSearch($model, $query, $fieldCode, $where);
            }
            if ($model instanceof JoinModel) {
                $fields = $fieldCode . '|' . $fieldDescription;
                $where[] = Where::like($fields, mb_strtolower($query, 'UTF8'));
                return self::joinModelAll($model, $fieldCode, $fieldDescription, $where);
            }
        }

        // validamos el nombre de tabla para evitar SQL injection
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

    private static function joinModelAll(JoinModel $model, string $fieldCode, string $fieldDescription, array $where): array
    {
        $results = [];
        $class = get_class($model);
        $codeAlias = self::stripTablePrefix($fieldCode);
        $descAlias = self::stripTablePrefix($fieldDescription);
        foreach ($class::all($where, [], 0, self::getLimit()) as $row) {
            $results[] = new static([
                'code' => $row->{$codeAlias},
                'description' => empty($descAlias) ? (string)$row->{$codeAlias} : (string)$row->{$descAlias},
            ]);
        }
        return $results;
    }

    private static function stripTablePrefix(string $field): string
    {
        $dot = strrpos($field, '.');
        return $dot === false ? $field : substr($field, $dot + 1);
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
        // permitimos campos vacíos (valores por defecto)
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

        // concat(arg1, arg2, ...) con identificadores o literales entre comillas simples
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
