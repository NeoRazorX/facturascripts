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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Lib\NewCodigoDoc;

/**
 * Description of DocumentoCompra
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait DocumentoCompra
{

    use ModelTrait {
        clear as traitClear;
    }

    /**
     * VAT number of the supplier.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Employee who created this document.
     *
     * @var string
     */
    public $codagente;

    /**
     * Warehouse in which the merchandise enters.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Currency of the document.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Related exercise. The one that corresponds to the date.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Unique identifier for humans.
     *
     * @var string
     */
    public $codigo;

    /**
     * Payment method associated.
     *
     * @var string
     */
    public $codpago;

    /**
     * Supplier code for this document.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Related serie.
     *
     * @var string
     */
    public $codserie;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Document time.
     *
     * @var string
     */
    public $hora;
    
    /**
     * Company id. of the document.
     *
     * @var int
     */
    public $idempresa;

    /**
     * % IRPF retention of the document. It is obtained from the series.
     * Each line can have a different%.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Provider's name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Number of the document.
     * Unique within the series + exercise.
     *
     * @var string
     */
    public $numero;

    /**
     * Supplier's document number, if any.
     * May contain letters.
     *
     * @var string
     */
    public $numproveedor;

    /**
     * Number of documents attached.
     *
     * @var int
     */
    public $numdocs;

    /**
     * Sum of the pvptotal of lines. Total of the document before taxes.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Rate of conversion to Euros of the selected currency.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Total sum of the document, with taxes.
     *
     * @var float|int
     */
    public $total;

    /**
     * Sum of the VAT of the lines.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * Total expressed in euros, if it were not the currency of the document.
     * totaleuros = total / tasaconv
     * It is not necessary to fill it, when doing save () the value is calculated.
     *
     * @var float|int
     */
    public $totaleuros;

    /**
     * Total sum of the IRPF withholdings of the lines.
     *
     * @var float|int
     */
    public $totalirpf;

    /**
     * Total sum of the equivalence surcharge of the lines.
     *
     * @var float|int
     */
    public $totalrecargo;

    /**
     * Notes of the document.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Initializes document values.
     */
    private function clearDocumentoCompra()
    {
        $this->traitClear();
        $this->codserie = AppSettings::get('default', 'codserie');
        $this->codpago = AppSettings::get('default', 'codpago');
        $this->codalmacen = AppSettings::get('default', 'codalmacen');
        $this->idempresa = AppSettings::get('default', 'idempresa');
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->irpf = 0.0;
        $this->neto = 0.0;
        $this->numdocs = 0;
        $this->tasaconv = 1.0;
        $this->total = 0.0;
        $this->totaleuros = 0.0;
        $this->totalirpf = 0.0;
        $this->totaliva = 0.0;
        $this->totalrecargo = 0.0;
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return false;
    }

    /**
     * Shorten the text of observations.
     *
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones == '') {
            return '-';
        }

        if (mb_strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return mb_substr($this->observaciones, 0, 50) . '...';
    }

    /**
     * Generates a new code.
     */
    private function newCodigo()
    {
        $newCodigoDoc = new NewCodigoDoc();
        $this->numero = (string) $newCodigoDoc->getNumero(static::tableName(), $this->codejercicio, $this->codserie);
        $this->codigo = $newCodigoDoc->getCodigo(static::tableName(), $this->numero, $this->codserie, $this->codejercicio);
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     *
     * @return bool
     */
    private function testTrait()
    {
        $this->nombre = static::noHtml($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }
        $this->numproveedor = static::noHtml($this->numproveedor);
        $this->observaciones = static::noHtml($this->observaciones);

        /**
         * We use the euro as a bridge currency when adding, compare
         * or convert amounts in several currencies. For this reason we need
         * many decimals.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);
        if (static::floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true)) {
            return true;
        }

        self::$miniLog->alert(self::$i18n->trans('bad-total-error'));
        return false;
    }

    /**
     * Run a complete test of tests.
     *
     * @param string $tipoDoc
     *
     * @return bool
     */
    private function fullTestTrait($tipoDoc)
    {
        $status = true;
        $subtotales = [];
        $irpf = 0;

        /// we calculate also with the previous method
        $netoAlt = 0;
        $ivaAlt = 0;
        $this->getSubtotales($status, $subtotales, $irpf, $netoAlt, $ivaAlt);

        /// round and add
        $neto = 0;
        $iva = 0;
        $recargo = 0;
        $irpf = round($irpf, FS_NF0);
        foreach ($subtotales as $subt) {
            $neto += round($subt['neto'], FS_NF0);
            $iva += round($subt['iva'], FS_NF0);
            $recargo += round($subt['recargo'], FS_NF0);
        }
        $netoAlt = round($netoAlt, FS_NF0);
        $ivaAlt = round($ivaAlt, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;
        $total_alt = $netoAlt + $ivaAlt - $irpf + $recargo;

        if (!static::floatcmp($this->neto, $neto, FS_NF0, true) && !static::floatcmp($this->neto, $netoAlt, FS_NF0, true)) {
            $values = ['%docType%' => $tipoDoc, '%docCode%' => $this->codigo, '%docNet%' => $this->neto, '%calcNet%' => $neto];
            self::$miniLog->alert(self::$i18n->trans('neto-value-error', $values));
            $status = false;
        }

        if (!static::floatcmp($this->totaliva, $iva, FS_NF0, true) && !static::floatcmp($this->totaliva, $ivaAlt, FS_NF0, true)) {
            $values = ['%docType%' => $tipoDoc, '%docCode%' => $this->codigo, '%docTotalTax%' => $this->totaliva, '%calcTotalTax%' => $iva];
            self::$miniLog->alert(self::$i18n->trans('totaliva-value-error', $values));
            $status = false;
        }

        if (!static::floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $values = ['%docType%' => $tipoDoc, '%docCode%' => $this->codigo, '%docTotalIRPF%' => $this->totalirpf, '%calcTotalIRPF%' => $irpf];
            self::$miniLog->alert(self::$i18n->trans('totalirpf-value-error', $values));
            $status = false;
        }

        if (!static::floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $values = ['%docType%' => $tipoDoc, '%docCode%' => $this->codigo, '%docTotalSurcharge%' => $this->totalrecargo, '%calcTotalSurcharge%' => $recargo];
            self::$miniLog->alert(self::$i18n->trans('totalrecargo-value-error', $values));
            $status = false;
        }

        if (!static::floatcmp($this->total, $total, FS_NF0, true) && !static::floatcmp($this->total, $total_alt, FS_NF0, true)) {
            $values = ['%docType%' => $tipoDoc, '%docCode%' => $this->codigo, '%docTotal%' => $this->total, '%calcTotal%' => $total];
            self::$miniLog->alert(self::$i18n->trans('total-value-error', $values));
            $status = false;
        }

        return $status;
    }

    /**
     * Calculates the subtotals of net, taxes and surcharge, by type of tax, in addition to the irpf, net and taxes
     * with the previous calculation.
     *
     * @param boolean $status
     * @param array $subtotales
     * @param int $irpf
     * @param int $netoAlt
     * @param int $ivaAlt
     */
    private function getSubtotales(&$status, &$subtotales, &$irpf, &$netoAlt, &$ivaAlt)
    {
        foreach ($this->getLineas() as $lin) {
            if (!$lin->test()) {
                $status = false;
            }
            $codimpuesto = ($lin->codimpuesto === null) ? 0 : $lin->codimpuesto;
            if (!array_key_exists($codimpuesto, $subtotales)) {
                $subtotales[$codimpuesto] = array(
                    'neto' => 0,
                    'iva' => 0, // Total IVA
                    'recargo' => 0, // Total Recargo
                );
            }
            /// Acumulamos por tipos de IVAs
            $subtotales[$codimpuesto]['neto'] += $lin->pvptotal;
            $subtotales[$codimpuesto]['iva'] += $lin->pvptotal * $lin->iva / 100;
            $subtotales[$codimpuesto]['recargo'] += $lin->pvptotal * $lin->recargo / 100;
            $irpf += $lin->pvptotal * $lin->irpf / 100;

            /// Cálculo anterior
            $netoAlt += $lin->pvptotal;
            $ivaAlt += $lin->pvptotal * $lin->iva / 100;
        }
    }

    /**
     * Returns the lines associated with the document.
     *
     * @return array
     */
    abstract public function getLineas();
}
