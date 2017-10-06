<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Description of LineaDocumentoCompra
 *
 * @author Carlos García Gómez
 */
trait LineaDocumentoCompra
{
    use ModelTrait {
        clear as private clearTrait;
    }

    /**
     * TODO
     *
     * @var float
     */
    public $cantidad;

    /**
     * Código de la combinación seleccionada, en el caso de los artículos con atributos.
     *
     * @var string
     */
    public $codcombinacion;

    /**
     * Código del impuesto relacionado.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * TODO
     *
     * @var string
     */
    public $descripcion;

    /**
     * % del impuesto relacionado.
     *
     * @var float
     */
    public $iva;

    /**
     * % de descuento.
     *
     * @var float
     */
    public $dtopor;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idlinea;

    /**
     * % de IRPF de la línea.
     *
     * @var float
     */
    public $irpf;

    /**
     * Importe neto de la línea, sin impuestos.
     *
     * @var float
     */
    public $pvptotal;

    /**
     * Importe neto sin descuentos.
     *
     * @var float
     */
    public $pvpsindto;

    /**
     * Precio del artículo, una unidad.
     *
     * @var float
     */
    public $pvpunitario;

    /**
     * % de recargo de equivalencia de la línea.
     *
     * @var float
     */
    public $recargo;

    /**
     * Referencia del artículo.
     *
     * @var string
     */
    public $referencia;

    public function primaryColumn()
    {
        return 'idlinea';
    }

    private function clearLinea()
    {
        $this->clearTrait();
        $this->cantidad = 0.0;
        $this->codcombinacion = null;
        $this->codimpuesto = NULL;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->idlinea = null;
        $this->irpf = 0.0;
        $this->iva = 0.0;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->recargo = 0.0;
        $this->referencia = null;
    }

    /**
     * TODO
     *
     * @return float|int
     */
    public function pvpIva()
    {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    /**
     * TODO
     *
     * @return float|int
     */
    public function totalIva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /**
     * TODO
     *
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
     *
     * @return string
     */
    public function descripcion()
    {
        return nl2br($this->descripcion);
    }

    public function test()
    {
        $this->descripcion = $this->noHtml($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!$this->floatcmp($this->pvptotal, $total, FS_NF0, TRUE)) {
            $this->miniLog->alert("Error en el valor de pvptotal de la línea " . $this->referencia . " del documento. Valor correcto: " . $total);
            return FALSE;
        } else if (!$this->floatcmp($this->pvpsindto, $totalsindto, FS_NF0, TRUE)) {
            $this->miniLog->alert("Error en el valor de pvpsindto de la línea " . $this->referencia . " del documento. Valor correcto: " . $totalsindto);
            return FALSE;
        }

        return TRUE;
    }
}
