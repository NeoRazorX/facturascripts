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
 * Propiedad de un artículos. Permite añadir propiedades a un artículo
 * sin necesidad de modificar la clase artículo.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloPropiedad
{

    use Base\ModelTrait;

    /**
     * Nombre de la propiedad
     *
     * @var string
     */
    public $name;

    /**
     * Referencia
     *
     * @var string
     */
    public $referencia;

    /**
     * Texto de la propiedad
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
        return 'articulo_propiedades';
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
     * Devuelve un array con los pares name => text para una referencia dada.
     *
     * @param string $ref
     *
     * @return array
     */
    public function arrayGet($ref)
    {
        $vlist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    /**
     * Guarda en la base de datos los pares name => text de propiedades de un artículo
     *
     * @param string $ref
     * @param array  $values
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
     * Devuelve el valor de la propiedad $name del artículo con referencia $ref
     *
     * @param string $ref
     * @param string $name
     *
     * @return bool
     */
    public function simpleGet($ref, $name)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref)
            . ' AND name = ' . $this->var2str($name) . ';';
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
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE text = ' . $this->var2str($text)
            . ' AND name = ' . $this->var2str($name) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return $data[0]['referencia'];
        }

        return false;
    }

    /**
     * Elimina una propiedad de un artículo.
     *
     * @param string $ref
     * @param string $name
     *
     * @return bool
     */
    public function simpleDelete($ref, $name)
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref)
            . ' AND name = ' . $this->var2str($name) . ';';

        return $this->dataBase->exec($sql);
    }
}
