<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools as DinBusinessDocumentTools;

/**
 * Description of BusinessDocumentFormTools
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class BusinessDocumentFormTools extends DinBusinessDocumentTools
{

    /**
     * Calculate document totals from form data and returns the new total and document lines.
     *
     * @param BusinessDocument $doc
     * @param array            $formLines
     *
     * @return string
     */
    public function recalculateForm(BusinessDocument &$doc, array &$formLines)
    {
        $this->clearTotals($doc);

        $lines = [];
        foreach ($formLines as $fLine) {
            $lines[] = $this->recalculateFormLine($fLine, $doc);
        }

        foreach ($this->getSubtotals($lines, [$doc->dtopor1, $doc->dtopor2]) as $subt) {
            $doc->neto += $subt['neto'];
            $doc->netosindto += $subt['netosindto'];
            $doc->totaliva += $subt['totaliva'];
            $doc->totalirpf += $subt['totalirpf'];
            $doc->totalrecargo += $subt['totalrecargo'];
            $doc->totalsuplidos += $subt['totalsuplidos'];
        }

        /// rounding totals again
        $doc->neto = \round($doc->neto, (int) \FS_NF0);
        $doc->netosindto = \round($doc->netosindto, (int) \FS_NF0);
        $doc->totalirpf = \round($doc->totalirpf, (int) \FS_NF0);
        $doc->totaliva = \round($doc->totaliva, (int) \FS_NF0);
        $doc->totalrecargo = \round($doc->totalrecargo, (int) \FS_NF0);
        $doc->totalsuplidos = \round($doc->totalsuplidos, (int) \FS_NF0);
        $doc->total = \round($doc->neto + $doc->totalsuplidos + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) \FS_NF0);
        return \json_encode([
            'doc' => $doc,
            'lines' => $lines
        ]);
    }

    /**
     *
     * @param array            $fLine
     * @param BusinessDocument $doc
     *
     * @return BusinessDocumentLine
     */
    protected function recalculateFormLine(array $fLine, BusinessDocument $doc)
    {
        if (isset($fLine['cantidad']) && '' !== $fLine['cantidad']) {
            /// edit line
            $newLine = $doc->getNewLine($fLine, ['actualizastock']);
        } elseif (isset($fLine['referencia']) && '' !== $fLine['referencia']) {
            /// new line with reference
            $newLine = $doc->getNewProductLine($fLine['referencia']);
        } else {
            /// new line without reference
            $newLine = $doc->getNewLine();
            $newLine->descripcion = $fLine['descripcion'] ?? '';
        }

        $this->recalculateFormLineTaxZones($newLine);

        $newLine->descripcion = Utils::fixHtml($newLine->descripcion);
        $newLine->pvpsindto = $newLine->pvpunitario * $newLine->cantidad;
        $newLine->pvptotal = $newLine->pvpsindto * (100 - $newLine->dtopor) / 100 * (100 - $newLine->dtopor2) / 100;
        $newLine->referencia = Utils::fixHtml($newLine->referencia);

        $suplido = isset($fLine['suplido']) && $fLine['suplido'] === 'true';
        if ($this->siniva || $newLine->codimpuesto === null || $suplido) {
            $newLine->codimpuesto = null;
            $newLine->iva = $newLine->recargo = 0.0;
        } elseif ($this->recargo === false) {
            $newLine->recargo = 0.0;
        }

        return $newLine;
    }

    /**
     *
     * @param BusinessDocumentLine $line
     */
    protected function recalculateFormLineTaxZones(&$line)
    {
        $newCodimpuesto = $line->codimpuesto;

        /// tax manually changed?
        if (\abs($line->getTax()->iva - $line->iva) >= 0.01) {
            /// only defined tax are allowed
            $newCodimpuesto = null;
            foreach ($line->getTax()->all() as $tax) {
                if ($line->iva == $tax->iva) {
                    $newCodimpuesto = $tax->codimpuesto;
                    break;
                }
            }
        } elseif ($line->codimpuesto === $line->getProducto()->codimpuesto) {
            /// apply tax zones
            foreach ($this->taxZones as $taxZone) {
                if ($newCodimpuesto === $taxZone->codimpuesto) {
                    $newCodimpuesto = $taxZone->codimpuestosel;
                    break;
                }
            }
        }

        if ($newCodimpuesto !== $line->codimpuesto) {
            /// set new tax
            $line->codimpuesto = $newCodimpuesto;
            $line->iva = $line->getTax()->iva;
            $line->recargo = $line->getTax()->recargo;
        }
    }
}
