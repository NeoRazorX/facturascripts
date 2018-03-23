<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Description of SalesDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocument extends BusinessDocument
{

    /**
     * Mail box of the client.
     *
     * @var string
     */
    public $apartado;

    /**
     * Post box of the shipping address.
     *
     * @var string
     */
    public $apartadoenv;

    /**
     * Last name of the shipping address.
     *
     * @var string
     */
    public $apellidosenv;

    /**
     * Customer's city
     *
     * @var string
     */
    public $ciudad;

    /**
     * City of the shipping address.
     *
     * @var string
     */
    public $ciudadenv;

    /**
     * Employee who created this document. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Customer of this document.
     *
     * @var string
     */
    public $codcliente;

    /**
     * ID of the customer's address. Customer_address model.
     *
     * @var int
     */
    public $coddir;

    /**
     * Shipping tracking code.
     *
     * @var string
     */
    public $codigoenv;

    /**
     * Customer's country.
     *
     * @var string
     */
    public $codpais;

    /**
     * Country code of the shipping address.
     *
     * @var string
     */
    public $codpaisenv;

    /**
     * Customer's postal code.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Postal code of the shipping address.
     *
     * @var string
     */
    public $codpostalenv;

    /**
     * Shipping code for the shipment.
     *
     * @var string
     */
    public $codtrans;

    /**
     * Customer's address
     *
     * @var string
     */
    public $direccion;

    /**
     * Address of the shipping address.
     *
     * @var string
     */
    public $direccionenv;

    /**
     * Customer name.
     *
     * @var string
     */
    public $nombrecliente;

    /**
     * Name of the shipping address.
     *
     * @var string
     */
    public $nombreenv;

    /**
     * Optional number available to the user.
     *
     * @var string
     */
    public $numero2;

    /**
     * % commission of the employee.
     *
     * @var float|int
     */
    public $porcomision;

    /**
     * Customer's province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Province of the shipping address.
     *
     * @var string
     */
    public $provinciaenv;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->direccion = '';
        $this->porcomision = 0.0;
    }
    
    public function getSubjectColumns()
    {
        return ['codcliente'];
    }

    /**
     * Assign the customer to the document.
     *
     * @param Cliente[] $subjects
     */
    public function setSubject($subjects)
    {
        if(!isset($subjects[0]->codcliente)) {
            return;
        }
        
        $this->codcliente = $subjects[0]->codcliente;
        $this->nombrecliente = $subjects[0]->razonsocial;
        $this->cifnif = $subjects[0]->cifnif;
        foreach ($subjects[0]->getDirecciones() as $dir) {
            $this->coddir = $dir->id;
            $this->codpais = $dir->codpais;
            $this->provincia = $dir->provincia;
            $this->ciudad = $dir->ciudad;
            $this->direccion = $dir->direccion;
            $this->codpostal = $dir->codpostal;
            $this->apartado = $dir->apartado;
            if ($dir->domfacturacion) {
                break;
            }
        }
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->apartado = Utils::noHtml($this->apartado);
        $this->apartadoenv = Utils::noHtml($this->apartadoenv);
        $this->apellidosenv = Utils::noHtml($this->apellidosenv);
        $this->ciudad = Utils::noHtml($this->ciudad);
        $this->ciudadenv = Utils::noHtml($this->ciudadenv);
        $this->codigoenv = Utils::noHtml($this->codigoenv);
        $this->codpostal = Utils::noHtml($this->codpostal);
        $this->codpostalenv = Utils::noHtml($this->codpostalenv);
        $this->direccion = Utils::noHtml($this->direccion);
        $this->direccionenv = Utils::noHtml($this->direccionenv);
        $this->nombrecliente = Utils::noHtml($this->nombrecliente);
        $this->nombreenv = Utils::noHtml($this->nombreenv);
        $this->numero2 = Utils::noHtml($this->numero2);
        $this->provincia = Utils::noHtml($this->provincia);
        $this->provinciaenv = Utils::noHtml($this->provinciaenv);

        return parent::test();
    }
}
