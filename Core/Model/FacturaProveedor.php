<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor as DinLineaFactura;
use FacturaScripts\Dinamic\Model\ReciboProveedor as DinReciboProveedor;

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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->pagada = false;
    }

    /**
     * Returns the lines associated with the invoice.
     *
     * @return DinLineaFactura[]
     */
    public function getLines()
    {
        $lineaModel = new DinLineaFactura();
        $where = [new DataBaseWhere('idfactura', $this->idfactura)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return DinLineaFactura
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idfactura'])
    {
        $newLine = new DinLineaFactura();
        $newLine->idfactura = $this->idfactura;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns all invoice's receipts.
     * 
     * @return DinReciboProveedor[]
     */
    public function getReceipts()
    {
        $receipt = new DinReciboProveedor();
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
        return 'facturasprov';
    }
}
