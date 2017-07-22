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
 * El cuarto nivel de un plan contable. Está relacionada con una única cuenta.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Subcuenta
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idsubcuenta;
    public $codsubcuenta;

    /**
     * ID de la cuenta a la que pertenece.
     * @var type 
     */
    public $idcuenta;
    public $codcuenta;
    public $codejercicio;
    public $coddivisa;
    public $codimpuesto;
    public $descripcion;
    public $haber;
    public $debe;
    public $saldo;
    public $recargo;
    public $iva;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'co_subcuentas', 'idsubcuenta');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->idsubcuenta = NULL;
        $this->codsubcuenta = NULL;
        $this->idcuenta = NULL;
        $this->codcuenta = NULL;
        $this->codejercicio = NULL;
        $this->coddivisa = $this->defaultItems->coddivisa();
        $this->codimpuesto = NULL;
        $this->descripcion = '';
        $this->debe = 0;
        $this->haber = 0;
        $this->saldo = 0;
        $this->recargo = 0;
        $this->iva = 0;
    }

    protected function install() {
        $this->clean_cache();
        /*

        /// eliminamos todos los PDFs relacionados
        if (file_exists('tmp/' . FS_TMP_NAME . 'libro_mayor')) {
            foreach (glob('tmp/' . FS_TMP_NAME . 'libro_mayor/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (file_exists('tmp/' . FS_TMP_NAME . 'libro_diario')) {
            foreach (glob('tmp/' . FS_TMP_NAME . 'libro_diario/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (file_exists('tmp/' . FS_TMP_NAME . 'inventarios_balances')) {
            foreach (glob('tmp/' . FS_TMP_NAME . 'inventarios_balances/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        /// forzamos la creación de la tabla de cuentas
        $cuenta = new \cuenta();
         */
        return '';
    }

    /**
     * Devuelve la descripción en base64.
     * @return string
     */
    public function get_descripcion_64() {
        return base64_encode($this->descripcion);
    }

    public function tasaconv() {
        if (isset($this->coddivisa)) {
            $divisa = new \divisa();
            $div0 = $divisa->get($this->coddivisa);
            if ($div0) {
                return $div0->tasaconv;
            } else {
                            return 1;
            }
        } else {
                    return 1;
        }
    }

    public function url() {
        if (is_null($this->idsubcuenta)) {
            return 'index.php?page=contabilidad_cuentas';
        } else {
                    return 'index.php?page=contabilidad_subcuenta&id=' . $this->idsubcuenta;
        }
    }

    public function get_cuenta() {
        $cuenta = new \cuenta();
        return $cuenta->get($this->idcuenta);
    }

    public function get_ejercicio() {
        $eje = new \ejercicio();
        return $eje->get($this->codejercicio);
    }

    public function get_partidas($offset = 0) {
        $part = new \partida();
        return $part->all_from_subcuenta($this->idsubcuenta, $offset);
    }

    public function get_partidas_full() {
        $part = new \partida();
        return $part->full_from_subcuenta($this->idsubcuenta);
    }

    public function count_partidas() {
        $part = new \partida();
        return $part->count_from_subcuenta($this->idsubcuenta);
    }

    public function get_totales() {
        $part = new \partida();
        return $part->totales_from_subcuenta($this->idsubcuenta);
    }

    public function get($id) {
        $subc = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idsubcuenta = " . $this->var2str($id) . ";");
        if ($subc) {
            return new \subcuenta($subc[0]);
        } else {
                    return FALSE;
        }
    }

    public function get_by_codigo($cod, $codejercicio, $crear = FALSE) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codsubcuenta = " . $this->var2str($cod)
                . " AND codejercicio = " . $this->var2str($codejercicio) . ";";

        $subc = self::$dataBase->select($sql);
        if ($subc) {
            return new \subcuenta($subc[0]);
        } else if ($crear) {
            /// buscamos la subcuenta equivalente en otro ejercicio
            $sql = "SELECT * FROM " . $this->table_name . " WHERE codsubcuenta = " . $this->var2str($cod)
                    . " ORDER BY idsubcuenta DESC;";
            $subc = self::$dataBase->select($sql);
            if ($subc) {
                $old_sc = new \subcuenta($subc[0]);

                /// buscamos la cuenta equivalente es ESTE ejercicio
                $cuenta = new \cuenta();
                $new_c = $cuenta->get_by_codigo($old_sc->codcuenta, $codejercicio);
                if ($new_c) {
                    $new_sc = new \subcuenta();
                    $new_sc->codcuenta = $new_c->codcuenta;
                    $new_sc->coddivisa = $old_sc->coddivisa;
                    $new_sc->codejercicio = $codejercicio;
                    $new_sc->codimpuesto = $old_sc->codimpuesto;
                    $new_sc->codsubcuenta = $old_sc->codsubcuenta;
                    $new_sc->descripcion = $old_sc->descripcion;
                    $new_sc->idcuenta = $new_c->idcuenta;
                    $new_sc->iva = $old_sc->iva;
                    $new_sc->recargo = $old_sc->recargo;
                    if ($new_sc->save()) {
                        return $new_sc;
                    } else {
                                            return FALSE;
                    }
                } else {
                    $this->new_error_msg('No se ha encontrado la cuenta equivalente a ' . $old_sc->codcuenta . ' en el ejercicio ' . $codejercicio
                            . ' <a href="index.php?page=contabilidad_ejercicio&cod=' . $codejercicio . '">¿Has importado el plan contable?</a>');
                    return FALSE;
                }
            } else {
                $this->new_error_msg('No se ha encontrado ninguna subcuenta equivalente a ' . $cod . ' para copiar.');
                return FALSE;
            }
        } else {
                    return FALSE;
        }
    }

    /**
     * Devuelve la primera subcuenta del ejercicio $codeje cuya cuenta madre
     * está marcada como cuenta especial $id.
     * @param type $id
     * @param type $codeje
     * @return \subcuenta|boolean
     */
    public function get_cuentaesp($id, $codeje) {
        $sql = "SELECT * FROM co_subcuentas WHERE idcuenta IN "
                . "(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = " . $this->var2str($id)
                . " AND codejercicio = " . $this->var2str($codeje) . ") ORDER BY codsubcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            return new \subcuenta($data[0]);
        } else {
                    return FALSE;
        }
    }

    public function tiene_saldo() {
        return !$this->floatcmp($this->debe, $this->haber, FS_NF0, TRUE);
    }

    public function exists() {
        if (is_null($this->idsubcuenta)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM " . $this->table_name
                            . " WHERE idsubcuenta = " . $this->var2str($this->idsubcuenta) . ";");
        }
    }

    public function test() {
        $this->descripcion = $this->no_html($this->descripcion);

        $limpiar_cache = FALSE;
        $totales = $this->get_totales();

        if (abs($this->debe - $totales['debe']) > .001) {
            $this->debe = $totales['debe'];
            $limpiar_cache = TRUE;
        }

        if (abs($this->haber - $totales['haber']) > .001) {
            $this->haber = $totales['haber'];
            $limpiar_cache = TRUE;
        }

        if (abs($this->saldo - $totales['saldo']) > .001) {
            $this->saldo = $totales['saldo'];
            $limpiar_cache = TRUE;
        }

        if ($limpiar_cache) {
            $this->clean_cache();
        }

        if (strlen($this->codsubcuenta) > 0 AND strlen($this->descripcion) > 0) {
            return TRUE;
        } else {
            $this->new_error_msg('Faltan datos en la subcuenta.');
            return FALSE;
        }
    }

    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET codsubcuenta = " . $this->var2str($this->codsubcuenta)
                        . ", idcuenta = " . $this->var2str($this->idcuenta)
                        . ", codcuenta = " . $this->var2str($this->codcuenta)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", coddivisa = " . $this->var2str($this->coddivisa)
                        . ", codimpuesto = " . $this->var2str($this->codimpuesto)
                        . ", descripcion = " . $this->var2str($this->descripcion)
                        . ", recargo = " . $this->var2str($this->recargo)
                        . ", iva = " . $this->var2str($this->iva)
                        . ", debe = " . $this->var2str($this->debe)
                        . ", haber = " . $this->var2str($this->haber)
                        . ", saldo = " . $this->var2str($this->saldo)
                        . "  WHERE idsubcuenta = " . $this->var2str($this->idsubcuenta) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (codsubcuenta,idcuenta,codcuenta,
               codejercicio,coddivisa,codimpuesto,descripcion,debe,haber,saldo,recargo,iva) VALUES
                      (" . $this->var2str($this->codsubcuenta)
                        . "," . $this->var2str($this->idcuenta)
                        . "," . $this->var2str($this->codcuenta)
                        . "," . $this->var2str($this->codejercicio)
                        . "," . $this->var2str($this->coddivisa)
                        . "," . $this->var2str($this->codimpuesto)
                        . "," . $this->var2str($this->descripcion)
                        . "," . $this->var2str($this->debe)
                        . "," . $this->var2str($this->haber)
                        . "," . $this->var2str($this->saldo)
                        . "," . $this->var2str($this->recargo)
                        . "," . $this->var2str($this->iva) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idsubcuenta = self::$dataBase->lastval();
                    return TRUE;
                } else {
                                    return FALSE;
                }
            }
        } else {
                    return FALSE;
        }
    }

    public function delete() {
        $this->clean_cache();
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idsubcuenta = " . $this->var2str($this->idsubcuenta) . ";");
    }

    public function clean_cache() 
    {
        /*
        if (file_exists('tmp/' . FS_TMP_NAME . 'libro_mayor/' . $this->idsubcuenta . '.pdf')) {
            if (!@unlink('tmp/' . FS_TMP_NAME . 'libro_mayor/' . $this->idsubcuenta . '.pdf')) {
                $this->new_error_msg('Error al eliminar tmp/' . FS_TMP_NAME . 'libro_mayor/' . $this->idsubcuenta . '.pdf');
            }
        }

        if (file_exists('tmp/' . FS_TMP_NAME . 'libro_diario/' . $this->codejercicio . '.pdf')) {
            if (!@unlink('tmp/' . FS_TMP_NAME . 'libro_diario/' . $this->codejercicio . '.pdf')) {
                $this->new_error_msg('Error al eliminar tmp/' . FS_TMP_NAME . 'libro_diario/' . $this->codejercicio . '.pdf');
            }
        }

        if (file_exists('tmp/' . FS_TMP_NAME . 'inventarios_balances/' . $this->codejercicio . '.pdf')) {
            if (!@unlink('tmp/' . FS_TMP_NAME . 'inventarios_balances/' . $this->codejercicio . '.pdf')) {
                $this->new_error_msg('Error al eliminar tmp/' . FS_TMP_NAME . 'inventarios_balances/' . $this->codejercicio . '.pdf');
            }
        }
         */
    }

    public function all() {
        $sublist = array();

        $subcuentas = self::$dataBase->select("SELECT * FROM " . $this->table_name . " ORDER BY idsubcuenta DESC;");
        if ($subcuentas) {
            foreach ($subcuentas as $s) {
                $sublist[] = new \subcuenta($s);
            }
        }

        return $sublist;
    }

    public function all_from_cuenta($idcuenta) {
        $sublist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idcuenta = " . $this->var2str($idcuenta)
                . " ORDER BY codsubcuenta ASC;";

        $subcuentas = self::$dataBase->select($sql);
        if ($subcuentas) {
            foreach ($subcuentas as $s) {
                $sublist[] = new \subcuenta($s);
            }
        }

        return $sublist;
    }

    /**
     * Devuelve las subcuentas del ejercicio $codeje cuya cuenta madre
     * está marcada como cuenta especial $id.
     * @param type $id
     * @param type $codeje
     * @return \subcuenta
     */
    public function all_from_cuentaesp($id, $codeje) {
        $cuentas = array();
        $sql = "SELECT * FROM co_subcuentas WHERE idcuenta IN "
                . "(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = " . $this->var2str($id)
                . " AND codejercicio = " . $this->var2str($codeje) . ") ORDER BY codsubcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $cuentas[] = new \subcuenta($d);
            }
        }

        return $cuentas;
    }

    /**
     * Devuelve las subcuentas de un ejercicio:
     * - Todas si $random = false.
     * - $limit si $random = true.
     * @param type $codejercicio
     * @param type $random
     * @param type $limit
     * @return \subcuenta
     */
    public function all_from_ejercicio($codejercicio, $random = FALSE, $limit = FALSE) {
        $sublist = array();

        if ($random AND $limit) {
            if (strtolower(FS_DB_TYPE) == 'mysql') {
                $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = "
                        . $this->var2str($codejercicio) . " ORDER BY RAND()";
            } else {
                $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = "
                        . $this->var2str($codejercicio) . " ORDER BY random()";
            }
            $subcuentas = self::$dataBase->select_limit($sql, $limit, 0);
        } else {
            $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = "
                    . $this->var2str($codejercicio) . " ORDER BY codsubcuenta ASC;";
            $subcuentas = self::$dataBase->select($sql);
        }

        if ($subcuentas) {
            foreach ($subcuentas as $s) {
                $sublist[] = new \subcuenta($s);
            }
        }

        return $sublist;
    }

    public function search($query) {
        $sublist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codsubcuenta LIKE '" . $query . "%'"
                . " OR codsubcuenta LIKE '%" . $query . "'"
                . " OR lower(descripcion) LIKE '%" . $query . "%'"
                . " ORDER BY codejercicio DESC, codcuenta ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $s) {
                $sublist[] = new \subcuenta($s);
            }
        }

        return $sublist;
    }

    /**
     * Devuelve los resultados de la búsuqeda $query sobre las subcuentas del
     * ejercicio $codejercicio
     * @param type $codejercicio
     * @param type $query
     * @return \subcuenta
     */
    public function search_by_ejercicio($codejercicio, $query) {
        $query = $this->escape_string(mb_strtolower(trim($query), 'UTF8'));

        $sublist = $this->cache->get_array('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query);
        if (count($sublist) < 1) {
            $sql = "SELECT * FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($codejercicio)
                    . " AND (codsubcuenta LIKE '" . $query . "%' OR codsubcuenta LIKE '%" . $query . "'"
                    . " OR lower(descripcion) LIKE '%" . $query . "%') ORDER BY codcuenta ASC;";

            $data = self::$dataBase->select($sql);
            if ($data) {
                foreach ($data as $s) {
                    $sublist[] = new \subcuenta($s);
                }
            }

            $this->cache->set('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query, $sublist, 300);
        }

        return $sublist;
    }

}
