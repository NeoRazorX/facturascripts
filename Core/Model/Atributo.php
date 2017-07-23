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

use FacturaScripts\Core\Base\Model;

/**
 * Un atributo para artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Atributo
{
    use Model {
        save as private saveTrait;
    }

    /**
     * Clave primaria.
     * @var
     */
    public $codatributo;
    /**
     * TODO
     * @var
     */
    public $nombre;

    /**
     * Atributo constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'atributos', 'codatributo');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=VentasAtributos&cod=' . urlencode($this->codatributo);
    }

    /**
     * TODO
     * @return array
     */
    public function valores()
    {
        $valor0 = new AtributoValor();
        return $valor0->allFromAtributo($this->codatributo);
    }

    /**
     * TODO
     *
     * @param $nombre
     * @param bool $minusculas
     *
     * @return Atributo|bool
     */
    public function getByNombre($nombre, $minusculas = false)
    {
        if ($minusculas) {
            $sql = 'SELECT * FROM ' . $this->tableName()
                . ' WHERE lower(nombre) = ' . $this->var2str(mb_strtolower($nombre, 'UTF8') . ';');
            $data = $this->database->select($sql);
        } else {
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE nombre = ' . $this->var2str($nombre) . ';';
            $data = $this->database->select($sql);
        }

        if ($data) {
            return new Atributo($data[0]);
        }

        return false;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->nombre = static::noHtml($this->nombre);

        return $this->saveTrait();
    }
}
