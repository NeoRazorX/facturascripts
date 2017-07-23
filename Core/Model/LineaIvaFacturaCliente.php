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
 * La línea de IVA de una factura de cliente.
 * Indica el neto, iva y total para un determinado IVA y una factura.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaIvaFacturaCliente
{
    use Model;

    /**
     * Clave primaria.
     * @var int
     */
    public $idlinea;

    /**
     * ID de la factura relacionada.
     * @var int
     */
    public $idfactura;

    /**
     * neto + totaliva + totalrecargo
     * @var float
     */
    public $totallinea;

    /**
     * Total de recargo de equivalencia para ese impuesto.
     * @var float
     */
    public $totalrecargo;

    /**
     * % de recargo de equivalencia del impuesto.
     * @var float
     */
    public $recargo;

    /**
     * Total de IVA para ese impuesto.
     * @var float
     */
    public $totaliva;

    /**
     * % de IVA del impuesto.
     * @var float
     */
    public $iva;

    /**
     * Código del impuesto relacionado.
     * @var string
     */
    public $codimpuesto;

    /**
     * Neto o base imponible para ese impuesto.
     * @var float
     */
    public $neto;

    /**
     * LineaIvaFacturaCliente constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'lineasivafactcli', 'idlinea');
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
        $this->idfactura = null;
        $this->neto = 0;
        $this->codimpuesto = null;
        $this->iva = 0;
        $this->totaliva = 0;
        $this->recargo = 0;
        $this->totalrecargo = 0;
        $this->totallinea = 0;
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        if ($this->floatcmp($this->totallinea, $this->neto + $this->totaliva + $this->totalrecargo, FS_NF0, true)) {
            return true;
        }
        $this->miniLog->alert('Error en el valor de totallinea de la línea de IVA del impuesto ' .
            $this->codimpuesto . ' de la factura. Valor correcto: ' .
            round($this->neto + $this->totaliva + $this->totalrecargo, FS_NF0));
        return false;
    }

    /**
     * TODO
     *
     * @param $idfactura
     * @param $neto
     * @param $totaliva
     * @param $totalrecargo
     *
     * @return bool
     */
    public function facturaTest($idfactura, $neto, $totaliva, $totalrecargo)
    {
        $status = true;

        $li_neto = 0;
        $li_iva = 0;
        $li_recargo = 0;
        foreach ($this->allFromFactura($idfactura) as $li) {
            if (!$li->test()) {
                $status = false;
            }

            $li_neto += $li->neto;
            $li_iva += $li->totaliva;
            $li_recargo += $li->totalrecargo;
        }

        $li_neto = round($li_neto, FS_NF0);
        $li_iva = round($li_iva, FS_NF0);
        $li_recargo = round($li_recargo, FS_NF0);

        if (!$this->floatcmp($neto, $li_neto, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los netos de las líneas de IVA debería ser: ' . $neto);
            $status = false;
        } elseif (!$this->floatcmp($totaliva, $li_iva, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los totales de iva de las líneas de IVA debería ser: ' . $totaliva);
            $status = false;
        } elseif (!$this->floatcmp($totalrecargo, $li_recargo, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los totalrecargo de las líneas de IVA debería ser: ' . $totalrecargo);
            $status = false;
        }

        return $status;
    }

    /**
     * TODO
     *
     * @param $id
     *
     * @return array
     */
    public function allFromFactura($id)
    {
        $linealist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($id)
            . ' ORDER BY iva DESC;';
        $data = $this->database->select($sql);
        if ($data) {
            foreach ($data as $l) {
                $linealist[] = new LineaIvaFacturaCliente($l);
            }
        }

        return $linealist;
    }
}
