<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\ModelView;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelView;
use FacturaScripts\Dinamic\Model\Familia;

/**
 * Auxiliary model to get sub-accounts of sales document lines
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class SalesDocLineAccount extends ModelView
{

    /**
     * Get totals for subaccount of sale document
     *
     * @param int $document
     * @return array
     */
    public function getTotalsForDocument($document)
    {
        $where = [ new DataBaseWhere('lineasfacturascli.idfactura', $document) ];
        $order = [
            'lineasfacturascli.idfactura' => 'ASC',
            'productos.codsubcuentaven' => 'ASC',
            "COALESCE(productos.codfamilia, '')" => 'ASC'
        ];

        $totals = [];
        foreach ($this->all($where, $order) as $row) {
            $codSubAccount = (empty($row->codsubcuenta))
                ? Familia::saleSubAccount($row->codfamilia)
                : $row->codsubcuenta;

            $amount = $totals[$codSubAccount] ?? 0.00;
            $totals[$codSubAccount] = $amount + $row->total;
        }
        return $totals;
    }

    /**
     * List of fields or columns to select
     */
    protected function getFields(): array
    {
        return [
            'idfactura' => 'lineasfacturascli.idfactura',
            'codsubcuenta' => 'productos.codsubcuentaven',
            'codfamilia' => "COALESCE(productos.codfamilia, '')",
            'total' => 'SUM(lineasfacturascli.pvptotal)',
        ];
    }

    /**
     * Return Group By fields
     *
     * @return string
     */
    protected function getGroupFields(): string
    {
        return 'lineasfacturascli.idfactura,'
            . 'productos.codsubcuentaven,'
            . "COALESCE(productos.codfamilia, '')";
    }

    /**
     * List of tables related to from sql
     */
    protected function getSQLFrom(): string
    {
        return 'lineasfacturascli'
            . ' INNER JOIN productos ON productos.idproducto = lineasfacturascli.idproducto';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'lineasfacturascli',
            'productos'
        ];
    }
}
