<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * A Value for an article attribute.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AtributoValor
{

    use Base\ModelTrait;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Relative attribute codeo.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Value of the attribute
     *
     * @var string
     */
    public $valor;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos_valores';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of an attribute
     *
     * @return string
     */
    public function getNombre()
    {
        $nombre = '';

        $sql = 'SELECT * FROM atributos WHERE codatributo = ' . $this->dataBase->var2str($this->codatributo) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $nombre = $data[0]['nombre'];
        }

        return $nombre;
    }

    /**
     * Select all attributes of an attribute code
     *
     * @param string $cod
     *
     * @return self[]
     */
    public function allFromAtributo($cod)
    {
        $lista = [];
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codatributo = ' . $this->dataBase->var2str($cod)
            . ' ORDER BY valor ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new self($d);
            }
        }

        return $lista;
    }

    /**
     * Update the model data in the database.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $sql = 'UPDATE atributos_valores SET valor = ' . $this->dataBase->var2str($this->valor)
            . ', codatributo = ' . $this->dataBase->var2str($this->codatributo)
            . '  WHERE id = ' . $this->dataBase->var2str($this->id) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Insert the model data in the database.
     *
     * @return bool
     */
    private function saveInsert()
    {
        if ($this->id === null) {
            $this->id = 1;

            $sql = 'SELECT MAX(id) AS max FROM ' . static::tableName() . ';';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                $this->id = 1 + (int) $data[0]['max'];
            }
        }

        return $this->saveInsertTrait();
    }
}
