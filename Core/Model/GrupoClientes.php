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
 * Un grupo de clientes, que puede estar asociado a una tarifa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class GrupoClientes
{
    use Base\ModelTrait;

    /**
     * Clave primaria
     *
     * @var
     */
    public $codgrupo;

    /**
     * Nombre del grupo
     *
     * @var
     */
    public $nombre;

    /**
     * Código de la tarifa asociada, si la hay
     *
     * @var
     */
    public $codtarifa;

    public function tableName()
    {
        return 'gruposclientes';
    }

    public function primaryColumn()
    {
        return 'codgrupo';
    }

    /**
     * Devuelve un nuevo código para un nuevo grupo de clientes
     *
     * @return string
     */
    public function getNewCodigo()
    {
        $sql = 'SELECT codgrupo FROM ' . $this->tableName() . " WHERE codgrupo REGEXP '^\d+$'"
            . ' ORDER BY CAST(`codgrupo` AS DECIMAL) DESC';
        if (strtolower(FS_DB_TYPE) === 'postgresql') {
            $sql = 'SELECT codgrupo FROM ' . $this->tableName() . " WHERE codgrupo ~ '^\d+$'"
                . ' ORDER BY codgrupo::INTEGER DESC';
        }

        $data = $this->dataBase->selectLimit($sql, 1);
        if (!empty($data)) {
            return sprintf('%06s', 1 + (int) $data[0]['codgrupo']);
        }

        return '000001';
    }

    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);

        return TRUE;
    }

    /**
     * Devuelve todos los grupos con la tarifa $cod
     *
     * @param string $cod
     *
     * @return array
     */
    public function allWithTarifa($cod)
    {
        $glist = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE codtarifa = ' . $this->var2str($cod) . ' ORDER BY codgrupo ASC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $glist[] = new self($d);
            }
        }

        return $glist;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        /// como hay una clave ajena a tarifas, tenemos que comprobar esa tabla antes
        //new Tarifa();

        return '';
    }
}
