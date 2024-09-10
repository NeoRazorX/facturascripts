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
use FacturaScripts\Core\Model\Proveedor as CoreProveedor;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ProductoProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of PurchaseDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocument extends TransformerDocument
{
    /**
     * Supplier code for this document.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Provider's name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Supplier's document number, if any.
     * May contain letters.
     *
     * @var string
     */
    public $numproveedor;

    public function clear()
    {
        parent::clear();

        // select default currency
        $coddivisa = Tools::settings('default', 'coddivisa');
        $this->setCurrency($coddivisa, true);
    }

    /**
     * Returns a new document line with the data of the product. Finds product
     * by reference or barcode.
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
        $where1 = [new DataBaseWhere('referencia', Tools::noHtml($reference))];
        $where2 = [new DataBaseWhere('codbarras', Tools::noHtml($reference))];
        if ($variant->loadFromCode('', $where1) || $variant->loadFromCode('', $where2)) {
            $product = $variant->getProducto();

            $newLine->codimpuesto = $product->getTax()->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $product->getTax()->iva;
            $newLine->pvpunitario = $variant->coste;
            $newLine->recargo = $product->getTax()->recargo;
            $newLine->referencia = $variant->referencia;

            $this->setLastSupplierPrice($newLine);

            // allow extensions
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
        $proveedor->loadFromCode($this->codproveedor);
        return $proveedor;
    }

    public function install(): string
    {
        // we need to call parent first
        $result = parent::install();

        // needed dependencies
        new Proveedor();

        return $result;
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

        $this->codalmacen = $user->codalmacen ?? $this->codalmacen;
        $this->idempresa = $user->idempresa ?? $this->idempresa;
        $this->nick = $user->nick;

        // allow extensions
        $this->pipe('setAuthor', $user);
        return true;
    }

    /**
     * Assign the supplier to the document.
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

        // supplier model
        $this->codproveedor = $subject->codproveedor;
        $this->nombre = $subject->razonsocial;
        $this->cifnif = $subject->cifnif ?? '';

        // commercial data
        if (empty($this->primaryColumnValue())) {
            $this->codpago = $subject->codpago ?? $this->codpago;
            $this->codserie = $subject->codserie ?? $this->codserie;
            $this->irpf = $subject->irpf() ?? $this->irpf;
        }

        // allow extensions
        $this->pipe('setSubject', $subject);
        return true;
    }

    public function subjectColumn(): string
    {
        return 'codproveedor';
    }

    /**
     * Returns True if there is no errors on properties values.
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
     * Updates subjects data in this document.
     *
     * @return bool
     */
    public function updateSubject(): bool
    {
        $proveedor = new Proveedor();
        return $this->codproveedor && $proveedor->loadFromCode($this->codproveedor) && $this->setSubject($proveedor);
    }

    /**
     * Sets the last price and discounts from this supplier.
     *
     * @param BusinessDocumentLine $newLine
     */
    protected function setLastSupplierPrice(&$newLine)
    {
        $supplierProd = new ProductoProveedor();
        $where = [
            new DataBaseWhere('codproveedor', $this->codproveedor),
            new DataBaseWhere('referencia', $newLine->referencia),
            new DataBaseWhere('precio', 0, '>')
        ];
        $orderBy = ['coddivisa' => 'DESC'];
        foreach ($supplierProd->all($where, $orderBy) as $prod) {
            if ($prod->coddivisa === $this->coddivisa || $prod->coddivisa === null) {
                $newLine->dtopor = $prod->dtopor;
                $newLine->dtopor2 = $prod->dtopor2;
                $newLine->pvpunitario = $prod->precio;
                return;
            }
        }
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['codproveedor'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
