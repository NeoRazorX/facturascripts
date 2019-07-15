<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of PagoProveedor
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PagoProveedor extends Base\Payment
{

    use Base\ModelTrait;

    /**
     * 
     * @return ReciboProveedor
     */
    public function getReceipt()
    {
        $receipt = new ReciboProveedor();
        $receipt->loadFromCode($this->idrecibo);
        return $receipt;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needes dependecies
        new ReciboProveedor();

        return parent::install();
    }

    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'pagosprov';
    }
}
