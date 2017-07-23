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
 You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Un grupo de clientes, que puede estar asociado a una tarifa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class GrupoClientes
{
    use Model {
        save as private saveTrait;
    }

    /**
     * Clave primaria
     * @var
     */
    public $codgrupo;

    /**
     * Nombre del grupo
     * @var
     */
    public $nombre;

    /**
     * Código de la tarifa asociada, si la hay
     * @var
     */
    public $codtarifa;

    /**
     * GrupoClientes constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'gruposclientes', 'codgrupo');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codgrupo = null;
        $this->nombre = null;
        $this->codtarifa = null;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codgrupo === null) {
            return 'index.php?page=VentasClientes#grupos';
        }

        return 'index.php?page=VentasGrupo&cod=' . urlencode($this->codgrupo);
    }

    /**
     * Devuelve un nuevo código para un nuevo grupo de clientes
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

        $data = $this->database->selectLimit($sql, 1);
        if (!empty($data)) {
            return sprintf('%06s', 1 + (int)$data[0]['codgrupo']);
        }

        return '000001';
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->nombre = static::noHtml($this->nombre);

        return $this->saveTrait();
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
        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $glist[] = new GrupoClientes($d);
            }
        }

        return $glist;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        /// como hay una clave ajena a tarifas, tenemos que comprobar esa tabla antes
        //new Tarifa();

        return '';
    }
}
