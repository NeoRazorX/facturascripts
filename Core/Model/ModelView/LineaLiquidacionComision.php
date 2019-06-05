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
use FacturaScripts\Dinamic\Model\LineaLiquidacionComision as ParentModel;

/**
 * Description of SettledReceipt
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class LineaLiquidacionComision extends ModelView
{

    /**
     * Constructor and class initializer.
     *
     * @param array $data
     */
    public function __construct($data = array())
    {
        parent::__construct($data);

        $this->setMasterModel(new ParentModel());
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'idreceipt' => 'lineasliquidacioncomision.idreceipt',
            'idsettled' => 'lineasliquidacioncomision.idsettled',
            'idinvoice' => 'lineasliquidacioncomision.idinvoice',
            'idcompany' => 'reciboscli.idempresa',
            'idcustomer' => 'reciboscli.codcliente',
            'receiptcode' => 'reciboscli.codigo',
            'receiptdate' => 'reciboscli.fecha',
            'receiptdateexpiration' => 'reciboscli.fechav',
            'receiptdatepayment' => 'reciboscli.fechapago',
            'amount' => 'reciboscli.importe',
            'liquidated' => 'reciboscli.liquidado',
            'expenses' => 'reciboscli.gastos',
            'invoicecode2' => 'facturascli.numero2',
            'customer' => 'facturascli.nombrecliente',
            'commission' => 'facturascli.porcomision',
            'total' => 'facturascli.total',
            'exercise' => 'ejercicios.nombre',
            'company' => 'empresas.nombre'
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'lineasliquidacioncomision'
            . ' INNER JOIN reciboscli ON reciboscli.idrecibo = settledreceipts.idreceipt'
            . ' INNER JOIN empresas ON empresas.idempresa = reciboscli.idempresa'
            . ' INNER JOIN facturascli ON facturascli.idfactura = settledreceipts.idinvoice'
            . ' INNER JOIN ejercicios ON ejercicios.codejercicio = facturascli.codejercicio';
    }

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'lineasliquidacioncomision',
            'reciboscli',
            'facturascli',
            'ejercicios',
            'empresas'
        ];
    }
}
