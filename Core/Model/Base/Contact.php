<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018    Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * Description of Contact
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Contact extends ModelClass
{

    /**
     * Tax identifier of the customer.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Email of the person.
     *
     * @var string
     */
    public $email;

    /**
     * Fax of the person.
     *
     * @var string
     */
    public $fax;

    /**
     * Date on which the customer was registered.
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Name by which we know the contact, not necessarily the official.
     *
     * @var string
     */
    public $nombre;

    /**
     * Observations of the person.
     *
     * @var string
     */
    public $observaciones;

    /**
     * True -> the customer is a natural person.
     * False -> the client is a legal person (company).
     *
     * @var boolean
     */
    public $personafisica;

    /**
     * Phone 1 of the person.
     *
     * @var string
     */
    public $telefono1;

    /**
     * Phone 2 of the person.
     *
     * @var string
     */
    public $telefono2;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fechaalta = date('d-m-Y');
        $this->personafisica = true;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->cifnif = Utils::noHtml($this->cifnif);
        $this->email = Utils::noHtml($this->email);
        $this->fax = Utils::noHtml($this->fax);
        $this->nombre = Utils::noHtml($this->nombre);
        $this->observaciones = Utils::noHtml($this->observaciones);
        $this->telefono1 = Utils::noHtml($this->telefono1);
        $this->telefono2 = Utils::noHtml($this->telefono2);

        return !empty($this->nombre);
    }
}
