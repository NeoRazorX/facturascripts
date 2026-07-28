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

namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Core\Template\JoinModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Familia;

/**
 * Modelo auxiliar para obtener las subcuentas de compra de las líneas de los
 * documentos de compra. Agrupa las líneas de la factura de proveedor (excluyendo
 * suplidos) por la subcuenta de compras del producto y por familia, para poder
 * repartir el neto entre las subcuentas de gasto al generar el asiento contable.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @property string $codfamilia
 * @property string $codsubcuenta
 * @property float $total
 */
class PurchasesDocLineAccount extends JoinModel
{
    /**
     * Obtiene los totales del documento de compra agrupados por subcuenta.
     * Para cada grupo de líneas toma la subcuenta de compras del producto,
     * si no la de la familia, y en último caso la subcuenta por defecto indicada.
     *
     * @param FacturaProveedor $document
     * @param string $defaultSubacode subcuenta a usar cuando ni el producto ni la familia definen una
     * @return array totales indexados por código de subcuenta
     */
    public function getTotalsForDocument($document, string $defaultSubacode): array
    {
        $totals = [];
        $where = [
            Where::eq('lineasfacturasprov.idfactura', $document->idfactura),
            Where::eq('lineasfacturasprov.suplido', false),
        ];
        $order = [
            "COALESCE(productos.codsubcuentacom, '')" => 'ASC',
            "COALESCE(productos.codfamilia, '')" => 'ASC'
        ];
        foreach (static::all($where, $order) as $row) {
            $codSubAccount = empty($row->codsubcuenta) ? Familia::purchaseSubAccount($row->codfamilia) : $row->codsubcuenta;
            if (empty($codSubAccount)) {
                $codSubAccount = $defaultSubacode;
            }

            $amount = $row->total * $document->getEUDiscount();
            $totals[$codSubAccount] = isset($totals[$codSubAccount]) ? $totals[$codSubAccount] + $amount : $amount;
        }

        return $this->checkTotals($totals, $document, $defaultSubacode);
    }

    /**
     * Redondea los totales y comprueba que su suma coincide con el neto del
     * documento. Si por los redondeos hay algún céntimo de diferencia,
     * lo añade a la subcuenta por defecto para que el asiento cuadre.
     */
    protected function checkTotals(array &$totals, $document, string $defaultSubacode): array
    {
        // redondeamos y sumamos los totales
        $sum = 0.0;
        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, FS_NF0);
            $sum += $totals[$key];
        }

        // corregimos el posible descuadre de céntimos
        if (!Tools::floatCmp($document->neto, $sum, FS_NF0, true)) {
            $diff = round($document->neto - $sum, FS_NF0);
            $totals[$defaultSubacode] = isset($totals[$defaultSubacode]) ? $totals[$defaultSubacode] + $diff : $diff;
        }

        return $totals;
    }

    protected function getFields(): array
    {
        return [
            'idfactura' => 'lineasfacturasprov.idfactura',
            'codsubcuenta' => "COALESCE(productos.codsubcuentacom, '')",
            'codfamilia' => "COALESCE(productos.codfamilia, '')",
            'total' => 'SUM(lineasfacturasprov.pvptotal)'
        ];
    }

    protected function getGroupFields(): string
    {
        return 'lineasfacturasprov.idfactura,'
            . "COALESCE(productos.codsubcuentacom, ''),"
            . "COALESCE(productos.codfamilia, '')";
    }

    protected function getSQLFrom(): string
    {
        return 'lineasfacturasprov LEFT JOIN productos ON productos.idproducto = lineasfacturasprov.idproducto';
    }

    protected function getTables(): array
    {
        return ['lineasfacturasprov', 'productos'];
    }
}
