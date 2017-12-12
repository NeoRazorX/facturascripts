<?php
/**
 * This file is part of facturacion_base
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

/**
 * An attribute for articles.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Atributo
{

    use Base\ModelTrait {
        save as private traitSave;
    }

    /**
     * Primary key.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Name of the attribute
     *
     * @var string
     */
    public $nombre;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codatributo';
    }

    /**
     * Obtain the attributes of an attribute code
     *
     * @return AtributoValor[]
     */
    public function valores()
    {
        $valor0 = new AtributoValor();

        return $valor0->allFromAtributo($this->codatributo);
    }

    /**
     * Get attribute by name
     *
     * @param string $nombre
     * @param bool $minusculas
     *
     * @return Atributo|bool
     */
    public function getByNombre($nombre, $minusculas = false)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE nombre = ' . $this->dataBase->var2str($nombre) . ';';
        if ($minusculas) {
            $sql = 'SELECT * FROM ' . $this->tableName()
                . ' WHERE lower(nombre) = ' . $this->dataBase->var2str(mb_strtolower($nombre, 'UTF8') . ';');
        }

        $data = $this->dataBase->select($sql);

        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Store the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        $this->nombre = self::noHtml($this->nombre);

        return $this->saveTrait();
    }
}
