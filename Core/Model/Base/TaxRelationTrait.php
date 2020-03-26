<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\Impuesto;

trait TaxRelationTrait
{

    /**
     * Tax identifier of the tax assigned.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Containt all avaliable taxes.
     *
     * @var Impuesto[]
     */
    private static $taxes = [];

    /**
     * Returns the current tax or the default one
     * 
     * @return Impuesto
     */
    public function getTax()
    {
        if (!isset(self::$taxes[$this->codimpuesto])) {
            self::$taxes[$this->codimpuesto] = new Impuesto();
            self::$taxes[$this->codimpuesto]->loadFromCode($this->codimpuesto);
        }

        return self::$taxes[$this->codimpuesto];
    }
}
