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
 * El asiento contable. Se relaciona con un ejercicio y se compone de partidas.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Asiento
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idasiento;

    /**
     * Número de asiento. Se modificará al renumerar.
     * @var type 
     */
    public $numero;
    public $idconcepto;
    public $concepto;
    public $fecha;
    public $codejercicio;
    public $codplanasiento;
    public $editable;
    public $documento;
    public $tipodocumento;
    public $importe;
    private $coddivisa;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'co_asientos', 'idasiento');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    public function clear()
    {
        $this->idasiento = NULL;
        $this->numero = NULL;
        $this->idconcepto = NULL;
        $this->concepto = NULL;
        $this->fecha = Date('d-m-Y');
        $this->codejercicio = NULL;
        $this->codplanasiento = NULL;
        $this->editable = TRUE;
        $this->documento = NULL;
        $this->tipodocumento = NULL;
        $this->importe = 0;
    }

    protected function install() {
        return '';
    }

    public function url() {
        if (is_null($this->idasiento)) {
            return 'index.php?page=contabilidad_asientos';
        } else
            return 'index.php?page=contabilidad_asiento&id=' . $this->idasiento;
    }

    public function get_factura() {
        if ($this->tipodocumento == 'Factura de cliente') {
            $fac = new \factura_cliente();
            return $fac->get_by_codigo($this->documento);
        } else if ($this->tipodocumento == 'Factura de proveedor') {
            $fac = new \factura_proveedor();
            return $fac->get_by_codigo($this->documento);
        } else
            return FALSE;
    }

    public function factura_url() {
        $fac = $this->get_factura();
        if ($fac) {
            return $fac->url();
        } else
            return '#';
    }

    public function ejercicio_url() {
        $ejercicio = new \ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            return $eje0->url();
        } else
            return '#';
    }

    /**
     * Devuelve el código de la divisa.
     * Lo que pasa es que ese dato se almacena en las partidas, por eso
     * hay que usar esta función.
     * @return type
     */
    public function coddivisa() {
        if (!isset($this->coddivisa)) {
            $this->coddivisa = $this->defaultItems->coddivisa();

            foreach ($this->get_partidas() as $par) {
                if ($par->coddivisa) {
                    $this->coddivisa = $par->coddivisa;
                    break;
                }
            }
        }

        return $this->coddivisa;
    }

    /**
     * Devuelve el asiento con el $id solicitado.
     * @param type $id
     * @return \asiento|boolean
     */
    public function get($id) {
        if (isset($id)) {
            $asiento = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idasiento = " . $this->var2str($id) . ";");
            if ($asiento) {
                return new \asiento($asiento[0]);
            } else
                return FALSE;
        } else
            return FALSE;
    }

    public function get_partidas() {
        $partida = new \partida();
        return $partida->all_from_asiento($this->idasiento);
    }

    public function exists() {
        if (is_null($this->idasiento)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idasiento = " . $this->var2str($this->idasiento) . ";");
        }
    }

    /**
     * Asignamos un número al asiento.
     */
    public function new_numero() {
        $this->numero = 1;
        $sql = "SELECT MAX(" . self::$dataBase->sql_to_int('numero') . ") as num FROM " . $this->table_name
                . " WHERE codejercicio = " . $this->var2str($this->codejercicio) . ";";

        $data = self::$dataBase->select($sql);
        if ($data) {
            $this->numero = 1 + intval($data[0]['num']);
        }

        /// Nos guardamos la secuencia para dar compatibilidad con eneboo
        $secc = new \secuencia_contabilidad();
        $secc0 = $secc->get_by_params2($this->codejercicio, 'nasiento');
        if ($secc0) {
            if ($this->numero >= $secc0->valorout) {
                $secc0->valorout = 1 + $this->numero;
                $secc0->save();
            }
        }
    }

    public function test() {
        $this->concepto = $this->no_html($this->concepto);
        $this->documento = $this->no_html($this->documento);

        if (strlen($this->concepto) > 255) {
            $this->new_error_msg("Concepto del asiento demasiado largo.");
            return FALSE;
        } else
            return TRUE;
    }

    public function full_test($duplicados = TRUE) {
        $status = TRUE;

        /*
         * Comprobamos que el asiento no esté vacío o descuadrado.
         * También comprobamos que las subcuentas pertenezcan al mismo ejercicio.
         */
        $debe = $haber = 0;
        $partidas = $this->get_partidas();
        if ($partidas) {
            foreach ($partidas as $p) {
                $debe += $p->debe;
                $haber += $p->haber;

                $sc = $p->get_subcuenta();
                if ($sc) {
                    if ($sc->codejercicio != $this->codejercicio) {
                        $this->new_error_msg('La subcuenta ' . $sc->codsubcuenta . ' pertenece a otro ejercicio.');
                        $status = FALSE;
                    }
                } else {
                    $this->new_error_msg('Subcuenta ' . $p->codsubcuenta . ' no encontrada.');
                    $status = FALSE;
                }
            }
        }

        if (!$this->floatcmp($debe, $haber, FS_NF0, TRUE)) {
            $this->new_error_msg("Asiento descuadrado. Descuadre: " . round($debe - $haber, FS_NF0 + 1));
            $status = FALSE;
        } else if (!$this->floatcmp($this->importe, max(array(abs($debe), abs($haber))), FS_NF0, TRUE)) {
            $this->new_error_msg('Importe del asiento incorrecto.');
            $status = FALSE;
        }

        /// comprobamos que la fecha sea correcta
        $ejercicio = new \ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) OR strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                $this->new_error_msg("La fecha de este asiento está fuera del rango del <a target='_blank' href='" . $eje0->url() . "'>ejercicio</a>.");
                $status = FALSE;
            }
        }

        if ($status AND $duplicados) {
            /// comprobamos si es un duplicado
            $asientos = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE fecha = " . $this->var2str($this->fecha) . "
            AND concepto = " . $this->var2str($this->concepto) . " AND importe = " . $this->var2str($this->importe) . "
            AND idasiento != " . $this->var2str($this->idasiento) . ";");
            if ($asientos) {
                foreach ($asientos as $as) {
                    /// comprobamos las líneas 
                    if (strtolower(FS_DB_TYPE) == 'mysql') {
                        $aux = self::$dataBase->select("SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = " . $this->var2str($this->idasiento) . "
                     AND NOT EXISTS(SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = " . $this->var2str($as['idasiento']) . ");");
                    } else {
                        $aux = self::$dataBase->select("SELECT codsubcuenta,debe,haber,codcontrapartida,concepto
                     FROM co_partidas WHERE idasiento = " . $this->var2str($this->idasiento) . "
                     EXCEPT SELECT codsubcuenta,debe,haber,codcontrapartida,concepto FROM co_partidas
                     WHERE idasiento = " . $this->var2str($as['idasiento']) . ";");
                    }

                    if (!$aux) {
                        $this->new_error_msg("Este asiento es un posible duplicado de
                     <a href='index.php?page=contabilidad_asiento&id=" . $as['idasiento'] . "'>este otro</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica el concepto.");
                        $status = FALSE;
                    }
                }
            }
        }

        return $status;
    }

    public function fix() {
        $importe_old = $this->importe;
        $debe = $haber = 0;
        foreach ($this->get_partidas() as $p) {
            $debe += $p->debe;
            $haber += $p->haber;
        }
        $total = $debe - $haber;
        $this->importe = max(array(abs($debe), abs($haber)));

        /// corregimos descuadres de menos de 0.01
        if ($this->floatcmp($debe, $haber, 2)) {
            $debe = $haber = 0;
            $partidas = $this->get_partidas();
            foreach ($partidas as $p) {
                $p->debe = bround($p->debe, 2);
                $debe += $p->debe;
                $p->haber = bround($p->haber, 2);
                $haber += $p->haber;
            }

            /// si con el redondeo se soluciona el problema, pues genial!
            if ($this->floatcmp($debe, $haber)) {
                $this->importe = max(array(abs($debe), abs($haber)));
                foreach ($partidas as $p) {
                    $p->save();
                }
            } else {
                /// si no ha funcionado, intentamos arreglarlo
                $total = 0;
                $partidas = $this->get_partidas();
                foreach ($partidas as $p) {
                    $total += ($p->debe - $p->haber);
                }

                if ($partidas[0]->debe != 0) {
                    $partidas[0]->debe = ($partidas[0]->debe - $total);
                } else if ($partidas[0]->haber != 0) {
                    $partidas[0]->haber += $total;
                }

                $debe = $haber = 0;
                foreach ($partidas as $p) {
                    $debe += $p->debe;
                    $haber += $p->haber;
                }

                /// si hemos resuelto el problema grabamos
                if ($this->floatcmp($debe, $haber)) {
                    $this->importe = max(array(abs($debe), abs($haber)));
                    foreach ($partidas as $p) {
                        $p->save();
                    }
                }
            }
        }

        /// si el importe ha cambiado, lo guardamos
        if (!$this->floatcmp($this->importe, $importe_old)) {
            $this->save();
        }

        /// comprobamos la factura asociada
        $status = TRUE;
        $fac = $this->get_factura();
        if ($fac) {
            if (is_null($fac->idasiento)) {
                $fac->idasiento = $this->idasiento;
                $status = $fac->save();
            }
        }

        if ($status) {
            return $this->full_test();
        } else
            return FALSE;
    }

    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET numero = " . $this->var2str($this->numero)
                        . ", idconcepto = " . $this->var2str($this->idconcepto)
                        . ", concepto = " . $this->var2str($this->concepto)
                        . ", fecha = " . $this->var2str($this->fecha)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", codplanasiento = " . $this->var2str($this->codplanasiento)
                        . ", editable = " . $this->var2str($this->editable)
                        . ", documento = " . $this->var2str($this->documento)
                        . ", tipodocumento = " . $this->var2str($this->tipodocumento)
                        . ", importe = " . $this->var2str($this->importe)
                        . "  WHERE idasiento = " . $this->var2str($this->idasiento) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $this->new_numero();
                $sql = "INSERT INTO " . $this->table_name . " (numero,idconcepto,concepto,
               fecha,codejercicio,codplanasiento,editable,documento,tipodocumento,importe)
               VALUES (" . $this->var2str($this->numero)
                        . "," . $this->var2str($this->idconcepto)
                        . "," . $this->var2str($this->concepto)
                        . "," . $this->var2str($this->fecha)
                        . "," . $this->var2str($this->codejercicio)
                        . "," . $this->var2str($this->codplanasiento)
                        . "," . $this->var2str($this->editable)
                        . "," . $this->var2str($this->documento)
                        . "," . $this->var2str($this->tipodocumento)
                        . "," . $this->var2str($this->importe) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idasiento = self::$dataBase->lastval();
                    return TRUE;
                } else
                    return FALSE;
            }
        } else
            return FALSE;
    }

    public function delete() {
        $bloquear = FALSE;

        $eje0 = new \ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($this->idasiento == $ejercicio->idasientoapertura) {
                /// permitimos eliminar el asiento de apertura
            } else if ($this->idasiento == $ejercicio->idasientocierre) {
                /// permitimos eliminar el asiento de cierre
            } else if ($this->idasiento == $ejercicio->idasientopyg) {
                /// permitimos eliminar el asiento de pérdidas y ganancias
            } else if ($ejercicio->abierto()) {
                $reg0 = new \regularizacion_iva();
                if ($reg0->get_fecha_inside($this->fecha)) {
                    $this->new_error_msg('El asiento se encuentra dentro de una regularización de '
                            . FS_IVA . '. No se puede eliminar.');
                    $bloquear = TRUE;
                }
            } else {
                $this->new_error_msg('El ejercicio ' . $ejercicio->nombre . ' está cerrado.');
                $bloquear = TRUE;
            }
        }

        if ($bloquear) {
            return FALSE;
        } else {
            /// desvinculamos la factura
            $fac = $this->get_factura();
            if ($fac) {
                if ($fac->idasiento == $this->idasiento) {
                    $fac->idasiento = NULL;
                    $fac->save();
                }
            }

            /// eliminamos las partidas una a una para forzar la actualización de las subcuentas asociadas
            foreach ($this->get_partidas() as $p) {
                $p->delete();
            }

            return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idasiento = " . $this->var2str($this->idasiento) . ";");
        }
    }

    public function search($query, $offset = 0) {
        $alist = array();
        $query = $this->no_html(mb_strtolower($query, 'UTF8'));

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $aux_sql = '';
            if (strtolower(FS_DB_TYPE) == 'postgresql') {
                $aux_sql = '::TEXT';
            }

            $consulta .= "numero" . $aux_sql . " LIKE '%" . $query . "%' OR concepto LIKE '%" . $query
                    . "%' OR importe BETWEEN " . ($query - .01) . " AND " . ($query + .01);
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query)) {
            $consulta .= "fecha = " . $this->var2str($query) . " OR concepto LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(concepto) LIKE '%" . $buscar = str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC";

        $data = self::$dataBase->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alist[] = new \asiento($a);
            }
        }

        return $alist;
    }

    public function all($offset = 0, $limit = FS_ITEM_LIMIT) {
        $alist = array();
        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY fecha DESC, numero DESC";

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $a) {
                $alist[] = new \asiento($a);
            }
        }

        return $alist;
    }

    public function descuadrados() {
        /// iniciamos partidas para asegurarnos que existe la tabla
        new \partida();

        $alist = array();
        $sql = "SELECT p.idasiento,SUM(p.debe) as sdebe,SUM(p.haber) as shaber
         FROM co_partidas p, " . $this->table_name . " a
          WHERE p.idasiento = a.idasiento
           GROUP BY p.idasiento
            HAVING ABS(SUM(p.haber) - SUM(p.debe)) > 0.01
             ORDER BY p.idasiento DESC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $a) {
                $alist[] = $this->get($a['idasiento']);
            }
        }

        return $alist;
    }

    /// renumera todos los asientos. Devuelve FALSE en caso de error
    public function renumerar() {
        $ejercicio = new \ejercicio();
        foreach ($ejercicio->all_abiertos() as $eje) {
            $posicion = 0;
            $numero = 1;
            $sql = '';
            $continuar = TRUE;
            $consulta = "SELECT idasiento,numero,fecha FROM " . $this->table_name
                    . " WHERE codejercicio = " . $this->var2str($eje->codejercicio)
                    . " ORDER BY codejercicio ASC, fecha ASC, idasiento ASC";

            $asientos = self::$dataBase->select_limit($consulta, 1000, $posicion);
            while ($asientos AND $continuar) {
                foreach ($asientos as $col) {
                    if ($col['numero'] != $numero) {
                        $sql .= "UPDATE " . $this->table_name . " SET numero = " . $this->var2str($numero)
                                . " WHERE idasiento = " . $this->var2str($col['idasiento']) . ";";
                    }

                    $numero++;
                }
                $posicion += 1000;

                if ($sql != '') {
                    if (!self::$dataBase->exec($sql)) {
                        $this->new_error_msg("Se ha producido un error mientras se renumeraban los asientos del ejercicio "
                                . $eje->codejercicio);
                        $continuar = FALSE;
                    }
                    $sql = '';
                }

                $asientos = self::$dataBase->select_limit($consulta, 1000, $posicion);
            }
        }

        return $continuar;
    }

    public function cron_job() {
        /**
         * Bloqueamos asientos de ejercicios cerrados o dentro de regularizaciones.
         */
        $eje0 = new \ejercicio();
        $regiva0 = new \regularizacion_iva();
        foreach ($eje0->all() as $ej) {
            if ($ej->abierto()) {
                foreach ($regiva0->all_from_ejercicio($ej->codejercicio) as $reg) {
                    self::$dataBase->exec("UPDATE " . $this->table_name . " SET editable = false WHERE editable = true"
                            . " AND codejercicio = " . $this->var2str($ej->codejercicio)
                            . " AND fecha >= " . $this->var2str($reg->fechainicio)
                            . " AND fecha <= " . $this->var2str($reg->fechafin) . ";");
                }
            } else {
                self::$dataBase->exec("UPDATE " . $this->table_name . " SET editable = false WHERE editable = true"
                        . " AND codejercicio = " . $this->var2str($ej->codejercicio) . ";");
            }
        }

        echo "\nRenumerando asientos...";
        $this->renumerar();
    }

}
