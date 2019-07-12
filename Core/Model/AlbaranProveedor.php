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
use FacturaScripts\Dinamic\Model\LineaAlbaranProveedor;

/**
 * Delivery note or purchase order. Represents the reception
 * of a material that has been purchased. It implies the entry of that material
 * to the warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AlbaranProveedor extends Base\PurchaseDocument
{

    use Base\ModelTrait;

    /**
     * Primary key. Integer
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Returns the lines associated with the delivery note.
     *
     * @return LineaAlbaranProveedor[]
     */
    public function getLines()
    {
        $lineaModel = new LineaAlbaranProveedor();
        $where = [new DataBaseWhere('idalbaran', $this->idalbaran)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     * 
     * @param array $data
     *
     * @return LineaAlbaranProveedor
     */
    public function getNewLine(array $data = [])
    {
        $newLine = new LineaAlbaranProveedor($data);
        $newLine->idalbaran = $this->idalbaran;
        if (empty($data)) {
            $newLine->irpf = $this->irpf;
        }

        $status = $this->getStatus();
        $newLine->actualizastock = $status->actualizastock;

        return $newLine;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idalbaran';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'albaranesprov';
    }
}
