<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Description of Direccion
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait Direccion
{

    /**
     * Country of the address.
     *
     * @var string
     */
    public $codpais;

    /**
     * Post office box of the address.
     *
     * @var string
     */
    public $apartado;

    /**
     * Province of the address.
     *
     * @var string
     */
    public $provincia;

    /**
     * City of the address.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Postal code of the address.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Address.
     *
     * @var string
     */
    public $direccion;

    /**
     * Description of the address.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Date of last modification.
     *
     * @var string
     */
    public $fecha;

    /**
     * Address test
     *
     * @return bool
     */
    public function testDireccion()
    {
        $this->apartado = self::noHtml($this->apartado);
        $this->ciudad = self::noHtml($this->ciudad);
        $this->codpostal = self::noHtml($this->codpostal);
        $this->descripcion = self::noHtml($this->descripcion);
        $this->direccion = self::noHtml($this->direccion);
        $this->provincia = self::noHtml($this->provincia);
        return true;
    }
}
