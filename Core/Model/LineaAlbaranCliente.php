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
 * Línea de un albarán de cliente.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaAlbaranCliente
{

    use Base\LineaDocumento;
    use Base\ModelTrait;

    /**
     * TODO
     * @var array
     */
    private static $albaranes;

    /**
     * ID de la línea del pedido relacionado, si es que lo hay.
     * @var int
     */
    public $idlineapedido;

    /**
     * ID del albaran de esta línea.
     * @var int
     */
    public $idalbaran;

    /**
     * ID del pedido relacionado con el albarán relacionado.
     * @var int
     */
    public $idpedido;

    /**
     * Posición de la linea en el documento. Cuanto más alto más abajo.
     * @var int
     */
    public $orden;

    /**
     * False -> no se muestra la columna cantidad al imprimir.
     * @var bool
     */
    public $mostrar_cantidad;

    /**
     * False -> no se muestran las columnas precio, descuento, impuestos y total al imprimir.
     * @var bool
     */
    public $mostrar_precio;

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
        return 'lineasalbaranescli';
    }

    public function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idlinea = null;
        $this->idlineapedido = null;
        $this->idalbaran = null;
        $this->idpedido = null;
        $this->referencia = null;
        $this->codcombinacion = null;
        $this->descripcion = '';
        $this->cantidad = 0;
        $this->dtopor = 0;
        $this->codimpuesto = null;
        $this->iva = 0;
        $this->pvptotal = 0;
        $this->pvpsindto = 0;
        $this->pvpunitario = 0;
        $this->irpf = 0;
        $this->recargo = 0;
        $this->orden = 0;
        $this->mostrar_cantidad = true;
        $this->mostrar_precio = true;
    }

    /**
     * TODO
     * @return string
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
    public function showNombrecliente()
    {
        $nombre = 'desconocido';

        foreach (self::$albaranes as $a) {
            if ($a->idalbaran === $this->idalbaran) {
                $nombre = $a->nombrecliente;
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
        $this->cache->delete('albcli_top_articulos');
    }

    /**
     * Devuelve las líneas del albarán.
     *
     * @param int $idalb
     *
     * @return array
     */
    public function allFromAlbaran($idalb)
    {
        $linealist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran = ' . $this->var2str($idalb)
            . ' ORDER BY orden DESC, idlinea ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $lin) {
                $linealist[] = new LineaAlbaranCliente($lin);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     *
     * @param string $ref
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function allFromArticulo($ref, $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $linealist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE referencia = ' . $this->var2str($ref)
            . ' ORDER BY idalbaran DESC';

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $l) {
                $linealist[] = new LineaAlbaranCliente($l);
            }
        }

        return $linealist;
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
                $linealist[] = new LineaAlbaranCliente($l);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     *
     * @param string $codcliente
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function searchFromCliente($codcliente, $query = '', $offset = 0)
    {
        $linealist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran IN
         (SELECT idalbaran FROM albaranescli WHERE codcliente = ' . $this->var2str($codcliente) . ') AND ';
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
                $linealist[] = new LineaAlbaranCliente($l);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     *
     * @param string $codcliente
     * @param string $ref
     * @param string $obs
     * @param int $offset
     *
     * @return array
     */
    public function searchFromCliente2($codcliente, $ref = '', $obs = '', $offset = 0)
    {
        $linealist = [];
        $ref = mb_strtolower(self::noHtml($ref), 'UTF8');
        $obs = mb_strtolower(self::noHtml($obs), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE idalbaran IN (SELECT idalbaran FROM albaranescli WHERE codcliente = '
            . $this->var2str($codcliente) . " AND lower(observaciones) LIKE '" . $obs . "%') AND ";
        if (is_numeric($ref)) {
            $sql .= "(referencia LIKE '%" . $ref . "%' OR descripcion LIKE '%" . $ref . "%')";
        } else {
            $buscar = str_replace(' ', '%', $ref);
            $sql .= "(lower(referencia) LIKE '%" . $ref . "%' OR lower(descripcion) LIKE '%" . $ref . "%')";
        }
        $sql .= ' ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $l) {
                $linealist[] = new LineaAlbaranCliente($l);
            }
        }

        return $linealist;
    }

    /**
     * TODO
     *
     * @param string $codcliente
     * @param int $offset
     *
     * @return array
     */
    public function lastFromCliente($codcliente, $offset = 0)
    {
        $linealist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran IN
         (SELECT idalbaran FROM albaranescli WHERE codcliente = ' . $this->var2str($codcliente) . ')
         ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $l) {
                $linealist[] = new LineaAlbaranCliente($l);
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
            $alb = new AlbaranCliente();
            $alb = $alb->get($this->idalbaran);
            if ($alb) {
                $this->codigo = $alb->codigo;
                $this->fecha = $alb->fecha;
                self::$albaranes[] = $alb;
            }
        }
    }
}
