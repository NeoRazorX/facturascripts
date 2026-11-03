<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Define la estructura de una tabla de forma fluida, alternativa al XML de Table/.
 * Se usa dentro de TableClass::define().
 *
 * Cada método de tipo (string, integer, boolean...) declara una columna nueva y los
 * modificadores que se encadenan después (nullable, default, primary, unique, index,
 * references, onDelete, rename) actúan sobre ESA última columna.
 *
 * Diferencia con el XML: las columnas son NOT NULL por defecto, usa ->nullable() para
 * permitir nulos. Las claves foráneas siempre llevan ON UPDATE CASCADE.
 *
 * Ejemplo:
 *
 *     $table->id();                                         // id serial + PRIMARY KEY (id)
 *     $table->string('codigo', 10)->unique();               // NOT NULL + UNIQUE (codigo)
 *     $table->string('nick', 50)->nullable()                // FOREIGN KEY (nick) REFERENCES users (nick)
 *         ->references('users', 'nick')->onDelete('SET NULL');
 *     $table->float('total')->default(0);
 *     $table->boolean('activo')->default(true);
 *     $table->text('notas')->nullable()->rename('observaciones'); // renombra la columna antigua
 *     $table->unique('codigo', 'idempresa');                // UNIQUE compuesto
 *     $table->index('fecha', 'codcliente');                 // índice compuesto
 *
 * Nombres de constraints e índices generados (iguales a los de los XML del core):
 *  - PRIMARY KEY: {tabla}_pkey
 *  - FOREIGN KEY: ca_{tabla}_{columna}
 *  - UNIQUE:      uniq_{tabla}_{col1}_{col2}
 *  - INDEX:       {tabla}_{col1}_{col2}
 */
final class Blueprint
{
    /** @var array columnas, indexadas por nombre, en el formato de DbUpdater::readTableXml() */
    private $columns = [];

    /** @var array restricciones (PRIMARY KEY, UNIQUE, FOREIGN KEY), indexadas por nombre */
    private $constraints = [];

    /** @var array índices, indexados por nombre */
    private $indexes = [];

    /** @var string nombre de la última columna declarada, sobre la que actúan los modificadores */
    private $last = '';

    /** @var string nombre de la tabla, usado para generar los nombres de constraints e índices */
    private $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /** Columna boolean. Ej: $table->boolean('activo')->default(true); */
    public function boolean(string $name): self
    {
        return $this->column($name, 'boolean');
    }

    /** Columna date. */
    public function date(string $name): self
    {
        return $this->column($name, 'date');
    }

