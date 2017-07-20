<?php

/*
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Propiedad de un artículos. Permite añadir propiedades a un artículo
 * sin necesidad de modificar la clase artículo.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ArticuloPropiedad
{
    use Model;

    public $name;
    public $referencia;
    public $text;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'articulo_propiedades', 'name');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear() 
    {
        $this->name = NULL;
        $this->referencia = NULL;
        $this->text = NULL;
    }

    protected function install() {
        return '';
    }

    /**
     * Devuelve TRUE si los datos existen en la base de datos
     * @return boolean
     */
    public function exists() {
        if (is_null($this->name) OR is_null($this->referencia)) {
            return FALSE;
        } else {
            $sql = "SELECT * FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name)
                    . " AND referencia = " . $this->var2str($this->referencia) . ";";

            return $this->db->select($sql);
        }
    }

    /**
     * Guarda los datos en la base de datos
     * @return type
     */
    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET text = " . $this->var2str($this->text)
                    . " WHERE name = " . $this->var2str($this->name)
                    . " AND referencia = " . $this->var2str($this->referencia) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (name,referencia,text) VALUES
                   (" . $this->var2str($this->name)
                    . "," . $this->var2str($this->referencia)
                    . "," . $this->var2str($this->text) . ");";
        }

        return $this->db->exec($sql);
    }

    /**
     * Elimina los datos de la base de datos
     * @return type
     */
    public function delete() {
        $sql = "DELETE FROM " . $this->table_name . " WHERE name = " . $this->var2str($this->name)
                . " AND referencia = " . $this->var2str($this->referencia) . ";";

        return $this->db->exec($sql);
    }

    /**
     * Devuelve un array con los pares name => text para una referencia dada.
     * @param type $ref
     * @return type
     */
    public function array_get($ref) {
        $vlist = array();

        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref) . ";");
        if ($data) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    /**
     * Guarda en la base de datos los pares name => text de propiedades de un artículo
     * @param type $ref
     * @param type $values
     * @return boolean
     */
    public function array_save($ref, $values) {
        $done = TRUE;

        foreach ($values as $key => $value) {
            $aux = new \articulo_propiedad();
            $aux->name = $key;
            $aux->referencia = $ref;
            $aux->text = $value;
            if (!$aux->save()) {
                $done = FALSE;
                break;
            }
        }

        return $done;
    }

    /**
     * Devuelve el valor de la propiedad $name del artículo con referencia $ref
     * @param type $ref
     * @param type $name
     * @return boolean
     */
    public function simple_get($ref, $name) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref)
                . " AND name = " . $this->var2str($name) . ";";
        $data = $this->db->select($sql);
        if ($data) {
            return $data[0]['text'];
        } else
            return FALSE;
    }

    /**
     * Devuelve la referencia del artículo que tenga la propiedad $name con valor $text
     * @param type $name
     * @param type $text
     * @return boolean
     */
    public function simple_get_ref($name, $text) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE text = " . $this->var2str($text)
                . " AND name = " . $this->var2str($name) . ";";
        $data = $this->db->select($sql);
        if ($data) {
            return $data[0]['referencia'];
        } else
            return FALSE;
    }

    /**
     * Elimina una propiedad de un artículo.
     * @param type $ref
     * @param type $name
     * @return type
     */
    public function simple_delete($ref, $name) {
        $sql = "DELETE FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref)
                . " AND name = " . $this->var2str($name) . ";";

        return $this->db->exec($sql);
    }

    public function all($offset = 0, $limit = FS_ITEM_LIMIT) {
        $aplist = array();

        $data = $this->db->select_limit("SELECT * FROM " . $this->table_name . " ORDER BY referencia ASC", $limit, $offset);
        if ($data) {
            foreach ($data as $d) {
                $aplist[] = new \articulo_propiedad($d);
            }
        }

        return $aplist;
    }

}
