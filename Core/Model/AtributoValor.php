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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * A Value for an article attribute.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AtributoValor extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     * Code of the related attribute.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Value of the attribute
     *
     * @var string
     */
    public $valor;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos_valores';
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
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new Atributo();

        return '';
    }

    /**
     * Check the delivery note data, return True if it is correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->valor = Utils::noHtml($this->valor);

        return true;
    }

    /**
     * Select all attributes of an attribute code
     *
     * @param string $cod
     *
     * @return self[]
     */
    public function allFromAtributo($cod)
    {
        $where = [new DataBaseWhere('codatributo', $cod)];
        $order = ['valor' => 'ASC'];

        return $this->all($where, $order);
    }
}
