<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Proveedor as CoreProveedor;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Documento de compra.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocument extends TransformerDocument
{
    /**
     * Apartado de correos del proveedor.
     *
     * @var string
     */
    public $apartado;

    /**
     * Ciudad del proveedor.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Código del país del proveedor.
     *
     * @var string
     */
    public $codpais;

    /**
     * Código postal del proveedor.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Código de proveedor de este documento.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Dirección del proveedor.
     *
     * @var string
     */
    public $direccion;

    /**
     * Nombre del proveedor.
     *
     * @var string
     */
    public $nombre;

    /**
     * Número de documento del proveedor, si lo tiene.
     * Puede contener letras.
     *
     * @var string
     */
    public $numproveedor;

    /**
     * Provincia del proveedor.
     *
     * @var string
     */
    public $provincia;

    public function clear(): void
    {
        parent::clear();

        $this->direccion = '';

        // seleccionamos la divisa por defecto
        $coddivisa = Tools::settings('default', 'coddivisa');
        $this->setCurrency($coddivisa, true);
    }

    /**
     * Devuelve una nueva línea de documento con los datos del producto.
     * Busca el producto por referencia o código de barras.
     *
     * @param string $reference
     *
     * @return PurchaseDocumentLine
     */
    public function getNewProductLine($reference)
    {
        // pasamos como parámetro la referencia para poder distinguir en getNewLine cuando se llama desde aquí
        $newLine = $this->getNewLine(['referencia' => $reference]);
        if (empty($reference)) {
            return $newLine;
        }

        $variant = new Variante();
        if (
            $variant->loadWhereEq('referencia', Tools::noHtml($reference))
            || $variant->loadWhereEq('codbarras', Tools::noHtml($reference))
        ) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->excepcioniva = $product->excepcioniva ?? $newLine->excepcioniva;
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $variant->coste;
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            Calculator::calculateLine($this, $newLine);

            // permitimos extensiones
            $this->pipe('getNewProductLine', $newLine, $variant, $product);
        }

        return $newLine;
    }

    /**
     * @return Proveedor
     */
    public function getSubject()
    {
        $proveedor = new Proveedor();
        $proveedor->load($this->codproveedor);
        return $proveedor;
    }

    public function install(): string
    {
        // necesitamos llamar primero al padre
        $result = parent::install();

        // dependencias necesarias
        new Proveedor();

        return $result;
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

        $this->codalmacen = $user->codalmacen ?? $this->codalmacen;
        $this->codserie = $user->codserie ?? $this->codserie;
        $this->idempresa = $user->idempresa ?? $this->idempresa;
        $this->nick = $user->nick;

        // permitimos extensiones
        $this->pipe('setAuthor', $user);

        return true;
    }

    /**
     * Asigna el proveedor al documento.
     *
     * @param CoreProveedor $subject
     *
     * @return bool
     */
    public function setSubject($subject): bool
    {
        if (!isset($subject->codproveedor)) {
            return false;
        }

        // modelo de proveedor
        $this->codproveedor = $subject->codproveedor;
        $this->nombre = $subject->razonsocial;
        $this->cifnif = $subject->cifnif ?? '';

        // dirección del proveedor
        $address = $subject->getDefaultAddress();
        $this->apartado = $address->apartado;
        $this->ciudad = $address->ciudad;
        $this->codpais = $address->codpais;
        $this->codpostal = $address->codpostal;
        $this->direccion = $address->direccion;
        $this->provincia = $address->provincia;

        // datos comerciales
        if (empty($this->id())) {
            $this->codpago = $subject->codpago ?? $this->codpago;
            $this->codserie = $subject->codserie ?? $this->codserie;
            $this->irpf = $subject->irpf() ?? $this->irpf;
            $this->operacion = $subject->operacion ?? $this->operacion;
        }

        // permitimos extensiones
        $this->pipe('setSubject', $subject);

        return true;
    }

    public function subjectColumn(): string
    {
        return 'codproveedor';
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
        $this->codpostal = Tools::noHtml($this->codpostal);
        $this->direccion = Tools::noHtml($this->direccion);
        $this->nombre = Tools::noHtml($this->nombre);
        $this->numproveedor = Tools::noHtml($this->numproveedor);
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
        $proveedor = new Proveedor();
        return $this->codproveedor && $proveedor->load($this->codproveedor) && $this->setSubject($proveedor);
    }

}
