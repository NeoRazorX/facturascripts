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

use FacturaScripts\Core\Lib\NewCodigoDoc;

/**
 * Description of DocumentoCompra
 *
 * @author Carlos García Gómez
 */
trait DocumentoCompra
{

    use ModelTrait {
        clear as clearTrait;
    }

    /**
     * CIF/NIF del proveedor
     *
     * @var string
     */
    public $cifnif;

    /**
     * Empleado que ha creado este albarán.
     *
     * @var string
     */
    public $codagente;

    /**
     * Almacén en el que entra la mercancía.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Divisa del albarán.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Identificador único de cara a humanos.
     *
     * @var string
     */
    public $codigo;

    /**
     * Forma de pago asociada.
     *
     * @var string
     */
    public $codpago;

    /**
     * Código del proveedor de este albarán.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Serie relacionada.
     *
     * @var string
     */
    public $codserie;

    /**
     * Fecha del albarán
     *
     * @var string
     */
    public $fecha;

    /**
     * Hora del albarán
     *
     * @var string
     */
    public $hora;

    /**
     * % de retención IRPF del albarán. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     *
     * @var float
     */
    public $irpf;

    /**
     * Nombre del proveedor
     *
     * @var string
     */
    public $nombre;

    /**
     * Número del albarán.
     * Único dentro de la serie+ejercicio.
     *
     * @var string
     */
    public $numero;

    /**
     * Número de albarán de proveedor, si lo hay.
     * Puede contener letras.
     *
     * @var string
     */
    public $numproveedor;

    /**
     * Número de documentos adjuntos.
     *
     * @var int
     */
    public $numdocs;

    /**
     * Suma del pvptotal de líneas. Total del albarán antes de impuestos.
     *
     * @var float
     */
    public $neto;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     *
     * @var float
     */
    public $tasaconv;

    /**
     * Suma total del albarán, con impuestos.
     *
     * @var float
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     *
     * @var float
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del albarán.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     *
     * @var float
     */
    public $totaleuros;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     *
     * @var float
     */
    public $totalirpf;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     *
     * @var float
     */
    public $totalrecargo;

    /**
     * Observaciones del albarán
     *
     * @var string
     */
    public $observaciones;

    private function clearDocumentoCompra()
    {
        $this->clearTrait();
        $this->codserie = $this->defaultItems->codSerie();
        $this->codpago = $this->defaultItems->codPago();
        $this->codalmacen = $this->defaultItems->codAlmacen();
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

    private function newCodigo()
    {
        $newCodigoDoc = new NewCodigoDoc();
        $this->numero = $newCodigoDoc->getNumero($this->tableName(), $this->codejercicio, $this->codserie);
        $this->codigo = $newCodigoDoc->getCodigo($this->tableName(), $this->numero, $this->codserie, $this->codejercicio);
    }

    private function testTrait()
    {
        $this->nombre = $this->noHtml($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }
        $this->numproveedor = $this->noHtml($this->numproveedor);
        $this->observaciones = $this->noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);
        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        }

        $this->miniLog->alert('bad-total-error');
        return FALSE;
    }

    private function fullTestTrait($tipoDoc)
    {
        $status = TRUE;
        $subtotales = [];
        $irpf = 0;

        /// calculamos también con el método anterior
        $neto_alt = 0;
        $iva_alt = 0;
        foreach ($this->get_lineas() as $lin) {
            if (!$lin->test()) {
                $status = FALSE;
            }
            $codimpuesto = ($lin->codimpuesto === null ) ? 0 : $lin->codimpuesto;
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
            $neto_alt += $lin->pvptotal;
            $iva_alt += $lin->pvptotal * $lin->iva / 100;
        }

        /// redondeamos y sumamos
        $neto = 0;
        $iva = 0;
        $recargo = 0;
        $irpf = round($irpf, FS_NF0);
        foreach ($subtotales as $subt) {
            $neto += round($subt['neto'], FS_NF0);
            $iva += round($subt['iva'], FS_NF0);
            $recargo += round($subt['recargo'], FS_NF0);
        }
        $neto_alt = round($neto_alt, FS_NF0);
        $iva_alt = round($iva_alt, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;
        $total_alt = $neto_alt + $iva_alt - $irpf + $recargo;

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE) && !$this->floatcmp($this->neto, $neto_alt, FS_NF0, TRUE)) {
            $this->miniLog->alert("Valor neto de " . $tipoDoc . " " . $this->codigo . " incorrecto (" . $this->neto . "). Valor correcto: " . $neto);
            $status = FALSE;
        }

        if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE) && !$this->floatcmp($this->totaliva, $iva_alt, FS_NF0, TRUE)) {
            $this->miniLog->alert("Valor totaliva de " . $tipoDoc . " " . $this->codigo . " incorrecto (" . $this->totaliva . "). Valor correcto: " . $iva);
            $status = FALSE;
        }

        if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->miniLog->alert("Valor totalirpf de " . $tipoDoc . " " . $this->codigo . " incorrecto (" . $this->totalirpf . "). Valor correcto: " . $irpf);
            $status = FALSE;
        }

        if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->miniLog->alert("Valor totalrecargo de " . $tipoDoc . " " . $this->codigo . " incorrecto (" . $this->totalrecargo . "). Valor correcto: " . $recargo);
            $status = FALSE;
        }

        if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE) && !$this->floatcmp($this->total, $total_alt, FS_NF0, TRUE)) {
            $this->miniLog->alert("Valor total de " . $tipoDoc . " " . $this->codigo . " incorrecto (" . $this->total . "). Valor correcto: " . $total);
            $status = FALSE;
        }

        return $status;
    }
}
