<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\LineaAlbaranProveedor;
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ProductoProveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of CostPriceTools
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <raljopa@gmail.com>
 */
class CostPriceTools
{

    /**
     *
     * @param Variante $variant
     */
    public static function update($variant)
    {
        $policy = static::toolBox()->appSettings()->get('default', 'costpricepolicy');
        switch ($policy) {
            case 'actual-price':
                static::updateActualPrice($variant);
                break;

            case 'average-price':
                static::updateAveragePrice($variant);
                break;

            case 'last-price':
                static::updateLastPrice($variant);
                break;
        }
    }

    /**
     * Returns the actual cost of the product stock.
     *
     * @param Variante $variant
     */
    protected static function updateActualPrice($variant)
    {
        if ($variant->stockfis < 1) {
            static::updateLastPrice($variant);
            return;
        }

        $rows = [];
        $where = [
            new DataBaseWhere('referencia', $variant->referencia),
            new DataBaseWhere('actualizastock', '1')
        ];
        $order = ['idlinea' => 'DESC'];

        /// we collect the latest delivery notes for this product
        $lineaAlbaran = new LineaAlbaranProveedor();
        foreach ($lineaAlbaran->all($where, $order, 0, (int) $variant->stockfis) as $line) {
            $rows[] = [
                'time' => \strtotime($line->getDocument()->fecha),
                'quantity' => $line->cantidad,
                'cost' => $line->pvptotal
            ];
        }

        /// we collect the latest invoices for this product
        $lineaFactura = new LineaFacturaProveedor();
        foreach ($lineaFactura->all($where, $order, 0, (int) $variant->stockfis) as $line) {
            $rows[] = [
                'time' => \strtotime($line->getDocument()->fecha),
                'quantity' => $line->cantidad,
                'cost' => $line->pvptotal
            ];
        }

        /// now we sort by date
        \usort($rows, function ($item1, $item2) {
            if ($item1['time'] > $item2['time']) {
                return -1;
            } elseif ($item1['time'] < $item2['time']) {
                return 1;
            }

            return 0;
        });

        $buyedUnits = 0.0;
        $totalCost = 0.0;
        foreach ($rows as $item) {
            if ($buyedUnits < $variant->stockfis) {
                $totalCost += $item['cost'];
                $buyedUnits += $item['quantity'];
            }
        }

        $newCost = empty($buyedUnits) ? 0.0 : $totalCost / $buyedUnits;
        $variant->coste = \round($newCost, Producto::ROUND_DECIMALS);
        $variant->save();
    }

    /**
     * Returns the average price to buy this product.
     *
     * @param Variante $variant
     */
    protected static function updateAveragePrice($variant)
    {
        $prices = [];
        $supplierProduct = new ProductoProveedor();
        $where = [new DataBaseWhere('referencia', $variant->referencia)];
        foreach ($supplierProduct->all($where, ['actualizado' => 'DESC'], 0, 0) as $prod) {
            $prices[] = $prod->neto;
        }

        $newCost = empty($prices) ? 0.0 : \array_sum($prices) / \count($prices);
        $variant->coste = \round($newCost, Producto::ROUND_DECIMALS);
        $variant->save();
    }

    /**
     * Returns the last price to buy this product.
     *
     * @param Variante $variant
     */
    protected static function updateLastPrice($variant)
    {
        $supplierProduct = new ProductoProveedor();
        $where = [new DataBaseWhere('referencia', $variant->referencia)];
        foreach ($supplierProduct->all($where, ['actualizado' => 'DESC'], 0, 1) as $prod) {
            $variant->coste = \round($prod->neto, Producto::ROUND_DECIMALS);
            $variant->save();
            break;
        }
    }

    /**
     *
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
