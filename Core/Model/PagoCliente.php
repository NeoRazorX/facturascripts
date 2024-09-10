<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\ReciboCliente as DinReciboCliente;

/**
 * Description of PagoCliente
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PagoCliente extends Base\Payment
{

    use Base\ModelTrait;

    /**
     * @var string
     */
    public $customid;

    /**
     * @var string
     */
    public $customstatus;

    /**
     * @var float
     */
    public $gastos;

    public function clear()
    {
        parent::clear();
        $this->gastos = 0.0;
    }

    public function getReceipt(): DinReciboCliente
    {
        $receipt = new DinReciboCliente();
        $receipt->loadFromCode($this->idrecibo);
        return $receipt;
    }

    public function install(): string
    {
        // needed dependencies
        new ReciboCliente();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'pagoscli';
    }
}
