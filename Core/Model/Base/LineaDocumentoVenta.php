<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of LineaDocumentoVenta
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait LineaDocumentoVenta
{

    use ModelTrait {
        clear as private traitClear;
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
     * Description of the line.
     *
     * @var string
     */
    public $descripcion;

    /**
     * % off.
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
     * @var boolean
     */
    public $mostrar_cantidad;

    /**
     * False -> price, discount, tax and total columns are not displayed when printing.
     *
     * @var boolean
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
        $this->traitClear();
        $this->cantidad = 0.0;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->irpf = 0.0;
        $this->iva = 0.0;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->recargo = 0.0;
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
     * Returns the retail price total (with VAT, IRPF and surcharge).
     *
     * @return integer
     */
    public function totalIva()
    {
        return $this->pvptotal * (100 + $this->iva - $this->irpf + $this->recargo) / 100;
    }

    /**
     * Returns the total retail price per product (without income tax or surcharge).
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
     * Returns true if there are no errors in the values of the model properties.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = static::noHtml($this->descripcion);
        $total = $this->pvpunitario * $this->cantidad * (100 - $this->dtopor) / 100;
        $totalsindto = $this->pvpunitario * $this->cantidad;

        if (!static::floatcmp($this->pvptotal, $total, FS_NF0, true)) {
            $values = ['%reference%' => $this->referencia, '%total%' => $total];
            self::$miniLog->alert(self::$i18n->trans('pvptotal-line-error', $values));
            return false;
        }
        if (!static::floatcmp($this->pvpsindto, $totalsindto, FS_NF0, true)) {
            $values = ['%reference%' => $this->referencia, '%totalWithoutDiscount%' => $totalsindto];
            self::$miniLog->alert(self::$i18n->trans('pvpsindto-line-error', $values));
            return false;
        }

        return true;
    }
}
