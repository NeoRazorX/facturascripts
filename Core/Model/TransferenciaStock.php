<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Regularization of the stock of a warehouse of articles on a specific date.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José <info@rsanjoseo.com>
 */
class TransferenciaStock extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idtrans;

    /**
     * Date of regularization.
     *
     * @var string
     */
    public $fecha;

    /**
     * Hour of regularization.
     *
     * @var string
     */
    public $hora;

    /**
     * Warehouse from which the goods leave
     *
     * @var string|null
     */
    public $codalmorigen;

    /**
     * Warehouse where the goods arrives.
     *
     * @var int
     */
    public $codalmdestino;

    /**
     * Nick of the user who has done the regularization.
     *
     * @var string
     */
    public $nick;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'transstock';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idtrans';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->codalmorigen == $this->codalmdestino) {
            self::$miniLog->alert(self::$i18n->trans('not-use-same-warehouse'));

            return false;
        }

        return true;
    }
}
