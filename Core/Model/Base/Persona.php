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

/**
 * Description of Persona
 *
 * @author carlos
 */
trait Persona
{

    /**
     * Nombre por el que conocemos al cliente, no necesariamente el oficial.
     * @var string
     */
    public $nombre;

    /**
     * Razón social del cliente, es decir, el nombre oficial. El que aparece en las facturas.
     * @var string
     */
    public $razonsocial;

    /**
     * Tipo de identificador fiscal del cliente.
     * Ejemplos: CIF, NIF, CUIT...
     * @var string
     */
    public $tipoidfiscal;

    /**
     * Identificador fiscal del cliente.
     * @var string
     */
    public $cifnif;

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
     * TODO
     * @var string
     */
    public $email;

    /**
     * TODO
     * @var string
     */
    public $web;

    /**
     * Serie predeterminada para este cliente.
     * @var string
     */
    public $codserie;

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
     * Empleado/agente asignado al cliente.
     * @var string
     */
    public $codagente;

    /**
     * Fecha en la que se dió de alta al cliente.
     * @var string
     */
    public $fechaalta;

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
}
