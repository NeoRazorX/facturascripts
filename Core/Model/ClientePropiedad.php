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
 * Description of cliente_propiedad
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ClientePropiedad
{

    use Base\ModelTrait;

    /**
     * Nombre del cliente
     *
     * @var string
     */
    public $name;

    /**
     * Código del cliente
     *
     * @var string
     */
    public $codcliente;

    /**
     * Nombre de la propiedad del cliente
     *
     * @var string
     */
    public $text;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cliente_propiedades';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'name';
    }

    /**
     * Elimina la propidad del cliente de la base de datos.
     *
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
     * Guardar array de propiedades del cliente
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
