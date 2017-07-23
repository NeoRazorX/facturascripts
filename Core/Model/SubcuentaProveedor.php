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

use FacturaScripts\Core\Base\Model;

/**
 * Relaciona a un proveedor con una subcuenta para cada ejercicio
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class SubcuentaProveedor
{
    use Model;

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
     * Código del proveedor
     * @var string
     */
    public $codproveedor;
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
     * SubcuentaProveedor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'co_subcuentasprov', 'id');
        $this->clear();
        if (!empty($data)) {
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
     * @param $pro
     * @param $idsc
     *
     * @return bool|SubcuentaProveedor
     */
    public function get($pro, $idsc)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = ' . $this->var2str($pro)
            . ' AND idsubcuenta = ' . $this->var2str($idsc) . ';';

        $data = $this->database->select($sql);
        if ($data) {
            return new SubcuentaProveedor($data[0]);
        }
        return false;
    }

    /**
     * TODO
     *
     * @param $id
     *
     * @return bool|SubcuentaProveedor
     */
    public function get2($id)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE id = ' . $this->var2str($id) . ';';
        $data = $this->database->select($sql);
        if ($data) {
            return new SubcuentaProveedor($data[0]);
        }
        return false;
    }

    /**
     * TODO
     *
     * @param string $codprov
     *
     * @return array
     */
    public function allFromProveedor($codprov)
    {
        $sclist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = ' . $this->var2str($codprov)
            . ' ORDER BY codejercicio DESC;';

        $data = $this->database->select($sql);
        if ($data) {
            foreach ($data as $s) {
                $sclist[] = new SubcuentaProveedor($s);
            }
        }

        return $sclist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $sql = 'DELETE FROM ' . $this->tableName()
            . ' WHERE codproveedor NOT IN (SELECT codproveedor FROM proveedores);';
        $this->database->exec($sql);
    }
}
