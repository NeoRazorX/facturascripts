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
 * Description of Persona
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Persona
{

    /**
     * Tax identifier of the customer.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Employee / agent assigned to the customer.
     *
     * @var string
     */
    public $codagente;

    /**
     * Identifier code of the customer.
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
     * Default series for this customer.
     *
     * @var string
     */
    public $codserie;

    /**
     * True -> the customer no longer buys us or we do not want anything with him.
     *
     * @var boolean
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
     * Date on which the customer was registered.
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
     * @var boolean
     */
    public $personafisica;

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
     * Type of VAT regime
     *
     * @var RegimenIVA
     */
    private static $regimenIVA;

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
     * Devuelve la persona por cifnif
     *
     * @param string $cifnif
     * @param string $razon
     *
     * @return mixed
     */
    abstract public function getByCifnif($cifnif, $razon = '');

    /**
     * Devuelve las direcciones de la persona
     *
     * @return mixed
     */
    abstract public function getDirecciones();

    /**
     * Devuelve las subcuentas de la persona
     *
     * @return mixed
     */
    abstract public function getSubcuentas();

    /**
     * Devuelve la subcuenta de la persona para el ejercicio dado
     *
     * @param string $codejercicio
     *
     * @return mixed
     */
    abstract public function getSubcuenta($codejercicio);

    /**
     * Acorta el texto de observaciones
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
     * Devuelve un array con los regimenes de iva disponibles.
     *
     * @return RegimenIVA
     */
    public function regimenesIVA()
    {
        return self::$regimenIVA;
    }
}