    /**
     * Valor por defecto de la última columna.
     *
     * Reglas:
     *  - Booleanos: se guardan como true/false. Ej: ->default(true)
     *  - Números (int, float o texto numérico como '0'): se pasan tal cual, sin comillas. Ej: ->default(0)
     *  - Textos: se escriben siempre entre comillas, como cualquier string de PHP.
     *    Ej: ->default('nuevo') genera DEFAULT 'nuevo'. No hace falta añadir más comillas dentro
     *    del texto (si se añaden, "'nuevo'", no se duplican) y una comilla interior se escapa:
     *    ->default("it's") genera DEFAULT 'it''s'.
     *  - Texto vacío: ->default('') genera DEFAULT ''. Si no llamas a default(), la columna no
     *    tiene valor por defecto.
     *  - Limitación: no admite funciones SQL (CURRENT_TIMESTAMP...), se tratarían como texto.
     */
    public function default($value): self
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_string($value) && !is_numeric($value)) {
            $value = "'" . str_replace("'", "''", preg_replace("/^'(.*)'$/s", '$1', $value)) . "'";
        }

        $this->columns[$this->last]['default'] = (string)$value;
        return $this;
    }

    /** Columna double precision. */
    public function float(string $name): self
    {
        return $this->column($name, 'double precision');
    }

    /** Atajo para la clave primaria autoincremental: serial + PRIMARY KEY. Ej: $table->id('idfactura'); */
    public function id(string $name = 'id'): self
    {
        return $this->serial($name)->primary();
    }

    /**
     * Crea un índice. Sin argumentos indexa la última columna; con argumentos crea un índice
     * compuesto. Ej: $table->string('email')->index(); o $table->index('fecha', 'codcliente');
     */
    public function index(string ...$cols): self
    {
        $cols = $cols ?: [$this->last];
        $name = $this->table . '_' . implode('_', $cols);
        $this->indexes[$name] = ['name' => $name, 'columns' => implode(',', $cols)];
        return $this;
    }

    /** Columna integer. */
    public function integer(string $name): self
    {
        return $this->column($name, 'integer');
    }

    /** Permite valores NULL en la última columna (por defecto son NOT NULL). */
    public function nullable(): self
    {
        $this->columns[$this->last]['null'] = 'YES';
        return $this;
    }

    /**
     * Acción ON DELETE de la última clave foránea: RESTRICT (por defecto), SET NULL o CASCADE.
     * Debe llamarse después de references(). Ej: ->references('users', 'nick')->onDelete('SET NULL')
     */
    public function onDelete(string $action): self
    {
        $name = 'ca_' . $this->table . '_' . $this->last;
        $this->constraints[$name]['constraint'] = preg_replace(
            '/ON DELETE .+ ON UPDATE/',
            'ON DELETE ' . strtoupper($action) . ' ON UPDATE',
            $this->constraints[$name]['constraint']
        );
        return $this;
    }

    /**
     * Crea la clave primaria. Sin argumentos usa la última columna; con argumentos crea una
     * clave compuesta. Ej: $table->string('codtrans', 8)->primary(); o $table->primary('a', 'b');
     */
    public function primary(string ...$cols): self
    {
        $cols = $cols ?: [$this->last];
        $name = $this->table . '_pkey';
        $this->constraints[$name] = ['name' => $name, 'constraint' => 'PRIMARY KEY (' . implode(',', $cols) . ')'];
        return $this;
    }

    /**
     * Clave foránea de la última columna hacia $table($column), con ON DELETE RESTRICT y
     * ON UPDATE CASCADE. Para cambiar el borrado, encadena ->onDelete().
     * Ej: $table->string('codagente', 10)->nullable()->references('agentes', 'codagente')->onDelete('SET NULL');
     */
    public function references(string $table, string $column): self
    {
        $name = 'ca_' . $this->table . '_' . $this->last;
        $this->constraints[$name] = [
            'name' => $name,
            'constraint' => 'FOREIGN KEY (' . $this->last . ') REFERENCES ' . $table . ' (' . $column . ') ON DELETE RESTRICT ON UPDATE CASCADE'
        ];
        return $this;
    }

    /**
     * Indica el nombre antiguo de la última columna para que DbUpdater la renombre en lugar de
     * crear una nueva. Ej: $table->text('notas')->nullable()->rename('observaciones');
     */
    public function rename(string $old_name): self
    {
        $this->columns[$this->last]['rename'] = $old_name;
        return $this;
    }

    /** Columna autoincremental (siempre NOT NULL y sin default). Normalmente se usa id(). */
    public function serial(string $name): self
    {
        $this->column($name, 'serial');
        $this->columns[$name]['null'] = 'NO';
        $this->columns[$name]['default'] = null;
        return $this;
    }

    /** Columna character varying($length). Longitud por defecto: 255. */
    public function string(string $name, int $length = 255): self
    {
        return $this->column($name, 'character varying(' . $length . ')');
    }

    /** Columna text. */
    public function text(string $name): self
    {
        return $this->column($name, 'text');
    }

    /** Columna time. */
    public function time(string $name): self
    {
        return $this->column($name, 'time');
    }

    /** Columna timestamp. */
    public function timestamp(string $name): self
    {
        return $this->column($name, 'timestamp');
    }

    /**
     * Devuelve la estructura en el mismo formato que DbUpdater::readTableXml():
     * ['columns' => [...], 'constraints' => [...], 'indexes' => [...]].
     */
    public function toArray(): array
    {
        return ['columns' => $this->columns, 'constraints' => $this->constraints, 'indexes' => $this->indexes];
    }

    /**
     * Crea una restricción UNIQUE. Sin argumentos usa la última columna; con argumentos crea
     * una restricción compuesta. Ej: $table->string('codigo')->unique(); o $table->unique('codigo', 'idempresa');
     */
    public function unique(string ...$cols): self
    {
        $cols = $cols ?: [$this->last];
        $name = 'uniq_' . $this->table . '_' . implode('_', $cols);
        $this->constraints[$name] = ['name' => $name, 'constraint' => 'UNIQUE (' . implode(',', $cols) . ')'];
        return $this;
    }

    /** Declara una columna NOT NULL, sin default ni rename, y la marca como "última" para los modificadores. */
    private function column(string $name, string $type): self
    {
        $this->last = $name;
        $this->columns[$name] = ['name' => $name, 'type' => $type, 'null' => 'NO', 'default' => '', 'rename' => ''];
        return $this;
    }
}
