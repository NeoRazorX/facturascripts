<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Impuesto;

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
        $taxException = $subject->excepcioniva ?? null;
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

            // El cliente o proveedor está exento de IVA
            if ($regimen === RegimenIVA::TAX_SYSTEM_EXEMPT) {
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

        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        foreach ($lines as $line) {
            $ivaKey = $line->iva . '|' . $line->recargo;
            if (!isset($subtotals[$ivaKey])) {
                continue;
            }

            // cálculos generales
            $totalCoste = isset($line->coste) ? $line->cantidad * $line->coste : 0.0;
            $pvpTotal = $line->pvptotal * (100 - $doc->dtopor1) / 100 * (100 - $doc->dtopor2) / 100;

            // si es una venta de segunda mano, calculamos el beneficio y el IVA
            if (self::applyUsedGoods($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste)) {
                continue;
            }

            // IVA
            if ($doc->operacion === InvoiceOperation::INTRA_COMMUNITY) {
                $subtotals['iva'][$ivaKey]['totaliva'] = 0;
            }

            // recargo de equivalencia
            if ($doc->operacion === InvoiceOperation::INTRA_COMMUNITY) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] = 0;
            }
        }
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
            $beneficio * $line->iva :
            $beneficio * $line->iva / 100;

        return true;
    }
}