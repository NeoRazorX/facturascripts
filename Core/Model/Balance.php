<?php

/*
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
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Model;

/**
 * Define que cuentas hay que usar para generar los distintos informes contables.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Balance
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $codbalance;
    public $descripcion4ba;
    public $descripcion4;
    public $nivel4;
    public $descripcion3;
    public $orden3;
    public $nivel3;
    public $descripcion2;
    public $nivel2;
    public $descripcion1;
    public $nivel1;
    public $naturaleza;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'co_codbalances08', 'codbalance');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        $this->codbalance = NULL;
        $this->naturaleza = NULL;
        $this->nivel1 = NULL;
        $this->descripcion1 = NULL;
        $this->nivel2 = NULL;
        $this->descripcion2 = NULL;
        $this->nivel3 = NULL;
        $this->descripcion3 = NULL;
        $this->orden3 = NULL;
        $this->nivel4 = NULL;
        $this->descripcion4 = NULL;
        $this->descripcion4ba = NULL;
    }

    protected function install() {
        return '';
    }

    public function url() {
        if (is_null($this->codbalance)) {
            return 'index.php?page=editar_balances';
        } else {
                    return 'index.php?page=editar_balances&cod=' . $this->codbalance;
        }
    }

    public function get($cod) {
        $balance = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codbalance = " . $this->var2str($cod) . ";");
        if ($balance) {
            return new \balance($balance[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->codbalance)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codbalance = " .
                            $this->var2str($this->codbalance) . ";");
        }
    }

    public function save() {
        $this->descripcion1 = $this->no_html($this->descripcion1);
        $this->descripcion2 = $this->no_html($this->descripcion2);
        $this->descripcion3 = $this->no_html($this->descripcion3);
        $this->descripcion4 = $this->no_html($this->descripcion4);
        $this->descripcion4ba = $this->no_html($this->descripcion4ba);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET naturaleza = " . $this->var2str($this->naturaleza) .
                    ", nivel1 = " . $this->var2str($this->nivel1) .
                    ", descripcion1 = " . $this->var2str($this->descripcion1) .
                    ", nivel2 = " . $this->var2str($this->nivel2) .
                    ", descripcion2 = " . $this->var2str($this->descripcion2) .
                    ", nivel3 = " . $this->var2str($this->nivel3) .
                    ", descripcion3 = " . $this->var2str($this->descripcion3) .
                    ", orden3 = " . $this->var2str($this->orden3) .
                    ", nivel4 = " . $this->var2str($this->nivel4) .
                    ", descripcion4 = " . $this->var2str($this->descripcion4) .
                    ", descripcion4ba = " . $this->var2str($this->descripcion4ba) .
                    "  WHERE codbalance = " . $this->var2str($this->codbalance) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codbalance,naturaleza,nivel1,descripcion1,
            nivel2,descripcion2,nivel3,descripcion3,orden3,nivel4,descripcion4,descripcion4ba) VALUES 
                  (" . $this->var2str($this->codbalance) .
                    "," . $this->var2str($this->naturaleza) .
                    "," . $this->var2str($this->nivel1) .
                    "," . $this->var2str($this->descripcion1) .
                    "," . $this->var2str($this->nivel2) .
                    "," . $this->var2str($this->descripcion2) .
                    "," . $this->var2str($this->nivel3) .
                    "," . $this->var2str($this->descripcion3) .
                    "," . $this->var2str($this->orden3) .
                    "," . $this->var2str($this->nivel4) .
                    "," . $this->var2str($this->descripcion4) .
                    "," . $this->var2str($this->descripcion4ba) . ");";
        }

        return self::$dataBase->exec($sql);
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE codbalance = " . $this->var2str($this->codbalance) . ";");
    }

    public function all() {
        $balist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " ORDER BY codbalance ASC;");
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance($b);
            }
        }

        return $balist;
    }

}

/**
 * Detalle de un balance.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class BalanceCuenta
{
    use Model;
    
    /**
     * Clave primaria.
     * @var type 
     */
    public $id;
    public $codbalance;
    public $codcuenta;
    public $desccuenta;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'co_cuentascb', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        $this->id = NULL;
        $this->codbalance = NULL;
        $this->codcuenta = NULL;
        $this->desccuenta = NULL;
    }

    protected function install() {
        return '';
    }

    public function get($id) {
        $bc = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($id) . ";");
        if ($bc) {
            return new \balance_cuenta($bc[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET codbalance = " . $this->var2str($this->codbalance) .
                    ", codcuenta = " . $this->var2str($this->codcuenta) .
                    ", desccuenta = " . $this->var2str($this->desccuenta) .
                    "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codbalance,codcuenta,desccuenta) VALUES "
                    . "(" . $this->var2str($this->codbalance)
                    . "," . $this->var2str($this->codcuenta)
                    . "," . $this->var2str($this->desccuenta) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else {
                            return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all() {
        $balist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . ";");
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance_cuenta($b);
            }
        }

        return $balist;
    }

    public function all_from_codbalance($cod) {
        $balist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codbalance = " . $this->var2str($cod) . " ORDER BY codcuenta ASC;");
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance_cuenta($b);
            }
        }

        return $balist;
    }

}

