<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Mod;

use FacturaScripts\Core\Contract\CalculatorModInterface;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\FiscalNumberValidator;
use FacturaScripts\Dinamic\Model\Contacto;

/**
 * This class implements the CalculatorModInterface for Spain.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class CalculatorModSpain implements CalculatorModInterface
{
    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        // No se aplica el cálculo si la empresa no está en España
        $company = $doc->getCompany();
        if ($company->codpais !== 'ESP') {
            return true;
        }

        $subject = $doc->getSubject();
        $regimen = $subject->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;

        foreach ($lines as $line) {
            // Si es una compra de bienes usados, no aplicamos impuestos
            if ($doc->subjectColumn() === 'codproveedor' &&
                $company->regimeniva === RegimenIVA::TAX_SYSTEM_USED_GOODS &&
                $line->getProducto()->tipo === ProductType::SECOND_HAND) {
                $line->codimpuesto = null;
                $line->iva = $line->recargo = 0.0;
                continue;
            }

            // ¿El régimen IVA es sin recargo de equivalencia?
            if ($regimen != RegimenIVA::TAX_SYSTEM_SURCHARGE) {
                $line->recargo = 0.0;
            }
        }

        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        // No se aplica el cálculo si la empresa no está en España
        $company = $doc->getCompany();
        if ($company->codpais !== 'ESP') {
            return true;
        }

        // si la línea no es nueva, no hacemos nada
        if (!empty($line->id())) {
            return true;
        }

        // excepción por defecto del sujeto (cliente o proveedor)
        $subjectException = $doc->getSubject()->excepcioniva ?? null;

        // si el documento es intracomunitario
        if ($doc->operacion === InvoiceOperation::INTRA_COMMUNITY) {
            $line->iva = 0.0;
            $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;

            if ($subjectException) {
                // usamos la excepción configurada en el sujeto
                $line->excepcioniva = $subjectException;
            } elseif ($doc->subjectColumn() === 'codcliente') {
                // valor por defecto para ventas intracomunitarias: E5 (art. 25 LIVA)
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_E5;
            } else {
                // valor por defecto para compras intracomunitarias: inversión del sujeto pasivo (art. 84 LIVA)
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT;
            }

            return true;
        }

        // si el documento es de exportación, aplicamos IVA 0% y exención del sujeto o E2 por defecto
        if ($doc->operacion === InvoiceOperation::EXPORT) {
            $line->iva = 0.0;
            $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
            $line->excepcioniva = $subjectException ?? TaxExceptions::ES_TAX_EXCEPTION_E2;
            return true;
        }

        // si el documento es de importación, la factura del proveedor va sin IVA
        // (el IVA se liquida en aduanas vía DUA)
        if ($doc->operacion === InvoiceOperation::IMPORT) {
            $line->iva = 0.0;
            $line->recargo = 0.0;
            $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
            $line->excepcioniva = null;
            return true;
        }

        // si el sujeto tiene una excepción de IVA configurada (p.ej. inversión del sujeto pasivo
        // en operaciones nacionales art. 84.1.2º LIVA, o no sujetas N1/N2), la aplicamos
        if ($subjectException) {
            $line->excepcioniva = $subjectException;

            // las excepciones de tipo "no sujeta" e "inversión del sujeto pasivo" implican IVA 0%
            $zeroIvaExceptions = [TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::ES_TAX_EXCEPTION_N2];
            if (in_array($subjectException, $zeroIvaExceptions)) {
                $line->iva = 0.0;
                $line->recargo = 0.0;
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
            }

            return true;
        }

        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    /**
     * @param array $subtotals
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     * @return bool
     */
    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        // No se aplica el cálculo si la empresa no está en España
        $company = $doc->getCompany();
        if ($company->codpais !== 'ESP') {
            return true;
        }

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

        $subject = $doc->getSubject();
        $addressShipping = new Contacto();
        if ($doc->hasColumn('idcontactoenv')) {
            $addressShipping->load($doc->idcontactoenv);
        }

        // método de cálculo configurable: classic (por defecto) o price-adjusted
        $taxMethod = Tools::settings('default', 'taxcalculationmethod', 'classic');

        // Inicialización de variables globales para las 4 comprobaciones
        $globalEx = $doc->operacion;
        $allZeroIva = true;
        $firstEx = null;
        $exenciones = [];
        $hasIva = false;
        $allE3 = $allE4 = $allE2 = $allE5 = $allN2 = true;
        $allLinesSaved = true;

        foreach ($lines as $line) {
            // si la línea es nueva, marcamos que al menos una línea no está guardada
            if (empty($line->primaryColumnValue())) {
                $allLinesSaved = false;
            }

            // 1. Acumular para validación global
            if ($line->iva > 0) $allZeroIva = false;
            if (empty($firstEx)) $firstEx = $line->excepcioniva;

            // 2. Acumular para conflictos de exenciones
            if ($line->iva > 0) $hasIva = true;
            if ($line->excepcioniva) $exenciones[$line->excepcioniva] = true;

            // 3. Validación de exención por línea
            if (!$this->validateLineExemptions($doc, $line, $subject->tipoidfiscal, $addressShipping, $globalEx)) {
                return false;
            }

            // 4. Acumular para sugerencia de global
            if ($line->iva > 0) $allE3 = $allE4 = $allE2 = $allE5 = false;
            if (!empty($line->excepcioniva) && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_E3) $allE3 = false;
            if (!empty($line->excepcioniva) && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_E4) $allE4 = false;
            if (!empty($line->excepcioniva) && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_E2) $allE2 = false;
            if (!empty($line->excepcioniva) && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_E5) $allE5 = false;
            if (!empty($line->excepcioniva) && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_N2) $allN2 = false;

            // coste
            $totalCoste = isset($line->coste) ? $line->cantidad * $line->coste : 0.0;
            if (isset($line->coste)) {
                $subtotals['totalcoste'] += $totalCoste;
            }

            $pvpTotal = $line->pvptotal * (100 - $doc->dtopor1) / 100 * (100 - $doc->dtopor2) / 100;
            if (empty($line->pvptotal)) {
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
            $noTaxOp = in_array($doc->operacion, [InvoiceOperation::INTRA_COMMUNITY, InvoiceOperation::EXPORT, InvoiceOperation::IMPORT]);
            if ($line->iva > 0 && !$noTaxOp) {
                if ($taxMethod === 'price-adjusted' && $line->getTax()->tipo !== Impuesto::TYPE_FIXED_VALUE) {
                    // calculamos el precio con IVA unitario
                    $pvp_iva = Tools::round($line->pvpunitario * (100 + $line->iva) / 100);

                    // calculamos el IVA como la diferencia
                    // entre el total con IVA redondeado y el neto redondeado
                    // para evitar errores de redondeo cuando se establece el precio con IVA incluido
                    $pvpTotalConIva = $line->cantidad * $pvp_iva
                        * (100 - $line->dtopor) / 100
                        * (100 - $line->dtopor2) / 100
                        * (100 - $doc->dtopor1) / 100
                        * (100 - $doc->dtopor2) / 100;
                    $subtotals['iva'][$ivaKey]['totaliva'] += Tools::round($pvpTotalConIva) - Tools::round($pvpTotal);
                } else {
                    $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                        $line->cantidad * $line->iva :
                        $pvpTotal * $line->iva / 100;
                }
            }

            // recargo de equivalencia
            if ($line->recargo > 0 && !$noTaxOp) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $line->cantidad * $line->recargo :
                    $pvpTotal * $line->recargo / 100;
            }
        }

        // 1. Validación global
        if (!$this->validateGlobalExemption($globalEx, $allZeroIva, $exenciones, $doc)) {
            return false;
        }

        // 2. Conflictos de exenciones
        if (!$this->checkLineConflicts($exenciones, $hasIva)) {
            return false;
        }

        // 4. Sugerencia automática de global
        $this->suggestGlobalExemption($doc, $lines, $allLinesSaved, $allE3, $allE4, $allE2, $allE5, $allN2);

        return true;
    }

    private static function applyUsedGoods(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste): bool
    {
        // si no es una venta de segunda mano, no hacemos nada
        if ($doc->subjectColumn() !== 'codcliente' ||
            $doc->getCompany()->regimeniva !== RegimenIVA::TAX_SYSTEM_USED_GOODS ||
            $line->getProducto()->tipo !== ProductType::SECOND_HAND) {
            return false;
        }

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
            $line->cantidad * $line->iva :
            $beneficio * $line->iva / 100;

        return true;
    }

    /**
     * Válida conflictos de exenciones a nivel de línea.
     * No permite mezclar exenciones incompatibles (ej: E1+IVA, E2+IVA, E1+E2, etc).
     * Lanza advertencia o retorna false si hay conflicto.
     */
    private function checkLineConflicts(array $exenciones, bool $hasIva): bool
    {
        if (isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E1]) && (count($exenciones) > 1 || $hasIva)) {
            Tools::log()->warning('Excepción fiscal ES_20 no puede combinarse con IVA o con otras exenciones.');
            return false;
        }

        if (isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E2]) && (count($exenciones) > 1 || $hasIva)) {
            Tools::log()->warning('Excepción fiscal ES_21 no puede combinarse con IVA o con otras exenciones.');
            return false;
        }

        if ((isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E3]) || isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E4]))
            && ($hasIva || isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E2]) || isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E1]))) {
            Tools::log()->warning('Excepción fiscal ES_22 o ES_23_24 no puede combinarse con IVA, ES_21 o ES_20.');
            return false;
        }

        if ((isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_N1]) || isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_N2])) && $hasIva) {
            Tools::log()->warning('Excepciones fiscales N1/N2 (no sujeta) no pueden combinarse con IVA.');
            return false;
        }

        return true;
    }

    /**
     * Sugiere el valor global de exención si todas las líneas son fiscalmente coherentes.
     * Devuelve el tipo de operación global sugerido o null si no aplica.
     */
    private function suggestGlobalExemption(BusinessDocument $doc, array $lines, bool $allLinesSaved, bool $allE3, bool $allE4, bool $allE2, bool $allE5, bool $allN2): void
    {
        // solo sugerir si todas las líneas están guardadas (tienen id)
        // no hay operación global
        // no hay líneas o todas las líneas son nuevas
        if (!$allLinesSaved || !empty($doc->operacion) || count($lines) === 0) {
            return;
        }

        if ($allE3 || $allE4 || $allE5 || $allN2) {
            Tools::log()->info('Sugerencia: Puedes marcar la factura como intracomunitaria porque todas las líneas son E3, E4, E5 o N2.');
        } elseif ($allE2) {
            Tools::log()->info('Sugerencia: Puedes marcar la factura como de exportación porque todas las líneas son E2.');
        }
    }

    /**
     * Válida coherencia entre operación global y líneas.
     * Si hay conflicto, anula el valor global y lanza advertencia.
     */
    private function validateGlobalExemption(?string $globalEx, bool $allZeroIva, array $exenciones, BusinessDocument $doc): bool
    {
        // si no hay operación global definida, no hay nada que validar
        if (empty($globalEx)) {
            return true;
        }

        if ($globalEx === InvoiceOperation::INTRA_COMMUNITY) {
            if (!$allZeroIva) {
                Tools::log()->warning('Las líneas no pueden tener IVA si la operación es intracomunitaria.');
                return false;
            }

            // en ventas, las excepciones válidas son E3, E4, E5, N2
            $validSales = [TaxExceptions::ES_TAX_EXCEPTION_E3, TaxExceptions::ES_TAX_EXCEPTION_E4, TaxExceptions::ES_TAX_EXCEPTION_E5, TaxExceptions::ES_TAX_EXCEPTION_N2];
            // en compras, las excepciones válidas son inversión sujeto pasivo, N1, N2
            $validPurchases = [TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::ES_TAX_EXCEPTION_N2];
            $valid = $doc->subjectColumn() === 'codcliente' ? $validSales : $validPurchases;

            foreach ($exenciones as $ex => $v) {
                if (!in_array($ex, $valid)) {
                    Tools::log()->warning('Excepción fiscal incompatible con operación intracomunitaria.');
                    return false;
                }
            }
        }

        if ($globalEx === InvoiceOperation::EXPORT) {
            if (!$allZeroIva || (count($exenciones) && !isset($exenciones[TaxExceptions::ES_TAX_EXCEPTION_E2]))) {
                Tools::log()->warning('Las líneas no pueden tener IVA si la operación es de exportación.');
                return false;
            }
        }

        if ($globalEx === InvoiceOperation::IMPORT) {
            if (!$allZeroIva) {
                Tools::log()->warning('Las líneas no pueden tener IVA si la operación es de importación.');
                return false;
            }
            if (count($exenciones) > 0) {
                Tools::log()->warning('Las líneas de importación no deben tener excepción de IVA.');
                return false;
            }
        }

        return true;
    }

    /**
     * Válida que cada línea tenga una exención fiscalmente coherente según el global y la propia línea.
     * Retorna false si alguna línea no cumple la lógica fiscal.
     */
    private function validateLineExemptions(BusinessDocument $doc, BusinessDocumentLine $line, ?string $subjectFiscalID, Contacto $addressShipping, ?string $globalEx): bool
    {
        // obtenemos y traducimos excepciones
        $exceptions = TaxExceptions::all();
        foreach ($exceptions as $key => $translationKey) {
            $exceptions[$key] = Tools::trans($translationKey);
        }

        // E1: ES_20 solo si global es exenta E1 o la línea lo justifica
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E1 && $line->iva > 0) {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_20'] . '" no puede tener IVA.');
            return false;
        }

        // E2: ES_21 solo si cliente fuera UE
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E2 && $line->iva > 0 && Paises::miembroUE($doc->codpais)) {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_21'] . '" no puede tener IVA si el cliente es de la UE.');
            return false;
        }

        // E3: ES_22 Exenta art. 22 LIVA – Operaciones asimiladas a exportaciones, nunca llevan IVA
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E3 && $line->iva > 0) {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_22'] . '" no puede tener IVA.');
            return false;
        }

        // E4: ES_23_24 Exenta arts. 23–24 LIVA – Zonas francas y depósitos aduaneros, nunca llevan IVA
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E4 && $line->iva > 0) {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_23_24'] . '" no puede tener IVA.');
            return false;
        }

        // E5: ES_25 Venta de bienes intracomunitarios E5
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E5 && $line->iva > 0 && !empty($addressShipping->id()) && $addressShipping->codpais === 'ESP') {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_25'] . '" no puede tener IVA si el transporte no es internacional.');
            return false;
        }

        // E6: ES_OTHER solo si justificado
        if ($line->excepcioniva === TaxExceptions::ES_TAX_EXCEPTION_E6 && $line->iva > 0) {
            Tools::log()->warning('Excepción fiscal "' . $exceptions['ES_OTHER'] . '" no puede tener IVA.');
            return false;
        }

        // Inversión del sujeto pasivo (art. 84), no sujetas N1 (art. 7, 14 y otros) y N2 (reglas de localización) nunca llevan IVA.
        if (in_array($line->excepcioniva, [TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::ES_TAX_EXCEPTION_N2])
            && $line->iva > 0) {
            Tools::log()->warning("Excepción fiscal $line->excepcioniva no puede tener IVA.");
            return false;
        }

        // Si hay global de intracomunitaria, validamos según sea compra o venta
        if ($globalEx === InvoiceOperation::INTRA_COMMUNITY) {
            // En VENTAS intracomunitarias, la línea debe ser E3, E4, E5 o N2
            if ($doc->subjectColumn() === 'codcliente' && !in_array($line->excepcioniva, [TaxExceptions::ES_TAX_EXCEPTION_E3, TaxExceptions::ES_TAX_EXCEPTION_E4, TaxExceptions::ES_TAX_EXCEPTION_E5, TaxExceptions::ES_TAX_EXCEPTION_N2])) {
                Tools::log()->warning('En ventas intracomunitarias, la línea debe ser E3, E4, E5 o N2.');
                return false;
            }

            // En COMPRAS intracomunitarias, la línea debe ser inversión del sujeto pasivo, N1 (no sujeta) o N2 (reglas de localización), o sin excepción
            if ($doc->subjectColumn() === 'codproveedor' &&
                !empty($line->excepcioniva) &&
                !in_array($line->excepcioniva, [TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::ES_TAX_EXCEPTION_N2])) {
                Tools::log()->warning('En compras intracomunitarias, la línea debe usar inversión del sujeto pasivo, N1 (no sujeta) o N2 (reglas de localización).');
                return false;
            }
        }

        // Si hay global de exportación, la línea debe ser E2
        if ($globalEx === InvoiceOperation::EXPORT && $line->excepcioniva !== TaxExceptions::ES_TAX_EXCEPTION_E2) {
            Tools::log()->warning('La línea debe ser E2 si la operación global es de exportación.');
            return false;
        }

        // Si hay global de importación, la línea no debe tener excepción
        if ($globalEx === InvoiceOperation::IMPORT && !empty($line->excepcioniva)) {
            Tools::log()->warning('Las líneas de importación no deben tener excepción de IVA.');
            return false;
        }

        return true;
    }
}
