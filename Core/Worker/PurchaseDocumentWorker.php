<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\ProductoProveedor;

class PurchaseDocumentWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // cargamos el tipo de documento
        if ($event->name === 'Model.AlbaranProveedor.Update') {
            $doc = new AlbaranProveedor();
        } elseif ($event->name === 'Model.FacturaProveedor.Update') {
            $doc = new FacturaProveedor();
        } elseif ($event->name === 'Model.PedidoProveedor.Update') {
            $doc = new PedidoProveedor();
        } elseif ($event->name === 'Model.PresupuestoProveedor.Update') {
            $doc = new PresupuestoProveedor();
        } else {
            return $this->done();
        }

        // cargamos el documento
        if (false === $doc->loadFromCode($event->value)) {
            return $this->done();
        }
        
        // recorremos las líneas del documento
        foreach ($doc->getLines() as $line) {
            if (empty($line->referencia) ||
                $line->cantidad <= 0 ||
                $line->pvpunitario <= 0 ||
                false === Tools::settings('default', 'updatesupplierprices')) {
                continue;
            }

            // buscamos el producto del proveedor
            $product = new ProductoProveedor();
            $where = [
                new DataBaseWhere('codproveedor', $doc->codproveedor),
                new DataBaseWhere('referencia', $line->referencia),
                new DataBaseWhere('coddivisa', $doc->coddivisa)
            ];
            if (false === $product->loadFromCode('', $where) ||
                strtotime($product->actualizado) <= strtotime($doc->fecha . ' ' . $doc->hora)) {
                $product->actualizado = Tools::dateTime($doc->fecha . ' ' . $doc->hora);
                $product->coddivisa = $doc->coddivisa;
                $product->codproveedor = $doc->codproveedor;
                $product->dtopor = $line->dtopor;
                $product->dtopor2 = $line->dtopor2;
                $product->idproducto = $line->idproducto;
                $product->precio = $line->pvpunitario;
                $product->referencia = $line->referencia;
                $product->save();
            }
        }

        return $this->done();
    }
}
