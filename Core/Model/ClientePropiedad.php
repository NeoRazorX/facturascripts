<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of cliente_propiedad
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ClientePropiedad
{

    use Base\ModelTrait;

    /**
     * Customer name.
     *
     * @var string
     */
    public $name;

    /**
     * Customer code.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Name of the client's property.
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
        return 'cliente_propiedades';
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
     * Remove the client property from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE name = ' .
            self::$dataBase->var2str($this->name) . ' AND codcliente = ' . self::$dataBase->var2str($this->codcliente) . ';';

        return self::$dataBase->exec($sql);
    }

    /**
     * Returns an array with the name => text pairs for a given client.
     *
     * @param string $cod
     *
     * @return array
     */
    public function arrayGet($cod)
    {
        $vlist = [];

        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codcliente = ' . self::$dataBase->var2str($cod) . ';';
        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    /**
     * Save array of client properties.
     *
     * @param string $cod
     * @param array  $values
     *
     * @return bool
     */
    public function arraySave($cod, $values)
    {
        $done = true;

        foreach ($values as $key => $value) {
            $aux = new self();
            $aux->name = (string) $key;
            $aux->codcliente = $cod;
            $aux->text = $value;
            if (!$aux->save()) {
                $done = false;
                break;
            }
        }

        return $done;
    }
}
