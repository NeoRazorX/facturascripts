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
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;

/**
 * Invoice of a client.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class FacturaCliente extends Base\SalesDocument
{

    use Base\ModelTrait;
    use Base\InvoiceTrait;

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        $asiento = $this->getAccountingEntry();
        if ($asiento->exists()) {
            return $asiento->delete() ? parent::delete() : false;
        }

        return parent::delete();
    }

    /**
     * Returns the lines associated with the invoice.
     *
     * @return LineaFacturaCliente[]
     */
    public function getLines()
    {
        $lineaModel = new LineaFacturaCliente();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     *
     * @return LineaFacturaCliente
     */
    public function getNewLine(array $data = [])
    {
        $newLine = new LineaFacturaCliente($data);
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
        return 'facturascli';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->vencimiento)) {
            $this->setPaymentMethod($this->codpago);
        }

        return parent::test();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (!parent::onChange($field)) {
            return false;
        }

        switch ($field) {
            case 'codpago':
                $this->setPaymentMethod($this->codpago);
                return true;

            case 'total':
                $asiento = $this->getAccountingEntry();
                if ($asiento->exists() && $asiento->delete()) {
                    $this->idasiento = null;
                }
                $tool = new InvoiceToAccounting();
                $tool->generate($this);
                return true;
        }

        return true;
    }
}
