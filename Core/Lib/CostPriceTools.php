<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
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
    public static function update(Variante $variant): void
    {
        if (empty($variant->id())) {
            return;
        }

        $policy = Tools::settings('default', 'costpricepolicy');

        switch ($policy) {
            case 'actual-price':
                static::updateActualPrice($variant);
                break;

            case 'average-price':
                static::updateAveragePrice($variant);
                break;

            case 'high-price':
                static::updateHighPrice($variant);
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
    protected static function updateActualPrice(Variante $variant): void
    {
        if ($variant->stockfis < 1) {
            static::updateLastPrice($variant);
            return;
        }

        $rows = [];
        $where = [
            Where::eq('referencia', $variant->referencia),
            Where::eq('actualizastock', '1'),
            Where::gt('pvptotal', 0),
        ];
        $order = ['idlinea' => 'DESC'];

        // we collect the latest delivery notes for this product
        foreach (LineaAlbaranProveedor::all($where, $order, 0, (int)$variant->stockfis) as $line) {
            $doc = $line->getDocument();
            $rows[] = [
                'time' => strtotime($doc->fecha),
                'quantity' => $line->cantidad,
                'cost' => static::toDefaultCurrency($line->pvptotal, $doc->coddivisa, $doc->tasaconv)
            ];
        }

        // we collect the latest invoices for this product
        foreach (LineaFacturaProveedor::all($where, $order, 0, (int)$variant->stockfis) as $line) {
            $doc = $line->getDocument();
            $rows[] = [
                'time' => strtotime($doc->fecha),
                'quantity' => $line->cantidad,
                'cost' => static::toDefaultCurrency($line->pvptotal, $doc->coddivisa, $doc->tasaconv)
            ];
        }

        // now we sort by date
        usort($rows, function ($item1, $item2) {
            if ($item1['time'] > $item2['time']) {
                return -1;
            } elseif ($item1['time'] < $item2['time']) {
                return 1;
            }

            return 0;
        });

        $units = 0.0;
        $totalCost = 0.0;
        foreach ($rows as $item) {
            if ($units < $variant->stockfis) {
                $totalCost += $item['cost'];
                $units += $item['quantity'];
            }
        }

        $newCost = empty($units) ? 0.0 : $totalCost / $units;
        $variant->coste = round($newCost, Producto::ROUND_DECIMALS);
        $variant->save();
    }

    /**
     * Returns the average price to buy this product.
     *
     * @param Variante $variant
     */
    protected static function updateAveragePrice(Variante $variant): void
    {
        $prices = [];
        $where = [
            Where::eq('referencia', $variant->referencia),
            Where::gt('neto', 0),
        ];
        foreach (ProductoProveedor::all($where, ['actualizado' => 'DESC'], 0, 0) as $prod) {
            $prices[] = static::supplierNet($prod);
        }

        $newCost = empty($prices) ? 0.0 : array_sum($prices) / count($prices);
        $variant->coste = round($newCost, Producto::ROUND_DECIMALS);
        $variant->save();
    }

    /**
     * Returns the last price to buy this product.
     *
     * @param Variante $variant
     */
    protected static function updateLastPrice(Variante $variant): void
    {
        $where = [
            Where::eq('referencia', $variant->referencia),
            Where::gt('neto', 0),
        ];
        foreach (ProductoProveedor::all($where, ['actualizado' => 'DESC'], 0, 1) as $prod) {
            $variant->coste = round(static::supplierNet($prod), Producto::ROUND_DECIMALS);
            $variant->save();
            break;
        }
    }

    /**
     * Returns the high price to buy this product.
     *
     * @param Variante $variant
     */
    protected static function updateHighPrice(Variante $variant): void
    {
        $where = [
            Where::eq('referencia', $variant->referencia),
            Where::gt('neto', 0),
        ];
        // los precios pueden estar en divisas distintas, así que comparamos ya convertidos
        $highPrice = null;
        $highNet = 0.0;
        foreach (ProductoProveedor::all($where, [], 0, 0) as $prod) {
            $price = static::toDefaultCurrency($prod->precio, $prod->coddivisa);
            if (null === $highPrice || $price > $highPrice) {
                $highPrice = $price;
                $highNet = static::supplierNet($prod);
            }
        }

        if (null !== $highPrice) {
            $variant->coste = round($highNet, Producto::ROUND_DECIMALS);
            $variant->save();
        }
    }

    /**
     * Devuelve el precio neto del proveedor convertido a la divisa por defecto.
     *
     * @param ProductoProveedor $prod
     *
     * @return float
     */
    protected static function supplierNet(ProductoProveedor $prod): float
    {
        return static::toDefaultCurrency($prod->neto, $prod->coddivisa);
    }

    /**
     * Convierte un importe de compra a la divisa por defecto. Si no se indica la tasa
     * de conversión, se usa la tasa de compra actual de la divisa.
     *
     * @param float $amount
     * @param string|null $coddivisa
     * @param float|null $tasaconv
     *
     * @return float
     */
    protected static function toDefaultCurrency(float $amount, ?string $coddivisa, ?float $tasaconv = null): float
    {
        $defaultCurrency = Tools::settings('default', 'coddivisa');
        if (empty($coddivisa) || $coddivisa === $defaultCurrency) {
            return $amount;
        }

        $rate = empty($tasaconv) ? Divisas::get($coddivisa)->tasaconvcompra : $tasaconv;
        $defaultRate = Divisas::get($defaultCurrency)->tasaconvcompra;
        if (empty($rate) || empty($defaultRate)) {
            return $amount;
        }

        return $amount / $rate * $defaultRate;
    }
}
