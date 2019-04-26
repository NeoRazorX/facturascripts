<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Pais;

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
     * Agent who created this document. Agente model.
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
     * 
     * @return string
     */
    public function country()
    {
        $country = new Pais();
        if ($country->loadFromCode($this->codpais)) {
            return Utils::fixHtml($country->nombre);
        }

        return $this->codpais;
    }

    /**
     * Assign the customer to the document.
     * 
     * @param Cliente|Contacto $subject
     * 
     * @return bool
     */
    public function setSubject($subject)
    {
        if (is_a($subject, '\\FacturaScripts\\Core\\Model\\Contacto')) {
            /// Contacto model
            $this->apartado = $subject->apartado;
            $this->cifnif = $subject->cifnif;
            $this->ciudad = $subject->ciudad;
            $this->codcliente = $subject->codcliente;
            $this->codpais = $subject->codpais;
            $this->codpostal = $subject->codpostal;
            $this->direccion = $subject->direccion;
            $this->idcontactoenv = $subject->idcontacto;
            $this->idcontactofact = $subject->idcontacto;
            $this->nombrecliente = empty($subject->empresa) ? $subject->fullName() : $subject->empresa;
            $this->provincia = $subject->provincia;
            return true;
        }

        if (is_a($subject, '\\FacturaScripts\\Core\\Model\\Cliente')) {
            /// Cliente model
            $this->cifnif = $subject->cifnif;
            $this->codcliente = $subject->codcliente;
            $this->nombrecliente = $subject->razonsocial;

            /// commercial data
            $this->codagente = $subject->codagente ?? $this->codagente;
            $this->codpago = $subject->codpago ?? $this->codpago;
            $this->codserie = $subject->codserie ?? $this->codserie;
            $this->irpf = $subject->irpf;

            /// billing address
            $billingAddress = $subject->getDefaultAddress('billing');
            $this->codpais = $billingAddress->codpais;
            $this->provincia = $billingAddress->provincia;
            $this->ciudad = $billingAddress->ciudad;
            $this->direccion = $billingAddress->direccion;
            $this->codpostal = $billingAddress->codpostal;
            $this->apartado = $billingAddress->apartado;
            $this->idcontactofact = $billingAddress->idcontacto;

            /// shipping address
            $shippingAddress = $subject->getDefaultAddress('shipping');
            $this->idcontactoenv = $shippingAddress->idcontacto;
            return true;
        }

        return false;
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
     * @return bool
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

        return $this->setSubject($cliente);
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = [
            'codalmacen', 'codcliente', 'coddivisa', 'codejercicio', 'codpago',
            'codserie', 'editable', 'fecha', 'hora', 'idempresa', 'idestado',
            'total'
        ];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
