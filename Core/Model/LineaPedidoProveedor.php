<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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
 * Línea de pedido de proveedor.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaPedidoProveedor
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idlinea;

    /**
     * ID del pedido.
     * @var type 
     */
    public $idpedido;
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
     * % de retención IRPF
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
     * Precio de un unidad.
     * @var type 
     */
    public $pvpunitario;

    /**
     * % de recargo de equivalencia RE.
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
    private static $pedidos;

    public function __construct($data = [])
    {
        if (!isset(self::$pedidos)) {
            self::$pedidos = array();
        }

        $this->init(__CLASS__, 'lineaspedidosprov', 'idlinea');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    public function clear()
    {
        $this->cantidad = 0;
        $this->codimpuesto = NULL;
        $this->descripcion = '';
        $this->dtopor = 0;
        $this->idlinea = NULL;
        $this->idpedido = NULL;
        $this->irpf = 0;
        $this->iva = 0;
        $this->pvpsindto = 0;
        $this->pvptotal = 0;
        $this->pvpunitario = 0;
        $this->recargo = 0;
        $this->referencia = NULL;
        $this->codcombinacion = NULL;
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
        foreach (self::$pedidos as $p) {
            if ($p->idpedido == $this->idpedido) {
                $codigo = $p->codigo;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PedidoProveedor();
            self::$pedidos[] = $pre->get($this->idpedido);
            $codigo = self::$pedidos[count(self::$pedidos) - 1]->codigo;
        }

        return $codigo;
    }

    public function show_fecha()
    {
        $fecha = 'desconocida';

        $encontrado = FALSE;
        foreach (self::$pedidos as $p) {
            if ($p->idpedido == $this->idpedido) {
                $fecha = $p->fecha;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PedidoProveedor();
            self::$pedidos[] = $pre->get($this->idpedido);
            $fecha = self::$pedidos[count(self::$pedidos) - 1]->fecha;
        }

        return $fecha;
    }

    public function show_nombre()
    {
        $nombre = 'desconocido';

        $encontrado = FALSE;
        foreach (self::$pedidos as $p) {
            if ($p->idpedido == $this->idpedido) {
                $nombre = $p->nombre;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PedidoProveedor();
            self::$pedidos[] = $pre->get($this->idpedido);
            $nombre = self::$pedidos[count(self::$pedidos) - 1]->nombre;
        }

        return $nombre;
    }

    public function url()
    {
        return 'index.php?page=compras_pedido&id=' . $this->idpedido;
    }

    public function articulo_url()
    {
        if (is_null($this->referencia) OR $this->referencia == '') {
            return "index.php?page=ventas_articulos";
        } else {
            return "index.php?page=ventas_articulo&ref=" . urlencode($this->referencia);
        }
    }

    public function test()
    {
        $this->descripcion = $this->no_html($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvptotal de la línea " . $this->referencia . " del " . FS_PEDIDO . ". Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->new_error_msg("Error en el valor de pvpsindto de la línea " . $this->referencia . " del " . FS_PEDIDO . ". Valor correcto: " . $totalsindto);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Busca todas las coincidencias de $query en las líneas.
     * @param string $query
     * @param integer $offset
     * @return \LineaPedidoProveedor
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
        $sql .= " ORDER BY idpedido DESC, idlinea ASC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new LineaPedidoProveedor($l);
            }
        }

        return $linealist;
    }
}
