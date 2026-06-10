<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Representa una cláusula WHERE de SQL y la traduce a un fragmento seguro de SQL para usarla
 * con la conexión de FacturaScripts.
 *
 * Cada instancia describe una condición simple (campo + operador + valor) o un grupo anidado
 * (operador `(` con `subWhere`). Las condiciones se construyen con los factory estáticos
 * (`Where::eq`, `Where::like`, `Where::in`, `Where::between`, sus variantes `or*`, etc.) y se
 * combinan con `Where::multiSql()` o `Where::multiSqlLegacy()`, esta última también acepta los
 * antiguos `DataBaseWhere` para compatibilidad.
 *
 * Detalles no triviales:
 * - El campo admite varios nombres separados por `|` (FIELD_SEPARATOR): se generan condiciones
 *   sobre cada campo unidas con OR y agrupadas entre paréntesis.
 * - Los nombres de campo se escapan con `escapeColumn()`, salvo si llevan paréntesis (se asume
 *   expresión SQL válida ya formada por el llamador) o si usan los prefijos `integer:` (cast a
 *   entero) o `lower:` (envuelve el campo en LOWER(...)).
 * - Los valores se serializan con `var2str()`, así que las APIs estándar son seguras frente a
 *   inyección. El prefijo `field:` solo se interpreta como referencia a otra columna cuando se
 *   activa `useField()`; en caso contrario se trata como literal.
 * - Con operador `=` o `!=`, un valor null se traduce automáticamente a `IS NULL` / `IS NOT NULL`.
 * - `LIKE` y `NOT LIKE` aplican `LOWER(...)` a ambos lados y, si el patrón no contiene `%`, lo
 *   envuelven con `%...%` automáticamente.
 * - `XLIKE` divide el valor por espacios y combina cada palabra con LIKE unidas por AND
 *   (búsqueda multipalabra).
 * - `IN` / `NOT IN` aceptan array, lista separada por comas o, si el string empieza por SELECT,
 *   una subconsulta literal.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Where
{
    /** Separador para indicar varios campos en `$fields`; produce condiciones unidas por OR. */
    const FIELD_SEPARATOR = '|';

    /** Conexión compartida perezosa, instanciada en el primer uso. @var DataBase */
    private static $db;

    /** Nombre del campo (o varios separados por FIELD_SEPARATOR). @var string */
    public $fields;

    /** Operador SQL: `=`, `!=`, `<`, `>`, `LIKE`, `IN`, `BETWEEN`, `XLIKE`, ... o `(` para grupos. @var string */
    public $operator;

    /** Conector con la cláusula previa al concatenar: `AND` u `OR`. @var string */
    public $operation;

    /** Cláusulas hijas cuando el operador es `(` (grupo anidado). @var Where[] */
    public $subWhere;

    /** Si es true, los valores con prefijo `field:` se interpretan como nombre de columna. @var bool */
    public $useField;

    /** Valor a comparar; puede ser escalar, array (IN/BETWEEN) o null (se traduce a IS NULL). @var mixed */
    public $value;

    /**
     * Construye una cláusula simple. En general es preferible usar los factory estáticos
     * (`eq`, `like`, `in`, ...) en lugar de instanciar directamente.
     */
    public function __construct(string $fields, $value, string $operator = '=', string $operation = 'AND', bool $useField = false)
    {
        $this->fields = $fields;
        $this->value = $value;
        $this->operator = $operator;
        $this->operation = $operation;
        $this->useField = $useField;
    }

    /**
     * Habilita la interpretación del prefijo `field:` en los valores para esta condición.
     *
     * Sin activar este flag, un valor como `'field:otra_columna'` se trata como string literal
     * y se escapa; con el flag activo, se traduce a una referencia escapada a esa columna,
     * permitiendo comparaciones campo-contra-campo.
     */
    public function useField(): self
    {
        $this->useField = true;

        return $this;
    }

    /** Crea `campo BETWEEN value1 AND value2`. */
    public static function between(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'BETWEEN');
    }

    /** Factory genérico que permite especificar operador y conector arbitrarios. */
    public static function column(string $fields, $value, string $operator = '=', string $operation = 'AND'): self
    {
        return new self($fields, $value, $operator, $operation);
    }

    /** Crea `campo = value` (o `campo IS NULL` si value es null). */
    public static function eq(string $fields, $value): self
    {
        return new self($fields, $value, '=');
    }

    /** Crea `campo > value`. */
    public static function gt(string $fields, $value): self
    {
        return new self($fields, $value, '>');
    }

    /** Crea `campo >= value`. */
    public static function gte(string $fields, $value): self
    {
        return new self($fields, $value, '>=');
    }

    /**
     * Crea `campo IN (...)`.
     *
     * `$values` puede ser un array, una lista separada por comas o un string que empiece
     * por `SELECT` para incrustar una subconsulta literal.
     */
    public static function in(string $fields, $values): self
    {
        return new self($fields, $values, 'IN');
    }

    /** Crea `campo IS NOT NULL`. */
    public static function isNotNull(string $fields): self
    {
        return new self($fields, null, 'IS NOT');
    }

    /** Crea `campo IS NULL`. */
    public static function isNull(string $fields): self
    {
        return new self($fields, null, 'IS');
    }

    /**
     * Crea `LOWER(campo) LIKE LOWER('%value%')`.
     *
     * Si `$value` ya contiene comodines `%`, se respetan tal cual; en caso contrario, se
     * envuelve automáticamente con `%...%`.
     */
    public static function like(string $fields, string $value): self
    {
        return new self($fields, $value, 'LIKE');
    }

    /** Crea `campo < value`. */
    public static function lt(string $fields, $value): self
    {
        return new self($fields, $value, '<');
    }

    /** Crea `campo <= value`. */
    public static function lte(string $fields, $value): self
    {
        return new self($fields, $value, '<=');
    }

    /**
     * Convierte un array de cláusulas Where en el SQL combinado, sin el prefijo `WHERE`.
     *
     * El conector entre cláusulas (AND/OR) se toma del campo `operation` de cada elemento; el
     * de la primera se ignora. Los grupos anidados (operador `(`) se renderizan recursivamente
     * entre paréntesis. Lanza Exception si algún elemento no es una instancia de Where.
     */
    public static function multiSql(array $where): string
    {
        $sql = '';
        foreach ($where as $item) {
            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }

            if (!empty($sql)) {
                $sql .= ' ' . $item->operation . ' ';
            }

            if ($item->operator === '(') {
                $sql .= '(' . self::multiSql($item->subWhere) . ')';
                continue;
            }

            $sql .= $item->sql();
        }

        return $sql;
    }

    /**
     * Variante de `multiSql()` para código legacy: acepta también instancias de `DataBaseWhere`
     * y devuelve el SQL ya prefijado con ` WHERE ` (cadena vacía si el array está vacío).
     *
     * Como los antiguos `DataBaseWhere` no soportaban grupos explícitos, esta función agrupa
     * automáticamente con paréntesis las secuencias consecutivas en las que el siguiente
     * elemento usa `operation = 'OR'`, replicando la precedencia que se asumía históricamente.
     */
    public static function multiSqlLegacy(array $where): string
    {
        $sql = '';
        $group = false;

        foreach ($where as $key => $item) {
            // si es una instancia de DataBaseWhere, lo convertimos a sql
            if ($item instanceof DataBaseWhere) {
                $dbWhere = new self($item->fields, $item->value, $item->operator, $item->operation, $item->useField ?? false);

                if (!empty($sql)) {
                    $sql .= ' ' . $item->operation . ' ';
                }

                // si el siguiente elemento es un OR, lo agrupamos
                if (!$group && isset($where[$key + 1]) && $where[$key + 1] instanceof DataBaseWhere && $where[$key + 1]->operation === 'OR') {
                    $sql .= '(';
                    $group = true;
                }

                $sql .= $dbWhere->sql();

                // si estamos agrupando y el siguiente elemento no es un OR, cerramos el grupo
                if ($group && (!isset($where[$key + 1]) || !($where[$key + 1] instanceof DataBaseWhere) || $where[$key + 1]->operation !== 'OR')) {
                    $sql .= ')';
                    $group = false;
                }
                continue;
            }

            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }

            if (!empty($sql)) {
                $sql .= ' ' . $item->operation . ' ';
            }

            if ($item->operator === '(') {
                $sql .= '(' . self::multiSql($item->subWhere) . ')';
                continue;
            }

            $sql .= $item->sql();
        }

        return empty($sql) ? '' : ' WHERE ' . $sql;
    }

    /** Crea `campo NOT BETWEEN value1 AND value2`. */
    public static function notBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'NOT BETWEEN');
    }

    /** Crea `campo != value` (o `campo IS NOT NULL` si value es null). */
    public static function notEq(string $fields, $value): self
    {
        return new self($fields, $value, '!=');
    }

    /** Crea `campo NOT IN (...)`. Mismas reglas de `$values` que `in()`. */
    public static function notIn(string $fields, $values): self
    {
        return new self($fields, $values, 'NOT IN');
    }

    /** Crea `LOWER(campo) NOT LIKE LOWER('%value%')` con el mismo manejo de comodines que `like()`. */
    public static function notLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'NOT LIKE');
    }

    /** Variante genérica con conector OR. */
    public static function or(string $fields, $value, string $operator = '='): self
    {
        return new self($fields, $value, $operator, 'OR');
    }

    /** Equivale a `between()` pero unido a la cláusula previa con OR. */
    public static function orBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'BETWEEN', 'OR');
    }

    /** Equivale a `eq()` pero unido a la cláusula previa con OR. */
    public static function orEq(string $fields, $value): self
    {
        return new self($fields, $value, '=', 'OR');
    }

    /** Equivale a `gt()` pero unido a la cláusula previa con OR. */
    public static function orGt(string $fields, $value): self
    {
        return new self($fields, $value, '>', 'OR');
    }

    /** Equivale a `gte()` pero unido a la cláusula previa con OR. */
    public static function orGte(string $fields, $value): self
    {
        return new self($fields, $value, '>=', 'OR');
    }

    /** Equivale a `in()` pero unido a la cláusula previa con OR. */
    public static function orIn(string $fields, $values): self
    {
        return new self($fields, $values, 'IN', 'OR');
    }

    /** Equivale a `isNotNull()` pero unido a la cláusula previa con OR. */
    public static function orIsNotNull(string $fields): self
    {
        return new self($fields, null, 'IS NOT', 'OR');
    }

    /** Equivale a `isNull()` pero unido a la cláusula previa con OR. */
    public static function orIsNull(string $fields): self
    {
        return new self($fields, null, 'IS', 'OR');
    }

    /** Equivale a `like()` pero unido a la cláusula previa con OR. */
    public static function orLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'LIKE', 'OR');
    }

    /** Equivale a `lt()` pero unido a la cláusula previa con OR. */
    public static function orLt(string $fields, $value): self
    {
        return new self($fields, $value, '<', 'OR');
    }

    /** Equivale a `lte()` pero unido a la cláusula previa con OR. */
    public static function orLte(string $fields, $value): self
    {
        return new self($fields, $value, '<=', 'OR');
    }

    /** Equivale a `notBetween()` pero unido a la cláusula previa con OR. */
    public static function orNotBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'NOT BETWEEN', 'OR');
    }

    /** Equivale a `notEq()` pero unido a la cláusula previa con OR. */
    public static function orNotEq(string $fields, $value): self
    {
        return new self($fields, $value, '!=', 'OR');
    }

    /** Equivale a `notIn()` pero unido a la cláusula previa con OR. */
    public static function orNotIn(string $fields, $values): self
    {
        return new self($fields, $values, 'NOT IN', 'OR');
    }

    /** Equivale a `notLike()` pero unido a la cláusula previa con OR. */
    public static function orNotLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'NOT LIKE', 'OR');
    }

    /** Equivale a `regexp()` pero unido a la cláusula previa con OR. */
    public static function orRegexp(string $fields, string $value): self
    {
        return new self($fields, $value, 'REGEXP', 'OR');
    }

    /** Equivale a `sub()` pero unido a la cláusula previa con OR. */
    public static function orSub(array $where): self
    {
        return self::sub($where, 'OR');
    }

    /** Equivale a `xlike()` pero unido a la cláusula previa con OR. */
    public static function orXlike(string $fields, string $value): self
    {
        return new self($fields, $value, 'XLIKE', 'OR');
    }

    /** Crea `campo REGEXP value` (la sintaxis exacta depende del motor, vía `getOperator`). */
    public static function regexp(string $fields, string $value): self
    {
        return new self($fields, $value, 'REGEXP');
    }

    /**
     * Renderiza esta cláusula simple como SQL (sin prefijo `WHERE` ni el conector `operation`).
     *
     * Si `$fields` contiene varios campos separados por `|`, se generan condiciones por cada
     * uno unidas con OR y rodeadas de paréntesis. Los grupos (operador `(`) no se procesan aquí,
     * sino en `multiSql()`.
     */
    public function sql(): string
    {
        $fields = explode(self::FIELD_SEPARATOR, $this->fields);

        $sql = count($fields) > 1 ? '(' : '';

        foreach ($fields as $key => $field) {
            if ($key > 0) {
                $sql .= ' OR ';
            }

            switch ($this->operator) {
                case '=':
                    $sql .= is_null($this->value) ?
                        self::sqlColumn($field) . ' IS NULL' :
                        self::sqlColumn($field) . ' = ' . $this->sqlValue($this->value);
                    break;

                case '!=':
                case '<>':
                    $sql .= is_null($this->value) ?
                        self::sqlColumn($field) . ' IS NOT NULL' :
                        self::sqlColumn($field) . ' ' . $this->operator . ' ' . $this->sqlValue($this->value);
                    break;

                case '>':
                case '<':
                case '>=':
                case '<=':
                case 'REGEXP':
                    $sql .= self::sqlColumn($field) . ' ' . self::db()->getOperator($this->operator) . ' ' . $this->sqlValue($this->value);
                    break;

                case 'IS':
                case 'IS NOT':
                    $sql .= self::sqlColumn($field) . ' ' . $this->operator . ' NULL';
                    break;

                case 'IN':
                case 'NOT IN':
                    $sql .= self::sqlOperatorIn($field, $this->value, $this->operator);
                    break;

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $sql .= $this->sqlOperatorBetween($field, $this->value, $this->operator);
                    break;

                case 'LIKE':
                case 'NOT LIKE':
                    $sql .= self::sqlOperatorLike($field, $this->value, $this->operator);
                    break;

                case 'XLIKE':
                    $sql .= self::sqlOperatorXLike($field, $this->value);
                    break;
            }
        }

        return count($fields) > 1 ? $sql . ')' : $sql;
    }

    /**
     * Agrupa varias cláusulas en una sub-condición que se renderizará entre paréntesis.
     *
     * Internamente devuelve una instancia con operador `(` y `subWhere` apuntando al array
     * de hijos; `multiSql()` la detecta y delega recursivamente. Lanza Exception si algún
     * elemento del array no es Where.
     */
    public static function sub(array $where, string $operation = 'AND'): self
    {
        // comprobamos si el $where es un array de Where
        foreach ($where as $item) {
            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }
        }

        $item = new self('', '', '(', $operation);
        $item->subWhere = $where;
        return $item;
    }

    /**
     * Crea una búsqueda multipalabra: divide `$value` por espacios y aplica un LIKE a cada
     * palabra, combinándolos con AND (la fila debe contener todas las palabras).
     */
    public static function xlike(string $fields, string $value): self
    {
        return new self($fields, $value, 'XLIKE');
    }

    private static function db(): DataBase
    {
        if (empty(self::$db)) {
            self::$db = new DataBase();
        }

        return self::$db;
    }

    private static function sqlColumn(string $field): string
    {
        // si lleva paréntesis, no escapamos
        if (strpos($field, '(') !== false && strpos($field, ')') !== false) {
            return $field;
        }

        // si empieza por integer, hacemos el cast
        if (substr($field, 0, 8) === 'integer:') {
            return self::db()->castInteger(substr($field, 8));
        }

        // si empieza por lower, hacemos el lower
        if (substr($field, 0, 6) === 'lower:') {
            return 'LOWER(' . self::db()->escapeColumn(substr($field, 6)) . ')';
        }

        return self::db()->escapeColumn($field);
    }

    private function sqlOperatorBetween(string $field, $values, string $operator): string
    {
        // si no es un array, lanzamos una excepción
        if (!is_array($values)) {
            throw new Exception('Invalid values in where clause ' . print_r($values, true));
        }

        // si no tiene 2 elementos, lanzamos una excepción
        if (count($values) !== 2) {
            throw new Exception('Invalid values in where clause ' . print_r($values, true));
        }

        return self::sqlColumn($field) . ' ' . $operator . ' ' . $this->sqlValue($values[0])
            . ' AND ' . $this->sqlValue($values[1]);
    }

    private static function sqlOperatorIn(string $field, $values, string $operator): string
    {
        if (is_array($values)) {
            $items = [];
            foreach ($values as $val) {
                $items[] = self::db()->var2str($val);
            }

            return self::sqlColumn($field) . ' ' . $operator . ' (' . implode(',', $items) . ')';
        }

        // si comienza por SELECT, lo tratamos como una subconsulta
        if (substr(strtoupper($values), 0, 6) === 'SELECT') {
            return self::sqlColumn($field) . ' ' . $operator . ' (' . $values . ')';
        }

        // es un string, separamos los valores por coma
        $items = [];
        foreach (explode(',', $values) as $val) {
            $items[] = self::db()->var2str(trim($val));
        }

        return self::sqlColumn($field) . ' ' . $operator . ' (' . implode(',', $items) . ')';
    }

    private static function sqlOperatorLike(string $field, string $value, string $operator): string
    {
        // si no contiene %, se los añadimos
        if (strpos($value, '%') === false) {
            return 'LOWER(' . self::sqlColumn($field) . ') ' . $operator
                . " LOWER('%" . self::db()->escapeString($value) . "%')";
        }

        // contiene algún comodín
        return 'LOWER(' . self::sqlColumn($field) . ') ' . $operator
            . " LOWER('" . self::db()->escapeString($value) . "')";
    }

    private static function sqlOperatorXLike(string $field, string $value): string
    {
        // separamos las palabras en $value
        $words = explode(' ', $value);

        // si solamente hay una palabra, la tratamos como un like
        if (count($words) === 1) {
            return '(' . self::sqlOperatorLike($field, $value, 'LIKE') . ')';
        }

        // si hay más de una palabra, las tratamos como un like con OR
        $sql = '';
        foreach ($words as $word) {
            if (!empty($sql)) {
                $sql .= ' AND ';
            }
            $sql .= self::sqlOperatorLike($field, trim($word), 'LIKE');
        }

        return '(' . $sql . ')';
    }

    private function sqlValue($value): string
    {
        // si empieza por field: lo tratamos como un campo solo si está autorizado
        if ($this->useField && substr($value, 0, 6) === 'field:') {
            return self::sqlColumn(substr($value, 6));
        }

        // si no, lo tratamos como un valor
        return self::db()->var2str($value);
    }
}
