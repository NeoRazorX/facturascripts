<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Detail of a balance.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BalanceCuenta extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Balance code.
     *
     * @var string
     */
    public $codbalance;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $desccuenta;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new Balance();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'balancescuentas';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->desccuenta = $this->toolBox()->utils()->noHtml($this->desccuenta);
        return parent::test();
    }
}
