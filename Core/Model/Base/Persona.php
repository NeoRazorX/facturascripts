<?php
/**
 * This file is part of facturacion_base
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
     * Identificador fiscal del cliente.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Empleado/agente asignado al cliente.
     *
     * @var string
     */
    public $codagente;

    /**
     * Código identificador del cliente.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Divisa predeterminada para este cliente.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Forma de pago predeterminada para este cliente.
     *
     * @var string
     */
    public $codpago;

    /**
     * Código identificador del proveedor.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Serie predeterminada para este cliente.
     *
     * @var string
     */
    public $codserie;

    /**
     * TRUE -> el cliente ya no nos compra o no queremos nada con él.
     *
     * @var boolean
     */
    public $debaja;

    /**
     * Email de la persona.
     *
     * @var string
     */
    public $email;

    /**
     * Fax de la persona.
     *
     * @var string
     */
    public $fax;

    /**
     * Fecha en la que se dió de alta al cliente.
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Fecha en la que se dió de baja al cliente.
     *
     * @var string
     */
    public $fechabaja;

    /**
     * Tipo de identificador fiscal
     *
     * @var IDFiscal
     */
    private static $idFiscal;

    /**
     * Nombre por el que conocemos al cliente, no necesariamente el oficial.
     *
     * @var string
     */
    public $nombre;

    /**
     * Observaciones de la persona.
     *
     * @var string
     */
    public $observaciones;

    /**
     * True  -> el cliente es una persona física.
     * False -> el cliente es una persona jurídica (empresa).
     *
     * @var boolean
     */
    public $personafisica;

    /**
     * Razón social del cliente, es decir, el nombre oficial. El que aparece en las facturas.
     *
     * @var string
     */
    public $razonsocial;

    /**
     * Régimen de fiscalidad del proveedor. Por ahora solo están implementados
     * general y exento.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Tipo de régimen de IVA
     *
     * @var RegimenIVA
     */
    private static $regimenIVA;

    /**
     * Teléfono de la persona.
     *
     * @var string
     */
    public $telefono1;

    /**
     * Teléfono de la persona.
     *
     * @var string
     */
    public $telefono2;

    /**
     * Tipo de identificador fiscal del cliente.
     * Ejemplos: CIF, NIF, CUIT...
     *
     * @var string
     */
    public $tipoidfiscal;

    /**
     * Página web de la persona.
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
     * Resetea los valores de todas las propiedades modelo.
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
