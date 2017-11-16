<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * La línea de IVA de una factura de proveedor.
 * Indica el neto, iva y total para un determinado IVA y una factura.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaIvaFacturaProveedor
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
     * neto + totaliva + totalrecargo.
     *
     * @var float|int
     */
    public $totallinea;

    /**
     * Total de recargo de equivalencia para ese impuesto.
     *
     * @var float|int
     */
    public $totalrecargo;

    /**
     * % de recargo de equivalencia del impuesto.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Total de IVA para ese impuesto.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * % de IVA del impuesto.
     *
     * @var float|int
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
     * @var float|int
     */
    public $neto;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasivafactprov';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
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
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        if (static::floatcmp($this->totallinea, $this->neto + $this->totaliva + $this->totalrecargo, FS_NF0, true)) {
            return true;
        }
        $this->miniLog->alert($this->i18n->trans('totallinea-value-error', [$this->codimpuesto, round($this->neto + $this->totaliva + $this->totalrecargo, FS_NF0)]));

        return false;
    }

    /**
     * Comprueba que las líneas de Iva de la factura sean correctas
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

        if (!static::floatcmp($neto, $liNeto, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('sum-netos-line-tax-must-be', [$neto]));
            $status = false;
        } elseif (!static::floatcmp($totaliva, $liIva, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('sum-total-line-tax-must-be', [$totaliva]));
            $status = false;
        } elseif (!static::floatcmp($totalrecargo, $liRecargo, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('sum-totalrecargo-line-tax-must-be', [$totalrecargo]));
            $status = false;
        }

        return $status;
    }

    /**
     * Devuelve todas las líneas de Iva de la factura
     *
     * @param int $idfac
     *
     * @return self[]
     */
    public function allFromFactura($idfac)
    {
        $linealist = [];

        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE idfactura = ' . $this->dataBase->var2str($idfac) . ' ORDER BY iva DESC;';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $lin) {
                $linealist[] = new self($lin);
            }
        }

        return $linealist;
    }
}
