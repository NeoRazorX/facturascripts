<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\DocTransformation;

trait InvoiceLineTrait
{
    public function refundedQuantity(): float
    {
        $quantity = 0.0;
        $where = [new DataBaseWhere('idlinearect', $this->idlinea)];
        foreach (self::all($where, [], 0, 0) as $line) {
            $quantity += abs($line->cantidad);
        }
        if ($quantity) {
            return $quantity;
        }

        $docTransformation = new DocTransformation();
        $whereTrans = [
            new DataBaseWhere('model1', $this->getDocument()->modelClassName()),
            new DataBaseWhere('iddoc1', $this->idfactura),
            new DataBaseWhere('idlinea1', $this->idlinea)
        ];
        foreach ($docTransformation->all($whereTrans, [], 0, 0) as $docTrans) {
            $quantity += abs($docTrans->cantidad);
        }

        return $quantity;
    }
}