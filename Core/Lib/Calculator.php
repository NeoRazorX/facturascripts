<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Contract\CalculatorModInterface;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\ImpuestoZona;
use FacturaScripts\Core\Template\CalculatorModClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Impuesto;

/**
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <contacto@danielfg.es>
 */
class Calculator
{
    /** @var CalculatorModClass[] */
    public static $mods = [];

    // Se activa cuando un mod corta el cálculo de subtotales con STOP_ALL.
    // La usamos en calculate() para abortar antes de leer un array parcial.
    private static $stopAllFromSubtotals = false;

    // Sin type-hint para aceptar ambos contratos durante la transición:
    // CalculatorModClass (nuevo) y CalculatorModInterface (legacy/deprecated).
    public static function addMod($mod): void
    {
        if ($mod instanceof CalculatorModClass || $mod instanceof CalculatorModInterface) {
            self::$mods[] = $mod;
            return;
        }

        throw new Exception('Mod must be an instance of CalculatorModClass');
    }

    public static function calculate(BusinessDocument $doc, array &$lines, bool $save): bool
    {
        // ponemos totales a 0
        if (false === self::clear($doc, $lines)) {
            return false;
        }

        // aplicamos configuraciones, excepciones, etc
        if (false === self::apply($doc, $lines)) {
            return false;
        }

        // calculamos subtotales en líneas
        foreach ($lines as $line) {
            if (false === self::calculateLine($doc, $line)) {
                return false;
            }
        }

        // calculamos los totales
        $subtotals = self::getSubtotals($doc, $lines);
        // Si getSubtotals() fue interrumpido por STOP_ALL, no usamos subtotales incompletos.
        if (self::$stopAllFromSubtotals) {
            return false;
        }

        $doc->irpf = $subtotals['irpf'];
        $doc->neto = $subtotals['neto'];
        $doc->netosindto = $subtotals['netosindto'];
        $doc->total = $subtotals['total'];
        $doc->totalirpf = $subtotals['totalirpf'];
        $doc->totaliva = $subtotals['totaliva'];
        $doc->totalrecargo = $subtotals['totalrecargo'];
        $doc->totalsuplidos = $subtotals['totalsuplidos'];

        // si tiene totalbeneficio, lo asignamos
        if ($doc->hasColumn('totalbeneficio')) {
            $doc->totalbeneficio = $subtotals['totalbeneficio'];
        }

        // si tiene totalcoste, lo asignamos
        if ($doc->hasColumn('totalcoste')) {
            $doc->totalcoste = $subtotals['totalcoste'];
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            $return = $mod->calculate($doc, $lines);

            // compatibilidad con mods antiguos
            if ($mod instanceof CalculatorModInterface) {
                // En mods legacy, false solo corta la cadena de mods, no todo el cálculo.
                if ($return === false) {
                    break;
                }
                continue;
            }

            if ($return === CalculatorModClass::STOP_MODS) {
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                return false;
            }
        }

        if ($save) {
            return self::save($doc, $lines);
        }

        return true;
    }

