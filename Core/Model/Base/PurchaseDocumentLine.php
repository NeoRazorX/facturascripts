<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\ProductoProveedor;

/**
 * Description of PurchaseDocumentLine
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class PurchaseDocumentLine extends BusinessDocumentLine
{

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if (parent::save()) {
            $this->updateSupplierProduct();
            return true;
        }

        return false;
    }

    protected function updateSupplierProduct()
    {
        if (empty($this->referencia) ||
            $this->cantidad <= 0 ||
            $this->pvpunitario <= 0 ||
            false === $this->toolBox()->appSettings()->get('default', 'updatesupplierprices')) {
            return;
        }

        $doc = $this->getDocument();

        $product = new ProductoProveedor();
        $where = [
            new DataBaseWhere('codproveedor', $doc->codproveedor),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $product->loadFromCode('', $where) ||
            \strtotime($product->actualizado) <= \strtotime($doc->fecha . ' ' . $doc->hora)) {
            $product->actualizado = \date(self::DATETIME_STYLE, \strtotime($doc->fecha . ' ' . $doc->hora));
            $product->coddivisa = $doc->coddivisa;
            $product->codproveedor = $doc->codproveedor;
            $product->dtopor = $this->dtopor;
            $product->dtopor2 = $this->dtopor2;
            $product->idproducto = $this->idproducto;
            $product->precio = $this->pvpunitario;
            $product->referencia = $this->referencia;
            $product->save();
        }
    }
}
