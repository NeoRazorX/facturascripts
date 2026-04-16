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

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Template\CalculatorModClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\ProductType;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * This class implements the CalculatorModInterface for Spain.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <contacto@danielfg.es>
 */
class CalculatorModSpain extends CalculatorModClass
{
    public function accumulateSubtotals(array &$subtotals, BusinessDocument $doc, array &$lines): string
    {
        // si la empresa no es española, saltamos
        if (false === self::isSpanishCompany($doc)) {
            return $this->done();
        }

        // método de cálculo configurable: classic (por defecto) o price-adjusted
        $taxMethod = Tools::settings('default', 'taxcalculationmethod', 'classic');

        foreach ($lines as $line) {
            // coste
            $totalCoste = isset($line->coste) ? $line->cantidad * $line->coste : 0.0;
            if (isset($line->coste)) {
                $subtotals['totalcoste'] += $totalCoste;
            }

            $pvpTotal = $line->pvptotal * $doc->getEUDiscount();
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

            // si es una venta de segunda mano o de agencia de viajes, calculamos el IVA sobre el margen
            if (
                self::applyUsedGoods($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste)
                || self::applyTravel($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste)
            ) {
                continue;
            }

            // neto
            $subtotals['iva'][$ivaKey]['neto'] += $pvpTotal;
            $subtotals['iva'][$ivaKey]['netosindto'] += $line->pvptotal;

            // IVA
            if ($line->iva <= 0) {
                $subtotals['iva'][$ivaKey]['totaliva'] += 0;
            } elseif ($taxMethod === 'price-adjusted' && $line->getTax()->tipo !== Impuesto::TYPE_FIXED_VALUE) {
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

            // recargo de equivalencia
            if ($line->recargo > 0) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $line->cantidad * $line->recargo :
                    $pvpTotal * $line->recargo / 100;
            }
        }

        return $this->stopMods();
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return string
     */
    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine $line): string
    {
        if (false === self::isSpanishCompany($doc)) {
            return $this->done();
        }

        if ($line->recargo > 0) {
            $docRegimen = $doc->getSubject()->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;
            $companyRegimen = $doc->getCompany()->regimeniva;

            $applyRecargo = ($doc instanceof SalesDocument && $docRegimen === RegimenIVA::TAX_SYSTEM_SURCHARGE)
                || ($doc instanceof PurchaseDocument && $companyRegimen === RegimenIVA::TAX_SYSTEM_SURCHARGE);
            if (false === $applyRecargo) {
                $line->recargo = 0.0;
            }
        }

        return $this->done();
    }

    public function apply(BusinessDocument $doc, array &$lines): string
    {
        // si la empresa no es española, saltamos
        if (false === self::isSpanishCompany($doc)) {
            return $this->done();
        }

        $docRegimen = $doc->getSubject()->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;
        $companyRegimen = $doc->getCompany()->regimeniva;

        foreach ($lines as $line) {
            // operaciones especiales: intracomunitaria, exportación, importación
            if (self::applyOperation($doc, $line)) {
                continue;
            }

            // Oro de inversión: exento de IVA (art. 140 LIVA)
            if ($docRegimen === RegimenIVA::TAX_SYSTEM_GOLD) {
                $line->codimpuesto = null;
                $line->iva = $line->recargo = 0.0;
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_OTHER;
                continue;
            }

            // REAGYP: compras a proveedores en régimen agrario no llevan recargo
            // y se marca con excepción E6 para el SII (arts. 124-134 LIVA)
            if ($doc instanceof PurchaseDocument && $docRegimen === RegimenIVA::TAX_SYSTEM_AGRARIAN) {
                $line->recargo = 0.0;
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_OTHER;
                continue;
            }

            // Si es una compra de bienes usados, no aplicamos impuestos
            if (
                $doc instanceof PurchaseDocument
                && $companyRegimen === RegimenIVA::TAX_SYSTEM_USED_GOODS
                && $line->getProducto()->tipo === ProductType::SECOND_HAND
            ) {
                $line->codimpuesto = null;
                $line->iva = $line->recargo = 0.0;
                continue;
            }

            // Recargo de equivalencia:
            // - Ventas: aplica si el CLIENTE tiene régimen RE
            // - Compras: aplica si la EMPRESA tiene régimen RE
            $applyRecargo = ($doc instanceof SalesDocument && $docRegimen === RegimenIVA::TAX_SYSTEM_SURCHARGE)
                || ($doc instanceof PurchaseDocument && $companyRegimen === RegimenIVA::TAX_SYSTEM_SURCHARGE);
            if (false === $applyRecargo) {
                $line->recargo = 0.0;
            }
        }

        return $this->done();
    }

