<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\ModelView\SalesReceipt;
use FacturaScripts\Dinamic\Model\LiquidacionComision;
use FacturaScripts\Dinamic\Model\ReciboCliente;

/**
 * List of Receipts Settled.
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class LineaLiquidacionComision extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Link to Settled Model
     *
     * @var integer
     */
    public $idliquidacion;

    /**
     * Primary Key
     * Link to Invoice Model
     *
     * @var integer
     */
    public $idfactura;

    /**
     * Add to the indicated settlement the list of customer receipts
     * according to the where filter.
     *
     * @param int $settled
     * @param DataBaseWhere $where
     */
    public function addSettledReceiptFromSales($settled, $where)
    {
        $salesReceipt = new SalesReceipt();
        foreach ($salesReceipt->all($where) as $row) {
            if (empty($row->idsettledreceipt)) {
                $this->idliquidacion = $settled;
                $this->idfactura = $row->idfactura;
                $this->save();
            }
        }
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new LiquidacionComision();
        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idfactura';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasliquidacioncomision';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return '#';
    }
}
