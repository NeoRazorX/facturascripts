<?php
/**
 * This file is part of FacturaScripts
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

namespace FacturaScripts\Core\Model\Base;

/**
 * This class group all data and method for sale line documents.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait LineaDocumentoVenta
{

    use ModelTrait {
        clear as private clearTrait;
    }

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Code of the selected combination, in the case of articles with attributes.
     *
     * @var string
     */
    public $codcombinacion;

    /**
     * Code of the related tax.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Line description.
     *
     * @var string
     */
    public $descripcion;

    /**
     * % discount.
     *
     * @var float|int
     */
    public $dtopor;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idlinea;

    /**
     * % of IRPF of the line.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * % of the related tax.
     *
     * @var float|int
     */
    public $iva;

    /**
     * False -> the quantity column is not displayed when printing.
     *
     * @var bool
     */
    public $mostrar_cantidad;

    /**
     * False -> the price, discount, tax and total columns are not displayed when printing.
     *
     * @var bool
     */
    public $mostrar_precio;

    /**
     * Position of the line in the document. The higher down.
     *
     * @var int
     */
    public $orden;

    /**
     * Net amount of the line, without taxes.
     *
     * @var float|int
     */
    public $pvptotal;

    /**
     * Net amount without discounts.
     *
     * @var float|int
     */
    public $pvpsindto;

    /**
     * Price of the item, one unit.
     *
     * @var float|int
     */
    public $pvpunitario;

    /**
     * % surcharge of line equivalence.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Reference of the article.
     *
     * @var string
     */
    public $referencia;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * Initializes the values of the line.
     */
    private function clearLinea()
    {
        $this->clearTrait();
        $this->cantidad = 0.0;
        $this->codcombinacion = null;
        $this->codimpuesto = null;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->idlinea = null;
        $this->irpf = 0.0;
        $this->iva = 0.0;
        $this->mostrar_cantidad = true;
        $this->mostrar_precio = true;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->recargo = 0.0;
        $this->referencia = null;
    }

    /**
     * Returns the retail price with VAT.
     *
     * @return float|int
     */
    public function pvpIva()
    {
        return $this->pvpunitario * (100 + $this->iva) / 100;
    }

    /**
     * Returns the total retail price (with VAT, IRPF and surcharge).
     *
     * @return integer
     */
    public function totalIva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /**
     * Returns the total PVP per product (no PIT or surcharge).
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
     * Returns the description.
     *
     * @return string
     */
    public function descripcion()
    {
        return nl2br($this->descripcion);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = static::noHtml($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!static::floatcmp($this->pvptotal, $total, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('pvptotal-line-error', [$this->referencia, $total]));
            return false;
        }
        if (!static::floatcmp($this->pvpsindto, $totalsindto, FS_NF0, true)) {
            $this->miniLog->alert($this->i18n->trans('pvpsindto-line-error', [$this->referencia, $totalsindto]));
            return false;
        }

        return true;
    }
}
