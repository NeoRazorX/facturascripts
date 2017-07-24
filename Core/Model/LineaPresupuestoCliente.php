<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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

/**
 * Línea de presupuesto de cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaPresupuestoCliente
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var integer
     */
    public $idlinea;

    /**
     * ID del presupuesto.
     * @var integer
     */
    public $idpresupuesto;
    public $cantidad;

    /**
     * Código del impuesto relacionado.
     * @var type 
     */
    public $codimpuesto;
    public $descripcion;

    /**
     * % de descuento.
     * @var type 
     */
    public $dtopor;

    /**
     * % de retención IRPF.
     * @var type 
     */
    public $irpf;

    /**
     * % del impuesto relacionado.
     * @var type 
     */
    public $iva;

    /**
     * Importe neto sin descuento, es decir, pvpunitario * cantidad.
     * @var type 
     */
    public $pvpsindto;

    /**
     * Importe neto de la linea, sin impuestos.
     * @var type 
     */
    public $pvptotal;

    /**
     * Precio de una unidad.
     * @var type 
     */
    public $pvpunitario;

    /**
     * % de recargo de equivalencia.
     * @var type 
     */
    public $recargo;

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
    private static $presupuestos;

    public function __construct($data = [])
    {
        if (!isset(self::$presupuestos)) {
            self::$presupuestos = array();
        }

        $this->init(__CLASS__, 'lineaspresupuestoscli', 'idlinea');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    public function clear()
    {
        $this->idlinea = NULL;
        $this->idpresupuesto = NULL;
        $this->cantidad = 0;
        $this->codimpuesto = NULL;
        $this->descripcion = '';
        $this->dtopor = 0;
        $this->irpf = 0;
        $this->iva = 0;
        $this->pvpsindto = 0;
        $this->pvptotal = 0;
        $this->pvpunitario = 0;
        $this->recargo = 0;
        $this->referencia = NULL;
        $this->codcombinacion = NULL;
        $this->orden = 0;
        $this->mostrar_cantidad = TRUE;
        $this->mostrar_precio = TRUE;
    }

    public function pvp_iva()
    {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    public function total_iva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    public function descripcion()
    {
        return nl2br($this->descripcion);
    }

    public function show_codigo()
    {
        $codigo = 'desconocido';

        $encontrado = FALSE;
        foreach (self::$presupuestos as $p) {
            if ($p->idpresupuesto == $this->idpresupuesto) {
                $codigo = $p->codigo;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PresupuestoCliente();
            self::$presupuestos[] = $pre->get($this->idpresupuesto);
            $codigo = self::$presupuestos[count(self::$presupuestos) - 1]->codigo;
        }

        return $codigo;
    }

    public function show_fecha()
    {
        $fecha = 'desconocida';

        $encontrado = FALSE;
        foreach (self::$presupuestos as $p) {
            if ($p->idpresupuesto == $this->idpresupuesto) {
                $fecha = $p->fecha;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PresupuestoCliente();
            self::$presupuestos[] = $pre->get($this->idpresupuesto);
            $fecha = self::$presupuestos[count(self::$presupuestos) - 1]->fecha;
        }

        return $fecha;
    }

    public function show_nombrecliente()
    {
        $nombre = 'desconocido';

        $encontrado = FALSE;
        foreach (self::$presupuestos as $p) {
            if ($p->idpresupuesto == $this->idpresupuesto) {
                $nombre = $p->nombrecliente;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PresupuestoCliente();
            self::$presupuestos[] = $pre->get($this->idpresupuesto);
            $nombre = self::$presupuestos[count(self::$presupuestos) - 1]->nombrecliente;
        }

        return $nombre;
    }

    public function url()
    {
        return 'index.php?page=ventas_presupuesto&id=' . $this->idpresupuesto;
    }

    public function articulo_url()
    {
        if (is_null($this->referencia) OR $this->referencia == '') {
            return "index.php?page=ventas_articulos";
        } else
            return "index.php?page=ventas_articulo&ref=" . urlencode($this->referencia);
    }

    public function test()
    {
        $this->descripcion = $this->no_html($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvptotal de la línea " . $this->referencia . " del " . FS_PRESUPUESTO . ". Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvpsindto de la línea " . $this->referencia . " del " . FS_PRESUPUESTO . ". Valor correcto: " . $totalsindto);
            return FALSE;
        } else
            return TRUE;
    }

    /**
     * Busca todas las coincidencias de $query en las líneas.
     * @param string $query
     * @param integer $offset
     * @return \LineaPresupuestoCliente
     */
    public function search($query = '', $offset = 0)
    {
        $linealist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $sql = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $sql .= "referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%'";
        }
        $sql .= " ORDER BY idpresupuesto DESC, idlinea ASC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new LineaPresupuestoCliente($l);
            }
        }

        return $linealist;
    }

    /**
     * Busca todas las coincidencias de $query en las líneas del cliente $codcliente
     * @param string $codcliente
     * @param string $ref
     * @param string $obs
     * @param integer $offset
     * @return \LineaPresupuestoCliente
     */
    public function search_from_cliente2($codcliente, $ref = '', $obs = '', $offset = 0)
    {
        $linealist = array();
        $ref = mb_strtolower($this->no_html($ref), 'UTF8');
        $obs = mb_strtolower($this->no_html($obs), 'UTF8');

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idpresupuesto IN
         (SELECT idpresupuesto FROM presupuestoscli WHERE codcliente = " . $this->var2str($codcliente) . "
         AND lower(observaciones) LIKE '" . $obs . "%') AND ";
        if (is_numeric($ref)) {
            $sql .= "(referencia LIKE '%" . $ref . "%' OR descripcion LIKE '%" . $ref . "%')";
        } else {
            $buscar = str_replace(' ', '%', $ref);
            $sql .= "(lower(referencia) LIKE '%" . $ref . "%' OR lower(descripcion) LIKE '%" . $ref . "%')";
        }
        $sql .= " ORDER BY idpresupuesto DESC, idlinea ASC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new LineaPresupuestoCliente($l);
            }
        }

        return $linealist;
    }
}
