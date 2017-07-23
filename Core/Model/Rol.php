<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <neorazorx at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
 * Define un paquete de permisos para asignar rápidamente a usuarios.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class Rol
{

    use Model;

    /**
     * TODO
     * @var string
     */
    public $codrol;

    /**
     * TODO
     * @var string
     */
    public $descripcion;

    /**
     * Rol constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'fs_roles', 'codrol');
        if (!empty($data)) {
            $this->codrol = $data['codrol'];
            $this->descripcion = $data['descripcion'];
        } else {
            $this->clear();
        }
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codrol === null) {
            return 'index.php?page=AdminRol';
        }

        return 'index.php?page=AdminRol&codrol=' . $this->codrol;
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     * Se ejecuta dentro del método save.
     * @return bool
     */
    public function test()
    {
        $this->descripcion = static::noHtml($this->descripcion);
        return true;
    }
}
