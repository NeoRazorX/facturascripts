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
 * Una cuenta bancaria de un proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class CuentaBancoProveedor
{
    use Model {
        save as private saveTrait;
    }

    /**
     * Clave primaria. Varchar(6).
     * @var int
     */
    public $codcuenta;

    /**
     * Código del proveedor.
     * @var string
     */
    public $codproveedor;
    /**
     * TODO
     * @var string
     */
    public $descripcion;
    /**
     * TODO
     * @var string
     */
    public $iban;
    /**
     * TODO
     * @var string
     */
    public $swift;
    /**
     * TODO
     * @var
     */
    public $principal;

    /**
     * CuentaBancoProveedor constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'cuentasbcopro', 'codcuenta');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codcuenta = null;
        $this->codproveedor = null;
        $this->descripcion = null;
        $this->iban = null;
        $this->swift = null;
        $this->principal = true;
    }

    /**
     * Devuelve el IBAN con o sin espacios.
     *
     * @param bool $espacios
     *
     * @return string
     */
    public function getIban($espacios = false)
    {
        if ($espacios) {
            $txt = '';
            $iban = str_replace(' ', '', $this->iban);
            for ($i = 0; $i < $len = strlen($iban); $i += 4) {
                $txt .= substr($iban, $i, 4) . ' ';
            }
            return $txt;
        }
        return str_replace(' ', '', $this->iban);
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codproveedor === null) {
            return '#';
        }
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor . '#cuentasb';
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->descripcion = static::noHtml($this->descripcion);

        if ($this->exists()) {
            $sql = $this->saveUpdateSQL();
        } else {
            $sql = $this->saveInsertSQL();
        }

        if ($this->principal) {
            /// si esta cuenta es la principal, desmarcamos las demás
            $sql .= 'UPDATE ' . $this->tableName() . ' SET principal = false' .
                ' WHERE codproveedor = ' . $this->var2str($this->codproveedor) .
                ' AND codcuenta != ' . $this->var2str($this->codcuenta) . ';';
        }

        return $this->database->exec($sql);
    }

    /**
     * TODO
     *
     * @param string $codpro
     *
     * @return array
     */
    public function allFromProveedor($codpro)
    {
        $clist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = ' . $this->var2str($codpro)
            . ' ORDER BY codcuenta DESC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $clist[] = new CuentaBancoProveedor($d);
            }
        }

        return $clist;
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     * @return string
     */
    private function saveUpdateSQL()
    {
        $sql = 'UPDATE ' . $this->tableName() . ' SET descripcion = ' . $this->var2str($this->descripcion) .
            ', codproveedor = ' . $this->var2str($this->codproveedor) .
            ', iban = ' . $this->var2str($this->iban) .
            ', swift = ' . $this->var2str($this->swift) .
            ', principal = ' . $this->var2str($this->principal) .
            '  WHERE codcuenta = ' . $this->var2str($this->codcuenta) . ';';
        return $sql;
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     * @return string
     */
    private function saveInsertSQL()
    {
        $this->codcuenta = $this->getNewCodigo();
        $sql = 'INSERT INTO ' . $this->tableName() . ' (codcuenta,codproveedor,descripcion,iban,swift,principal)' .
            ' VALUES (' . $this->var2str($this->codcuenta) .
            ',' . $this->var2str($this->codproveedor) .
            ',' . $this->var2str($this->descripcion) .
            ',' . $this->var2str($this->iban) .
            ',' . $this->var2str($this->swift) .
            ',' . $this->var2str($this->principal) . ');';
        return $sql;
    }

    /**
     * TODO
     * @return int
     */
    private function getNewCodigo()
    {
        $sql = 'SELECT MAX(' . $this->database->sql2Int('codcuenta') . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->database->select($sql);
        if (!empty($cod)) {
            return 1 + (int)$cod[0]['cod'];
        }
        return 1;
    }
}
