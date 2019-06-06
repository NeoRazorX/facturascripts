<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelView;

/**
 * Description of SettledReceipt
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class LiquidacionComisionFactura extends ModelView
{

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'idliquidacion' => 'facturascli.idliquidacion',
            'idfactura' => 'facturascli.idfactura',
            'idempresa' => 'facturascli.idempresa',
            'codcliente' => 'facturascli.codcliente',
            'codigo' => 'facturascli.codigo',
            'numero' => 'facturascli.numero',
            'numero2' => 'facturascli.numero2',
            'fecha' => 'facturascli.fecha',
            'pagada' => 'facturascli.pagada',
            'neto' => 'facturascli.neto',
            'total' => 'facturascli.total',
            'porcomision' => 'facturascli.porcomision',
            'cliente' => 'facturascli.nombrecliente',
            'ejercicio' => 'ejercicios.nombre',
            'empresa' => 'empresas.nombrecorto'
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'facturascli'
            . ' INNER JOIN ejercicios ON ejercicios.codejercicio = facturascli.codejercicio'
            . ' INNER JOIN empresas ON empresas.idempresa = facturascli.idempresa';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'facturascli',
            'ejercicios',
            'empresas'
        ];
    }

    /**
     * Add to the indicated settlement the list of customer receipts
     * according to the where filter.
     *
     * @param int $settled
     * @param DataBaseWhere $where
     */
    /*
    public function addSettledReceiptFromSales($settled, $where)
    {
        $salesReceipt = new SalesReceipt();
        foreach ($salesReceipt->all($where) as $row) {
            if (empty($row->idsettledreceipt)) {
                $this->idliquidacion = $settled;
                $this->idfactura = $row->idfactura;
                $this->idrecibo = $row->idrecibo;
                $this->save();
            }
        }
    }
    */
}
