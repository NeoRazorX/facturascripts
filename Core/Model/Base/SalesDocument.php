<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\CommissionTools;
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of SalesDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocument extends TransformerDocument
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
     * Customer's province.
     *
     * @var string
     */
    public $provincia;

    /**
     * % commission of the agent.
     *
     * @var float|int
     */
    public $totalcomision;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->direccion = '';
        $this->totalcomision = 0.0;

        /// select default currency
        $coddivisa = $this->toolBox()->appSettings()->get('default', 'coddivisa');
        $this->setCurrency($coddivisa, false);
    }

    /**
     * 
     * @return string
     */
    public function country()
    {
        $country = new Pais();
        if ($country->loadFromCode($this->codpais)) {
            return $this->toolBox()->utils()->fixHtml($country->nombre);
        }

        return $this->codpais;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if (empty($this->total)) {
            return parent::delete();
        }

        if (parent::delete()) {
            /// update customer risk
            $customer = $this->getSubject();
            $customer->riesgoalcanzado = CustomerRiskTools::getCurrent($customer->primaryColumnValue());
            $customer->save();

            return true;
        }

        return false;
    }

    /**
     * Returns a new document line with the data of the product. Finds product
     * by reference or barcode.
     *
     * @param string $reference
     *
     * @return SalesDocumentLine
     */
    public function getNewProductLine($reference)
    {
        $newLine = $this->getNewLine();
        if (empty($reference)) {
            return $newLine;
        }

        $variant = new Variante();
        $where1 = [new DataBaseWhere('referencia', $this->toolBox()->utils()->noHtml($reference))];
        $where2 = [new DataBaseWhere('codbarras', $this->toolBox()->utils()->noHtml($reference))];
        if ($variant->loadFromCode('', $where1) || $variant->loadFromCode('', $where2)) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $this->getRate()->applyTo($variant, $product);
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            /// allow extensions
            $this->pipe('getNewProductLine', $newLine, $variant, $product);
        }

        return $newLine;
    }

    /**
     * 
     * @return Tarifa
     */
    public function getRate()
    {
        $rate = new Tarifa();
        $subject = $this->getSubject();
        if ($subject->codtarifa && $rate->loadFromCode($subject->codtarifa)) {
            return $rate;
        }

        $group = new GrupoClientes();
        if ($subject->codgrupo && $group->loadFromCode($subject->codgrupo) && $group->codtarifa) {
            $rate->loadFromCode($group->codtarifa);
        }

        return $rate;
    }

    /**
     * 
     * @return Cliente
     */
    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// we need to call parent first
        $result = parent::install();

        /// needed dependencies
        new Cliente();

        return $result;
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (empty($this->total)) {
            return parent::save();
        }

        /// check if the customer has exceeded the maximum risk
        $customer = $this->getSubject();
        if ($customer->riesgomax && $customer->riesgoalcanzado > $customer->riesgomax) {
            $this->toolBox()->i18nLog()->warning('customer-reached-maximum-risk');
            return false;
        } elseif (empty($customer->primaryColumnValue())) {
            return parent::save();
        }

        if (parent::save()) {
            /// reload customer after save
            $updatedCustomer = $this->getSubject();

            /// update customer risk
            $updatedCustomer->riesgoalcanzado = CustomerRiskTools::getCurrent($updatedCustomer->primaryColumnValue());
            $updatedCustomer->save();

            return true;
        }

        return false;
    }

    /**
     * Sets the author for this document.
     * 
     * @param User $author
     *
     * @return bool
     */
    public function setAuthor($author)
    {
        if (!isset($author->nick)) {
            return false;
        }

        $this->codagente = $author->codagente ?? $this->codagente;
        $this->codalmacen = $author->codalmacen ?? $this->codalmacen;
        $this->idempresa = $author->idempresa ?? $this->idempresa;
        $this->nick = $author->nick;

        /// allow extensions
        $this->pipe('setAuthor', $author);
        return true;
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
        $return = false;
        switch ($subject->modelClassName()) {
            case 'Cliente':
                $return = $this->setCustomer($subject);
                break;

            case 'Contacto':
                $return = $this->setContact($subject);
                break;
        }

        /// allow extensions
        $this->pipe('setSubject', $subject);
        return $return;
    }

    /**
     * 
     * @return string
     */
    public function subjectColumn()
    {
        return 'codcliente';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->apartado = $utils->noHtml($this->apartado);
        $this->ciudad = $utils->noHtml($this->ciudad);
        $this->codigoenv = $utils->noHtml($this->codigoenv);
        $this->codpostal = $utils->noHtml($this->codpostal);
        $this->direccion = $utils->noHtml($this->direccion);
        $this->nombrecliente = $utils->noHtml($this->nombrecliente);
        $this->numero2 = $utils->noHtml($this->numero2);
        $this->provincia = $utils->noHtml($this->provincia);

        if (null === $this->codagente) {
            $this->totalcomision = 0.0;
        }

        return parent::test();
    }

    /**
     * Updates subjects data in this document.
     *
     * @return bool
     */
    public function updateSubject()
    {
        $cliente = new Cliente();
        return $this->codcliente && $cliente->loadFromCode($this->codcliente) ? $this->setSubject($cliente) : false;
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        /// before parent checks
        if ('codagente' === $field) {
            return $this->onChangeAgent();
        }

        if (false === parent::onChange($field)) {
            return false;
        }

        /// after parent checks
        switch ($field) {
            case 'direccion':
                $contact = new Contacto();
                /// if address is changed and customer billing address is empty, then save new values
                if ($contact->loadFromCode($this->idcontactofact) && empty($contact->direccion)) {
                    $contact->apartado = $this->apartado;
                    $contact->ciudad = $this->ciudad;
                    $contact->codpais = $this->codpais;
                    $contact->codpostal = $this->codpostal;
                    $contact->direccion = $this->direccion;
                    $contact->provincia = $this->provincia;
                    $contact->save();
                }
                break;

            case 'idcontactofact':
                $contact = new Contacto();
                /// if billing address is changed, then change all billing fields
                if ($contact->loadFromCode($this->idcontactofact)) {
                    $this->apartado = $contact->apartado;
                    $this->ciudad = $contact->ciudad;
                    $this->codpais = $contact->codpais;
                    $this->codpostal = $contact->codpostal;
                    $this->direccion = $contact->direccion;
                    $this->provincia = $contact->provincia;
                    return true;
                }
                return false;
        }

        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function onChangeAgent()
    {
        if (null !== $this->codagente && $this->total > 0) {
            $lines = $this->getLines();
            $commissions = new CommissionTools();
            $commissions->recalculate($this, $lines);
        }

        return true;
    }

    /**
     * 
     * @param Contacto $subject
     *
     * @return bool
     */
    protected function setContact($subject)
    {
        $this->apartado = $subject->apartado;
        $this->cifnif = $subject->cifnif ?? '';
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

    /**
     * 
     * @param Cliente $subject
     *
     * @return bool
     */
    protected function setCustomer($subject)
    {
        $this->cifnif = $subject->cifnif ?? '';
        $this->codcliente = $subject->codcliente;
        $this->nombrecliente = $subject->razonsocial;

        /// commercial data
        $this->codagente = $this->codagente ?? $subject->codagente;
        $this->codpago = $subject->codpago ?? $this->codpago;
        $this->codserie = $subject->codserie ?? $this->codserie;
        $this->irpf = $subject->irpf() ?? $this->irpf;

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

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['codagente', 'codcliente', 'direccion', 'idcontactofact'];
        parent::setPreviousData(\array_merge($more, $fields));
    }
}
