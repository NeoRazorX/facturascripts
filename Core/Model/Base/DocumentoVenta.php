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
 * Description of DocumentoVenta
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait DocumentoVenta
{

    use ModelTrait {
        clear as traitClear;
    }

    /**
     * Mail box of the client.
     *
     * @var string
     */
    public $apartado;

    /**
     * CIF / NIF of the client.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Customer's city
     *
     * @var string
     */
    public $ciudad;

    /**
     * Employee who created this document. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Warehouse from which the goods leave.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Customer of this document.
     *
     * @var string
     */
    public $codcliente;

    /**
     * Currency of this document.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * ID of the customer's address. Customer_address model.
     *
     * @var int
     */
    public $coddir;

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
     * Form of payment of this document.
     *
     * @var string
     */
    public $codpago;

    /**
     * Customer's country.
     *
     * @var string
     */
    public $codpais;

    /**
     * Customer's postal code.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Related serie.
     *
     * @var string
     */
    public $codserie;

    /**
     * Customer's address
     *
     * @var string
     */
    public $direccion;

    /**
     * Shipping code for the shipment.
     *
     * @var string
     */
    public $envio_codtrans;

    /**
     * Shipping tracking code.
     *
     * @var string
     */
    public $envio_codigo;

    /**
     * Name of the shipping address.
     *
     * @var string
     */
    public $envio_nombre;

    /**
     * Last name of the shipping address.
     *
     * @var string
     */
    public $envio_apellidos;

    /**
     * Post box of the shipping address.
     *
     * @var string
     */
    public $envio_apartado;

    /**
     * Address of the shipping address.
     *
     * @var string
     */
    public $envio_direccion;

    /**
     * Postal code of the shipping address.
     *
     * @var string
     */
    public $envio_codpostal;

    /**
     * City of the shipping address.
     *
     * @var string
     */
    public $envio_ciudad;

    /**
     * Province of the shipping address.
     *
     * @var string
     */
    public $envio_provincia;

    /**
     * Country code of the shipping address.
     *
     * @var string
     */
    public $envio_codpais;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Date on which the document was sent by email.
     *
     * @var string
     */
    public $femail;

    /**
     * Time of the document.
     *
     * @var string
     */
    public $hora;

    /**
     * Company ID of the document.
     * 
     * @var int 
     */
    public $idempresa;

    /**
     * % IRPF retention of the delivery note. It is obtained from the series.
     * Each line can have a different %.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * Sum of the pvptotal of lines. Total of the invoice before taxes.
     *
     * @var float|int
     */
    public $neto;

    /**
     * Customer name.
     *
     * @var string
     */
    public $nombrecliente;

    /**
     * Number of documents attached.
     *
     * @var int
     */
    public $numdocs;

    /**
     * Delivery note number.
     * It is unique within the serie + exercise.
     *
     * @var string
     */
    public $numero;

    /**
     * Optional number available to the user.
     *
     * @var string
     */
    public $numero2;

    /**
     * % commission of the employee.
     *
     * @var float|int
     */
    public $porcomision;

    /**
     * Customer's province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Rate of conversion to Euros of the selected currency.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Total amount of the delivery note, with taxes.
     *
     * @var float|int
     */
    public $total;

    /**
     * Total sum of the VAT of the lines.
     *
     * @var float|int
     */
    public $totaliva;

    /**
     * Total expressed in euros, if it were not the currency of the delivery note.
     * totaleuros = total / tasaconv
     * It is not necessary to fill it, when doing save() the value is calculated.
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
     * Observations of the document.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Initializes document values.
     */
    private function clearDocumentoVenta()
    {
        $this->traitClear();
        $this->codserie = AppSettings::get('default', 'codserie');
        $this->codalmacen = AppSettings::get('default', 'codalmacen');
        $this->codpago = AppSettings::get('default', 'codpago');
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
        if ($this->observaciones === '') {
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
        $this->nombrecliente = static::noHtml($this->nombrecliente);
        if ($this->nombrecliente == '') {
            $this->nombrecliente = '-';
        }
        $this->direccion = static::noHtml($this->direccion);
        $this->ciudad = static::noHtml($this->ciudad);
        $this->provincia = static::noHtml($this->provincia);
        $this->envio_nombre = static::noHtml($this->envio_nombre);
        $this->envio_apellidos = static::noHtml($this->envio_apellidos);
        $this->envio_direccion = static::noHtml($this->envio_direccion);
        $this->envio_ciudad = static::noHtml($this->envio_ciudad);
        $this->envio_provincia = static::noHtml($this->envio_provincia);
        $this->numero2 = static::noHtml($this->numero2);
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
     * Returns true if there are no errors in the values of the model properties.
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
                    'iva' => 0, // Total VAT
                    'recargo' => 0, // Total Surcharge
                );
            }
            /// We accumulate by VAT rates
            $subtotales[$codimpuesto]['neto'] += $lin->pvptotal;
            $subtotales[$codimpuesto]['iva'] += $lin->pvptotal * $lin->iva / 100;
            $subtotales[$codimpuesto]['recargo'] += $lin->pvptotal * $lin->recargo / 100;
            $irpf += $lin->pvptotal * $lin->irpf / 100;

            /// Previous calculation
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
