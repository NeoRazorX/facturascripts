<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\ImpuestoZona;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * A set of tools to recalculate business documents.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BusinessDocumentTools
{

    /**
     *
     * @var CommissionTools
     */
    protected $commissionTools;

    /**
     *
     * @var ImpuestoZona[]
     */
    protected $impuestosZonas = [];

    /**
     *
     * @var bool
     */
    protected $recargo = false;

    /**
     *
     * @var bool
     */
    protected $siniva = false;

    public function __construct()
    {
        $this->commissionTools = new CommissionTools();
    }

    /**
     * Returns subtotals by tax.
     *
     * @param BusinessDocumentLine[] $lines
     * @param array                  $discounts
     *
     * @return array
     */
    public function getSubtotals(array $lines, array $discounts): array
    {
        /// calculate total discount
        $totalDto = 1.0;
        foreach ($discounts as $dto) {
            $totalDto *= 1 - $dto / 100;
        }

        $irpf = 0.0;
        $subtotals = [];
        $totalIrpf = 0.0;
        foreach ($lines as $line) {
            $codimpuesto = empty($line->codimpuesto) ? $line->iva . '-' . $line->recargo : $line->codimpuesto;
            if (!\array_key_exists($codimpuesto, $subtotals)) {
                $subtotals[$codimpuesto] = [
                    'irpf' => 0.0,
                    'iva' => $line->iva,
                    'neto' => 0.0,
                    'netosindto' => 0.0,
                    'recargo' => $line->recargo,
                    'totalirpf' => 0.0,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0,
                ];
            }

            $pvpTotal = $line->pvptotal * $totalDto;
            $subtotals[$codimpuesto]['neto'] += $pvpTotal;
            $subtotals[$codimpuesto]['netosindto'] += $line->pvptotal;

            $irpf = \max([$irpf, $line->irpf]);
            $totalIrpf += $pvpTotal * $line->irpf / 100;

            switch ($line->getTax()->tipo) {
                case Impuesto::TYPE_FIXED_VALUE:
                    $subtotals[$codimpuesto]['totaliva'] += $line->iva * $line->cantidad;
                    $subtotals[$codimpuesto]['totalrecargo'] += $line->recargo * $line->cantidad;
                    break;

                default:
                    $subtotals[$codimpuesto]['totaliva'] += $pvpTotal * $line->iva / 100;
                    $subtotals[$codimpuesto]['totalrecargo'] += $pvpTotal * $line->recargo / 100;
                    break;
            }
        }

        /// IRPF to the first subtotal
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['irpf'] = $irpf;
            $subtotals[$key]['totalirpf'] = $totalIrpf;
            break;
        }

        /// rounding totals
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['neto'] = \round($value['neto'], (int) \FS_NF0);
            $subtotals[$key]['netosindto'] = \round($value['netosindto'], (int) \FS_NF0);
            $subtotals[$key]['totalirpf'] = \round($value['totalirpf'], (int) \FS_NF0);
            $subtotals[$key]['totaliva'] = \round($value['totaliva'], (int) \FS_NF0);
            $subtotals[$key]['totalrecargo'] = \round($value['totalrecargo'], (int) \FS_NF0);
        }

        return $subtotals;
    }

    /**
     * Recalculates document totals.
     *
     * @param BusinessDocument $doc
     */
    public function recalculate(BusinessDocument &$doc)
    {
        $this->clearTotals($doc);

        $lines = $doc->getLines();
        foreach (\array_keys($lines) as $key) {
            $this->recalculateLine($lines[$key]);
        }

        foreach ($this->getSubtotals($lines, [$doc->dtopor1, $doc->dtopor2]) as $subt) {
            $doc->neto += $subt['neto'];
            $doc->netosindto += $subt['netosindto'];
            $doc->totalirpf += $subt['totalirpf'];
            $doc->totaliva += $subt['totaliva'];
            $doc->totalrecargo += $subt['totalrecargo'];
        }

        /// rounding totals again
        $doc->neto = \round($doc->neto, (int) \FS_NF0);
        $doc->netosindto = \round($doc->netosindto, (int) \FS_NF0);
        $doc->totalirpf = \round($doc->totalirpf, (int) \FS_NF0);
        $doc->totaliva = \round($doc->totaliva, (int) \FS_NF0);
        $doc->totalrecargo = \round($doc->totalrecargo, (int) \FS_NF0);
        $doc->total = \round($doc->neto + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) \FS_NF0);

        /// recalculate commissions
        $this->commissionTools->recalculate($doc, $lines);
    }

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
        }

        /// rounding totals again
        $doc->neto = \round($doc->neto, (int) \FS_NF0);
        $doc->netosindto = \round($doc->netosindto, (int) \FS_NF0);
        $doc->totalirpf = \round($doc->totalirpf, (int) \FS_NF0);
        $doc->totaliva = \round($doc->totaliva, (int) \FS_NF0);
        $doc->totalrecargo = \round($doc->totalrecargo, (int) \FS_NF0);
        $doc->total = \round($doc->neto + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) \FS_NF0);
        return \json_encode([
            'doc' => $doc,
            'lines' => $lines,
        ]);
    }

    /**
     *
     * @param BusinessDocument $doc
     */
    protected function clearTotals(BusinessDocument &$doc)
    {
        $this->impuestosZonas = [];
        $this->recargo = false;
        $this->siniva = false;

        $doc->neto = 0.0;
        $doc->netosindto = 0.0;
        $doc->total = 0.0;
        $doc->totaleuros = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalrecargo = 0.0;

        $serie = new Serie();
        if ($serie->loadFromCode($doc->codserie)) {
            $this->siniva = $serie->siniva;
        }

        if (isset($doc->codcliente)) {
            $cliente = new Cliente();
            if ($cliente->loadFromCode($doc->codcliente)) {
                $doc->irpf = $cliente->irpf();
                $this->loadRegimenIva($cliente->regimeniva);
                $this->loadTaxZones($doc);
            }
        } elseif (isset($doc->codproveedor)) {
            $proveedor = new Proveedor();
            if ($proveedor->loadFromCode($doc->codproveedor)) {
                $doc->irpf = $proveedor->irpf();
                $this->loadRegimenIva($proveedor->regimeniva);
            }

            $empresa = new Empresa();
            if ($empresa->loadFromCode($doc->idempresa)) {
                $this->loadRegimenIva($empresa->regimeniva);
            }
        }
    }

    /**
     *
     * @param string $reg
     */
    protected function loadRegimenIva($reg)
    {
        switch ($reg) {
            case RegimenIVA::TAX_SYSTEM_EXEMPT:
                $this->siniva = true;
                break;

            case RegimenIVA::TAX_SYSTEM_SURCHARGE:
                $this->recargo = true;
                break;
        }
    }

    /**
     *
     * @param BusinessDocument $doc
     */
    protected function loadTaxZones($doc)
    {
        $impuestoZonaModel = new ImpuestoZona();
        foreach ($impuestoZonaModel->all([], ['prioridad' => 'DESC']) as $impZona) {
            if ($impZona->codpais == $doc->codpais && $impZona->provincia() == $doc->provincia) {
                $this->impuestosZonas[] = $impZona;
            } elseif ($impZona->codpais == $doc->codpais && $impZona->codisopro == null) {
                $this->impuestosZonas[] = $impZona;
            } elseif ($impZona->codpais == null) {
                $this->impuestosZonas[] = $impZona;
            }
        }
    }

    /**
     *
     * @param BusinessDocumentLine $line
     */
    protected function recalculateLine(&$line)
    {
        /// apply tax zones
        $newCodimpuesto = $line->getProducto()->codimpuesto;
        foreach ($this->impuestosZonas as $impZona) {
            if ($newCodimpuesto == $impZona->codimpuesto) {
                $newCodimpuesto = $impZona->codimpuestosel;
                break;
            }
        }

        $save = false;
        if ($this->siniva || $newCodimpuesto === null) {
            $line->codimpuesto = null;
            $line->irpf = $line->iva = $line->recargo = 0.0;
            $save = true;
        } elseif ($newCodimpuesto != $line->codimpuesto) {
            /// get new tax
            $impuesto = new Impuesto();
            $impuesto->loadFromCode($newCodimpuesto);

            $line->codimpuesto = $newCodimpuesto;
            $line->iva = $impuesto->iva;
            $line->recargo = $impuesto->recargo;
            $save = true;
        }

        if ($line->recargo && !$this->recargo) {
            $line->recargo = 0.0;
            $save = true;
        }

        if ($save) {
            $line->save();
        }
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
            $this->recalculateFormLineTaxZones($newLine);
        } else {
            /// new line without reference
            $newLine = $doc->getNewLine();
            $newLine->descripcion = $fLine['descripcion'] ?? '';
            $this->recalculateFormLineTaxZones($newLine);
        }

        $newLine->descripcion = Utils::fixHtml($newLine->descripcion);
        $newLine->pvpsindto = $newLine->pvpunitario * $newLine->cantidad;
        $newLine->pvptotal = $newLine->pvpsindto * (100 - $newLine->dtopor) / 100 * (100 - $newLine->dtopor2) / 100;
        $newLine->referencia = Utils::fixHtml($newLine->referencia);

        if ($this->siniva) {
            $newLine->codimpuesto = null;
            $newLine->irpf = $newLine->iva = $newLine->recargo = 0.0;
        } elseif (!$this->recargo) {
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
        foreach ($this->impuestosZonas as $impZona) {
            if ($newCodimpuesto == $impZona->codimpuesto) {
                $newCodimpuesto = $impZona->codimpuestosel;
                break;
            }
        }

        if ($newCodimpuesto != $line->codimpuesto) {
            /// get new tax
            $impuesto = new Impuesto();
            $impuesto->loadFromCode($newCodimpuesto);

            $line->codimpuesto = $newCodimpuesto;
            $line->iva = $impuesto->iva;
            $line->recargo = $impuesto->recargo;
        }
    }
}
