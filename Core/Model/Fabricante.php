<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Un fabricante de artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Fabricante
{
    use Model;

    /**
     * Clave primaria.
     * @var
     */
    public $codfabricante;
    /**
     * TODO
     * @var
     */
    public $nombre;

    /**
     * Fabricante constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'fabricantes', 'codfabricante');
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
        $this->codfabricante = null;
        $this->nombre = '';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codfabricante === null) {
            return 'index.php?page=VentasFabricantes';
        }
        return 'index.php?page=VentasFabricante&cod=' . urlencode($this->codfabricante);
    }

    /**
     * TODO
     * @param int $len
     *
     * @return string
     */
    public function getNombre($len = 12)
    {
        if (mb_strlen($this->nombre) > $len) {
            return substr($this->nombre, 0, $len) . '...';
        }
        return $this->nombre;
    }

    /**
     * TODO
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getArticulos($offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $articulo = new Articulo();
        return $articulo->allFromFabricante($this->codfabricante, $offset, $limit);
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codfabricante = static::noHtml($this->codfabricante);
        $this->nombre = static::noHtml($this->nombre);

        if (empty($this->codfabricante) || strlen($this->codfabricante) > 8) {
            $this->miniLog->alert('Código de fabricante no válido. Deben ser entre 1 y 8 caracteres.');
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            $this->miniLog->alert('Descripción de fabricante no válida.');
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Devuelve un array con todos los fabricantes
     * @return array
     */
    public function all()
    {
        /// leemos la lista de la caché
        $fablist = $this->cache->get('m_fabricante_all');
        if (!$fablist) {
            /// si la lista no está en caché, leemos de la base de datos
            $sql = 'SELECT * FROM ' . $this->tableName() . ' ORDER BY nombre ASC;';
            $data = $this->database->select($sql);
            if ($data) {
                foreach ($data as $d) {
                    $fablist[] = new Fabricante($d);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_fabricante_all', $fablist);
        }

        return $fablist;
    }

    /**
     * TODO
     * @param $query
     *
     * @return array
     */
    public function search($query)
    {
        $fablist = [];
        $query = static::noHtml(mb_strtolower($query, 'UTF8'));

        $sql = 'SELECT * FROM ' . $this->tableName()
            . " WHERE lower(nombre) LIKE '%" . $query . "%' ORDER BY nombre ASC;";
        $data = $this->database->select($sql);
        if ($data) {
            foreach ($data as $f) {
                $fablist[] = new Fabricante($f);
            }
        }

        return $fablist;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    private function install()
    {
        $this->cleanCache();
        $sql = 'INSERT INTO ' . $this->tableName() . " (codfabricante,nombre) VALUES ('OEM','OEM');";
        return $sql;
    }

    /**
     * TODO
     */
    private function cleanCache()
    {
        $this->cache->delete('m_fabricante_all');
    }
}
