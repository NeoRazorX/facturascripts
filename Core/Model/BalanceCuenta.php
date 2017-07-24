<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Detalle de un balance.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class BalanceCuenta
{
    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var
     */
    public $id;
    /**
     * TODO
     * @var
     */
    public $codbalance;
    /**
     * TODO
     * @var
     */
    public $codcuenta;
    /**
     * TODO
     * @var
     */
    public $desccuenta;

    /**
     * BalanceCuenta constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'co_cuentascb', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function allFromCodbalance($cod)
    {
        $balist = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE codbalance = ' . $this->var2str($cod) . ' ORDER BY codcuenta ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $b) {
                $balist[] = new BalanceCuenta($b);
            }
        }

        return $balist;
    }
}