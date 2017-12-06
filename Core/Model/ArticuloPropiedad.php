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

/**
 * Ownership of an article
 * no need to modify the article class.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloPropiedad
{

    use Base\ModelTrait;

    /**
     * Name of the property
     *
     * @var string
     */
    public $name;

    /**
     * Reference
     *
     * @var string
     */
    public $referencia;

    /**
     * Property text
     *
     * @var string
     */
    public $text;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulo_propiedades';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'name';
    }

    /**
     * Returns an array with the name => text pairs for a given reference.
     *
     * @param string $ref
     *
     * @return array
     */
    public function arrayGet($ref)
    {
        $vlist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->dataBase->var2str($ref) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    /**
     * Save the name => text pairs of properties in an article in the database
     *
     * @param string $ref
     * @param array $values
     *
     * @return bool
     */
    public function arraySave($ref, $values)
    {
        $done = true;

        foreach ($values as $key => $value) {
            $aux = new self();
            $aux->name = $key;
            $aux->referencia = $ref;
            $aux->text = $value;
            if (!$aux->save()) {
                $done = false;
                break;
            }
        }

        return $done;
    }

    /**
     * Returns the value of the $ name property of the item with reference $ ref
     *
     * @param string $ref
     * @param string $name
     *
     * @return bool
     */
    public function simpleGet($ref, $name)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->dataBase->var2str($ref)
            . ' AND name = ' . $this->dataBase->var2str($name) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return $data[0]['text'];
        }

        return false;
    }

    /**
     * Devuelve la referencia del artículo que tenga la propiedad $name con valor $text
     *
     * @param string $name
     * @param string $text
     *
     * @return bool
     */
    public function simpleGetRef($name, $text)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE text = ' . $this->dataBase->var2str($text)
            . ' AND name = ' . $this->dataBase->var2str($name) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return $data[0]['referencia'];
        }

        return false;
    }

    /**
     * Remove a property from an article.
     *
     * @param string $ref
     * @param string $name
     *
     * @return bool
     */
    public function simpleDelete($ref, $name)
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->dataBase->var2str($ref)
            . ' AND name = ' . $this->dataBase->var2str($name) . ';';

        return $this->dataBase->exec($sql);
    }
}
