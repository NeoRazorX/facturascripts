<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

/**
 * Allows to relate special accounts (SALES, for example)
  * with the real account or sub-account.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaEspecial extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Special account identifier.
     *
     * @var string
     */
    public $idcuentaesp;

    /**
     * Description of the special account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Return the name of the tabel that this model uses.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_cuentasesp';
    }

    /**
     * Return the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcuentaesp';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url($type = 'auto', $list = 'List')
    {
        return parent::url($type, 'ListCuenta?active=List');
    }
}
