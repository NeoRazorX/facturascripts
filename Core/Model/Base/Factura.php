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

use FacturaScripts\Core\Model\Asiento;

/**
 * Description of Factura
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait Factura
{

    /**
     * Primary key.
     *
     * @var int
     */
    public $idfactura;

    /**
     * Related seat ID, if any.
     *
     * @var int
     */
    public $idasiento;

    /**
     * ID of the related payment seat, if any.
     *
     * @var int
     */
    public $idasientop;

    /**
     * ID of the invoice that you rectify.
     *
     * @var int
     */
    public $idfacturarect;

    /**
     * Code of the invoice that rectifies.
     *
     * @var string
     */
    public $codigorect;

    /**
     * True => paid.
     *
     * @var bool
     */
    public $pagada;

    /**
     * TRUE => voided.
     *
     * @var bool
     */
    public $anulada;

    /**
     * Due date of the invoice.
     *
     * @var string
     */
    public $vencimiento;

    /**
     * Returns the associated seat.
     *
     * @return bool|Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasiento);
    }

    /**
     * Returns the associated payment entry.
     *
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasientop);
    }

    /**
     * Returns the rectifying invoices.
     */
    public function getRectificativas()
    {
        return [];
    }

    /**
     * Get the VAT lines.
     *
     * @param string $className
     * @param int $dueTotales
     *
     * @return mixed
     */
    private function getLineasIvaTrait($className, $dueTotales = 1)
    {
        $linea_iva = new $className();
        $lineas = $this->getLineas();
        $lineasi = $linea_iva->allFromFactura($this->idfactura);
        /// If there are no VAT lines, we generate them
        if (empty($lineasi) && !empty($lineas)) {
            /// we need totals for tax
            $subtotales = [];
            foreach ($lineas as $lin) {
                $codimpuesto = ($lin->codimpuesto === null) ? 0 : $lin->codimpuesto;
                if (!array_key_exists($codimpuesto, $subtotales)) {
                    $subtotales[$codimpuesto] = array(
                        'neto' => 0,
                        'iva' => 0,
                        'ivapor' => $lin->iva,
                        'recargo' => 0,
                        'recargopor' => $lin->recargo
                    );
                }
                // We accumulate by VAT rates
                $subtotales[$codimpuesto]['neto'] += $lin->pvptotal * $dueTotales;
                $subtotales[$codimpuesto]['iva'] += $lin->pvptotal * $dueTotales * ($lin->iva / 100);
                $subtotales[$codimpuesto]['recargo'] += $lin->pvptotal * $dueTotales * ($lin->recargo / 100);
            }
            /// round
            foreach ($subtotales as $codimp => $subt) {
                $subtotales[$codimp]['neto'] = round($subt['neto'], FS_NF0);
                $subtotales[$codimp]['iva'] = round($subt['iva'], FS_NF0);
                $subtotales[$codimp]['recargo'] = round($subt['recargo'], FS_NF0);
            }
            /// now we create the lines of iva
            foreach ($subtotales as $codimp => $subt) {
                $lineasi[$codimp] = new $className();
                $lineasi[$codimp]->idfactura = $this->idfactura;
                $lineasi[$codimp]->codimpuesto = $codimp;
                $lineasi[$codimp]->iva = $subt['ivapor'];
                $lineasi[$codimp]->recargo = $subt['recargopor'];
                $lineasi[$codimp]->neto = $subt['neto'];
                $lineasi[$codimp]->totaliva = $subt['iva'];
                $lineasi[$codimp]->totalrecargo = $subt['recargo'];
                $lineasi[$codimp]->totallinea = $subt['neto'] + $subt['iva'] + $subt['recargo'];
                $lineasi[$codimp]->save();
            }
        }
        return $lineasi;
    }

    /**
     * Returns the lines associated with the document.
     *
     * @return LineaFacturaCliente[]|LineaFacturaProveedor[]
     */
    abstract public function getLineas();
}
