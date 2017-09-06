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
 * La línea de IVA de una factura de cliente.
 * Indica el neto, iva y total para un determinado IVA y una factura.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaIvaFacturaCliente
{
    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idlinea;

    /**
     * ID de la factura relacionada.
     *
     * @var int
     */
    public $idfactura;

    /**
     * neto + totaliva + totalrecargo
     *
     * @var float
     */
    public $totallinea;

    /**
     * Total de recargo de equivalencia para ese impuesto.
     *
     * @var float
     */
    public $totalrecargo;

    /**
     * % de recargo de equivalencia del impuesto.
     *
     * @var float
     */
    public $recargo;

    /**
     * Total de IVA para ese impuesto.
     *
     * @var float
     */
    public $totaliva;

    /**
     * % de IVA del impuesto.
     *
     * @var float
     */
    public $iva;

    /**
     * Código del impuesto relacionado.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Neto o base imponible para ese impuesto.
     *
     * @var float
     */
    public $neto;

    public function tableName()
    {
        return 'lineasivafactcli';
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
     *
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
     * @param int   $idfactura
     * @param float $neto
     * @param float $totaliva
     * @param float $totalrecargo
     *
     * @return bool
     */
    public function facturaTest($idfactura, $neto, $totaliva, $totalrecargo)
    {
        $status = true;

        $liNeto = 0;
        $liIva = 0;
        $liRecargo = 0;
        foreach ($this->allFromFactura($idfactura) as $li) {
            if (!$li->test()) {
                $status = false;
            }

            $liNeto += $li->neto;
            $liIva += $li->totaliva;
            $liRecargo += $li->totalrecargo;
        }

        $liNeto = round($liNeto, FS_NF0);
        $liIva = round($liIva, FS_NF0);
        $liRecargo = round($liRecargo, FS_NF0);

        if (!$this->floatcmp($neto, $liNeto, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los netos de las líneas de IVA debería ser: ' . $neto);
            $status = false;
        } elseif (!$this->floatcmp($totaliva, $liIva, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los totales de iva de las líneas de IVA debería ser: ' . $totaliva);
            $status = false;
        } elseif (!$this->floatcmp($totalrecargo, $liRecargo, FS_NF0, true)) {
            $this->miniLog->alert('La suma de los totalrecargo de las líneas de IVA debería ser: ' . $totalrecargo);
            $status = false;
        }

        return $status;
    }

    /**
     * TODO
     *
     * @param int $idfac
     *
     * @return array
     */
    public function allFromFactura($idfac)
    {
        $linealist = [];

        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($idfac)
            . ' ORDER BY iva DESC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $lin) {
                $linealist[] = new self($lin);
            }
        }

        return $linealist;
    }
}
