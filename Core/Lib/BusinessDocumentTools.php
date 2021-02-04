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

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Lib\RegimenIVA as DinRegimenIVA;
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
    protected $taxZones = [];

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
        /// calculates the equivalent unified discount
        $eud = 1.0;
        foreach ($discounts as $dto) {
            $eud *= 1 - $dto / 100;
        }

        $irpf = 0.0;
        $subtotals = [];
        $totalIrpf = 0.0;
        $totalSuplidos = 0.0;
        foreach ($lines as $line) {
            $pvpTotal = $line->pvptotal * $eud;
            if (empty($pvpTotal)) {
                continue;
            } elseif ($line->suplido) {
                $totalSuplidos += $pvpTotal;
                continue;
            }

            $codimpuesto = empty($line->codimpuesto) ? $line->iva . '-' . $line->recargo : $line->codimpuesto;
            if (false === \array_key_exists($codimpuesto, $subtotals)) {
                $subtotals[$codimpuesto] = [
                    'irpf' => 0.0,
                    'iva' => $line->iva,
                    'neto' => 0.0,
                    'netosindto' => 0.0,
                    'recargo' => $line->recargo,
                    'totalirpf' => 0.0,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0,
                    'totalsuplidos' => 0.0
                ];
            }

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

        /// Aditional taxes to the first subtotal
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['irpf'] = $irpf;
            $subtotals[$key]['totalirpf'] = $totalIrpf;
            $subtotals[$key]['totalsuplidos'] = $totalSuplidos;
            break;
        }

        /// rounding totals
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['neto'] = \round($value['neto'], (int) \FS_NF0);
            $subtotals[$key]['netosindto'] = \round($value['netosindto'], (int) \FS_NF0);
            $subtotals[$key]['totalirpf'] = \round($value['totalirpf'], (int) \FS_NF0);
            $subtotals[$key]['totaliva'] = \round($value['totaliva'], (int) \FS_NF0);
            $subtotals[$key]['totalrecargo'] = \round($value['totalrecargo'], (int) \FS_NF0);
            $subtotals[$key]['totalsuplidos'] = \round($value['totalsuplidos'], (int) \FS_NF0);
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

        /// recalculate commissions
        $this->commissionTools->recalculate($doc, $lines);
    }

    /**
     *
     * @param BusinessDocument $doc
     */
    protected function clearTotals(BusinessDocument &$doc)
    {
        $this->taxZones = [];
        $this->recargo = false;
        $this->siniva = false;

        $doc->neto = 0.0;
        $doc->netosindto = 0.0;
        $doc->total = 0.0;
        $doc->totaleuros = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalrecargo = 0.0;
        $doc->totalsuplidos = 0.0;

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
            case DinRegimenIVA::TAX_SYSTEM_EXEMPT:
                $this->siniva = true;
                break;

            case DinRegimenIVA::TAX_SYSTEM_SURCHARGE:
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
        $taxZoneModel = new ImpuestoZona();
        foreach ($taxZoneModel->all([], ['prioridad' => 'DESC']) as $taxZone) {
            if ($taxZone->codpais == $doc->codpais && $taxZone->provincia() == $doc->provincia) {
                $this->taxZones[] = $taxZone;
            } elseif ($taxZone->codpais == $doc->codpais && $taxZone->codisopro == null) {
                $this->taxZones[] = $taxZone;
            } elseif ($taxZone->codpais == null) {
                $this->taxZones[] = $taxZone;
            }
        }
    }

    /**
     *
     * @param BusinessDocumentLine $line
     */
    protected function recalculateLine(&$line)
    {
        $save = false;
        $newCodimpuesto = $this->recalculateLineTax($line);

        if ($this->siniva || $newCodimpuesto === null || $line->suplido) {
            $line->codimpuesto = null;
            $line->iva = $line->recargo = 0.0;
            $save = true;
        } elseif ($newCodimpuesto !== $line->codimpuesto) {
            /// set new tax
            $line->codimpuesto = $newCodimpuesto;
            $line->iva = $line->getTax()->iva;
            $line->recargo = $line->getTax()->recargo;
            $save = true;
        }

        if ($line->recargo && $this->recargo === false) {
            $line->recargo = 0.0;
            $save = true;
        }

        if ($save) {
            $line->save();
        }
    }

    /**
     * 
     * @param BusinessDocumentLine $line
     *
     * @return string
     */
    protected function recalculateLineTax(&$line)
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

        return $newCodimpuesto;
    }
}
