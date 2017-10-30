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
     * Clave primaria.
     *
     * @var int
     */
    public $idfactura;

    /**
     * ID del asiento relacionado, si lo hay.
     *
     * @var int
     */
    public $idasiento;

    /**
     * ID del asiento de pago relacionado, si lo hay.
     *
     * @var int
     */
    public $idasientop;

    /**
     * ID de la factura que rectifica.
     *
     * @var int
     */
    public $idfacturarect;

    /**
     * Código de la factura que rectifica.
     *
     * @var string
     */
    public $codigorect;

    /**
     * TRUE => pagada
     *
     * @var bool
     */
    public $pagada;

    /**
     * TRUE => anulada
     *
     * @var bool
     */
    public $anulada;

    /**
     * Fecha de vencimiento de la factura.
     *
     * @var string
     */
    public $vencimiento;

    /**
     * Devuelve el asiento asociado
     *
     * @return bool|Asiento
     */
    public function getAsiento()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasiento);
    }

    /**
     * Devuelve el asiento de pago asociado
     *
     * @return bool|mixed
     */
    public function getAsientoPago()
    {
        $asiento = new Asiento();

        return $asiento->get($this->idasientop);
    }

    /**
     * Devuelve las facturas rectificativas
     */
    public function getRectificativas()
    {
        return [];
    }

    /**
     * Obtiene las líneas de iva
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
        /// si no hay lineas de IVA, las generamos
        if (empty($lineasi) && !empty($lineas)) {
            /// necesitamos los totales por impuesto
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
                // Acumulamos por tipos de IVAs
                $subtotales[$codimpuesto]['neto'] += $lin->pvptotal * $dueTotales;
                $subtotales[$codimpuesto]['iva'] += $lin->pvptotal * $dueTotales * ($lin->iva / 100);
                $subtotales[$codimpuesto]['recargo'] += $lin->pvptotal * $dueTotales * ($lin->recargo / 100);
            }
            /// redondeamos
            foreach ($subtotales as $codimp => $subt) {
                $subtotales[$codimp]['neto'] = round($subt['neto'], FS_NF0);
                $subtotales[$codimp]['iva'] = round($subt['iva'], FS_NF0);
                $subtotales[$codimp]['recargo'] = round($subt['recargo'], FS_NF0);
            }
            /// ahora creamos las líneas de iva
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
     * Devuelve las líneas asociadas al documento.
     *
     * @return LineaFacturaCliente[]|LineaFacturaProveedor[]
     */
    abstract public function getLineas();
}
