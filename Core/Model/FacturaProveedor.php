<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor;
use FacturaScripts\Core\Lib\Accounting\InvoiceToAccounting;

/**
 * Invoice from a supplier.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaProveedor extends Base\PurchaseDocument
{

    use Base\ModelTrait;
    use Base\InvoiceTrait;

    /**
     * Returns the lines associated with the invoice.
     *
     * @return LineaFacturaProveedor[]
     */
    public function getLines()
    {
        $lineaModel = new LineaFacturaProveedor();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     *
     * @return LineaFacturaProveedor
     */
    public function getNewLine(array $data = [])
    {
        $newLine = new LineaFacturaProveedor($data);
        $newLine->idfactura = $this->idfactura;
        if (empty($data)) {
            $newLine->irpf = $this->irpf;
        }

        $status = $this->getStatus();
        $newLine->actualizastock = $status->actualizastock;

        return $newLine;
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
        $sql = parent::install();
        new Asiento();
        return $sql;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
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
        return 'facturasprov';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->vencimiento)) {
            $this->vencimiento = $this->fecha;
        }

        return parent::test();
    }

    /**
     * Generates the accounting entry for the document
     *
     * @return bool
     */
    private function accountingDocument()
    {
        $accounting = new InvoiceToAccounting($this);
        return $accounting->accountPurchase();
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        $this->accountingDocument();
        return parent::saveInsert($values);
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        $this->accountingDocument();
        return parent::saveUpdate($values);
    }
}