    public static function calculateLine(BusinessDocument $doc, BusinessDocumentLine $line): bool
    {
        $line->pvpsindto = $line->cantidad * $line->pvpunitario;
        $line->pvptotal = $line->pvpsindto * (100 - $line->dtopor) / 100 * (100 - $line->dtopor2) / 100;

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            $return = $mod->calculateLine($doc, $line);

            // compatibilidad con mods antiguos
            if ($mod instanceof CalculatorModInterface) {
                // En mods legacy, false solo corta la cadena de mods, no todo el cálculo.
                if ($return === false) {
                    break;
                }
                continue;
            }

            if ($return === CalculatorModClass::STOP_MODS) {
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return array
     */
    public static function getSubtotals(BusinessDocument $doc, array $lines): array
    {
        // Reiniciamos la marca en cada ejecución para no arrastrar estado previo.
        self::$stopAllFromSubtotals = false;

        $subtotals = [
            'irpf' => 0.0,
            'iva' => [],
            'neto' => 0.0,
            'netosindto' => 0.0,
            'totalbeneficio' => 0.0,
            'totalcoste' => 0.0,
            'totalirpf' => 0.0,
            'totaliva' => 0.0,
            'totalrecargo' => 0.0,
            'totalsuplidos' => 0.0
        ];

        $done = false;
        foreach (self::$mods as $mod) {
            if ($mod instanceof CalculatorModInterface) {
                continue;
            }

            $return = $mod->accumulateSubtotals($subtotals, $doc, $lines);

            if ($return === CalculatorModClass::STOP_MODS) {
                // El mod ya ha calculado/acumulado subtotales y corta esta fase.
                // Marcamos done=true para NO ejecutar el accumulate() interno del Calculator.
                $done = true;
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                // Guardamos el motivo de salida para que calculate() pueda abortar de forma segura.
                self::$stopAllFromSubtotals = true;
                return $subtotals;
            }
        }

        if (!$done) {
            // Solo ejecutamos el acumulado interno si ningún mod ha respondido STOP_MODS.
            $subtotals = self::accumulate($subtotals, $doc, $lines);
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            if ($mod instanceof CalculatorModInterface) {
                // Contrato legacy: false detiene solo esta fase de modificación de subtotales.
                if (false === $mod->getSubtotals($subtotals, $doc, $lines)) {
                    break;
                }
                continue;
            }

            $return = $mod->updateSubtotals($subtotals, $doc, $lines);

            if ($return === CalculatorModClass::STOP_MODS) {
                // STOP_MODS aquí solo detiene updateSubtotals; no cancela el cálculo global.
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                // Guardamos el motivo de salida para que calculate() pueda abortar de forma segura.
                self::$stopAllFromSubtotals = true;
                return $subtotals;
            }
        }

        // redondeamos los IVA
        foreach ($subtotals['iva'] as $key => $value) {
            $subtotals['iva'][$key]['neto'] = Tools::round($value['neto']);
            $subtotals['iva'][$key]['netosindto'] = Tools::round($value['netosindto']);
            $subtotals['iva'][$key]['totaliva'] = Tools::round($value['totaliva']);
            $subtotals['iva'][$key]['totalrecargo'] = Tools::round($value['totalrecargo']);

            // trasladamos a los subtotales
            $subtotals['neto'] += Tools::round($value['neto']);
            $subtotals['netosindto'] += Tools::round($value['netosindto']);
            $subtotals['totaliva'] += Tools::round($value['totaliva']);
            $subtotals['totalrecargo'] += Tools::round($value['totalrecargo']);
        }

        // redondeamos los subtotales
        $subtotals['neto'] = Tools::round($subtotals['neto']);
        $subtotals['netosindto'] = Tools::round($subtotals['netosindto']);
        $subtotals['totalirpf'] = Tools::round($subtotals['totalirpf']);
        $subtotals['totaliva'] = Tools::round($subtotals['totaliva']);
        $subtotals['totalrecargo'] = Tools::round($subtotals['totalrecargo']);
        $subtotals['totalsuplidos'] = Tools::round($subtotals['totalsuplidos']);

        // calculamos el beneficio
        $subtotals['totalbeneficio'] = Tools::round($subtotals['neto'] - $subtotals['totalcoste']);

        // calculamos el total
        $subtotals['total'] = Tools::round($subtotals['neto'] + $subtotals['totalsuplidos'] + $subtotals['totaliva']
            + $subtotals['totalrecargo'] - $subtotals['totalirpf']);

        return $subtotals;
    }

    private static function accumulate(array $subtotals, BusinessDocument $doc, array $lines): array
    {
        // método de cálculo configurable: classic (por defecto) o price-adjusted
        $taxMethod = Tools::settings('default', 'taxcalculationmethod', 'classic');

        // acumulamos por cada línea
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

            // neto
            $subtotals['iva'][$ivaKey]['neto'] += $pvpTotal;
            $subtotals['iva'][$ivaKey]['netosindto'] += $line->pvptotal;

            // IVA
            if ($line->iva > 0) {
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
            if ($line->recargo > 0) {
                $subtotals['iva'][$ivaKey]['totalrecargo'] += $line->getTax()->tipo === Impuesto::TYPE_FIXED_VALUE ?
                    $line->cantidad * $line->recargo :
                    $pvpTotal * $line->recargo / 100;
            }
        }

        return $subtotals;
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return bool
     */
    private static function apply(BusinessDocument $doc, array &$lines): bool
    {
        $subject = $doc->getSubject();
        $noTax = $doc->getSerie()->siniva;
        $taxException = $subject->excepcioniva ?? null;
        $regimen = $subject->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;

        // cargamos las zonas de impuestos para los documentos de venta
        $taxZones = [];
        if ($doc instanceof SalesDocument) {
            foreach (ImpuestoZona::all([], ['prioridad' => 'DESC']) as $taxZone) {
                if ($taxZone->matchPais($doc->codpais, $doc->provincia)) {
                    $taxZones[] = $taxZone;
                }
            }
        }

        // recorremos las líneas del documento
        foreach ($lines as $line) {
            // ¿La serie es sin impuestos o el régimen exento?
            if ($noTax || $regimen === RegimenIVA::TAX_SYSTEM_EXEMPT) {
                $line->codimpuesto = Impuestos::get('IVA0')->codimpuesto;
                $line->excepcioniva = $taxException;
                $line->iva = $line->recargo = 0.0;
                continue;
            }

            // aplicamos la zona de impuestos
            foreach ($taxZones as $taxZone) {
                if ($line->codimpuesto === $taxZone->codimpuesto) {
                    $line->codimpuesto = $taxZone->codimpuestosel;
                    $line->excepcioniva = $taxZone->excepcioniva;
                    $line->iva = $line->getTax()->iva;
                    $line->recargo = $line->getTax()->recargo;
                    break;
                }
            }

            // Recargo de equivalencia en compras: solo aplica si la empresa tiene régimen RE
            if ($line->recargo > 0 && $doc instanceof PurchaseDocument) {
                $companyRegimen = $doc->getCompany()->regimeniva ?? RegimenIVA::TAX_SYSTEM_GENERAL;
                if ($companyRegimen !== RegimenIVA::TAX_SYSTEM_SURCHARGE) {
                    $line->recargo = 0.0;
                }
            }
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            $return = $mod->apply($doc, $lines);

            // compatibilidad con mods antiguos
            if ($mod instanceof CalculatorModInterface) {
                if ($return === false) {
                    break;
                }
                continue;
            }

            if ($return === CalculatorModClass::STOP_MODS) {
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return bool
     */
    private static function clear(BusinessDocument $doc, array &$lines): bool
    {
        $doc->neto = $doc->netosindto = 0.0;
        $doc->total = $doc->totaleuros = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalrecargo = 0.0;
        $doc->totalsuplidos = 0.0;

        // si tiene totalcoste, lo reiniciamos
        if ($doc->hasColumn('totalcoste')) {
            $doc->totalcoste = 0.0;
        }

        // si tiene totalbeneficio, lo reiniciamos
        if ($doc->hasColumn('totalbeneficio')) {
            $doc->totalbeneficio = 0.0;
        }

        foreach ($lines as $line) {
            $line->pvpsindto = $line->pvptotal = 0.0;
        }

        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            $return = $mod->clear($doc, $lines);

            // compatibilidad con mods antiguos
            if ($mod instanceof CalculatorModInterface) {
                if ($return === false) {
                    break;
                }
                continue;
            }

            if ($return === CalculatorModClass::STOP_MODS) {
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param BusinessDocument $doc
     * @param BusinessDocumentLine[] $lines
     *
     * @return bool
     */
    private static function save(BusinessDocument $doc, array $lines): bool
    {
        // turno para que los mods apliquen cambios
        foreach (self::$mods as $mod) {
            // excluimos mods antiguos
            if ($mod instanceof CalculatorModInterface) {
                continue;
            }

            $return = $mod->save($doc, $lines);

            if ($return === CalculatorModClass::STOP_MODS) {
                break;
            }

            if ($return === CalculatorModClass::STOP_ALL) {
                return false;
            }
        }

        foreach ($lines as $line) {
            if (false === $line->save()) {
                return false;
            }
        }

        return $doc->save();
    }
}
