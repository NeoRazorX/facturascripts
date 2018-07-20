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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
     * Customer's city
     *
     * @var string
     */
    public $ciudad;

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
     * Customer's postal code.
     *
     * @var string
     */
    public $codpostal;

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
     * ID of contact for shippment.
     *
     * @var int
     */
    public $idcontactoenv;

    /**
     * ID of contact for invoice.
     *
     * @var int
     */
    public $idcontactofact;

    /**
     * User who created this document. User model.
     *
     * @var string
     */
    public $nick;

    /**
     * Customer name.
     *
     * @var string
     */
    public $nombrecliente;

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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->direccion = '';
        $this->porcomision = 0.0;
    }

    /**
     * Returns an array with the column for identify the subject(s),
     *
     * @return array
     */
    public function getSubjectColumns()
    {
        return ['codcliente'];
    }

    /**
     * Assign the customer to the document.
     * 
     * @param Cliente[] $subjects
     * 
     * @return boolean
     */
    public function setSubject($subjects)
    {
        if (!isset($subjects[0]->codcliente)) {
            return false;
        }

        $this->codcliente = $subjects[0]->codcliente;
        $this->nombrecliente = $subjects[0]->razonsocial;
        $this->cifnif = $subjects[0]->cifnif;
        foreach ($subjects[0]->getDirecciones() as $dir) {
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

        return true;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->apartado = Utils::noHtml($this->apartado);
        $this->ciudad = Utils::noHtml($this->ciudad);
        $this->codigoenv = Utils::noHtml($this->codigoenv);
        $this->codpostal = Utils::noHtml($this->codpostal);
        $this->direccion = Utils::noHtml($this->direccion);
        $this->nombrecliente = Utils::noHtml($this->nombrecliente);
        $this->numero2 = Utils::noHtml($this->numero2);
        $this->provincia = Utils::noHtml($this->provincia);

        return parent::test();
    }

    /**
     * Updates subjects data in this document.
     *
     * @return boolean
     */
    public function updateSubject()
    {
        if (empty($this->codcliente)) {
            return false;
        }

        $cliente = new Cliente();
        if (!$cliente->loadFromCode($this->codcliente)) {
            return false;
        }

        return $this->setSubject([$cliente]);
    }
}
