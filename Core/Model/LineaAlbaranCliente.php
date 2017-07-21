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
 * Línea de un albarán de cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaAlbaranCliente 
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idlinea;

    /**
     * ID de la línea del pedido relacionado, si es que lo hay.
     * @var type 
     */
    public $idlineapedido;

    /**
     * ID del albaran de esta línea.
     * @var type 
     */
    public $idalbaran;

    /**
     * ID del pedido relacionado con el albarán relacionado.
     * @var type 
     */
    public $idpedido;

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
    public $descripcion;
    public $cantidad;

    /**
     * % de descuento.
     * @var type 
     */
    public $dtopor;

    /**
     * Código del impuesto del artículo.
     * @var type 
     */
    public $codimpuesto;

    /**
     * % del impuesto relacionado.
     * @var type 
     */
    public $iva;

    /**
     * Importe neto de la linea, sin impuestos.
     * @var type 
     */
    public $pvptotal;

    /**
     * Importe neto sin descuento, es decir, pvpunitario * cantidad.
     * @var type 
     */
    public $pvpsindto;

    /**
     * Precio del artículo, una sola unidad.
     * @var type 
     */
    public $pvpunitario;

    /**
     * % de IRPF de la línea.
     * @var type 
     */
    public $irpf;

    /**
     * % de recargo de equivalencia de la línea.
     * @var type 
     */
    public $recargo;

    /**
     * Posición de la linea en el documento. Cuanto más alto más abajo.
     * @var type 
     */
    public $orden;

    /**
     * False -> no se muestra la columna cantidad al imprimir.
     * @var type 
     */
    public $mostrar_cantidad;

    /**
     * False -> no se muestran las columnas precio, descuento, impuestos y total al imprimir.
     * @var type 
     */
    public $mostrar_precio;
    private $codigo;
    private $fecha;
    private static $albaranes;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'lineasalbaranescli', 'idlinea');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear() 
    {
        $this->idlinea = NULL;
        $this->idlineapedido = NULL;
        $this->idalbaran = NULL;
        $this->idpedido = NULL;
        $this->referencia = NULL;
        $this->codcombinacion = NULL;
        $this->descripcion = '';
        $this->cantidad = 0;
        $this->dtopor = 0;
        $this->codimpuesto = NULL;
        $this->iva = 0;
        $this->pvptotal = 0;
        $this->pvpsindto = 0;
        $this->pvpunitario = 0;
        $this->irpf = 0;
        $this->recargo = 0;
        $this->orden = 0;
        $this->mostrar_cantidad = TRUE;
        $this->mostrar_precio = TRUE;
    }

    protected function install() {
        return '';
    }

    /**
     * Completa con los datos del albarán.
     */
    private function fill() {
        $encontrado = FALSE;
        foreach (self::$albaranes as $a) {
            if ($a->idalbaran == $this->idalbaran) {
                $this->codigo = $a->codigo;
                $this->fecha = $a->fecha;
                $encontrado = TRUE;
                break;
            }
        }
        if (!$encontrado) {
            $alb = new \albaran_cliente();
            $alb = $alb->get($this->idalbaran);
            if ($alb) {
                $this->codigo = $alb->codigo;
                $this->fecha = $alb->fecha;
                self::$albaranes[] = $alb;
            }
        }
    }

    public function pvp_iva() {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    public function total_iva() {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /// Devuelve el precio total por unidad (con descuento incluido e iva aplicado)
    public function total_iva2() {
        if ($this->cantidad == 0) {
            return 0;
        } else {
                    return $this->pvptotal * (100 + $this->iva) / 100 / $this->cantidad;
        }
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

    public function show_nombrecliente() {
        $nombre = 'desconocido';

        foreach (self::$albaranes as $a) {
            if ($a->idalbaran == $this->idalbaran) {
                $nombre = $a->nombrecliente;
                break;
            }
        }

        return $nombre;
    }

    public function url() {
        return 'index.php?page=ventas_albaran&id=' . $this->idalbaran;
    }

    public function articulo_url() {
        if (is_null($this->referencia) OR $this->referencia == '') {
            return "index.php?page=ventas_articulos";
        } else {
                    return "index.php?page=ventas_articulo&ref=" . urlencode($this->referencia);
        }
    }

    /**
     * Devuelve los datos de una linea.
     * @param type $idlinea
     * @return boolean|\linea_albaran_cliente
     */
    public function get($idlinea) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($idlinea) . ";");
        if ($data) {
            return new \linea_albaran_cliente($data[0]);
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
                    . " del " . FS_ALBARAN . ". Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvpsindto de la línea " . $this->referencia
                    . " del " . FS_ALBARAN . ". Valor correcto: " . $totalsindto);
            return FALSE;
        } else {
                    return TRUE;
        }
    }

    public function save() {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET idalbaran = " . $this->var2str($this->idalbaran)
                        . ", idpedido = " . $this->var2str($this->idpedido)
                        . ", idlineapedido = " . $this->var2str($this->idlineapedido)
                        . ", referencia = " . $this->var2str($this->referencia)
                        . ", codcombinacion = " . $this->var2str($this->codcombinacion)
                        . ", descripcion = " . $this->var2str($this->descripcion)
                        . ", cantidad = " . $this->var2str($this->cantidad)
                        . ", dtopor = " . $this->var2str($this->dtopor)
                        . ", codimpuesto = " . $this->var2str($this->codimpuesto)
                        . ", iva = " . $this->var2str($this->iva)
                        . ", pvptotal = " . $this->var2str($this->pvptotal)
                        . ", pvpsindto = " . $this->var2str($this->pvpsindto)
                        . ", pvpunitario = " . $this->var2str($this->pvpunitario)
                        . ", irpf = " . $this->var2str($this->irpf)
                        . ", recargo = " . $this->var2str($this->recargo)
                        . ", orden = " . $this->var2str($this->orden)
                        . ", mostrar_cantidad = " . $this->var2str($this->mostrar_cantidad)
                        . ", mostrar_precio = " . $this->var2str($this->mostrar_precio)
                        . "  WHERE idlinea = " . $this->var2str($this->idlinea) . ";";

                return self::$dataBase->exec($sql);
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (idlineapedido,idalbaran,idpedido,referencia,codcombinacion,
               descripcion,cantidad,dtopor,codimpuesto,iva,pvptotal,pvpsindto,pvpunitario,irpf,recargo,orden,
               mostrar_cantidad,mostrar_precio) VALUES
                      (" . $this->var2str($this->idlineapedido)
                        . "," . $this->var2str($this->idalbaran)
                        . "," . $this->var2str($this->idpedido)
                        . "," . $this->var2str($this->referencia)
                        . "," . $this->var2str($this->codcombinacion)
                        . "," . $this->var2str($this->descripcion)
                        . "," . $this->var2str($this->cantidad)
                        . "," . $this->var2str($this->dtopor)
                        . "," . $this->var2str($this->codimpuesto)
                        . "," . $this->var2str($this->iva)
                        . "," . $this->var2str($this->pvptotal)
                        . "," . $this->var2str($this->pvpsindto)
                        . "," . $this->var2str($this->pvpunitario)
                        . "," . $this->var2str($this->irpf)
                        . "," . $this->var2str($this->recargo)
                        . "," . $this->var2str($this->orden)
                        . "," . $this->var2str($this->mostrar_cantidad)
                        . "," . $this->var2str($this->mostrar_precio) . ");";

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
        $this->clean_cache();
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idlinea = " . $this->var2str($this->idlinea) . ";");
    }

    public function clean_cache() {
        $this->cache->delete('albcli_top_articulos');
    }

    /**
     * Devuelve las líneas del albarán.
     * @param type $id
     * @return \linea_albaran_cliente
     */
    public function all_from_albaran($id) {
        $linealist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idalbaran = " . $this->var2str($id)
                . " ORDER BY orden DESC, idlinea ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
            }
        }

        return $linealist;
    }

    public function all_from_articulo($ref, $offset = 0, $limit = FS_ITEM_LIMIT) {
        $linealist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref)
                . " ORDER BY idalbaran DESC";

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
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
        $sql .= " ORDER BY idalbaran DESC, idlinea ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
            }
        }

        return $linealist;
    }

    public function search_from_cliente($codcliente, $query = '', $offset = 0) {
        $linealist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idalbaran IN
         (SELECT idalbaran FROM albaranescli WHERE codcliente = " . $this->var2str($codcliente) . ") AND ";
        if (is_numeric($query)) {
            $sql .= "(referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "(lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%')";
        }
        $sql .= " ORDER BY idalbaran DESC, idlinea ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
            }
        }

        return $linealist;
    }

    public function search_from_cliente2($codcliente, $ref = '', $obs = '', $offset = 0) {
        $linealist = array();
        $ref = mb_strtolower($this->no_html($ref), 'UTF8');
        $obs = mb_strtolower($this->no_html($obs), 'UTF8');

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idalbaran IN
         (SELECT idalbaran FROM albaranescli WHERE codcliente = " . $this->var2str($codcliente) . "
         AND lower(observaciones) LIKE '" . $obs . "%') AND ";
        if (is_numeric($ref)) {
            $sql .= "(referencia LIKE '%" . $ref . "%' OR descripcion LIKE '%" . $ref . "%')";
        } else {
            $buscar = str_replace(' ', '%', $ref);
            $sql .= "(lower(referencia) LIKE '%" . $ref . "%' OR lower(descripcion) LIKE '%" . $ref . "%')";
        }
        $sql .= " ORDER BY idalbaran DESC, idlinea ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
            }
        }

        return $linealist;
    }

    public function last_from_cliente($codcliente, $offset = 0) {
        $linealist = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idalbaran IN
         (SELECT idalbaran FROM albaranescli WHERE codcliente = " . $this->var2str($codcliente) . ")
         ORDER BY idalbaran DESC, idlinea ASC";

        $data = self::$dataBase->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new \linea_albaran_cliente($l);
            }
        }

        return $linealist;
    }

    public function count_by_articulo() {
        $lineas = self::$dataBase->select("SELECT COUNT(DISTINCT referencia) as total FROM " . $this->table_name . ";");
        if ($lineas) {
            return intval($lineas[0]['total']);
        } else {
                    return 0;
        }
    }

}
