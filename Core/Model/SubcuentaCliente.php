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
 * Relaciona a un cliente con una subcuenta para cada ejercicio.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class SubcuentaCliente
{

    use Base\ModelTrait;

    /**
     * Clave primaria
     * @var int
     */
    public $id;

    /**
     * ID de la subcuenta
     * @var int
     */
    public $idsubcuenta;

    /**
     * Código del cliente
     * @var string
     */
    public $codcliente;

    /**
     * TODO
     * @var string
     */
    public $codsubcuenta;

    /**
     * TODO
     * @var string
     */
    public $codejercicio;

    /**
     * SubcuentaCliente constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'co_subcuentascli', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * TODO
     * @return bool|mixed
     */
    public function getSubcuenta()
    {
        $subc = new Subcuenta();
        return $subc->get($this->idsubcuenta);
    }

    /**
     * TODO
     *
     * @param string $cli
     * @param int $idsc
     *
     * @return bool|SubcuentaCliente
     */
    public function get($cli, $idsc)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($cli)
            . ' AND idsubcuenta = ' . $this->var2str($idsc) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new SubcuentaCliente($data[0]);
        }
        return false;
    }

    /**
     * TODO
     *
     * @param int $idsubc
     *
     * @return bool|SubcuentaCliente
     */
    public function get2($idsubc)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE id = ' . $this->var2str($idsubc) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new SubcuentaCliente($data[0]);
        }
        return false;
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function allFromCliente($cod)
    {
        $sublist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($cod)
            . ' ORDER BY codejercicio DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $s) {
                $sublist[] = new SubcuentaCliente($s);
            }
        }

        return $sublist;
    }
}
