<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\FiscalNumberValidator;

/**
 * Description of Contact
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Contact extends ModelClass
{
    use GravatarTrait;

    abstract public function checkVies(): bool;

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

    /** @var string */
    public $langcode;

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
     * @var bool
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
     * Type of tax identification of the client.
     * Examples: CIF, NIF, CUIT ...
     *
     * @var string
     */
    public $tipoidfiscal;

    public function clear()
    {
        parent::clear();
        $this->fechaalta = Tools::date();
        $this->personafisica = true;
        $this->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->cifnif = Tools::noHtml($this->cifnif);
        if ($this->email !== null) {
            $this->email = Tools::noHtml(mb_strtolower($this->email, 'UTF8'));
        }
        $this->fax = Tools::noHtml($this->fax) ?? '';
        $this->nombre = Tools::noHtml($this->nombre);
        $this->observaciones = Tools::noHtml($this->observaciones) ?? '';
        $this->telefono1 = Tools::noHtml($this->telefono1) ?? '';
        $this->telefono2 = Tools::noHtml($this->telefono2) ?? '';

        $validator = new FiscalNumberValidator();
        if (!empty($this->cifnif) && false === $validator->validate($this->tipoidfiscal, $this->cifnif)) {
            Tools::log()->warning('not-valid-fiscal-number', ['%type%' => $this->tipoidfiscal, '%number%' => $this->cifnif]);
            return false;
        }

        if (empty($this->email)) {
            $this->email = '';
        } elseif (false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            Tools::log()->warning('not-valid-email', ['%email%' => $this->email]);
            $this->email = '';
            return false;
        }

        return parent::test();
    }
}
