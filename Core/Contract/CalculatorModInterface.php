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

namespace FacturaScripts\Core\Contract;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;

/**
 * Permite modificar el cálculo de totales de documentos de negocio.
 *
 * @deprecated Usa CalculatorModClass en su lugar, que ofrece un control de
 *             flujo más preciso mediante los valores de retorno 'continue',
 *             'stop-mods' y 'stop-all'.
 *
 * Registro: Calculator::addMod(new MiCalculatorMod()).
 */
interface CalculatorModInterface
{
    /**
     * Prepara el documento antes de calcular las líneas: configura regímenes
     * fiscales especiales, activa flags o inicializa estructuras auxiliares
     * (p. ej., determinar si aplica recargo de equivalencia).
     * Devuelve false para que no se ejecuten los mods siguientes.
     *
     * @param BusinessDocument $doc   Documento a calcular (por referencia).
     * @param array            $lines Líneas del documento (por referencia).
     *
     * @return bool False detiene la cadena de mods.
     */
    public function apply(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Calcula los totales globales del documento (neto, impuestos, total)
     * y aplica redondeos o ajustes finales sobre los subtotales ya acumulados.
     * Devuelve false para que no se ejecuten los mods siguientes.
     *
     * @param BusinessDocument $doc   Documento con subtotales ya acumulados (por referencia).
     * @param array            $lines Líneas del documento (por referencia).
     *
     * @return bool False detiene la cadena de mods.
     */
    public function calculate(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Calcula el importe de una línea individual: pvptotal, descuentos,
     * recargos y lógica fiscal específica de la línea.
     * Devuelve false para que no se ejecuten los mods siguientes en esta línea.
     *
     * @param BusinessDocument     $doc  Documento al que pertenece la línea.
     * @param BusinessDocumentLine $line Línea a calcular (por referencia).
     *
     * @return bool False detiene la cadena de mods para esta línea.
     */
    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool;

    /**
     * Pone a cero todos los campos de totales y subtotales del documento
     * para que los mods posteriores partan de un estado limpio.
     * Devuelve false para que no se ejecuten los mods siguientes.
     *
     * @param BusinessDocument $doc   Documento a limpiar (por referencia).
     * @param array            $lines Líneas del documento (por referencia).
     *
     * @return bool False detiene la cadena de mods.
     */
    public function clear(BusinessDocument &$doc, array &$lines): bool;

    /**
     * Acumula en $subtotals los importes de las líneas agrupados por tipo
     * de impuesto (IVA, IRPF, recargo de equivalencia, etc.).
     *
     * Estructura esperada de cada entrada en $subtotals:
     * [
     *   'taxp'    => '21',   // porcentaje de IVA
     *   'neto'    => 100.0,  // base imponible
     *   'iva'     => 21.0,   // cuota de IVA
     *   'recargo' => 0.0,    // recargo de equivalencia
     *   'irpf'    => 0.0,    // retención IRPF
     * ]
     *
     * Devuelve false para que no se ejecuten los mods siguientes.
     *
     * @param array            $subtotals Subtotales acumulados hasta el momento (por referencia).
     * @param BusinessDocument $doc       Documento en proceso de cálculo.
     * @param array            $lines     Líneas del documento ya calculadas.
     *
     * @return bool False detiene la cadena de mods.
     */
    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool;
}
