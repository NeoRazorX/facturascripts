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
 * Línea de pedido de cliente.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaPedidoCliente
{

    use Base\LineaDocumento;
    use Base\ModelTrait;

    /**
     * ID de la linea relacionada en el presupuesto relacionado,
     * si lo hay.
     * @var integer
     */
    public $idlineapresupuesto;

    /**
     * ID del pedido.
     * @var integer
     */
    public $idpedido;

    /**
     * ID del presupuesto relacionado, si lo hay.
     * @var integer
     */
    public $idpresupuesto;

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
    private static $pedidos;

    public function tableName()
    {
        return 'lineaspedidoscli';
    }

    public function primaryColumn()
    {
        return 'idlinea';
    }

    public function clear()
    {
        $this->idlinea = NULL;
        $this->idlineapresupuesto = NULL;
        $this->idpedido = NULL;
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
            $pre = new PedidoCliente();
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
            $pre = new PedidoCliente();
            self::$pedidos[] = $pre->get($this->idpedido);
            $fecha = self::$pedidos[count(self::$pedidos) - 1]->fecha;
        }

        return $fecha;
    }

    public function show_nombrecliente()
    {
        $nombre = 'desconocido';

        $encontrado = FALSE;
        foreach (self::$pedidos as $p) {
            if ($p->idpedido == $this->idpedido) {
                $nombre = $p->nombrecliente;
                $encontrado = TRUE;
                break;
            }
        }

        if (!$encontrado) {
            $pre = new PedidoCliente();
            self::$pedidos[] = $pre->get($this->idpedido);
            $nombre = self::$pedidos[count(self::$pedidos) - 1]->nombrecliente;
        }

        return $nombre;
    }

    public function url()
    {
        return 'index.php?page=ventas_pedido&id=' . $this->idpedido;
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
     * @return \LineaPedidoCliente
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
                $linealist[] = new LineaPedidoCliente($l);
            }
        }

        return $linealist;
    }
}
