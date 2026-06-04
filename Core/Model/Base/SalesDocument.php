<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Cliente as CoreCliente;
use FacturaScripts\Core\Model\Contacto as CoreContacto;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
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
 * Documento de venta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocument extends TransformerDocument
{
    /**
     * Apartado de correos del cliente.
     *
     * @var string
     */
    public $apartado;

    /**
     * Ciudad del cliente.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Agente que creó este documento. Modelo Agente.
     *
     * @var string
     */
    public $codagente;

    /**
     * Cliente de este documento.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Código de seguimiento del envío.
     *
     * @var string
     */
    public $codigoenv;

    /**
     * País del cliente.
     *
     * @var string
     */
    public $codpais;

    /**
     * Código postal del cliente.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Código de transporte del envío.
     *
     * @var string
     */
    public $codtrans;

    /**
     * Dirección del cliente.
     *
     * @var string
     */
    public $direccion;

    /**
     * ID del contacto de envío.
     *
     * @var int
     */
    public $idcontactoenv;

    /**
     * ID del contacto de facturación.
     *
     * @var int
     */
    public $idcontactofact;

    /**
     * Nombre del cliente.
     *
     * @var string
     */
    public $nombrecliente;

    /**
     * Número opcional disponible para el usuario.
     *
     * @var string
     */
    public $numero2;

    /**
     * Provincia del cliente.
     *
     * @var string
     */
    public $provincia;

    /**
     * Suma total del beneficio de las líneas.
     *
     * @var float
     */
    public $totalbeneficio;

    /**
     * Suma total del coste de las líneas.
     *
     * @var float
     */
    public $totalcoste;

    /**
     * Restablece los valores de todas las propiedades del modelo.
     */
    public function clear(): void
    {
        parent::clear();

        $this->direccion = '';
        $this->totalbeneficio = 0.0;
        $this->totalcoste = 0.0;

        // seleccionamos la divisa por defecto
        $coddivisa = Tools::settings('default', 'coddivisa');
        $this->setCurrency($coddivisa, false);
    }

    public function country(): string
    {
        $country = new Pais();
        if ($country->load($this->codpais)) {
            return Tools::fixHtml($country->nombre) ?? '';
        }

        return $this->codpais ?? '';
    }

    public function delete(): bool
    {
        if (empty($this->total)) {
            return parent::delete();
        }

        if (false === parent::delete()) {
            return false;
        }

        if (!empty($this->codcliente)) {
            // actualizamos el riesgo del cliente
            $customer = $this->getSubject();
            $customer->riesgoalcanzado = empty($customer->id()) ?
                0.00 :
                CustomerRiskTools::getCurrent($customer->id());
            $customer->save();
        }

        return true;
    }

    /**
     * Devuelve una nueva línea de documento con los datos del producto.
     * Busca el producto por referencia o código de barras.
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
        $where1 = [Where::eq('referencia', Tools::noHtml($reference))];
        $where2 = [Where::eq('codbarras', Tools::noHtml($reference))];
        if ($variant->loadWhere($where1) || $variant->loadWhere($where2)) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->coste = $variant->coste;
            $newLine->descripcion = $variant->description();
            $newLine->excepcioniva = $product->excepcioniva ?? $newLine->excepcioniva;
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $this->getRate()->applyTo($variant, $product);
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            Calculator::calculateLine($this, $newLine);

            // permitimos extensiones
            $this->pipe('getNewProductLine', $newLine, $variant, $product);
        }

        return $newLine;
    }

    public function getRate(): Tarifa
    {
        $rate = new Tarifa();
        $subject = $this->getSubject();
        if ($subject->codtarifa && $rate->load($subject->codtarifa)) {
            return $rate;
        }

        $group = new GrupoClientes();
        if ($subject->codgrupo && $group->load($subject->codgrupo) && $group->codtarifa) {
            $rate->load($group->codtarifa);
        }

        return $rate;
    }

    /**
     * @return Cliente
     */
    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->load($this->codcliente);
        return $cliente;
    }

    public function install(): string
    {
        // necesitamos llamar primero al padre
        $result = parent::install();

        // dependencias necesarias
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

        // comprobamos si el cliente ha superado el riesgo máximo
        $customer = $this->getSubject();
        if ($customer->riesgomax && $customer->riesgoalcanzado > $customer->riesgomax) {
            Tools::log()->warning('customer-reached-maximum-risk');
            return false;
        } elseif (empty($customer->id())) {
            return parent::save();
        }

        if (false === parent::save()) {
            return false;
        }

        // recargamos el cliente después de guardar
        $updatedCustomer = $this->getSubject();
        if ($updatedCustomer->id() !== null) {
            // actualizamos el riesgo del cliente
            $updatedCustomer->riesgoalcanzado = CustomerRiskTools::getCurrent($updatedCustomer->id());
            $updatedCustomer->save();
        }

        return true;
    }

    /**
     * Establece el autor de este documento.
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
        $this->codserie = $user->codserie ?? $this->codserie;
        $this->idempresa = $user->idempresa ?? $this->idempresa;
        $this->nick = $user->nick;

        // permitimos extensiones
        $this->pipe('setAuthor', $user);

        return true;
    }

    /**
     * Asigna el cliente al documento.
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

        // permitimos extensiones
        $this->pipe('setSubject', $subject);

        return $return;
    }

    public function subjectColumn(): string
    {
        return 'codcliente';
    }

    /**
     * Devuelve True si no hay errores en los valores de las propiedades.
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
     * Actualiza los datos del sujeto en este documento.
     *
     * @return bool
     */
    public function updateSubject(): bool
    {
        $cliente = new Cliente();
        return $this->codcliente && $cliente->load($this->codcliente) && $this->setSubject($cliente);
    }

    protected function onChange(string $field): bool
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        if (empty($this->idcontactofact)) {
            return true;
        }

        // después de las comprobaciones del padre
        $contact = new Contacto();
        switch ($field) {
            case 'direccion':
                // si la dirección cambia y la dirección de facturación del cliente está vacía, guardamos los nuevos valores
                if ($this->direccion && $contact->load($this->idcontactofact) && empty($contact->direccion)) {
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
                // si cambia la dirección de facturación, cambiamos todos los campos de facturación
                if ($contact->load($this->idcontactofact)) {
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

    protected function onInsert(): void
    {
        // si la dirección de facturación está vacía, guardamos los nuevos valores
        $contact = new Contacto();
        if ($this->direccion && $contact->load($this->idcontactofact) && empty($contact->direccion)) {
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
     * Este método se ejecuta después de actualizar un registro en la base de datos (saveUpdate).
     */
    protected function onUpdate(): void
    {
        if ($this->isDirty('codcliente')) {
            $customer = new Cliente();
            if ($customer->load($this->getOriginal('codcliente'))) {
                $customer->riesgoalcanzado = CustomerRiskTools::getCurrent($customer->id());
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

        // datos comerciales
        if (empty($this->id())) {
            $this->codagente = $this->codagente ?? $subject->codagente;
            $this->codpago = $subject->codpago ?? $this->codpago;
            $this->codserie = $subject->codserie ?? $this->codserie;
            $this->irpf = $subject->irpf() ?? $this->irpf;
            $this->operacion = $subject->operacion ?? $this->operacion;
        }

        // dirección de facturación
        $billingAddress = $subject->getDefaultAddress('billing');
        $this->codpais = $billingAddress->codpais;
        $this->provincia = $billingAddress->provincia;
        $this->ciudad = $billingAddress->ciudad;
        $this->direccion = $billingAddress->direccion;
        $this->codpostal = $billingAddress->codpostal;
        $this->apartado = $billingAddress->apartado;
        $this->idcontactofact = $billingAddress->idcontacto;

        // dirección de envío
        $this->idcontactoenv = $subject->getDefaultAddress('shipping')->idcontacto;

        return true;
    }
}
