<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\Contract\CalculatorModInterface;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Model\ImpuestoZona;

/**
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 *
 * @deprecated replaced by Core/Lib/Calculator
 */
final class Calculator
{
    /** @var CalculatorModInterface[] */
    public static $mods = [];

    public static function addMod(CalculatorModInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public static function calculate(BusinessDocument &$doc, array &$lines, bool $save): bool
    {
        // ponemos totales a 0
        self::clear($doc, $lines);

        // aplicamos configuraciones, excepciones, etc
        self::apply($doc, $lines);

        // calculamos subtotales en líneas
        foreach ($lines as $line) {
            self::calculateLine($doc, $line);
        }

        // calculamos los totales
        $subtotals = self::getSubtotals($doc, $lines);
        $doc->irpf = $subtotals['irpf'];
        $doc->neto = $subtotals['neto'];
        $doc->netosindto = $subtotals['netosindto'];
        $doc->total = $subtotals['total'];
        $doc->totalirpf = $subtotals['totalirpf'];
        $doc->totaliva = $subtotals['totaliva'];
        $doc->totalrecargo = $subtotals['totalrecargo'];
        $doc->totalsuplidos = $subtotals['totalsuplidos'];

        // si tiene totalbeneficio, lo asignamos
        if (property_exists($doc, 'totalbeneficio')) {
            $doc->totalbeneficio = $subtotals['totalbeneficio'];
        }

        // si tiene totalcoste, lo asignamos
        if (property_exists($doc, 'totalcoste')) {
            $doc->totalcoste = $subtotals['totalcoste'];
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // si el mod devuelve false, terminamos
            if (false === $mod->calculate($doc, $lines)) {
                break;
            }
        }

        return $save && self::save($doc, $lines);
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return array
     */
    public static function getSubtotals(BusinessDocument $doc, array $lines): array
    {
        $subtotals = [
            'irpf' => 0.0,
            'iva' => [],
            'neto' => 0.0,
            'netosindto' => 0.0,
            'totalcoste' => 0.0,
            'totalirpf' => 0.0,
            'totaliva' => 0.0,
            'totalrecargo' => 0.0,
            'totalsuplidos' => 0.0
        ];

        // acumulamos por cada línea
        foreach ($lines as $line) {
            // coste
            $totalCoste = isset($line->coste) ? $line->cantidad * $line->coste : 0.0;
            if (isset($line->coste)) {
                $subtotals['totalcoste'] += $totalCoste;
            }

            $pvpTotal = $line->pvptotal * (100 - $doc->dtopor1) / 100 * (100 - $doc->dtopor2) / 100;
            if (empty($pvpTotal)) {
                continue;
            }

            // los suplidos no tienen IVA ni IRPF
            if ($line->suplido) {
                $subtotals['totalsuplidos'] += $pvpTotal;
                continue;
            }

            // IRPF
            $subtotals['irpf'] = max([$line->irpf, $subtotals['irpf']]);
            $subtotals['totalirpf'] += $pvpTotal * $line->irpf / 100;

            // IVA
            $ivaKey = $line->iva . '|' . $line->recargo;
            if (false === array_key_exists($ivaKey, $subtotals['iva'])) {
                $subtotals['iva'][$ivaKey] = [
                    'codimpuesto' => $line->codimpuesto,
                    'iva' => $line->iva,
                    'neto' => 0.0,
                    'netosindto' => 0.0,
                    'recargo' => $line->recargo,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0
                ];
            }

            // si es una venta de segunda mano, calculamos el beneficio y el IVA
            if (self::applyUsedGoods($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste)) {
                continue;
            }

            // neto
            $subtotals['iva'][$ivaKey]['neto'] += $pvpTotal;
            $subtotals['iva'][$ivaKey]['netosindto'] += $line->pvptotal;

            // IVA
            if ($line->iva > 0 && $doc->operacion != InvoiceOperation::INTRA_COMMUNITY) {
                $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $pvpTotal * $line->iva :
                    $pvpTotal * $line->iva / 100;
            }

            // recargo de equivalencia
            if ($line->recargo > 0 && $doc->operacion != InvoiceOperation::INTRA_COMMUNITY) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $pvpTotal * $line->recargo :
                    $pvpTotal * $line->recargo / 100;
            }
        }

        // redondeamos los IVA
        foreach ($subtotals['iva'] as $key => $value) {
            $subtotals['iva'][$key]['neto'] = round($value['neto'], FS_NF0);
            $subtotals['iva'][$key]['netosindto'] = round($value['netosindto'], FS_NF0);
            $subtotals['iva'][$key]['totaliva'] = round($value['totaliva'], FS_NF0);
            $subtotals['iva'][$key]['totalrecargo'] = round($value['totalrecargo'], FS_NF0);

            // trasladamos a los subtotales
            $subtotals['neto'] += round($value['neto'], FS_NF0);
            $subtotals['netosindto'] += round($value['netosindto'], FS_NF0);
            $subtotals['totaliva'] += round($value['totaliva'], FS_NF0);
            $subtotals['totalrecargo'] += round($value['totalrecargo'], FS_NF0);
        }

        // redondeamos los subtotales
        $subtotals['neto'] = round($subtotals['neto'], FS_NF0);
        $subtotals['netosindto'] = round($subtotals['netosindto'], FS_NF0);
        $subtotals['totalirpf'] = round($subtotals['totalirpf'], FS_NF0);
        $subtotals['totaliva'] = round($subtotals['totaliva'], FS_NF0);
        $subtotals['totalrecargo'] = round($subtotals['totalrecargo'], FS_NF0);
        $subtotals['totalsuplidos'] = round($subtotals['totalsuplidos'], FS_NF0);

        // calculamos el beneficio
        $subtotals['totalbeneficio'] = round($subtotals['neto'] - $subtotals['totalcoste'], FS_NF0);

        // calculamos el total
        $subtotals['total'] = round($subtotals['neto'] + $subtotals['totalsuplidos'] + $subtotals['totaliva']
            + $subtotals['totalrecargo'] - $subtotals['totalirpf'], FS_NF0);

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // si el mod devuelve false, terminamos
            if (false === $mod->getSubtotals($subtotals, $doc, $lines)) {
                break;
            }
        }

        return $subtotals;
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return void
     */
    private static function apply(BusinessDocument &$doc, array &$lines): void
    {
        $subject = $doc->getSubject();
        $noTax = $doc->getSerie()->siniva;
        $taxException = $subject->excepcioniva ?? null;
        $regimen = $subject->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;
        $company = $doc->getCompany();

        // cargamos las zonas de impuestos
        $taxZones = [];
        if (isset($doc->codpais) && $doc->codpais) {
            $taxZoneModel = new ImpuestoZona();
            foreach ($taxZoneModel->all([], ['prioridad' => 'DESC']) as $taxZone) {
                if ($taxZone->codpais == $doc->codpais && $taxZone->provincia() == $doc->provincia) {
                    $taxZones[] = $taxZone;
                } elseif ($taxZone->codpais == $doc->codpais && $taxZone->codisopro == null) {
                    $taxZones[] = $taxZone;
                } elseif ($taxZone->codpais == null) {
                    $taxZones[] = $taxZone;
                }
            }
        }

        foreach ($lines as $line) {
            // Si es una compra de bienes usados, no aplicamos impuestos
            if ($doc->subjectColumn() === 'codproveedor' &&
                $company->regimeniva === RegimenIVA::TAX_SYSTEM_USED_GOODS &&
                $line->getProducto()->tipo === ProductType::SECOND_HAND) {
                $line->codimpuesto = null;
                $line->iva = $line->recargo = 0.0;
                continue;
            }

            // aplicamos las excepciones de impuestos
            foreach ($taxZones as $taxZone) {
                if ($line->codimpuesto === $taxZone->codimpuesto) {
                    $line->codimpuesto = $taxZone->codimpuestosel;
                    $line->iva = $line->getTax()->iva;
                    $line->recargo = $line->getTax()->recargo;
                    break;
                }
            }

            // ¿La serie es sin impuestos o el régimen exento?
            if ($noTax || $regimen === RegimenIVA::TAX_SYSTEM_EXEMPT) {
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
                $line->iva = $line->recargo = 0.0;
                $line->excepcioniva = $taxException;
                continue;
            }

            // ¿El régimen IVA es sin recargo de equivalencia?
            if ($regimen != RegimenIVA::TAX_SYSTEM_SURCHARGE) {
                $line->recargo = 0.0;
            }
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // si el mod devuelve false, terminamos
            if (false === $mod->apply($doc, $lines)) {
                break;
            }
        }
    }

    private static function applyUsedGoods(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste): bool
    {
        if ($doc->subjectColumn() === 'codcliente' &&
            $doc->getCompany()->regimeniva === RegimenIVA::TAX_SYSTEM_USED_GOODS &&
            $line->getProducto()->tipo === ProductType::SECOND_HAND) {
            // IVA 0%
            $ivaKey0 = '0|0';
            if (false === array_key_exists($ivaKey0, $subtotals['iva'])) {
                $subtotals['iva'][$ivaKey0] = [
                    'codimpuesto' => null,
                    'iva' => 0.0,
                    'neto' => 0.0,
                    'netosindto' => 0.0,
                    'recargo' => 0.0,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0
                ];
            }
            $subtotals['iva'][$ivaKey0]['neto'] += $totalCoste;
            $subtotals['iva'][$ivaKey0]['netosindto'] += $totalCoste;

            // si el beneficio es negativo y la serie no es rectificativa, no hay IVA
            $beneficio = $pvpTotal - $totalCoste;
            if ($beneficio <= 0 && $doc->getSerie()->tipo !== 'R') {
                return true;
            }

            // IVA seleccionado
            $subtotals['iva'][$ivaKey]['neto'] += $beneficio;
            $subtotals['iva'][$ivaKey]['netosindto'] += $beneficio;
            $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                $beneficio * $line->iva :
                $beneficio * $line->iva / 100;
            return true;
        }

        return false;
    }

    private static function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): void
    {
        $line->pvpsindto = $line->cantidad * $line->pvpunitario;
        $line->pvptotal = $line->pvpsindto * (100 - $line->dtopor) / 100 * (100 - $line->dtopor2) / 100;

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // si el mod devuelve false, terminamos
            if (false === $mod->calculateLine($doc, $line)) {
                break;
            }
        }
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return void
     */
    private static function clear(BusinessDocument &$doc, array &$lines): void
    {
        $doc->neto = $doc->netosindto = 0.0;
        $doc->total = $doc->totaleuros = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalrecargo = 0.0;
        $doc->totalsuplidos = 0.0;

        // si tiene totalcoste, lo reiniciamos
        if (property_exists($doc, 'totalcoste')) {
            $doc->totalcoste = 0.0;
        }

        foreach ($lines as $line) {
            $line->pvpsindto = $line->pvptotal = 0.0;
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // si el mod devuelve false, terminamos
            if (false === $mod->clear($doc, $lines)) {
                break;
            }
        }
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return bool
     */
    private static function save(BusinessDocument &$doc, array &$lines): bool
    {
        foreach ($lines as $line) {
            if (false === $line->save()) {
                return false;
            }
        }

        return $doc->save();
    }
}
