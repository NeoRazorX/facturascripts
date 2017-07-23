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

use FacturaScripts\Core\Base\Model;

/**
 * Línea de un albarán de proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaAlbaranProveedor
{
    use Model;

    /**
     * TODO
     * @var array
     */
    private static $albaranes;
    /**
     * Clave primaria.
     * @var int
     */
    public $idlinea;
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
     * Referencia del artículo.
     * @var string
     */
    public $referencia;
    /**
     * Código de la combinación seleccionada, en el caso de los artículos con atributos.
     * @var
     */
    public $codcombinacion;
    /**
     * TODO
     * @var string
     */
    public $descripcion;
    /**
     * TODO
     * @var float
     */
    public $cantidad;
    /**
     * % de descuento.
     * @var float
     */
    public $dtopor;
    /**
     * Código del impuesto relacionado.
     * @var string
     */
    public $codimpuesto;
    /**
     * % del impuesto relacionado.
     * @var float
     */
    public $iva;
    /**
     * Importe neto de la línea, sin impuestos.
     * @var float
     */
    public $pvptotal;
    /**
     * Importe neto sin descuentos.
     * @var float
     */
    public $pvpsindto;
    /**
     * Precio del artículo, una unidad.
     * @var float
     */
    public $pvpunitario;
    /**
     * % de IRPF de la línea.
     * @var float
     */
    public $irpf;
    /**
     * % de recargo de equivalencia de la línea.
     * @var float
     */
    public $recargo;
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

    /**
     * LineaAlbaranProveedor constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'lineasalbaranesprov', 'idlinea');
        $this->clear();
        if (!empty($data)) {
            $this->loadFromData($data);
        }
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
    }

    /**
     * TODO
     * @return float|int
     */
    public function pvpIva()
    {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    /**
     * TODO
     * @return float|int
     */
    public function totalIva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /**
     * TODO
     * @return float|int
     */
    public function totalIva2()
    {
        if ($this->cantidad === 0) {
            return 0;
        }
        return $this->pvptotal * (100 + $this->iva) / 100 / $this->cantidad;
    }

    /**
     * TODO
     * @return string
     */
    public function getDescripcion()
    {
        return nl2br($this->descripcion);
    }

    /// Devuelve el precio total por unidad (con descuento incluido e iva aplicado)

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
     * @return \DateTime|null
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
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=ComprasAlbaran&id=' . $this->idalbaran;
    }

    /**
     * TODO
     * @return string
     */
    public function articuloUrl()
    {
        if ($this->referencia === null || $this->referencia === '') {
            return 'index.php?page=VentasArticulos';
        }
        return 'index.php?page=VentasArticulo&ref=' . urlencode($this->referencia);
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $this->descripcion = static::noHtml($this->descripcion);
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
     * @param int $idalb
     *
     * @return array
     */
    public function allFromAlbaran($idalb)
    {
        $linealist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran = ' . $this->var2str($idalb)
            . ' ORDER BY idlinea ASC;';

        $data = $this->database->select($sql);
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

        $data = $this->database->selectLimit($sql, $limit, $offset);
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
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query = '', $offset = 0)
    {
        $linealist = [];
        $query = mb_strtolower(static::noHtml($query), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $sql .= "referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%'";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%'";
        }
        $sql .= ' ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
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
        $query = mb_strtolower(static::noHtml($query), 'UTF8');

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idalbaran IN
         (SELECT idalbaran FROM albaranesprov WHERE codproveedor = ' . $this->var2str($codproveedor) . ') AND ';
        if (is_numeric($query)) {
            $sql .= "(referencia LIKE '%" . $query . "%' OR descripcion LIKE '%" . $query . "%')";
        } else {
            $buscar = str_replace(' ', '%', $query);
            $sql .= "(lower(referencia) LIKE '%" . $buscar . "%' OR lower(descripcion) LIKE '%" . $buscar . "%')";
        }
        $sql .= ' ORDER BY idalbaran DESC, idlinea ASC';

        $data = $this->database->selectLimit($sql, FS_ITEM_LIMIT, $offset);
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
        $lineas = $this->database->select($sql);
        if (!empty($lineas)) {
            return (int)$lineas[0]['total'];
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
