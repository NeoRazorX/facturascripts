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
 * Elemento de tercer nivel del plan contable.
 * Está relacionada con un único ejercicio y epígrafe,
 * pero puede estar relacionada con muchas subcuentas.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Cuenta
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idcuenta;
    public $codcuenta;

    /**
     * Código del ejercicio de esta cuenta.
     * @var type 
     */
    public $codejercicio;
    public $idepigrafe;
    public $codepigrafe;
    public $descripcion;
    public $idcuentaesp;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'co_cuentas', 'idcuenta');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        $this->idcuenta = NULL;
        $this->codcuenta = NULL;
        $this->codejercicio = NULL;
        $this->idepigrafe = NULL;
        $this->codepigrafe = NULL;
        $this->descripcion = '';
        $this->idcuentaesp = NULL;
    }

    protected function install() {
        /// forzamos la creación de la tabla epigrafes
        //$epi = new \epigrafe();

        return '';
    }

    public function url() {
        if (is_null($this->idcuenta)) {
            return 'index.php?page=contabilidad_cuentas';
        } else
            return 'index.php?page=contabilidad_cuenta&id=' . $this->idcuenta;
    }

    public function get_subcuentas() {
        $subcuenta = new \subcuenta();
        return $subcuenta->all_from_cuenta($this->idcuenta);
    }

    public function get_ejercicio() {
        $eje = new \ejercicio();
        return $eje->get($this->codejercicio);
    }

    public function get($id) {
        $cuenta = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idcuenta = " . $this->var2str($id) . ";");
        if ($cuenta) {
            return new \cuenta($cuenta[0]);
        } else
            return FALSE;
    }

    public function get_by_codigo($cod, $codejercicio) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcuenta = " . $this->var2str($cod) .
                " AND codejercicio = " . $this->var2str($codejercicio) . ";";

        $data = self::$dataBase->select($sql);
        if ($data) {
            return new \cuenta($data[0]);
        } else
            return FALSE;
    }

    /**
     * Obtiene la primera cuenta especial seleccionada.
     * @param type $id
     * @param type $codejercicio
     * @return boolean|\cuenta
     */
    public function get_cuentaesp($id, $codejercicio) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idcuentaesp = " . $this->var2str($id) .
                " AND codejercicio = " . $this->var2str($codejercicio) . " ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            return new \cuenta($data[0]);
        } else
            return FALSE;
    }

    public function exists() {
        if (is_null($this->idcuenta)) {
            return FALSE;
        } else
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idcuenta = " . $this->var2str($this->idcuenta) . ";");
    }

    public function test() {
        $this->descripcion = $this->no_html($this->descripcion);

        if (strlen($this->codcuenta) > 0 AND strlen($this->descripcion) > 0) {
            return TRUE;
        } else {
            $this->new_error_msg('Faltan datos en la cuenta');
            return FALSE;
        }
    }

    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET codcuenta = " . $this->var2str($this->codcuenta) .
                        ", codejercicio = " . $this->var2str($this->codejercicio) .
                        ", idepigrafe = " . $this->var2str($this->idepigrafe) .
                        ", codepigrafe = " . $this->var2str($this->codepigrafe) .
                        ", descripcion = " . $this->var2str($this->descripcion) .
                        ", idcuentaesp = " . $this->var2str($this->idcuentaesp) .
                        "  WHERE idcuenta = " . $this->var2str($this->idcuenta) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (codcuenta,codejercicio,idepigrafe,codepigrafe," .
                        "descripcion,idcuentaesp) VALUES (" .
                        $this->var2str($this->codcuenta) . "," .
                        $this->var2str($this->codejercicio) . "," .
                        $this->var2str($this->idepigrafe) . "," .
                        $this->var2str($this->codepigrafe) . "," .
                        $this->var2str($this->descripcion) . "," .
                        $this->var2str($this->idcuentaesp) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idcuenta = self::$dataBase->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idcuenta = " . $this->var2str($this->idcuenta) . ";");
    }

    public function all($offset = 0) {
        $cuenlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY codejercicio DESC, codcuenta ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $c) {
                $cuenlist[] = new \cuenta($c);
            }
        }

        return $cuenlist;
    }

    public function full_from_epigrafe($id) {
        $cuenlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idepigrafe = " . $this->var2str($id)
                . " ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $c) {
                $cuenlist[] = new \cuenta($c);
            }
        }

        return $cuenlist;
    }

    public function all_from_ejercicio($codejercicio, $offset = 0) {
        $cuenlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($codejercicio) .
                " ORDER BY codcuenta ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $c) {
                $cuenlist[] = new \cuenta($c);
            }
        }

        return $cuenlist;
    }

    public function full_from_ejercicio($codejercicio) {
        $cuenlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($codejercicio)
                . " ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $c) {
                $cuenlist[] = new \cuenta($c);
            }
        }

        return $cuenlist;
    }

    public function all_from_cuentaesp($id, $codejercicio) {
        $cuenlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idcuentaesp = " . $this->var2str($id)
                . " AND codejercicio = " . $this->var2str($codejercicio) . " ORDER BY codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $cuenlist[] = new \cuenta($d);
            }
        }

        return $cuenlist;
    }

    public function search($query, $offset = 0) {
        $cuenlist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');
        $sql = "SELECT * FROM " . $this->table_name .
                " WHERE codcuenta LIKE '" . $query . "%' OR lower(descripcion) LIKE '%" . $query . "%'" .
                " ORDER BY codejercicio DESC, codcuenta ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $c) {
                $cuenlist[] = new \cuenta($c);
            }
        }

        return $cuenlist;
    }

    public function new_subcuenta($suma_codigo) {
        $ejercicio = new \ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            $codsubcuenta = floatval(sprintf('%-0' . $eje0->longsubcuenta . 's', $this->codcuenta)) + $suma_codigo;
            $subcuenta = new \subcuenta();
            $subc0 = $subcuenta->get_by_codigo($codsubcuenta, $this->codejercicio);
            if ($subc0) {
                return $subc0;
            } else {
                $subc0 = new \subcuenta();
                $subc0->codcuenta = $this->codcuenta;
                $subc0->idcuenta = $this->idcuenta;
                $subc0->codejercicio = $this->codejercicio;
                $subc0->codsubcuenta = $codsubcuenta;

                return $subc0;
            }
        } else
            return FALSE;
    }

}
