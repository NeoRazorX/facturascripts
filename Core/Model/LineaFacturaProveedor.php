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
 * Línea de una factura de proveedor.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaFacturaProveedor
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idlinea;

    /**
     * ID de la linea del albarán relacionado, si lo hay.
     * @var type 
     */
    public $idlineaalbaran;

    /**
     * ID de la factura de esta línea.
     * @var type 
     */
    public $idfactura;

    /**
     * ID del albarán relacionado con la factura, si lo hay.
     * @var type 
     */
    public $idalbaran;

    /**
     * Importe neto de la línea, sin impuestos.
     * @var type 
     */
    public $pvptotal;

    /**
     * % de descuento.
     * @var type 
     */
    public $dtopor;

    /**
     * % de recargo de equivalencia.
     * @var type 
     */
    public $recargo;

    /**
     * % de IRPF
     * @var type 
     */
    public $irpf;

    /**
     * Importe neto sin descuentos.
     * @var type 
     */
    public $pvpsindto;
    public $cantidad;

    /**
     * Impuesto relacionado.
     * @var type 
     */
    public $codimpuesto;

    /**
     * Precio del artículo, una unidad.
     * @var type 
     */
    public $pvpunitario;
    public $descripcion;

    /**
     * Referencia del artículo.
     * @var type 
     */
    public $referencia;

    /**
     * Código de la combinación seleccionada, en el caso de los artículos con atributos.
     * @var type 
     */
    public $codcombinacion;

    /**
     * % de iva, el que corresponde al impuesto.
     * @var type 
     */
    public $iva;
    private $codigo;
    private $fecha;
    private $albaran_codigo;
    private $albaran_numero;
    private static $facturas;
    private static $albaranes;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'lineasfacturasprov', 'idlinea');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear($l = FALSE)
    {
        $this->idlinea = NULL;
        $this->idlineaalbaran = NULL;
        $this->idfactura = NULL;
        $this->idalbaran = NULL;
        $this->referencia = NULL;
        $this->codcombinacion = NULL;
        $this->descripcion = '';
        $this->cantidad = 0;
        $this->pvpunitario = 0;
        $this->pvpsindto = 0;
        $this->dtopor = 0;
        $this->pvptotal = 0;
        $this->codimpuesto = NULL;
        $this->iva = 0;
        $this->recargo = 0;
        $this->irpf = 0;
    }

    protected function install() {
        return '';
    }

    /**
     * Completa con los datos de la factura.
     */
    private function fill() {
        $encontrado = FALSE;
        foreach (self::$facturas as $f) {
            if ($f->idfactura == $this->idfactura) {
                $this->codigo = $f->codigo;
                $this->fecha = $f->fecha;
                $encontrado = TRUE;
                break;
            }
        }
        if (!$encontrado) {
            $fac = new \factura_proveedor();
            $fac = $fac->get($this->idfactura);
            if ($fac) {
                $this->codigo = $fac->codigo;
                $this->fecha = $fac->fecha;
                self::$facturas[] = $fac;
            }
        }

        if (!is_null($this->idalbaran)) {
            $encontrado = FALSE;
            foreach (self::$albaranes as $a) {
                if ($a->idalbaran == $this->idalbaran) {
                    $this->albaran_codigo = $a->codigo;
                    if (is_null($a->numproveedor) OR $a->numproveedor == '') {
                        $this->albaran_numero = $a->numero;
                    } else {
                        $this->albaran_numero = $a->numproveedor;
                    }
                    $encontrado = TRUE;
                    break;
                }
            }
            if (!$encontrado) {
                $alb = new \albaran_proveedor();
                $alb = $alb->get($this->idalbaran);
                if ($alb) {
                    $this->albaran_codigo = $alb->codigo;
                    if (is_null($alb->numproveedor) OR $alb->numproveedor == '') {
                        $this->albaran_numero = $alb->numero;
                    } else {
                        $this->albaran_numero = $alb->numproveedor;
                    }
                    self::$albaranes[] = $alb;
                }
            }
        }
    }

    public function total_iva() {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    public function descripcion() {
        return nl2br($this->descripcion);
    }

    public function show_codigo() {
        if (!isset($this->codigo)) {
            $this->fill();
        }
        return $this->codigo;
    }

    public function show_fecha() {
        if (!isset($this->fecha)) {
            $this->fill();
        }
        return $this->fecha;
    }

    public function show_nombre() {
        $nombre = 'desconocido';

        foreach (self::$facturas as $a) {
            if ($a->idfactura == $this->idfactura) {
                $nombre = $a->nombre;
                break;
            }
        }

        return $nombre;
    }

    public function url() {
        return 'index.php?page=compras_factura&id=' . $this->idfactura;
    }

    public function albaran_codigo() {
        if (!isset($this->albaran_codigo)) {
            $this->fill();
        }
        return $this->albaran_codigo;
    }

    public function albaran_url() {
        if (is_null($this->idalbaran)) {
            return 'index.php?page=compras_albaranes';
        } else {
                    return 'index.php?page=compras_albaran&id=' . $this->idalbaran;
        }
    }

    public function albaran_numero() {
        if (!isset($this->albaran_numero)) {
            $this->fill();
        }
        return $this->albaran_numero;
    }

    public function articulo_url() {
        if (is_null($this->referencia) OR $this->referencia == '') {
            return "index.php?page=ventas_articulos";
        } else {
                    return "index.php?page=ventas_articulo&ref=" . urlencode($this->referencia);
        }
    }

    /**
     * Devuelve los datos de una linea
     * @param type $idlinea
     * @return boolean|\linea_factura_proveedor
     */
    public function get($idlinea) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($idlinea) . ";");
        if ($data) {
            return new \linea_factura_proveedor($data[0]);
        } else {
            return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->idlinea)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
        }
    }

    public function test() {
        $this->descripcion = $this->no_html($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvptotal de la línea " . $this->referencia
                    . " de la factura. Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvpsindto de la línea " . $this->referencia
                    . " de la factura. Valor correcto: " . $totalsindto);
            return FALSE;
        } else {
                    return TRUE;
        }
    }

    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET pvptotal = " . $this->var2str($this->pvptotal)
                        . ", dtopor = " . $this->var2str($this->dtopor)
                        . ", recargo = " . $this->var2str($this->recargo)
                        . ", irpf = " . $this->var2str($this->irpf)
                        . ", pvpsindto = " . $this->var2str($this->pvpsindto)
                        . ", cantidad = " . $this->var2str($this->cantidad)
                        . ", codimpuesto = " . $this->var2str($this->codimpuesto)
                        . ", pvpunitario = " . $this->var2str($this->pvpunitario)
                        . ", idfactura = " . $this->var2str($this->idfactura)
                        . ", idalbaran = " . $this->var2str($this->idalbaran)
                        . ", idlineaalbaran = " . $this->var2str($this->idlineaalbaran)
                        . ", descripcion = " . $this->var2str($this->descripcion)
                        . ", referencia = " . $this->var2str($this->referencia)
                        . ", codcombinacion = " . $this->var2str($this->codcombinacion)
                        . ", iva = " . $this->var2str($this->iva)
                        . "  WHERE idlinea = " . $this->var2str($this->idlinea) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (pvptotal,dtopor,recargo,irpf,pvpsindto,cantidad,
               codimpuesto,pvpunitario,idfactura,idalbaran,idlineaalbaran,descripcion,referencia,
               codcombinacion,iva) VALUES 
                      (" . $this->var2str($this->pvptotal)
                        . "," . $this->var2str($this->dtopor)
                        . "," . $this->var2str($this->recargo)
                        . "," . $this->var2str($this->irpf)
                        . "," . $this->var2str($this->pvpsindto)
                        . "," . $this->var2str($this->cantidad)
                        . "," . $this->var2str($this->codimpuesto)
                        . "," . $this->var2str($this->pvpunitario)
                        . "," . $this->var2str($this->idfactura)
                        . "," . $this->var2str($this->idalbaran)
                        . "," . $this->var2str($this->idlineaalbaran)
                        . "," . $this->var2str($this->descripcion)
                        . "," . $this->var2str($this->referencia)
                        . "," . $this->var2str($this->codcombinacion)
                        . "," . $this->var2str($this->iva) . ");";

                if (self::$dataBase->exec($sql)) {
                    $this->idlinea = self::$dataBase->lastval();
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
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
    }

    public function all_from_factura($id) {
        $linlist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idfactura = " . $this->var2str($id)
                . " ORDER BY idlinea ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $l) {
                $linlist[] = new \linea_factura_proveedor($l);
            }
        }

        return $linlist;
    }

    public function all_from_articulo($ref, $offset = 0) {
        $linealist = array();
        $sql = "SELECT * FROM " . $this->table_name .
                " WHERE referencia = " . $this->var2str($ref) .
                " ORDER BY idfactura DESC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_factura_proveedor($l);
            }
        }

        return $linealist;
    }

    public function search($query = '', $offset = 0) {
        $linealist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $sql = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $sql .= "referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%'";
        }
        $sql .= " ORDER BY idfactura DESC, idlinea ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_factura_proveedor($l);
            }
        }

        return $linealist;
    }

    public function facturas_from_albaran($id) {
        $facturalist = array();
        $sql = "SELECT DISTINCT idfactura FROM " . $this->table_name
                . " WHERE idalbaran = " . $this->var2str($id) . ";";

        $data = self::$dataBase->select($sql);
        if ($data) {
            $factura = new \factura_proveedor();
            foreach ($data as $l) {
                $fac = $factura->get($l['idfactura']);
                if ($fac) {
                    $facturalist[] = $fac;
                }
            }
        }

        return $facturalist;
    }

}
