<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ClientePropiedad
{

    use Base\ModelTrait;

    /**
     * TODO
     * @var string
     */
    public $name;

    /**
     * TODO
     * @var string
     */
    public $codcliente;

    /**
     * TODO
     * @var string
     */
    public $text;

    /**
     * ClientePropiedad constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'cliente_propiedades', 'name');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * TODO
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE name = ' .
            $this->var2str($this->name) . ' AND codcliente = ' . $this->var2str($this->codcliente) . ';';
        return $this->dataBase->exec($sql);
    }

    /**
     * Devuelve un array con los pares name => text para una codcliente dado.
     *
     * @param string $cod
     *
     * @return array
     */
    public function arrayGet($cod)
    {
        $vlist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($cod) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    /**
     * TODO
     *
     * @param string $cod
     * @param array $values
     *
     * @return bool
     */
    public function arraySave($cod, $values)
    {
        $done = true;

        foreach ($values as $key => $value) {
            $aux = new ClientePropiedad();
            $aux->name = $key;
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
