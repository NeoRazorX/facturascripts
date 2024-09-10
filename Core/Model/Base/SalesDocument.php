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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cliente as CoreCliente;
use FacturaScripts\Core\Model\Contacto as CoreContacto;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of SalesDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocument extends TransformerDocument
{
    /**
     * Mailbox of the client.
     *
     * @var string
     */
    public $apartado;

    /**
     * Customer's city.
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
     * sum total of the benefits of the lines.
     *
     * @var float
     */
    public $totalbeneficio;

    /**
     * total sum of the costs of the lines.
     *
     * @var float
     */
    public $totalcoste;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->direccion = '';
        $this->totalbeneficio = 0.0;
        $this->totalcoste = 0.0;

        // select default currency
        $coddivisa = Tools::settings('default', 'coddivisa');
        $this->setCurrency($coddivisa, false);
    }

    public function country(): string
    {
        $country = new Pais();
        if ($country->loadFromCode($this->codpais)) {
            return Tools::fixHtml($country->nombre) ?? '';
        }

        return $this->codpais ?? '';
    }

    public function delete(): bool
    {
        if (empty($this->total)) {
            return parent::delete();
        }

        if (parent::delete()) {
            // update customer risk
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
        // pasamos la referencia como parámetro para poder distinguir en getNewLine cuando se llama desde aquí
        $newLine = $this->getNewLine(['referencia' => $reference]);
        if (empty($reference)) {
            return $newLine;
        }

        $variant = new Variante();
        $where1 = [new DataBaseWhere('referencia', Tools::noHtml($reference))];
        $where2 = [new DataBaseWhere('codbarras', Tools::noHtml($reference))];
        if ($variant->loadFromCode('', $where1) || $variant->loadFromCode('', $where2)) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->coste = $variant->coste;
            $newLine->descripcion = $variant->description();
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $this->getRate()->applyTo($variant, $product);
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            // allow extensions
            $this->pipe('getNewProductLine', $newLine, $variant, $product);
        }

        return $newLine;
    }

    public function getRate(): Tarifa
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
     * @return Cliente
     */
    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    public function install(): string
    {
        // we need to call parent first
        $result = parent::install();

        // needed dependencies
        new AgenciaTransporte();
        new Agente();
        new Cliente();

        return $result;
    }

    public function save(): bool
    {
        if (empty($this->total)) {
            return parent::save();
        }

        // check if the customer has exceeded the maximum risk
        $customer = $this->getSubject();
        if ($customer->riesgomax && $customer->riesgoalcanzado > $customer->riesgomax) {
            Tools::log()->warning('customer-reached-maximum-risk');
            return false;
        } elseif (empty($customer->primaryColumnValue())) {
            return parent::save();
        }

        if (parent::save()) {
            // reload customer after save
            $updatedCustomer = $this->getSubject();

            // update customer risk
            $updatedCustomer->riesgoalcanzado = CustomerRiskTools::getCurrent($updatedCustomer->primaryColumnValue());
            $updatedCustomer->save();
            return true;
        }

        return false;
    }

    /**
     * Sets the author for this document.
     *
     * @param User $user
     *
     * @return bool
     */
    public function setAuthor($user): bool
    {
        if (!isset($user->nick)) {
            return false;
        }

        $this->codagente = $user->codagente ?? $this->codagente;
        $this->codalmacen = $user->codalmacen ?? $this->codalmacen;
        $this->idempresa = $user->idempresa ?? $this->idempresa;
        $this->nick = $user->nick;

        // allow extensions
        $this->pipe('setAuthor', $user);
        return true;
    }

    /**
     * Assign the customer to the document.
     *
     * @param CoreCliente|CoreContacto $subject
     *
     * @return bool
     */
    public function setSubject($subject): bool
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

        // allow extensions
        $this->pipe('setSubject', $subject);
        return $return;
    }

    public function subjectColumn(): string
    {
        return 'codcliente';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->apartado = Tools::noHtml($this->apartado);
        $this->ciudad = Tools::noHtml($this->ciudad);
        $this->codigoenv = Tools::noHtml($this->codigoenv);
        $this->codpostal = Tools::noHtml($this->codpostal);
        $this->direccion = Tools::noHtml($this->direccion);
        $this->nombrecliente = Tools::noHtml($this->nombrecliente);
        $this->numero2 = Tools::noHtml($this->numero2);
        $this->provincia = Tools::noHtml($this->provincia);

        return parent::test();
    }

    /**
     * Updates subjects data in this document.
     *
     * @return bool
     */
    public function updateSubject(): bool
    {
        $cliente = new Cliente();
        return $this->codcliente && $cliente->loadFromCode($this->codcliente) && $this->setSubject($cliente);
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        if (empty($this->idcontactofact)) {
            return true;
        }

        // after parent checks
        $contact = new Contacto();
        switch ($field) {
            case 'direccion':
                // if address is changed and customer billing address is empty, then save new values
                if ($this->direccion && $contact->loadFromCode($this->idcontactofact) && empty($contact->direccion)) {
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
                // if billing address is changed, then change all billing fields
                if ($contact->loadFromCode($this->idcontactofact)) {
                    $this->apartado = $contact->apartado;
                    $this->ciudad = $contact->ciudad;
                    $this->codpais = $contact->codpais;
                    $this->codpostal = $contact->codpostal;
                    $this->direccion = $contact->direccion;
                    $this->provincia = $contact->provincia;
                    break;
                }
                return false;
        }

        return true;
    }

    protected function onInsert()
    {
        // if billing address is empty, then save new values
        $contact = new Contacto();
        if ($this->direccion && $contact->loadFromCode($this->idcontactofact) && empty($contact->direccion)) {
            $contact->apartado = $this->apartado;
            $contact->ciudad = $this->ciudad;
            $contact->codpais = $this->codpais;
            $contact->codpostal = $this->codpostal;
            $contact->direccion = $this->direccion;
            $contact->provincia = $this->provincia;
            $contact->save();
        }

        parent::onInsert();
    }

    /**
     * This method is called after a record is updated on the database (saveUpdate).
     */
    protected function onUpdate()
    {
        if ($this->previousData['codcliente'] !== $this->codcliente) {
            $customer = new Cliente();
            if ($customer->loadFromCode($this->previousData['codcliente'])) {
                $customer->riesgoalcanzado = CustomerRiskTools::getCurrent($customer->primaryColumnValue());
                $customer->save();
            }
        }
        parent::onUpdate();
    }

    /**
     * @param CoreContacto $subject
     *
     * @return bool
     */
    protected function setContact($subject): bool
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
     * @param CoreCliente $subject
     *
     * @return bool
     */
    protected function setCustomer($subject): bool
    {
        $this->cifnif = $subject->cifnif ?? '';
        $this->codcliente = $subject->codcliente;
        $this->nombrecliente = $subject->razonsocial;

        // commercial data
        if (empty($this->primaryColumnValue())) {
            $this->codagente = $this->codagente ?? $subject->codagente;
            $this->codpago = $subject->codpago ?? $this->codpago;
            $this->codserie = $subject->codserie ?? $this->codserie;
            $this->irpf = $subject->irpf() ?? $this->irpf;
        }

        // billing address
        $billingAddress = $subject->getDefaultAddress('billing');
        $this->codpais = $billingAddress->codpais;
        $this->provincia = $billingAddress->provincia;
        $this->ciudad = $billingAddress->ciudad;
        $this->direccion = $billingAddress->direccion;
        $this->codpostal = $billingAddress->codpostal;
        $this->apartado = $billingAddress->apartado;
        $this->idcontactofact = $billingAddress->idcontacto;

        // shipping address
        $this->idcontactoenv = $subject->getDefaultAddress('shipping')->idcontacto;
        return true;
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['codagente', 'codcliente', 'direccion', 'idcontactofact'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
