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
use FacturaScripts\Dinamic\Model\ProductoProveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of CostPriceTools
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Raul Jimenez <raljopa@gmail.com>
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
            case 'average-price':
                static::updateAveragePrice($variant);
                break;

            case 'last-price':
                static::updateLastPrice($variant);
                break;
        }
    }

    /**
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

        $variant->coste = empty($prices) ? 0.0 : \array_sum($prices) / \count($prices);
        $variant->save();
    }

    /**
     *
     * @param Variante $variant
     */
    protected static function updateLastPrice($variant)
    {
        $supplierProduct = new ProductoProveedor();
        $where = [new DataBaseWhere('referencia', $variant->referencia)];
        foreach ($supplierProduct->all($where, ['actualizado' => 'DESC'], 0, 1) as $prod) {
            $variant->coste = $prod->neto;
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

    /**
     * Return de effective cost of a referencia
     *
     * @param string $referencia
     * @return double
     */
    public function effectiveCost($variant)
    {
        $init = 0;
        $albaran = new \FacturaScripts\Dinamic\Model\AlbaranProveedor();
        $factura = new \FacturaScripts\Dinamic\Model\FacturaProveedor();

        $datos = [];
        $where = [new DataBaseWhere('referencia', $variant->referencia), new DataBaseWhere('actualizastock', '1')];
        $order = ['idlinea' => 'DESC'];
        $totalCost = 0;
        $buyedUnits = 0;
        while ($buyedUnits < $variant->stockfis) {
            $lineasAlbaran = new \FacturaScripts\Dinamic\Model\lineaAlbaranProveedor();
            $lineasFactura = new \FacturaScripts\Dinamic\Model\lineaFacturaProveedor();
            $lineasAlbaran = $lineasAlbaran->all($where, $order, $init, FS_ITEM_LIMIT);
            $lineasFactura = $lineasFactura->all($where, $order, $init, FS_ITEM_LIMIT);

            foreach ($lineasAlbaran as $linea) {
                $albaran->loadFromCode($linea->idalbaran);
                $datos[] = ['fecha' => $albaran->fecha, 'cantidad' => $linea->cantidad, 'pvpunitario' => $linea->pvpunitario];
            }

            foreach ($lineasFactura as $linea) {
                $factura->loadFromcode($linea->idfactura);
                $datos[] = ['fecha' => $factura->fecha, 'cantidad' => $linea->cantidad, 'pvpunitario' => $linea->pvpunitario];
            }

            foreach ($datos as $clave => $valor) {
                $fecha[$clave] = $valor['fecha'];
                $cantidad[$clave] = $valor['cantidad'];
                $pvpunitario[$clave] = $valor['pvpunitario'];
            }
            array_multisort($fecha, SORT_DESC, $cantidad, SORT_ASC, $pvpunitario, SORT_ASC, $datos);

            foreach ($datos as $dato) {
                if ($buyedUnits < $variant->stockfis) {
                    if ($buyedUnits + $dato['cantidad'] <= $variant->stockfis) {
                        $totalCost += $dato['pvpunitario'] * $dato['cantidad'];
                        $buyedUnits += $dato['cantidad'];
                    } else {
                        $totalCost += $dato['pvpunitario'] * ( $variant->stockfis - $buyedUnits);
                        $buyedUnits += ( $variant->stockfis - $buyedUnits);
                    }
                }
            }
            $init += FS_ITEM_LIMIT;
        }
        return $totalCost / $buyedUnits;
    }
}
