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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Proveedor as CoreProveedor;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\ProductoProveedor;
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
     * Código de proveedor de este documento.
     *
     * @var string
     */
    public $codproveedor;

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

    public function clear(): void
    {
        parent::clear();

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
        $where1 = [Where::eq('referencia', Tools::noHtml($reference))];
        $where2 = [Where::eq('codbarras', Tools::noHtml($reference))];
        if ($variant->loadWhere($where1) || $variant->loadWhere($where2)) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->excepcioniva = $product->excepcioniva ?? $newLine->excepcioniva;
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $variant->coste;
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            $this->setLastSupplierPrice($newLine);

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
        $this->nombre = Tools::noHtml($this->nombre);
        $this->numproveedor = Tools::noHtml($this->numproveedor);

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

    /**
     * Establece el último precio y descuentos de este proveedor.
     *
     * @param BusinessDocumentLine $newLine
     */
    protected function setLastSupplierPrice(&$newLine): void
    {
        $where = [
            Where::eq('codproveedor', $this->codproveedor),
            Where::eq('referencia', $newLine->referencia),
            Where::gt('precio', 0)
        ];
        $orderBy = ['coddivisa' => 'DESC'];
        foreach (ProductoProveedor::all($where, $orderBy) as $prod) {
            if ($prod->coddivisa === $this->coddivisa || $prod->coddivisa === null) {
                $newLine->dtopor = $prod->dtopor;
                $newLine->dtopor2 = $prod->dtopor2;
                $newLine->pvpunitario = $prod->precio;
                return;
            }
        }
    }
}
