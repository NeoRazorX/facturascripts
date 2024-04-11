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
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Description of ComercialContact
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ComercialContact extends Contact
{
    /**
     * Identifier code of the customer.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Default payment method for this customer.
     *
     * @var string
     */
    public $codpago;

    /**
     * Identifier code of the supplier.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Identifier code of the retention applied to this contact.
     *
     * @var string
     */
    public $codretencion;

    /**
     * Default series for this customer.
     *
     * @var string
     */
    public $codserie;

    /**
     * Accounting code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * True -> the customer no longer buys us or we do not want anything with him.
     *
     * @var bool
     */
    public $debaja;

    /**
     * Date on which the customer was discharged.
     *
     * @var string
     */
    public $fechabaja;

    /**
     * Social reason of the client, that is, the official name. The one that appears on the invoices.
     *
     * @var string
     */
    public $razonsocial;

    /**
     * Taxation regime of the provider. For now they are only implemented general and exempt.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Website of the person.
     *
     * @var string
     */
    public $web;

    /**
     * Return address from this contact.
     *
     * @return mixed
     */
    abstract public function getAddresses(): array;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->debaja = false;
        $this->regimeniva = RegimenIVA::defaultValue();
    }

    public function install(): string
    {
        // needed dependencies
        new Retencion();
        new Serie();
        new FormaPago();

        return parent::install();
    }

    /**
     * Returns default contact retention value.
     *
     * @return float
     */
    public function irpf()
    {
        if (empty($this->codretencion)) {
            return 0.0;
        }

        $retention = new Retencion();
        if ($retention->loadFromCode($this->codretencion)) {
            return $retention->porcentaje;
        }

        return 0.0;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->debaja = !empty($this->fechabaja);

        $this->razonsocial = Tools::noHtml($this->razonsocial);
        if (empty($this->razonsocial)) {
            $this->razonsocial = $this->nombre;
        }

        $this->web = Tools::noHtml($this->web);
        // check if the web is a valid url
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->warning('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test();
    }
}
