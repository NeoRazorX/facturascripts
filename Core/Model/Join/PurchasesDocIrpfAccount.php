<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Familia;

/**
 * Auxiliary model to get sub-accounts of purchases document IRPF
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @property string $codfamilia
 * @property string $codsubcuenta
 * @property float $total
 */
class PurchasesDocIrpfAccount extends JoinModel
{

    /**
     * Get totals for subaccount of IRPF purchase document
     *
     * @param FacturaProveedor $document
     * @param string $defaultSubacode
     * @param float $percentage
     *
     * @return array
     */
    public function getTotalsForDocument($document, string $defaultSubacode, float $percentage): array
    {
        $totals = [];
        $where = [
            new DataBaseWhere('lineasfacturasprov.idfactura', $document->idfactura),
            new DataBaseWhere('lineasfacturasprov.suplido', false)
        ];
        $order = [
            "COALESCE(productos.codsubcuentairpfcom, '')" => 'ASC',
            "COALESCE(productos.codfamilia, '')" => 'ASC'
        ];
        foreach ($this->all($where, $order) as $row) {
            $codSubAccount = empty($row->codsubcuenta) ? Familia::purchaseIrpfSubAccount($row->codfamilia) : $row->codsubcuenta;
            if (empty($codSubAccount)) {
                $codSubAccount = $defaultSubacode;
            }

            $amount = ($row->total * $document->getEUDiscount()) * ($percentage / 100);
            $totals[$codSubAccount] = isset($totals[$codSubAccount]) ? $totals[$codSubAccount] + $amount : $amount;
        }

        return $this->checkTotals($totals, $document, $defaultSubacode);
    }

    /**
     * @param array $totals
     * @param FacturaProveedor $document
     * @param string $defaultSubacode
     *
     * @return array
     */
    protected function checkTotals(array &$totals, $document, string $defaultSubacode): array
    {
        // round and add the totals
        $sum = 0.0;
        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, FS_NF0);
            $sum += $totals[$key];
        }

        // fix occasional penny mismatch
        if (!Utils::floatcmp($document->totalirpf, $sum, FS_NF0, true)) {
            $diff = round($document->totalirpf - $sum, FS_NF0);
            $totals[$defaultSubacode] = isset($totals[$defaultSubacode]) ? $totals[$defaultSubacode] + $diff : $diff;
        }

        return $totals;
    }

    /**
     * List of fields or columns to select.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'idfactura' => 'lineasfacturasprov.idfactura',
            'codsubcuenta' => "COALESCE(productos.codsubcuentairpfcom, '')",
            'codfamilia' => "COALESCE(productos.codfamilia, '')",
            'total' => 'SUM(lineasfacturasprov.pvptotal)'
        ];
    }

    /**
     * Return Group By fields
     *
     * @return string
     */
    protected function getGroupFields(): string
    {
        return 'lineasfacturasprov.idfactura,'
            . "COALESCE(productos.codsubcuentairpfcom, ''),"
            . "COALESCE(productos.codfamilia, '')";
    }

    /**
     * List of tables related to from sql.
     *
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'lineasfacturasprov LEFT JOIN productos ON productos.idproducto = lineasfacturasprov.idproducto';
    }

    /**
     * List of tables required for the execution of the view.
     *
     * @return array
     */
    protected function getTables(): array
    {
        return ['lineasfacturasprov', 'productos'];
    }
}
