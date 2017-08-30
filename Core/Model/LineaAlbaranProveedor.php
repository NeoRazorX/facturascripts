<?php
/**
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

/**
 * Línea de un albarán de proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaAlbaranProveedor
{

    use Base\LineaDocumento;
    use Base\ModelTrait;

    /**
     * TODO
     * @var array
     */
    private static $albaranes;

    /**
     * ID de la línea del pedido relacionada, si la hay.
     * @var int
     */
    public $idlineapedido;

    /**
     * ID del albarán de esta línea.
     * @var int
     */
    public $idalbaran;

    /**
     * ID del pedido relacionado con el albarán, si lo hay.
     * @var int
     */
    public $idpedido;

    /**
     * TODO
     * @var string
     */
    private $codigo;

    /**
     * TODO
     * @var string
     */
    private $fecha;

    public function tableName()
    {
        return 'lineasalbaranesprov';
    }

    public function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * TODO
     * @return null|string
     */
    public function showCodigo()
    {
        if ($this->codigo === null) {
            $this->fill();
        }

        return $this->codigo;
    }

    /**
     * TODO
     * @return string
     */
    public function showFecha()
    {
        if ($this->fecha === null) {
            $this->fill();
        }

        return $this->fecha;
    }

    /**
     * TODO
     * @return string
     */
    public function showNombre()
    {
        $nombre = 'desconocido';

        foreach (self::$albaranes as $a) {
            if ($a->idalbaran === $this->idalbaran) {
                $nombre = $a->nombre;
                break;
            }
        }

        return $nombre;
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, true)) {
            $this->miniLog->alert('Error en el valor de pvptotal de la línea ' . $this->referencia
                . ' del ' . FS_ALBARAN . '. Valor correcto: ' . $total);

            return false;
        }
        if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, true)) {
            $this->miniLog->alert('Error en el valor de pvpsindto de la línea ' . $this->referencia
                . ' del ' . FS_ALBARAN . '. Valor correcto: ' . $totalsindto);

            return false;
        }

        return true;
    }

    /**
     * TODO
     */
    public function cleanCache()
    {
        $this->cache->delete('albpro_top_articulos');
    }

    /**
     * TODO
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query = '', $offset = 0)
    {
        $linealist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $sql .= "referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%'";
        }
        $sql .= ' ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $l) {
                $linealist[] = new LineaAlbaranProveedor($l);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     *
     * @param string $codproveedor
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function searchFromProveedor($codproveedor, $query = '', $offset = 0)
    {
        $linealist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran IN
         (SELECT idalbaran FROM albaranesprov WHERE codproveedor = ' . $this->var2str($codproveedor) . ') AND ';
        if (is_numeric($query)) {
            $sql .= "(referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "(lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%')";
        }
        $sql .= ' ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $l) {
                $linealist[] = new LineaAlbaranProveedor($l);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     * @return int
     */
    public function countByArticulo()
    {
        $sql = 'SELECT COUNT(DISTINCT referencia) AS total FROM ' . $this->tableName() . ';';
        $lineas = $this->dataBase->select($sql);
        if (!empty($lineas)) {
            return (int) $lineas[0]['total'];
        }

        return 0;
    }

    /**
     * Completa con los datos del albarán.
     */
    private function fill()
    {
        $encontrado = false;
        foreach (self::$albaranes as $a) {
            if ($a->idalbaran === $this->idalbaran) {
                $this->codigo = $a->codigo;
                $this->fecha = $a->fecha;
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $alb = new AlbaranProveedor();
            $alb = $alb->get($this->idalbaran);
            if ($alb) {
                $this->codigo = $alb->codigo;
                $this->fecha = $alb->fecha;
                self::$albaranes[] = $alb;
            }
        }
    }
}
