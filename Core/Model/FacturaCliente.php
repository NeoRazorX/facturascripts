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
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new LiquidacionComision();

        return parent::install();
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
     * Returns all invoice's receipts.
     *
     * @return ReciboCliente[]
     */
    public function getReceipts()
    {
        $receipt = new ReciboCliente();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        return $receipt->all($where, ['numero' => 'ASC', 'idrecibo' => 'ASC'], 0, 0);
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
}
