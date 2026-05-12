<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Query builder fluido para construir y ejecutar consultas SQL contra la base de datos.
 *
 * Ofrece una API encadenable al estilo de Laravel/Eloquent: se parte de `DbQuery::table('tabla')`
 * y se van añadiendo cláusulas (`select`, `where*`, `orderBy`, `groupBy`, `limit`, ...) hasta
 * ejecutar la consulta con un método terminal (`get`, `first`, `count`, `sum`, `delete`,
 * `insert`, `update`, etc.). Internamente comparte una conexión perezosa singleton (`self::db()`).
 *
 * Los nombres de columnas y tablas se escapan con `escapeColumn()` y los valores con `var2str()`,
 * así que las APIs estándar son seguras frente a inyección. La excepción es `selectRaw()` y la
 * cláusula `having()`, que aceptan SQL en bruto: el llamador es responsable de no concatenar ahí
 * datos no confiables.
 *
 * También expone helpers dinámicos `where{Campo}($valor)` (vía `__call`) que se traducen a
 * `whereEq` con el nombre de campo en minúsculas.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class DbQuery
{
    /** Conexión compartida perezosa para todas las instancias del builder. @var DataBase */
    private static $db;

    /** Lista de campos del SELECT (ya escapados o en bruto si vinieron de selectRaw). @var string */
    public $fields = '*';

    /** Cláusula GROUP BY ya construida (campos escapados separados por coma). @var string */
    public $groupBy;

    /** Cláusula HAVING en bruto. @var string */
    public $having;

    /** Límite de filas. 0 significa sin límite. @var int */
    public $limit = 0;

    /** Desplazamiento de filas para paginación. @var int */
    public $offset = 0;

    /** Lista de fragmentos `campo ASC|DESC` ya construidos para el ORDER BY. @var array */
    public $orderBy = [];

    /** Nombre de la tabla destino, sin escapar. @var string */
    private $table;

    /** Cláusulas WHERE acumuladas, combinadas con AND al construir el SQL. @var Where[] */
    private $where = [];

    /** Inicia una consulta sobre la tabla indicada; usar preferentemente el factory `DbQuery::table()`. */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Soporta llamadas dinámicas del tipo `whereCampo($valor)` traduciéndolas a `whereEq('campo', $valor)`.
     *
     * Cualquier otra llamada se delega al método correspondiente si existe; en caso contrario,
     * se lanza una excepción para evitar fallos silenciosos.
     */
    public function __call($method, $parameters)
    {
        // Si se llama al where dinámicamente
        // whereNombre(), whereCiudad()
        if (str_starts_with($method, 'where')) {
            $field = strtolower(substr($method, 5));
            return $this->whereEq($field, $parameters[0]);
        }

        if (false === method_exists($this, $method)) {
            throw new Exception('Call to undefined method ' . get_class($this) . '::' . $method . '()');
        }

        return $this->$method(...$parameters);
    }

    /**
     * Ejecuta la consulta y devuelve un array asociativo donde cada fila aporta una pareja `$key => $value`.
     *
     * Si dos filas comparten la misma `$key`, la última sobrescribe a las anteriores.
     */
    public function array(string $key, string $value): array
    {
        $result = [];
        foreach ($this->get() as $row) {
            $result[$row[$key]] = $row[$value];
        }

        return $result;
    }

    /**
     * Devuelve la media de `$field`, opcionalmente redondeada a `$decimals` decimales.
     *
     * Sustituye la cláusula SELECT por `AVG(field)`, así que invalida cualquier `select()` previo.
     */
    public function avg(string $field, ?int $decimals = null): float
    {
        $this->fields = 'AVG(' . self::db()->escapeColumn($field) . ') as _avg';

        $row = $this->first();
        return is_null($decimals) ?
            (float)$row['_avg'] :
            round((float)$row['_avg'], $decimals);
    }

    /** Devuelve la media de `$field` agrupada por `$groupByKey`, en formato `clave => media`. */
    public function avgArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', AVG(' . self::db()->escapeColumn($field) . ') as _avg';

        return $this->groupBy($groupByKey)->array($groupByKey, '_avg');
    }

    /**
     * Cuenta filas según el modo elegido.
     *
     * - Sin argumento y sin `select()` previo: `COUNT(*)`.
     * - Con `$field`: `COUNT(DISTINCT field)`, útil para contar valores únicos de una columna.
     * - Con `select()` previo: envuelve el SELECT existente en un `COUNT(...)`.
     */
    public function count(string $field = ''): int
    {
        if ($field !== '') {
            $this->fields = 'COUNT(DISTINCT ' . self::db()->escapeColumn($field) . ') as _count';
        } elseif ($this->fields === '*' || empty($this->fields)) {
            $this->fields = 'COUNT(*) as _count';
        } else {
            $this->fields = 'COUNT(' . $this->fields . ') as _count';
        }

        foreach ($this->first() as $value) {
            return (int)$value;
        }

        return 0;
    }

    /** Devuelve el conteo de `$field` agrupado por `$groupByKey`, en formato `clave => count`. */
    public function countArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', COUNT(' . self::db()->escapeColumn($field) . ') as _count';

        return $this->groupBy($groupByKey)->array($groupByKey, '_count');
    }

    /**
     * Ejecuta un DELETE sobre la tabla aplicando los `where*` acumulados.
     *
     * Importante: si no se han añadido cláusulas WHERE, se borra la tabla entera. La clase no
     * impone una verificación adicional, así que el llamador debe asegurarse de añadir las
     * condiciones que correspondan.
     */
    public function delete(): bool
    {
        $sql = 'DELETE FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        return self::db()->exec($sql);
    }

    /**
     * Devuelve la primera fila resultante, o un array vacío si no hay coincidencias.
     *
     * Internamente fuerza `limit=1` y `offset=0`, sobrescribiendo cualquier valor previo.
     */
    public function first(): array
    {
        $this->limit = 1;
        $this->offset = 0;

        foreach ($this->get() as $row) {
            return $row;
        }

        return [];
    }

    /** Ejecuta la consulta SELECT y devuelve todas las filas como arrays asociativos. */
    public function get(): array
    {
        return self::db()->selectLimit($this->sql(), $this->limit, $this->offset);
    }

    /** Define el GROUP BY a partir de una lista de campos separados por comas; cada campo se escapa por separado. */
    public function groupBy(string $fields): self
    {
        $list = [];
        foreach (explode(',', $fields) as $field) {
            $list[] = self::db()->escapeColumn(trim($field));
        }

        $this->groupBy = implode(', ', $list);

        return $this;
    }

    /**
     * Define la cláusula HAVING en bruto.
     *
     * No se escapa: el llamador es responsable de no concatenar datos no confiables aquí.
     */
    public function having(string $having): self
    {
        $this->having = $having;

        return $this;
    }

    /**
     * Inserta una fila o un lote de filas en la tabla.
     *
     * Detecta automáticamente el modo: si el primer elemento de `$data` es a su vez un array,
     * trata `$data` como una lista de filas y emite un INSERT múltiple usando los campos del
     * primer registro como referencia (todas las filas deben tener las mismas claves). En caso
     * contrario, hace un INSERT simple con el array recibido. Devuelve false si `$data` viene
     * vacío.
     */
    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        // comprobamos si es una inserción simple (no es un array de arrays)
        $first = reset($data);
        if (!is_array($first)) {
            foreach ($data as $field => $value) {
                $fields[] = self::db()->escapeColumn($field);
                $values[] = self::db()->var2str($value);
            }

            $sql = 'INSERT INTO ' . self::db()->escapeColumn($this->table)
                . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ');';
            return self::db()->exec($sql);
        }

        // inserción múltiple
        foreach (array_keys($first) as $field) {
            $fields[] = self::db()->escapeColumn($field);
        }

        foreach ($data as $row) {
            $line = [];
            foreach ($row as $value) {
                $line[] = self::db()->var2str($value);
            }
            $values[] = '(' . implode(', ', $line) . ')';
        }

        $sql = 'INSERT INTO ' . self::db()->escapeColumn($this->table)
            . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $values) . ';';
        return self::db()->exec($sql);
    }

    /** Inserta y devuelve el último ID autoincremental generado, o null si la inserción falla. */
    public function insertGetId(array $data): ?int
    {
        if ($this->insert($data)) {
            return self::db()->lastval();
        }

        return null;
    }

    /** Establece el LIMIT de la consulta; 0 significa sin límite. */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /** Devuelve el valor MAX de `$field` como float, opcionalmente redondeado a `$decimals` decimales. */
    public function max(string $field, ?int $decimals = null): float
    {
        $max = $this->maxString($field);
        return is_null($decimals) ?
            (float)$max :
            round((float)$max, $decimals);
    }

    /** Devuelve el valor MAX de `$field` como string, útil para columnas no numéricas (fechas, textos). */
    public function maxString(string $field): string
    {
        $this->fields = 'MAX(' . self::db()->escapeColumn($field) . ') as _max';
        return $this->first()['_max'];
    }

    /** Devuelve el MAX de `$field` agrupado por `$groupByKey`, en formato `clave => max`. */
    public function maxArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', MAX(' . self::db()->escapeColumn($field) . ') as _max';

        return $this->groupBy($groupByKey)->array($groupByKey, '_max');
    }

    /** Devuelve el valor MIN de `$field` como float, opcionalmente redondeado a `$decimals` decimales. */
    public function min(string $field, ?int $decimals = null): float
    {
        $min = $this->minString($field);
        return is_null($decimals) ?
            (float)$min :
            round((float)$min, $decimals);
    }

    /** Devuelve el valor MIN de `$field` como string, útil para columnas no numéricas. */
    public function minString(string $field): string
    {
        $this->fields = 'MIN(' . self::db()->escapeColumn($field) . ') as _min';
        return $this->first()['_min'];
    }

    /** Devuelve el MIN de `$field` agrupado por `$groupByKey`, en formato `clave => min`. */
    public function minArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', MIN(' . self::db()->escapeColumn($field) . ') as _min';

        return $this->groupBy($groupByKey)->array($groupByKey, '_min');
    }

    /** Establece el OFFSET (número de filas a saltar) para paginación. */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Añade una cláusula ORDER BY validando estrictamente la entrada para evitar inyección SQL.
     *
     * Aceptación de `$order`: cualquier valor distinto de 'DESC' (case-insensitive) cae en 'ASC'.
     *
     * Aceptación de `$field`:
     * - Expresión `RAND()` o `RANDOM()`: se sustituye por el operador aleatorio del motor.
     * - Funciones `LOWER(col)`, `UPPER(col)`, `CAST(col AS tipo)` o `COALESCE(col, valor)` con
     *   patrones validados por regex; se aceptan tal cual.
     * - Cualquier otro paréntesis se considera no permitido y se cae a la rama de escape simple.
     * - Prefijos especiales `integer:`, `lower:` y `upper:` aplican el cast/función correspondiente.
     * - Resto: se escapa con `escapeColumn()`.
     */
    public function orderBy(string $field, string $order = 'ASC'): self
    {
        // validamos que order sea ASC o DESC
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        // si lleva paréntesis, validamos que sea una expresión permitida
        if (str_contains($field, '(') && str_contains($field, ')')) {
            // si es RAND() o RANDOM(), usamos la función random del engine
            if (preg_match('/^(RAND|RANDOM)\(\)$/i', $field)) {
                $this->orderBy[] = self::db()->random();
                return $this;
            }

            // permitimos LOWER(), UPPER(), CAST() y COALESCE()
            if (
                preg_match('/^(LOWER|UPPER)\([a-zA-Z0-9_.]+\)$/i', $field) ||
                preg_match('/^CAST\([a-zA-Z0-9_.]+ AS [a-zA-Z0-9_ ]+\)$/i', $field) ||
                preg_match("/^COALESCE\([a-zA-Z0-9_.]+\s*,\s*(?:'[^']*'|-?\d+(?:\.\d+)?)\)$/i", $field)
            ) {
                $this->orderBy[] = $field . ' ' . $order;
                return $this;
            }
            // si no es una expresión permitida, escapamos el campo completo
        }

        // si el campo comienza por integer: hacemos el cast a integer
        if (str_starts_with($field, 'integer:')) {
            $field = self::db()->castInteger(substr($field, 8));
            $this->orderBy[] = $field . ' ' . $order;
            return $this;
        }

        // si empieza por lower, hacemos el lower
        if (str_starts_with($field, 'lower:')) {
            $field = 'LOWER(' . self::db()->escapeColumn(substr($field, 6)) . ')';
            $this->orderBy[] = $field . ' ' . $order;
            return $this;
        }

        // si empieza por upper, hacemos el upper
        if (str_starts_with($field, 'upper:')) {
            $field = 'UPPER(' . self::db()->escapeColumn(substr($field, 6)) . ')';
            $this->orderBy[] = $field . ' ' . $order;
            return $this;
        }

        $this->orderBy[] = self::db()->escapeColumn($field) . ' ' . $order;

        return $this;
    }

    /** Añade varias cláusulas ORDER BY a partir de un array `campo => orden`. */
    public function orderMulti(array $fields): self
    {
        foreach ($fields as $field => $order) {
            $this->orderBy($field, $order);
        }

        return $this;
    }

    /** Añade un ORDER BY aleatorio usando la función específica del motor de base de datos. */
    public function orderByRandom(): self
    {
        $this->orderBy[] = self::db()->random();

        return $this;
    }

    /** Limpia el ORDER BY acumulado para empezar a definirlo de nuevo. */
    public function reorder(): self
    {
        $this->orderBy = [];

        return $this;
    }

    /** Define la lista de campos del SELECT a partir de una cadena separada por comas; cada campo se escapa por separado. */
    public function select(string $fields): self
    {
        $list = [];
        foreach (explode(',', $fields) as $field) {
            $list[] = self::db()->escapeColumn(trim($field));
        }

        $this->fields = implode(', ', $list);

        return $this;
    }

    /**
     * Define la lista de campos del SELECT en bruto, sin escapar.
     *
     * Pensado para expresiones complejas (subconsultas, funciones, alias). El llamador es
     * responsable de no concatenar aquí entrada del usuario sin sanear.
     */
    public function selectRaw(string $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Construye y devuelve la sentencia SELECT resultante (sin LIMIT/OFFSET, que se aplican al ejecutar).
     *
     * Útil para inspeccionar la consulta, depurar o ejecutarla manualmente.
     */
    public function sql(): string
    {
        $sql = 'SELECT ' . $this->fields . ' FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->having;
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        return $sql;
    }

    /** Devuelve la suma de `$field`, opcionalmente redondeada a `$decimals` decimales. */
    public function sum(string $field, ?int $decimals = null): float
    {
        $this->fields = 'SUM(' . self::db()->escapeColumn($field) . ') as _sum';

        $row = $this->first();
        return is_null($decimals) ?
            (float)$row['_sum'] :
            round((float)$row['_sum'], $decimals);
    }

    /** Devuelve la suma de `$field` agrupada por `$groupByKey`, en formato `clave => suma`. */
    public function sumArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', SUM(' . self::db()->escapeColumn($field) . ') as _sum';

        return $this->groupBy($groupByKey)->array($groupByKey, '_sum');
    }

    /** Factory recomendado: crea una nueva consulta sobre la tabla indicada. */
    public static function table(string $table): self
    {
        return new self($table);
    }

    /**
     * Ejecuta un UPDATE sobre la tabla aplicando los `where*` acumulados.
     *
     * Devuelve false si `$data` viene vacío. Igual que `delete()`, si no hay cláusulas WHERE,
     * el UPDATE afecta a todas las filas; el llamador es responsable de filtrar.
     */
    public function update(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        foreach ($data as $field => $value) {
            $fields[] = self::db()->escapeColumn($field) . ' = ' . self::db()->var2str($value);
        }

        $sql = 'UPDATE ' . self::db()->escapeColumn($this->table) . ' SET ' . implode(', ', $fields);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        return self::db()->exec($sql);
    }

    /**
     * Añade un conjunto de cláusulas WHERE preconstruidas.
     *
     * Acepta tanto instancias de `Where` (nuevo estilo) como de `DataBaseWhere` (legacy), para
     * facilitar la migración progresiva. Cualquier otro valor lanza excepción.
     *
     * @param Where[]|DataBaseWhere[] $where
     * @return $this
     * @throws Exception si algún elemento no es una cláusula válida
     */
    public function where(array $where): self
    {
        // si el array está vacío, no hacemos nada
        if (empty($where)) {
            return $this;
        }

        foreach ($where as $value) {
            // si no es una instancia de Where o DataBaseWhere, lanzamos una excepción
            if (!($value instanceof Where) && !($value instanceof DataBaseWhere)) {
                throw new Exception('Invalid where clause ' . print_r($value, true));
            }

            $this->where[] = $value;
        }

        return $this;
    }

    /** Añade una condición `field BETWEEN value1 AND value2`. */
    public function whereBetween(string $field, $value1, $value2): self
    {
        $this->where[] = Where::between($field, $value1, $value2);

        return $this;
    }

    /** Añade una condición `field = value`. Equivalente al helper dinámico `where{Campo}($valor)`. */
    public function whereEq(string $field, $value): self
    {
        $this->where[] = Where::eq($field, $value);

        return $this;
    }

    /** Añade una condición `field > value`. */
    public function whereGt(string $field, $value): self
    {
        $this->where[] = Where::gt($field, $value);

        return $this;
    }

    /** Añade una condición `field >= value`. */
    public function whereGte(string $field, $value): self
    {
        $this->where[] = Where::gte($field, $value);

        return $this;
    }

    /** Añade una condición `field IN (values...)`. */
    public function whereIn(string $field, array $values): self
    {
        $this->where[] = Where::in($field, $values);

        return $this;
    }

    /** Añade una condición `field LIKE value`. El llamador es quien añade los comodines `%` cuando los necesite. */
    public function whereLike(string $field, string $value): self
    {
        $this->where[] = Where::like($field, $value);

        return $this;
    }

    /** Añade una condición `field < value`. */
    public function whereLt(string $field, $value): self
    {
        $this->where[] = Where::lt($field, $value);

        return $this;
    }

    /** Añade una condición `field <= value`. */
    public function whereLte(string $field, $value): self
    {
        $this->where[] = Where::lte($field, $value);

        return $this;
    }

    /** Añade una condición `field <> value`. */
    public function whereNotEq(string $field, $value): self
    {
        $this->where[] = Where::notEq($field, $value);

        return $this;
    }

    /** Añade una condición `field NOT IN (values...)`. */
    public function whereNotIn(string $field, array $values): self
    {
        $this->where[] = Where::notIn($field, $values);

        return $this;
    }

    /** Añade una condición `field IS NOT NULL`. */
    public function whereNotNull(string $field): self
    {
        $this->where[] = Where::isNotNull($field);

        return $this;
    }

    /** Añade una condición `field IS NULL`. */
    public function whereNull(string $field): self
    {
        $this->where[] = Where::isNull($field);

        return $this;
    }

    /** Devuelve la conexión, abriéndola la primera vez (singleton perezoso compartido por todas las consultas). */
    private static function db(): DataBase
    {
        if (null === self::$db) {
            self::$db = new DataBase();
            self::$db->connect();
        }

        return self::$db;
    }
}
