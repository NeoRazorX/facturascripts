<?php

/*
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
 * Un concepto predefinido para una partida (la línea de un asiento contable).
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ConceptoPartida
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idconceptopar;
    public $concepto;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'co_conceptospar', 'idconceptopar');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        $this->idconceptopar = NULL;
        $this->concepto = NULL;
    }

    protected function install() {
        return "";
    }

    public function get($id) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idconceptopar = " . $this->var2str($id) . ";");
        if ($data) {
            return new \concepto_partida($data[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->idconceptopar)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idconceptopar = " . $this->var2str($this->idconceptopar) . ";");
        }
    }

    public function test() {
        $this->concepto = $this->no_html($this->concepto);
        return TRUE;
    }

    public function save() {
        return FALSE;
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idconceptopar = " . $this->var2str($this->idconceptopar) . ";");
    }

    public function all() {
        $concelist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " ORDER BY idconceptopar ASC;");
        if ($data) {
            foreach ($data as $c) {
                $concelist[] = new \concepto_partida($c);
            }
        }

        return $concelist;
    }

}
