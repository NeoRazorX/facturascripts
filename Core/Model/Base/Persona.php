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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\IDFiscal;
use FacturaScripts\Core\Lib\RegimenIVA;

/**
 * Persona contains all common code for person, like customer or provider.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Persona
{

    /**
     * Tax identifier of the client.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Employee / agent assigned to the client.
     *
     * @var string
     */
    public $codagente;

    /**
     * Identifier code of the client.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Default currency for this customer.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Default payment method for this client.
     *
     * @var string
     */
    public $codpago;

    /**
     * Identifier code of the provider.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Default series for this client.
     *
     * @var string
     */
    public $codserie;

    /**
     * If client is suspended True, else False.
     *
     * @var bool
     */
    public $debaja;

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
     * Date on which the client was registered.
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Date on which the customer was discharged.
     *
     * @var string
     */
    public $fechabaja;

    /**
     * Type of fiscal identifier.
     *
     * @var IDFiscal
     */
    private static $idFiscal;

    /**
     * Name by which we know the client, not necessarily the official.
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
     * Social reason of the client, that is, the official name. The one that appears on the invoices.
     *
     * @var string
     */
    public $razonsocial;

    /**
     * Taxation regime of the provider. For now they are only implemented.
     * general and exempt.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Type of VAT regime.
     *
     * @var RegimenIVA
     */
    private static $regimenIVA;

    /**
     * Phone of the person.
     *
     * @var string
     */
    public $telefono1;

    /**
     * Phone of the person.
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

    /**
     * Website of the person.
     *
     * @var string
     */
    public $web;

    /**
     * Persona constructor.
     */
    public function __construct()
    {
        if (self::$idFiscal === null) {
            self::$idFiscal = new IDFiscal();
            self::$regimenIVA = new RegimenIVA();
        }
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->cifnif = '';
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->codpago = AppSettings::get('default', 'codpago');
        $this->debaja = false;
        $this->email = '';
        $this->fax = '';
        $this->fechaalta = date('d-m-Y');
        $this->fechabaja = null;
        $this->nombre = '';
        $this->personafisica = true;
        $this->razonsocial = '';
        $this->regimeniva = self::$regimenIVA->defaultValue();
        $this->telefono1 = '';
        $this->telefono2 = '';
        $this->tipoidfiscal = self::$idFiscal->defaultValue();
        $this->web = '';
    }

    /**
     * Returns the person by cifnif.
     *
     * @param string $cifnif
     * @param string $razon
     *
     * @return mixed
     */
    abstract public function getByCifnif($cifnif, $razon = '');

    /**
     * Returns the addresses of the person.
     *
     * @return mixed
     */
    abstract public function getDirecciones();

    /**
     * Returns the sub-accounts of the person.
     *
     * @return mixed
     */
    abstract public function getSubcuentas();

    /**
     * Returns the sub-account of the person for the given year.
     *
     * @param string $codejercicio
     *
     * @return mixed
     */
    abstract public function getSubcuenta($codejercicio);

    /**
     * Shorten the text of observations.
     *
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones === '') {
            return '-';
        }
        if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return substr($this->observaciones, 0, 50) . '...';
    }

    /**
     * Returns an array with available VAT regimes.
     *
     * @return RegimenIVA
     */
    public function regimenesIVA()
    {
        return self::$regimenIVA;
    }
}