    protected static function applyOperation(BusinessDocument $doc, BusinessDocumentLine $line): bool
    {
        // Intracomunitaria de bienes (AIB)
        if ($doc->operacion === InvoiceOperation::INTRA_COMMUNITY) {
            // Ventas intracomunitarias: IVA 0% con exención E5 (art. 25 LIVA)
            if ($doc instanceof SalesDocument) {
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
                $line->iva = $line->recargo = 0.0;
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_25;
                return true;
            }

            // Compras intracomunitarias (AIB): mantenemos codimpuesto e IVA originales
            // para que la contabilidad pueda calcular la autorepercusión.
            $line->recargo = 0.0;
            $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_84;
            return true;
        }

        // Servicios intracomunitarios (B2B UE)
        if ($doc->operacion === InvoiceOperation::INTRA_COMMUNITY_SERVICES) {
            // Ventas de servicios a empresarios UE: no sujeta por reglas de localización
            // (arts. 69-70 LIVA: el servicio tributa en destino)
            if ($doc instanceof SalesDocument) {
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
                $line->iva = $line->recargo = 0.0;
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_68_70;
                return true;
            }

            // Compras de servicios a empresarios UE: ISP (art. 84.Uno.2º LIVA).
            // Mantenemos codimpuesto e IVA originales para la autorepercusión.
            $line->recargo = 0.0;
            $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_84;
            return true;
        }

        // ISP doméstico (art. 84.Uno.2º LIVA): construcción, chatarra, oro, inmuebles...
        if ($doc->operacion === InvoiceOperation::REVERSE_CHARGE) {
            // Ventas con ISP: el vendedor no cobra IVA, el comprador autorepercute
            if ($doc instanceof SalesDocument) {
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
                $line->iva = $line->recargo = 0.0;
                $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_84;
                return true;
            }

            // Compras con ISP: mantenemos codimpuesto e IVA originales para la autorepercusión
            $line->recargo = 0.0;
            $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_84;
            return true;
        }

        // Exportación: venta a terceros países, IVA 0% con exención E2 (art. 21 LIVA)
        if ($doc instanceof SalesDocument && $doc->operacion === InvoiceOperation::EXPORT) {
            $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
            $line->iva = $line->recargo = 0.0;
            $line->excepcioniva = TaxExceptions::ES_TAX_EXCEPTION_21;
            return true;
        }

        // Importación: el proveedor extranjero no cobra IVA español.
        // El IVA se liquida en aduanas (DUA), que es un documento aparte.
        if ($doc instanceof PurchaseDocument && $doc->operacion === InvoiceOperation::IMPORT) {
            $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
            $line->iva = $line->recargo = 0.0;
            return true;
        }

        return false;
    }

    public function updateSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): string
    {
        if (false === self::isSpanishCompany($doc)) {
            return $this->done();
        }

        // En compras intracomunitarias (AIB e ISP servicios), la autorepercusión se compensa:
        // IVA devengado = IVA deducible → efecto neto 0 en el documento.
        // Mantenemos neto, codimpuesto e iva% en el array para que la contabilidad
        // pueda recalcular y generar los asientos de autorepercusión.
        if (
            $doc instanceof PurchaseDocument
            && in_array($doc->operacion, [InvoiceOperation::INTRA_COMMUNITY, InvoiceOperation::INTRA_COMMUNITY_SERVICES, InvoiceOperation::REVERSE_CHARGE])
        ) {
            foreach ($subtotals['iva'] as &$value) {
                $value['totaliva'] = 0.0;
                $value['totalrecargo'] = 0.0;
            }
            unset($value);
        }

        return $this->done();
    }

    protected static function applyUsedGoods(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste): bool
    {
        // si no es una venta de segunda mano, no hacemos nada
        if (
            $doc instanceof PurchaseDocument
            || $doc->getCompany()->regimeniva !== RegimenIVA::TAX_SYSTEM_USED_GOODS
            || $line->getProducto()->tipo !== ProductType::SECOND_HAND
        ) {
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

        $beneficio = $pvpTotal - $totalCoste;

        // siempre acumulamos el margen en el neto (puede ser negativo)
        $subtotals['iva'][$ivaKey]['neto'] += $beneficio;
        $subtotals['iva'][$ivaKey]['netosindto'] += $beneficio;

        // solo aplicamos IVA si hay beneficio o es rectificativa
        if ($beneficio > 0 || $doc->getSerie()->tipo === 'R') {
            $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                $line->cantidad * $line->iva :
                $beneficio * $line->iva / 100;
        }

        return true;
    }

    protected static function applyTravel(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste): bool
    {
        // si no es una venta de una agencia de viajes, no hacemos nada
        if (
            $doc instanceof PurchaseDocument
            || $doc->getCompany()->regimeniva !== RegimenIVA::TAX_SYSTEM_TRAVEL
        ) {
            return false;
        }

        // IVA 0% para la parte del coste (arts. 141-147 LIVA)
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

        $margen = $pvpTotal - $totalCoste;

        // siempre acumulamos el margen en el neto (puede ser negativo)
        $subtotals['iva'][$ivaKey]['neto'] += $margen;
        $subtotals['iva'][$ivaKey]['netosindto'] += $margen;

        // solo aplicamos IVA si hay margen positivo o es rectificativa
        if ($margen > 0 || $doc->getSerie()->tipo === 'R') {
            $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                $line->cantidad * $line->iva :
                $margen * $line->iva / 100;
        }

        return true;
    }

    protected static function isSpanishCompany(BusinessDocument $doc): bool
    {
        return $doc->getCompany()->codpais === 'ESP';
    }
}
