<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Model;

/**
 * Description of DocumentCalculator
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DocumentCalculator
{

    /**
     * Calculates and assigns document totals.
     *
     * @param Model\FacturaCliente|Model\FacturaProveedor $doc
     */
    public function calculate($doc)
    {
        /// clear totals
        $doc->neto = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totalrecargo = 0.0;

        $lines = $doc->getLineas();
        foreach ($this->getSubtotals($lines) as $subt) {
            $doc->neto += $subt['neto'];
            $doc->totaliva += $subt['totaliva'];
            $doc->totalirpf += $subt['totalirpf'];
            $doc->totalrecargo += $subt['totalrecargo'];
        }

        $doc->total = round($doc->neto + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) FS_NF0);
    }

    /**
     * Calculate and return the new total document based on line content.
     *
     * @param mixed $doc
     * @param mixed $formLines
     *
     * @return string
     */
    public function calculateForm(&$doc, &$formLines)
    {
        /// clear totals
        $doc->neto = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totalrecargo = 0.0;

        $lines = [];
        foreach ($formLines as $fLine) {
            if (!empty($fLine['referencia']) && empty($fLine['descripcion'])) {
                $articulo = new Model\Articulo();
                if ($articulo->loadFromCode($fLine['referencia'])) {
                    $fLine['descripcion'] = $articulo->descripcion;
                    $fLine['cantidad'] = 1;
                    $fLine['iva'] = $articulo->getIva();
                    $fLine['pvpunitario'] = $articulo->pvp;
                }
            }

            $newLine = new Model\LineaAlbaranCliente();
            $newLine->cantidad = (float) $fLine['cantidad'];
            $newLine->descripcion = $fLine['descripcion'];
            $newLine->dtopor = (float) $fLine['dtopor'];
            $newLine->irpf = (float) $fLine['irpf'];
            $newLine->iva = (float) $fLine['iva'];
            $newLine->pvpunitario = (float) $fLine['pvpunitario'];
            $newLine->recargo = (float) $fLine['recargo'];
            $newLine->pvpsindto = $newLine->pvpunitario * $newLine->cantidad;
            $newLine->pvptotal = $newLine->pvpsindto * (100 - $newLine->dtopor) / 100;
            $newLine->referencia = $fLine['referencia'];
            $lines[] = $newLine;
        }

        foreach ($this->getSubtotals($lines) as $subt) {
            $doc->neto += $subt['neto'];
            $doc->totaliva += $subt['totaliva'];
            $doc->totalirpf += $subt['totalirpf'];
            $doc->totalrecargo += $subt['totalrecargo'];
        }

        $json = [
            'total' => round($doc->neto + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) FS_NF0),
            'lines' => $lines
        ];

        return json_encode($json);
    }

    /**
     * Returns subtotals by tax.
     *
     * @param Model\Base\BusinessDocumentLine[] $lines
     *
     * @return array
     */
    private function getSubtotals($lines)
    {
        $irpf = 0.0;
        $subtotals = [];
        foreach ($lines as $line) {
            $codimpuesto = empty($line->codimpuesto) ? $line->iva . '-' . $line->recargo : $line->codimpuesto;
            if (!array_key_exists($codimpuesto, $subtotals)) {
                $subtotals[$codimpuesto] = [
                    'neto' => 0.0,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0,
                    'totalirpf' => 0.0,
                ];
            }

            $subtotals[$codimpuesto]['neto'] += $line->pvptotal;
            $subtotals[$codimpuesto]['totaliva'] += $line->pvptotal * $line->iva / 100;
            $subtotals[$codimpuesto]['totalrecargo'] += $line->pvptotal * $line->recargo / 100;
            $irpf += $line->pvptotal * $line->irpf / 100;
        }

        /// IRPF to the first subtotal
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['irpf'] = $irpf;
            break;
        }

        /// rounding totals
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['neto'] = round($value['neto'], (int) FS_NF0);
            $subtotals[$key]['totaliva'] = round($value['totaliva'], (int) FS_NF0);
            $subtotals[$key]['totalrecargo'] = round($value['totalrecargo'], (int) FS_NF0);
            $subtotals[$key]['totalirpf'] = round($value['totalirpf'], (int) FS_NF0);
        }

        return $subtotals;
    }
}
