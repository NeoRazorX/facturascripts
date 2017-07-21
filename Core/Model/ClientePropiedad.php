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
 * Description of cliente_propiedad
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ClientePropiedad
{
    use Model;

    public $name;
    public $codcliente;
    public $text;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'cliente_propiedades', 'name');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear()
    {
        $this->name = NULL;
        $this->codcliente = NULL;
        $this->text = NULL;
    }

    protected function install() {
        return '';
    }

    public function exists() {
        if (is_null($this->name) OR is_null($this->codcliente)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM cliente_propiedades WHERE name = " .
                            $this->var2str($this->name) . " AND codcliente = " . $this->var2str($this->codcliente) . ";");
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE cliente_propiedades SET text = " . $this->var2str($this->text) . " WHERE name = " .
                    $this->var2str($this->name) . " AND codcliente = " . $this->var2str($this->codcliente) . ";";
        } else {
            $sql = "INSERT INTO cliente_propiedades (name,codcliente,text) VALUES
            (" . $this->var2str($this->name) . "," . $this->var2str($this->codcliente) . "," . $this->var2str($this->text) . ");";
        }

        return self::$dataBase->exec($sql);
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM cliente_propiedades WHERE name = " .
                        $this->var2str($this->name) . " AND codcliente = " . $this->var2str($this->codcliente) . ";");
    }

    /**
     * Devuelve un array con los pares name => text para una codcliente dado.
     * @param type $cod
     * @return type
     */
    public function array_get($cod) {
        $vlist = array();

        $data = self::$dataBase->select("SELECT * FROM cliente_propiedades WHERE codcliente = " . $this->var2str($cod) . ";");
        if ($data) {
            foreach ($data as $d) {
                $vlist[$d['name']] = $d['text'];
            }
        }

        return $vlist;
    }

    public function array_save($cod, $values) {
        $done = TRUE;

        foreach ($values as $key => $value) {
            $aux = new \cliente_propiedad();
            $aux->name = $key;
            $aux->codcliente = $cod;
            $aux->text = $value;
            if (!$aux->save()) {
                $done = FALSE;
                break;
            }
        }

        return $done;
    }

}
