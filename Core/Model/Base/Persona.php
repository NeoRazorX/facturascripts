<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\DefaultItems;
use FacturaScripts\Core\Lib\IDFiscal;
use FacturaScripts\Core\Lib\RegimenIVA;

/**
 * Description of Persona
 *
 * @author carlos
 */
abstract class Persona
{

    /**
     * Identificador fiscal del cliente.
     * @var string
     */
    public $cifnif;

    /**
     * Empleado/agente asignado al cliente.
     * @var string
     */
    public $codagente;

    /**
     * Código identificador del cliente.
     * @var string
     */
    public $codcliente;

    /**
     * Divisa predeterminada para este cliente.
     * @var string
     */
    public $coddivisa;

    /**
     * Forma de pago predeterminada para este cliente.
     * @var string
     */
    public $codpago;

    /**
     * Código identificador del proveedor.
     * @var string
     */
    public $codproveedor;

    /**
     * Serie predeterminada para este cliente.
     * @var string
     */
    public $codserie;

    /**
     * TRUE -> el cliente ya no nos compra o no queremos nada con él.
     * @var boolean
     */
    public $debaja;

    /**
     *
     * @var DefaultItems
     */
    private static $defaultItems;

    /**
     * TODO
     * @var string
     */
    public $email;

    /**
     * Fecha en la que se dió de alta al cliente.
     * @var string
     */
    public $fechaalta;

    /**
     * Fecha en la que se dió de baja al cliente.
     * @var string
     */
    public $fechabaja;

    /**
     *
     * @var IDFiscal
     */
    private static $idFiscal;

    /**
     * Nombre por el que conocemos al cliente, no necesariamente el oficial.
     * @var string
     */
    public $nombre;

    /**
     * TODO
     * @var string
     */
    public $observaciones;

    /**
     * TRUE  -> el cliente es una persona física.
     * FALSE -> el cliente es una persona jurídica (empresa).
     * @var boolean
     */
    public $personafisica;

    /**
     * Razón social del cliente, es decir, el nombre oficial. El que aparece en las facturas.
     * @var string
     */
    public $razonsocial;

    /**
     * Régimen de fiscalidad del proveedor. Por ahora solo están implementados
     * general y exento.
     * @var string
     */
    public $regimeniva;

    /**
     *
     * @var RegimenIVA
     */
    private static $regimenIVA;

    /**
     * TODO
     * @var string
     */
    public $telefono1;

    /**
     * TODO
     * @var string
     */
    public $telefono2;

    /**
     * Tipo de identificador fiscal del cliente.
     * Ejemplos: CIF, NIF, CUIT...
     * @var string
     */
    public $tipoidfiscal;

    /**
     * TODO
     * @var string
     */
    public $web;

    public function __construct()
    {
        if (self::$defaultItems === NULL) {
            self::$defaultItems = new DefaultItems();
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
        $this->coddivisa = self::$defaultItems->codDivisa();
        $this->codpago = self::$defaultItems->codPago();
        $this->debaja = FALSE;
        $this->email = '';
        $this->fax = '';
        $this->fechaalta = date('d-m-Y');
        $this->fechabaja = NULL;
        $this->nombre = '';
        $this->personafisica = TRUE;
        $this->razonsocial = '';
        $this->regimeniva = self::$regimenIVA->defaultValue();
        $this->telefono1 = '';
        $this->telefono2 = '';
        $this->tipoidfiscal = self::$idFiscal->defaultValue();
        $this->web = '';
    }

    abstract public function getByCifnif($cifnif, $razon = '');

    abstract public function getDirecciones();

    abstract public function getSubcuentas();

    abstract public function getSubcuenta($codejercicio);

    /**
     * Acorta el texto de observaciones
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
     * @return array
     */
    public function regimenesIVA()
    {
        return self::$regimenIVA;
    }
}
