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

    use Base\LineaDocumento;
    use Base\ModelTrait;

    /**
     * ID del pedido.
     * @var integer
     */
    public $idpedido;
    private static $pedidos;

    public function __construct($data = [])
    {
        if (!isset(self::$pedidos)) {
            self::$pedidos = [];
        }

        $this->init('lineaspedidosprov', 'idlinea');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
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

    public function test()
    {
        $this->descripcion = $this->no_html($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
            $this->miniLog->critical("Error en el valor de pvptotal de la línea " . $this->referencia . " del " . FS_PEDIDO . ". Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->miniLog->critical("Error en el valor de pvpsindto de la línea " . $this->referencia . " del " . FS_PEDIDO . ". Valor correcto: " . $totalsindto);
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
