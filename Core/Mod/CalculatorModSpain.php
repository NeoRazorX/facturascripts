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

use FacturaScripts\Core\Contract\CalculatorModInterface2026;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\ProductType;
use FacturaScripts\Dinamic\Lib\TaxException;
use FacturaScripts\Dinamic\Lib\TaxRegime;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Impuesto;

/**
 * This class implements the CalculatorModInterface for Spain.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class CalculatorModSpain implements CalculatorModInterface2026
{
    public function apply(BusinessDocument &$doc, array &$lines): int
    {
        // No se aplica el cálculo si la empresa no está en España
        $company = $doc->getCompany();
        if ($company->codpais !== 'ESP') {
            return 1;
        }

        $subject = $doc->getSubject();
        $regimenCompany = $company->regimeniva ?? TaxRegime::ES_TAX_REGIME_GENERAL;
        $regimenSubject = $subject->regimeniva ?? TaxRegime::ES_TAX_REGIME_GENERAL;
        $isSale = $doc->subjectColumn() === 'codcliente';

        foreach ($lines as $line) {
            // Aplicar cálculo según el régimen correspondiente
            if ($isSale) {
                $this->applySaleRegime($doc, $line, $regimenCompany, $regimenSubject);
            } else {
                $this->applyPurchaseRegime($doc, $line, $regimenCompany, $regimenSubject);
            }
        }

        return 1;
    }

    public function calculate(BusinessDocument &$doc, array &$lines, bool $save): int
    {
        return 1;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): int
    {
        return 1;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

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

        $addressShipping = new Contacto();
        if (property_exists($doc, 'idcontactoenv')) {
            $addressShipping->load($doc->idcontactoenv);
        }

        // Obtener el régimen de la empresa (se usa en varios cálculos)
        $regimenCompany = $company->regimeniva ?? TaxRegime::ES_TAX_REGIME_GENERAL;

        foreach ($lines as $line) {
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

            // Regímenes especiales que requieren cálculo de subtotales específico

            // 1. Bienes Usados: IVA solo sobre beneficio
            if ($this->getSubtotalsUsedGoods($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste, $regimenCompany)) {
                continue;
            }

            // 2. Agencias de Viaje: IVA sobre margen
            if ($this->getSubtotalsTravelAgency($subtotals, $doc, $line, $ivaKey, $pvpTotal, $totalCoste, $regimenCompany)) {
                continue;
            }

            // Régimen General y otros regímenes estándar
            // neto
            $subtotals['iva'][$ivaKey]['neto'] += $pvpTotal;
            $subtotals['iva'][$ivaKey]['netosindto'] += $line->pvptotal;

            // IVA - Se calcula siempre que haya IVA, independientemente de la operación
            // Las operaciones exentas (intra, export) ya tienen IVA=0 desde calculateLine()
            if ($line->iva > 0) {
                $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $pvpTotal * $line->iva :
                    $pvpTotal * $line->iva / 100;
            }

            // Recargo de equivalencia - Se calcula siempre que haya recargo
            // Ya se neutralizó en apply() si el cliente también está en recargo
            if ($line->recargo > 0) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $pvpTotal * $line->recargo :
                    $pvpTotal * $line->recargo / 100;
            }
        }

        return true;
    }

    public function save(BusinessDocument &$doc, array $lines): int
    {
        return match ($doc->operacion) {
            InvoiceOperation::ES_INTRA_COMMUNITY => self::saveIntraCommunity($doc, $lines),
            InvoiceOperation::ES_EXPORT => self::saveExport($doc, $lines),
            InvoiceOperation::ES_IMPORT => self::saveImport($doc, $lines),
            InvoiceOperation::EXEMPT => self::saveExempt($doc, $lines),
            InvoiceOperation::ES_BENEFIT_THIRD_PARTIES => self::saveBenefitThirdParties($doc, $lines),
            InvoiceOperation::ES_SUCCESSIVE_TRACT => self::saveSuccessiveTract($doc, $lines),
            InvoiceOperation::ES_WORK_CERTIFICATION => self::saveWorkCertification($doc, $lines),
            default => 1,
        };
    }

    /**
     * Aplica el régimen de IVA correspondiente para COMPRAS
     */
    private function applyPurchaseRegime(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany, string $regimenSubject): void
    {
        // En compras, el régimen que manda es el del PROVEEDOR
        switch ($regimenSubject) {
            case TaxRegime::ES_TAX_REGIME_GENERAL:
                $this->applyPurchaseGeneral($doc, $line, $regimenCompany);
                break;

            case TaxRegime::ES_TAX_REGIME_SURCHARGE:
                $this->applyPurchaseSurcharge($doc, $line, $regimenCompany);
                break;

            case TaxRegime::ES_TAX_REGIME_USED_GOODS:
                $this->applyPurchaseUsedGoods($doc, $line, $regimenCompany);
                break;

            case TaxRegime::ES_TAX_REGIME_CASH_CRITERIA:
                $this->applyPurchaseCash($doc, $line, $regimenCompany);
                break;

            case TaxRegime::ES_TAX_REGIME_AGRARIAN:
                $this->applyPurchaseAgriculture($doc, $line, $regimenCompany);
                break;

            case TaxRegime::ES_TAX_REGIME_SIMPLIFIED:
                $this->applyPurchaseSimplified($doc, $line, $regimenCompany);
                break;

            default:
                // Por defecto, aplicamos el régimen general
                $this->applyPurchaseGeneral($doc, $line, $regimenCompany);
                break;
        }
    }

    /**
     * VENTA - Régimen Agrario
     * Tipos reducidos por producto (4/10%). Lógica fiscal por clasificación.
     */
    private function applySaleAgriculture(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // El régimen agrario generalmente usa tipos reducidos
        // El cálculo es similar al general, pero con tipos específicos
        $this->applySaleGeneral($doc, $line, $regimenSubject);
    }

    /**
     * VENTA - Régimen de Caja
     * Cálculo idéntico al general; el devengo del IVA se difiere al cobro.
     */
    private function applySaleCash(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // El cálculo es igual que el régimen general
        $this->applySaleGeneral($doc, $line, $regimenSubject);

        // La diferencia es solo en el devengo (se gestiona a nivel contable)
    }

    /**
     * VENTA - Ventas a Distancia
     * IVA del país de destino (consumidores UE). Intracomunitaria si empresa con VAT.
     */
    private function applySaleDistanceSales(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // En ventas a distancia, el IVA es del país de destino
        // Si el cliente es empresa con VAT válido, se trata como intracomunitaria
        $this->applySaleGeneral($doc, $line, $regimenSubject);

        // La aplicación del IVA del país destino se gestiona normalmente a nivel de configuración, campo operación del dcocumento
    }

    /**
     * VENTA - Régimen General
     * IVA normal sobre el neto. El cliente puede afectar en casos especiales (intra, export, recargo).
     */
    private function applySaleGeneral(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // Si es intracomunitaria o exportación, ya se gestiona en calculateLine
        // Aquí solo ajustamos el recargo según el cliente

        // Si el cliente NO está en recargo de equivalencia, no aplicamos recargo
        if ($regimenSubject !== TaxRegime::ES_TAX_REGIME_SURCHARGE) {
            $line->recargo = 0.0;
        }
    }

    /**
     * VENTA - Régimen del Oro
     * Regla general 21%; excepciones/trueques requieren casuística específica.
     */
    private function applySaleGold(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // Por defecto, IVA general del 21%
        $this->applySaleGeneral($doc, $line, $regimenSubject);

        // Implementar excepciones específicas del oro si es necesario
    }

    /**
     * VENTA - Grupo de Entidades
     * Operaciones intragrupo: posibles particularidades; validar normativa.
     */
    private function applySaleGroupEntities(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // Las operaciones intragrupo pueden tener reglas especiales
        $this->applySaleGeneral($doc, $line, $regimenSubject);

        // Implementar particularidades de grupo de entidades si es necesario
    }

    /**
     * Aplica el régimen de IVA correspondiente para VENTAS
     */
    private function applySaleRegime(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany, string $regimenSubject): void
    {
        // En ventas, el régimen que manda es el de la EMPRESA (vendedor)
        switch ($regimenCompany) {
            case TaxRegime::ES_TAX_REGIME_GENERAL:
                $this->applySaleGeneral($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_SURCHARGE:
                $this->applySaleSurcharge($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_USED_GOODS:
                $this->applySaleUsedGoods($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_CASH_CRITERIA:
                $this->applySaleCash($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_AGRARIAN:
                $this->applySaleAgriculture($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_TRAVEL:
                $this->applySaleTravelAgency($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_DISTANCE_SALES:
                $this->applySaleDistanceSales($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_GOLD:
                $this->applySaleGold($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_GROUP_ENTITIES:
                $this->applySaleGroupEntities($doc, $line, $regimenSubject);
                break;

            case TaxRegime::ES_TAX_REGIME_SIMPLIFIED:
                $this->applySaleSimplified($doc, $line, $regimenSubject);
                break;

            default:
                // Por defecto, aplicamos el régimen general
                $this->applySaleGeneral($doc, $line, $regimenSubject);
                break;
        }
    }

    /**
     * VENTA - Régimen Simplificado
     * Similar al régimen general con simplificaciones administrativas.
     */
    private function applySaleSimplified(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // El cálculo es igual que el régimen general
        $this->applySaleGeneral($doc, $line, $regimenSubject);

        // Las simplificaciones son principalmente administrativas
    }

    /**
     * VENTA - Recargo de Equivalencia
     * Una empresa en recargo de equivalencia NO aplica recargo en sus VENTAS.
     * El recargo solo se paga en COMPRAS (lo paga el minorista a su proveedor).
     * Cuando el minorista vende, solo repercute IVA sin recargo.
     */
    private function applySaleSurcharge(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // Las empresas en recargo de equivalencia NO aplican recargo en sus ventas
        // solo pagan recargo en sus compras
        $line->recargo = 0.0;
    }

    /**
     * VENTA - Agencias de Viaje
     * IVA sobre margen (venta - coste de servicios). No sobre el total del paquete.
     * Similar a bienes usados: el cálculo se hace en getSubtotalsTravelAgency().
     */
    private function applySaleTravelAgency(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // El recargo no aplica en agencias de viaje (régimen especial sobre margen)
        $line->recargo = 0.0;

        // El cálculo del IVA sobre margen se hace en getSubtotalsTravelAgency()
        // Aquí solo nos aseguramos de que el recargo esté a 0
    }

    /**
     * VENTA - Bienes Usados
     * IVA solo sobre el beneficio (venta - coste). El coste va al 0%.
     * La lógica de cálculo está en applyUsedGoods() dentro de getSubtotals().
     */
    private function applySaleUsedGoods(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenSubject): void
    {
        // Solo aplicamos esto si el producto es de segunda mano
        $producto = $line->getProducto();
        if ($producto->tipo !== ProductType::SECOND_HAND) {
            // Si no es segunda mano, aplicamos régimen general
            $this->applySaleGeneral($doc, $line, $regimenSubject);
            return;
        }

        // El recargo no aplica en bienes usados
        $line->recargo = 0.0;

        // El cálculo del IVA sobre beneficio se hace en getSubtotalsUsedGoods()
        // Aquí solo nos aseguramos de que el impuesto esté configurado
    }

    /**
     * COMPRA - Proveedor en Régimen Agrario
     * IRPF (15%) si compra sin factura (retención).
     * IVA reducido (p. ej. 4%) sobre neto.
     */
    private function applyPurchaseAgriculture(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        // IVA reducido sobre el neto
        // El recargo no aplica normalmente en compras agrarias
        if ($regimenCompany !== TaxRegime::ES_TAX_REGIME_SURCHARGE) {
            $line->recargo = 0.0;
        }

        // Si es compra sin factura, se aplicaría IRPF del 15%
        // Esto debería gestionarse a nivel de documento o configuración
    }

    /**
     * COMPRA - Proveedor en Régimen de Caja
     * Igual que general; devengo al pago.
     */
    private function applyPurchaseCash(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        // El cálculo es igual que el régimen general
        $this->applyPurchaseGeneral($doc, $line, $regimenCompany);

        // La diferencia es solo en el devengo (se gestiona a nivel contable)
    }

    /**
     * COMPRA - Régimen General del Proveedor
     * IVA soportado deducible sobre el neto.
     */
    private function applyPurchaseGeneral(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        // IVA soportado normal
        // El recargo solo se paga si NOSOTROS (la empresa) estamos en recargo
        if ($regimenCompany !== TaxRegime::ES_TAX_REGIME_SURCHARGE) {
            $line->recargo = 0.0;
        }
    }

    /**
     * COMPRA - Proveedor en Régimen Simplificado
     * Similar al régimen general.
     */
    private function applyPurchaseSimplified(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        // El cálculo es igual que el régimen general
        $this->applyPurchaseGeneral($doc, $line, $regimenCompany);

        // Las simplificaciones son principalmente administrativas del proveedor
    }

    /**
     * COMPRA - Proveedor en Recargo de Equivalencia
     * Si NOSOTROS (empresa) también estamos en recargo, pagamos el recargo.
     * Si NO estamos en recargo, no lo pagamos.
     */
    private function applyPurchaseSurcharge(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        // Solo pagamos recargo si NOSOTROS también estamos en recargo
        if ($regimenCompany !== TaxRegime::ES_TAX_REGIME_SURCHARGE) {
            $line->recargo = 0.0;
        }
        // Si estamos en recargo, el recargo se mantiene según el impuesto
    }

    /**
     * Calcula los subtotales para el régimen de AGENCIAS DE VIAJE
     * IVA solo sobre el margen (venta - coste de servicios).
     *
     * @return bool True si se aplicó el régimen de agencias de viaje, false si no aplica
     */
    private function getSubtotalsTravelAgency(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste, string $regimenCompany): bool
    {
        // Solo aplica si:
        // 1. Es una VENTA (no compra)
        // 2. La empresa está en régimen de agencias de viaje
        if ($doc->subjectColumn() !== 'codcliente' ||
            $regimenCompany !== TaxRegime::ES_TAX_REGIME_TRAVEL) {
            return false;
        }

        // Parte 1: El COSTE de servicios va al 0% IVA
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

        // Parte 2: El MARGEN lleva IVA
        $margen = $pvpTotal - $totalCoste;

        // Si el margen es negativo y la serie no es rectificativa, no hay IVA
        if ($margen <= 0 && $doc->getSerie()->tipo !== 'R') {
            return true;
        }

        // IVA sobre el margen
        $subtotals['iva'][$ivaKey]['neto'] += $margen;
        $subtotals['iva'][$ivaKey]['netosindto'] += $margen;

        if ($line->iva > 0) {
            $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                $margen * $line->iva :
                $margen * $line->iva / 100;
        }

        return true;
    }

    /**
     * COMPRA - Proveedor en Bienes Usados
     * IVA soportado menor (sobre el margen del proveedor).
     * Si nosotros estamos en bienes usados, no aplicamos impuestos en productos de segunda mano.
     */
    private function applyPurchaseUsedGoods(BusinessDocument $doc, BusinessDocumentLine &$line, string $regimenCompany): void
    {
        $producto = $line->getProducto();

        // Si nosotros también estamos en bienes usados y el producto es de segunda mano,
        // no aplicamos impuestos (compra sin IVA)
        if ($regimenCompany === TaxRegime::ES_TAX_REGIME_USED_GOODS &&
            $producto->tipo === ProductType::SECOND_HAND) {
            $line->codimpuesto = null;
            $line->iva = 0.0;
            $line->recargo = 0.0;
            return;
        }

        // Si no, IVA soportado sobre el margen del proveedor (IVA reducido)
        // El recargo no aplica
        $line->recargo = 0.0;
    }

    /**
     * Calcula los subtotales para el régimen de BIENES USADOS
     * IVA solo sobre el beneficio (venta - coste). El coste va al 0%.
     *
     * @return bool True si se aplicó el régimen de bienes usados, false si no aplica
     */
    private function getSubtotalsUsedGoods(array &$subtotals, BusinessDocument $doc, BusinessDocumentLine $line, string $ivaKey, float $pvpTotal, float $totalCoste, string $regimenCompany): bool
    {
        // Solo aplica si:
        // 1. Es una VENTA (no compra)
        // 2. La empresa está en régimen de bienes usados
        // 3. El producto es de segunda mano
        if ($doc->subjectColumn() !== 'codcliente' ||
            $regimenCompany !== TaxRegime::ES_TAX_REGIME_USED_GOODS ||
            $line->getProducto()->tipo !== ProductType::SECOND_HAND) {
            return false;
        }

        // Parte 1: El COSTE va al 0% IVA
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

        // Parte 2: El BENEFICIO lleva IVA
        $beneficio = $pvpTotal - $totalCoste;

        // Si el beneficio es negativo y la serie no es rectificativa, no hay IVA
        if ($beneficio <= 0 && $doc->getSerie()->tipo !== 'R') {
            return true;
        }

        // IVA sobre el beneficio
        $subtotals['iva'][$ivaKey]['neto'] += $beneficio;
        $subtotals['iva'][$ivaKey]['netosindto'] += $beneficio;

        if ($line->iva > 0) {
            $subtotals['iva'][$ivaKey]['totaliva'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                $beneficio * $line->iva :
                $beneficio * $line->iva / 100;
        }

        return true;
    }

    private static function saveBenefitThirdParties(BusinessDocument $doc, array $lines): int
    {
        // si no hay líneas, permitimos guardar
        if (empty($lines)) {
            return 1;
        }

        // Comprobamos que al menos una línea este marcada como suplido, si no, no es válido
        foreach ($lines as $line) {
            if ($line->suplido) {
                return 1;
            }
        }

        Tools::log()->warning('error-no-lines-marked-as-supplied');
        return -1;
    }

    private static function saveExempt(BusinessDocument $doc, array $lines): int
    {
        $messages = [];

        // Requerimos que todas las líneas sean sin IVA
        foreach ($lines as $line) {
            if ($line->iva != 0.0) {
                $messages[] = Tools::trans('line-exempt-error-has-iva', ['%order%' => $line->orden]);
            }
        }

        // Sí hay errores, mostrarlos y rechazar
        if (!empty($messages)) {
            foreach ($messages as $message) {
                Tools::log()->warning($message);
            }
            return -1;
        }

        return 1;
    }

    private static function saveExport(BusinessDocument $doc, array $lines): int
    {
        // si no es un documento de venta, no es exportación
        if ($doc->subjectColumn() !== 'codcliente') {
            Tools::log()->warning('error-not-sale-document');
            return -1;
        }

        // Cargar dirección de envío
        $addressShipping = new Contacto();
        if (false === $addressShipping->load($doc->idcontactoenv)) {
            Tools::log()->warning('error-no-shipping-contact');
            return -1;
        }

        // Validación 1: No puede ser España
        if ($addressShipping->codpais === 'ESP') {
            Tools::log()->warning('error-shipping-country-spain');
            return -1;
        }

        // Validación 2: No puede ser un país de la UE (eso sería intracomunitaria, no exportación)
        // Art. 21 LIVA: Exportaciones son solo a países TERCEROS (fuera de la UE)
        if (Paises::miembroUE($addressShipping->codpais)) {
            Tools::log()->warning('error-destination-is-eu-country');
            return -1;
        }

        // VENTAS/EXPORTACIONES (Art. 21 LIVA):
        //   - Exención del IVA
        //   - IVA = 0% en la factura
        //   - Excepción fiscal ES_21

        // Validación 3: Requieren IVA=0% y excepción ES_21
        $expectedTaxException = TaxException::ES_TAX_EXCEPTION_21;
        $messages = [];

        foreach ($lines as $line) {
            // Verificar que el IVA sea 0%
            if ($line->iva != 0.0) {
                $messages[] = Tools::trans('line-error-iva-not-zero', ['%order%' => $line->orden]);
            }

            // Verificar que tenga la excepción fiscal correcta (ES_21)
            if ($line->excepcioniva !== $expectedTaxException) {
                $messages[] = Tools::trans('line-error-wrong-tax-exception', [
                    '%order%' => $line->orden,
                    '%expected%' => Tools::trans(TaxException::get($expectedTaxException)),
                    '%found%' => Tools::trans(TaxException::get($line->excepcioniva)),
                ]);
            }
        }

        // Sí hay errores, mostrarlos y rechazar
        if (!empty($messages)) {
            foreach ($messages as $message) {
                Tools::log()->warning($message);
            }
            return -1;
        }

        return 1;
    }

    private static function saveImport(BusinessDocument $doc, array $lines): int
    {
        // si no es un documento de compra, no es importación
        if ($doc->subjectColumn() !== 'codproveedor') {
            Tools::log()->warning('error-not-purchase-document');
            return -1;
        }

        $subject = $doc->getSubject();
        $addressShipping = new Contacto();
        if (false === $addressShipping->load($subject->idcontacto)) {
            Tools::log()->warning('error-no-shipping-contact');
            return -1;
        }

        // Validación 1: No puede ser España
        if ($addressShipping->codpais === 'ESP') {
            Tools::log()->warning('error-origin-country-spain');
            return -1;
        }

        // Validación 2: No puede ser un país de la UE (eso sería intracomunitaria, no importación)
        // Las importaciones son solo desde países TERCEROS (fuera de la UE)
        if (Paises::miembroUE($addressShipping->codpais)) {
            Tools::log()->warning('error-origin-is-eu-country');
            return -1;
        }

        // COMPRAS/IMPORTACIONES (Art. 14 LIVA o regularización en aduanas):
        //   - IVA de importación se paga en aduanas
        //   - En la factura del proveedor: IVA = 0%
        //   - Excepción fiscal ES_14 (regímenes aduaneros) o sin excepción
        //   - El IVA se regulariza en la declaración de importación

        // Validación 3: IVA=0% en factura proveedor, se regulariza en aduanas
        // Pueden tener excepción ES_14 (regímenes aduaneros) o ninguna
        $messages = [];
        foreach ($lines as $line) {
            // Verificar que el IVA sea 0% en la factura del proveedor
            if ($line->iva != 0.0) {
                $messages[] = Tools::trans('import-error-line-has-iva', ['%order%' => $line->orden]);
            }

            // En importaciones no es obligatorio tener excepción fiscal específica
            // El IVA se paga en aduanas, no en la factura del proveedor
            // Opcionalmente puede tener ES_14 (regímenes aduaneros)
            if (!in_array($line->excepcioniva, [null, '', TaxException::ES_TAX_EXCEPTION_14])) {
                $messages[] = Tools::trans('error-wrong-tax-exception', [
                    '%order%' => $line->orden,
                    '%expected%' => Tools::trans(TaxException::get(TaxException::ES_TAX_EXCEPTION_14)),
                    '%found%' => Tools::trans(TaxException::get($line->excepcioniva)),
                ]);
            }
        }

        // Sí hay errores, mostrarlos y rechazar
        if (!empty($messages)) {
            foreach ($messages as $message) {
                Tools::log()->warning($message);
            }
            return -1;
        }

        return 1;
    }

    private static function saveIntraCommunity(BusinessDocument $doc, array $lines): int
    {
        $company = $doc->getCompany();
        $subject = $doc->getSubject();

        // si la empresa no supera la comprobación VIES, terminamos
        if (false === $company->checkVies()) {
            return -1;
        }

        // si el cliente o proveedor no supera la comprobación VIES, terminamos
        if (false === $subject->checkVies()) {
            return -1;
        }

        // Determinar la excepción fiscal esperada según el tipo de operación:
        //
        // VENTAS intracomunitarias (Art. 25 LIVA):
        //   - Exención del IVA
        //   - IVA = 0% en la factura
        //   - El cliente de la UE autoliquida el IVA en su país
        //
        // COMPRAS intracomunitarias (Art. 84 LIVA):
        //   - Inversión del sujeto pasivo
        //   - IVA = 0% en la factura del proveedor
        //   - El comprador español debe autoliquidar el IVA en España (modelo 303)
        //   - Se declara como IVA devengado y simultáneamente como IVA deducible
        $expectedTaxException = $doc->subjectColumn() === 'codcliente'
            ? TaxException::ES_TAX_EXCEPTION_25  // Entregas intracomunitarias (ventas)
            : TaxException::ES_TAX_EXCEPTION_84; // Inversión del sujeto pasivo (compras)

        // Las operaciones intracomunitarias requieren:
        // 1. IVA = 0% en todas las líneas (tanto en ventas como en compras)
        // 2. Excepción fiscal correcta según sea venta (ES_25) o compra (ES_84)
        //
        // Nota: En las compras, aunque el IVA es 0% en la factura del proveedor,
        // el comprador español debe autoliquidar el IVA posteriormente en el modelo 303
        $messages = [];
        foreach ($lines as $line) {
            // Verificar que el IVA sea 0% (tanto en ventas como en compras intracomunitarias)
            if ($line->iva != 0.0) {
                $messages[] = Tools::trans('line-error-iva-not-zero', ['%order%' => $line->orden]);
            }

            // Verificar la excepción correcta de iva
            if ($line->excepcioniva !== $expectedTaxException) {
                $messages[] = Tools::trans('line-error-wrong-tax-exception', [
                    '%order%' => $line->orden,
                    '%expected%' => Tools::trans(TaxException::get($expectedTaxException)),
                    '%found%' => Tools::trans(TaxException::get($line->excepcioniva))
                ]);
            }
        }

        // Sí hay errores, mostrarlos y rechazar
        if (!empty($messages)) {
            foreach ($messages as $message) {
                Tools::log()->warning($message);
            }
            return -1;
        }

        return 1;
    }

    private static function saveSuccessiveTract(BusinessDocument $doc, array $lines): int
    {
        // No hay que validar nada específico para este tipo de operación
        return 1;
    }

    private static function saveWorkCertification(BusinessDocument $doc, array $lines): int
    {
        // No hay que validar nada específico para este tipo de operación
        return 1;
    }
}