/**
 * Detalle abreviado de un balance.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class BalanceCuentaA
{
    use Model;
    
    /**
     * Clave primaria.
     * @var type 
     */
    public $id;
    public $codbalance;
    public $codcuenta;
    public $desccuenta;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'co_cuentascbba', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear() {
        $this->id = NULL;
        $this->codbalance = NULL;
        $this->codcuenta = NULL;
        $this->desccuenta = NULL;
    }

    protected function install() {
        return '';
    }

    /**
     * Devuelve el saldo del balance de un ejercicio.
     * @param ejercicio $ejercicio
     * @param type $desde
     * @param type $hasta
     * @return int
     */
    public function saldo(&$ejercicio, $desde = FALSE, $hasta = FALSE) {
        $extra = '';
        if (isset($ejercicio->idasientopyg)) {
            if (isset($ejercicio->idasientocierre)) {
                $extra = " AND idasiento NOT IN (" . $this->var2str($ejercicio->idasientocierre)
                        . "," . $this->var2str($ejercicio->idasientopyg) . ')';
            } else {
                            $extra = " AND idasiento != " . $this->var2str($ejercicio->idasientopyg);
            }
        } else if (isset($ejercicio->idasientocierre)) {
            $extra = " AND idasiento != " . $this->var2str($ejercicio->idasientocierre);
        }

        if ($desde AND $hasta) {
            $extra .= " AND idasiento IN (SELECT idasiento FROM co_asientos WHERE "
                    . "fecha >= " . $this->var2str($desde) . " AND "
                    . "fecha <= " . $this->var2str($hasta) . ")";
        }

        if ($this->codcuenta == '129') {
            $data = self::$dataBase->select("SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE (codcuenta LIKE '6%' OR codcuenta LIKE '7%') AND codejercicio = " . $this->var2str($ejercicio->codejercicio) . ")" . $extra . ";");
        } else {
            $data = self::$dataBase->select("SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE codcuenta LIKE '" . $this->no_html($this->codcuenta) . "%'"
                    . " AND codejercicio = " . $this->var2str($ejercicio->codejercicio) . ")" . $extra . ";");
        }

        if ($data) {
            return floatval($data[0]['haber']) - floatval($data[0]['debe']);
        } else {
                    return 0;
        }
    }

    public function get($id) {
        $bca = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($id) . ";");
        if ($bca) {
            return new \balance_cuenta_a($bca[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET codbalance = " . $this->var2str($this->codbalance) .
                    ", codcuenta = " . $this->var2str($this->codcuenta) .
                    ", desccuenta = " . $this->var2str($this->desccuenta) .
                    "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codbalance,codcuenta,desccuenta) VALUES "
                    . "(" . $this->var2str($this->codbalance)
                    . "," . $this->var2str($this->codcuenta)
                    . "," . $this->var2str($this->desccuenta) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else {
                            return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all() {
        $balist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . ";");
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance_cuenta_a($b);
            }
        }

        return $balist;
    }

    public function all_from_codbalance($cod) {
        $balist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codbalance = " . $this->var2str($cod) . " ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance_cuenta_a($b);
            }
        }

        return $balist;
    }

    public function search_by_codbalance($cod) {
        $balist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codbalance LIKE '" . $this->no_html($cod) . "%' ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $b) {
                $balist[] = new \balance_cuenta_a($b);
            }
        }

        return $balist;
    }

}
