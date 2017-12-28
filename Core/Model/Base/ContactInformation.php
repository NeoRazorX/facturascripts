<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * This class groups the contact data for a generic use.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
trait ContactInformation
{

    /**
     * Contact's telephone.
     *
     * @var string
     */
    public $telefono;

    /**
     * Contact's fax number.
     *
     * @var string
     */
    public $fax;

    /**
     * Contacts email.
     *
     * @var string
     */
    public $email;

    /**
     * Contact's website
     *
     * @var string
     */
    public $web;

    /**
     * Contact's address
     *
     * @var string
     */
    public $direccion;

    /**
     * Contact's postal code.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Contact's post box.
     *
     * @var string
     */
    public $apartado;

    /**
     * Contact's city.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Contact's population name.
     *
     * @var string
     */
    public $poblacion;

    /**
     * Contact's province
     *
     * @var string
     */
    public $provincia;

    /**
     * Code that represents the country where the contact is.
     *
     * @var string
     */
    public $codpais;

    /**
     * Initializes the contact's values.
     */
    private function clearContactInformation()
    {
        $this->telefono = null;
        $this->fax = null;
        $this->email = null;
        $this->web = null;
        $this->direccion = null;
        $this->codpostal = null;
        $this->apartado = null;
        $this->ciudad = null;
        $this->poblacion = null;
        $this->provincia = null;
        $this->codpais = null;
    }
}
