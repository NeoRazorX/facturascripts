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
namespace FacturaScripts\Core\Model;

/**
 * The VAT line of a supplier invoice.
 * Indicates net, VAT and total for a specific VAT and an invoice.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaIvaFacturaProveedor
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idlinea;

    /**
     * Related invoice ID.
     *
     * @var int
     */
    public $idfactura;

    /**
     * net + total VAT + total surcharge.
     *
     * @var float|int
     */
    public $totallinea;

    /**
     * Total surcharge of equivalence for that tax.
     *
     * @var float|int
     */
    public $totalrecargo;

    /**
     * % surcharge of tax equivalence.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Total VAT for that tax.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * % VAT of the tax.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Code of the related tax.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Net or tax base for that tax.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasivafactprov';
    }

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
     * Reset the values of all model properties.
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
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (static::floatcmp($this->totallinea, $this->neto + $this->totaliva + $this->totalrecargo, FS_NF0, true)) {
            return true;
        }
        $totalLine = round($this->neto + $this->totaliva + $this->totalrecargo, FS_NF0);
        $values = ['%taxCode%' => $this->codimpuesto, '%totalLine%' => $totalLine];
        self::$miniLog->alert(self::$i18n->trans('totallinea-value-error', $values));

        return false;
    }

    /**
     * Check that the invoice lines on the invoice are correct.
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
            self::$miniLog->alert(self::$i18n->trans('sum-netos-line-tax-must-be', ['%lineNet%' => $neto]));
            $status = false;
        } elseif (!static::floatcmp($totaliva, $liIva, FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('sum-total-line-tax-must-be', ['%lineTotalTax%' => $totaliva]));
            $status = false;
        } elseif (!static::floatcmp($totalrecargo, $liRecargo, FS_NF0, true)) {
            self::$miniLog->alert(self::$i18n->trans('sum-totalrecargo-line-tax-must-be', ['%lineTotalSurcharge%' => $totalrecargo]));
            $status = false;
        }

        return $status;
    }

    /**
     * Returns all the VAT lines of the invoice.
     *
     * @param int $idfac
     *
     * @return self[]
     */
    public function allFromFactura($idfac)
    {
        $linealist = [];

        $sql = 'SELECT * FROM ' . static::tableName()
            . ' WHERE idfactura = ' . self::$dataBase->var2str($idfac) . ' ORDER BY iva DESC;';
        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $lin) {
                $linealist[] = new self($lin);
            }
        }

        return $linealist;
    }
}
